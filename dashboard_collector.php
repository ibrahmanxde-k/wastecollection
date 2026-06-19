<?php
session_start();
include 'db.php';

// AUTH
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'collector'){ 
    header("Location:index.php"); 
    exit; 
}

$user = $_SESSION['user'];

// Pata collector ID
$staff_stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
$staff_stmt->bind_param("s", $user);
$staff_stmt->execute();
$staff_data = $staff_stmt->get_result()->fetch_assoc();
$staff_stmt->close();

if(!$staff_data){ header("Location:index.php"); exit; }
$staff_id = (int)$staff_data['id'];

// HANDLE TASK ACTION
if(isset($_POST['task_id']) && isset($_POST['action'])){
    $task_id = intval($_POST['task_id']);
    $action  = $_POST['action'];
    if(in_array($action, ["Completed","Ongoing","Failed"])){
        $stmt = $conn->prepare("UPDATE tasks SET status=? WHERE id=? AND assigned_to=?");
        $stmt->bind_param("sii", $action, $task_id, $staff_id); 
        $stmt->execute(); 
        $stmt->close();
    }
}

// FETCH TASKS — zote zilizopangiwa collector huyu
$task_stmt = $conn->prepare("
    SELECT t.id, t.task_name, t.location, t.lat, t.lng, t.status, t.created_at,
           b.bin_id, b.status AS bin_status
    FROM tasks t
    LEFT JOIN bins b ON t.location = b.location
    WHERE t.assigned_to = ?
    ORDER BY t.created_at DESC
");
$task_stmt->bind_param("i", $staff_id);
$task_stmt->execute();
$tasks = $task_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$task_stmt->close();

// Kama hakuna tasks zilizopangiwa moja kwa moja, fetch zote za role
if(empty($tasks)){
    $task_stmt2 = $conn->prepare("
        SELECT t.id, t.task_name, t.location, t.lat, t.lng, t.status, t.created_at,
               b.bin_id, b.status AS bin_status
        FROM tasks t
        LEFT JOIN bins b ON t.location = b.location
        WHERE t.role = 'collector' OR t.assigned_to = ?
        ORDER BY t.created_at DESC
    ");
    $task_stmt2->bind_param("i", $staff_id);
    $task_stmt2->execute();
    $tasks = $task_stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $task_stmt2->close();
}

// FETCH PAYMENTS
$pay_result = $conn->query("
    SELECT u.username, p.amount, p.collector_share, p.method, p.reference, p.status, p.created_at
    FROM payments p
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'Paid'
    ORDER BY p.created_at DESC
");
$payments = $pay_result->fetch_all(MYSQLI_ASSOC);

// TOTALS
$total          = 0;
$task_total     = count($tasks);
$task_completed = 0;
$task_ongoing   = 0;
$task_pending   = 0;

foreach($payments as $p){ $total += $p['collector_share']; }
foreach($tasks as $t){
    if($t['status'] === 'Completed') $task_completed++;
    elseif($t['status'] === 'Ongoing') $task_ongoing++;
    else $task_pending++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Collector Dashboard</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<style>
* { box-sizing: border-box; }
body { margin:0; font-family:'Segoe UI',sans-serif; background:#f8fafc; }

.navbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:linear-gradient(135deg,#d6a100,#ffc107);
    color:black;
    padding:12px 20px;
}
.navbar a {
    background:rgba(0,0,0,0.15);
    color:black;
    padding:6px 14px;
    border-radius:6px;
    text-decoration:none;
    font-size:13px;
}

.summary-strip {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
    padding:16px 20px;
}
.summary-card {
    background:#fff;
    border:1px solid #ece6c7;
    border-radius:12px;
    padding:14px;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
}
.summary-card .label { font-size:12px; color:#64748b; margin-bottom:4px; }
.summary-card .value { font-size:24px; font-weight:700; color:#0f172a; }

.dashboard {
    padding:0 20px 20px;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
}

.card {
    background:#fff;
    border-radius:12px;
    padding:16px;
    border:1px solid #ece6c7;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
}
.card h3 { margin:0 0 14px; font-size:15px; color:#0f172a; }

table { width:100%; border-collapse:collapse; font-size:13px; }
th, td { border:1px solid #e2e8f0; padding:9px 10px; text-align:left; }
th { background:#f8fafc; font-weight:600; color:#475569; }
tr:hover td { background:#fefce8; }

.badge {
    display:inline-block;
    padding:3px 10px;
    border-radius:20px;
    font-size:11px;
    font-weight:600;
}
.badge-pending   { background:#fef3c7; color:#92400e; }
.badge-ongoing   { background:#dbeafe; color:#1e40af; }
.badge-completed { background:#dcfce7; color:#166534; }
.badge-failed    { background:#fee2e2; color:#991b1b; }
.badge-full      { background:#fee2e2; color:#991b1b; }
.badge-medium    { background:#fef3c7; color:#92400e; }
.badge-empty     { background:#dcfce7; color:#166534; }

.btn {
    padding:5px 10px;
    border:none;
    border-radius:5px;
    cursor:pointer;
    font-size:12px;
    font-weight:600;
    margin:2px;
}
.btn-complete { background:#16a34a; color:white; }
.btn-ongoing  { background:#2563eb; color:white; }
.btn-fail     { background:#dc2626; color:white; }
.btn:hover { opacity:0.85; }

#map { height:380px; width:100%; border-radius:10px; }

.total-box {
    margin-top:12px;
    padding:10px 14px;
    background:#fefce8;
    border-radius:8px;
    font-weight:700;
    border:1px solid #fde68a;
    color:#78350f;
}

.empty-msg {
    text-align:center;
    padding:30px;
    color:#94a3b8;
    font-size:14px;
}

@media(max-width:900px){
    .summary-strip,.dashboard{ grid-template-columns:1fr; }
    .dashboard .full-width{ grid-column:span 1; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <h2 style="margin:0;">🟡 Collector Dashboard</h2>
    <div style="display:flex;align-items:center;gap:12px;">
        <span>Welcome, <strong><?php echo htmlspecialchars($user); ?></strong> 👋</span>
        <a href="index.php">🚪 Logout</a>
    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="summary-strip">
    <div class="summary-card">
        <div class="label">📋 Total Tasks</div>
        <div class="value"><?= $task_total ?></div>
    </div>
    <div class="summary-card">
        <div class="label">✅ Completed</div>
        <div class="value" style="color:#16a34a;"><?= $task_completed ?></div>
    </div>
    <div class="summary-card">
        <div class="label">🔄 Ongoing</div>
        <div class="value" style="color:#2563eb;"><?= $task_ongoing ?></div>
    </div>
    <div class="summary-card">
        <div class="label">💰 My Earnings</div>
        <div class="value" style="color:#d97706;">Tsh <?= number_format($total) ?></div>
    </div>
</div>

<!-- MAIN DASHBOARD -->
<div class="dashboard">

<!-- ASSIGNED TASKS -->
<div class="card">
    <h3>📋 Assigned Tasks</h3>
    <?php if(empty($tasks)): ?>
        <div class="empty-msg">📭 Hakuna tasks zilizopangiwa kwako bado.</div>
    <?php else: ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Task</th>
            <th>Location</th>
            <th>Bin Status</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach($tasks as $t): 
            // Badge ya task status
            $badge = 'badge-pending';
            if($t['status'] == 'Completed') $badge = 'badge-completed';
            elseif($t['status'] == 'Ongoing') $badge = 'badge-ongoing';
            elseif($t['status'] == 'Failed')  $badge = 'badge-failed';

            // Badge ya bin status
            $bin_badge = 'badge-empty';
            $bin_status = $t['bin_status'] ?? 'Unknown';
            if($bin_status == 'Full')   $bin_badge = 'badge-full';
            elseif($bin_status == 'Medium') $bin_badge = 'badge-medium';
        ?>
        <tr>
            <td><strong>#<?= $t['id'] ?></strong></td>
            <td><?= htmlspecialchars($t['task_name'] ?? 'Waste Collection') ?></td>
            <td>📍 <?= htmlspecialchars($t['location']) ?></td>
            <td><span class="badge <?= $bin_badge ?>"><?= htmlspecialchars($bin_status) ?></span></td>
            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($t['status']) ?></span></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                    <?php if($t['status'] != 'Completed'): ?>
                        <button class="btn btn-complete" name="action" value="Completed">✅ Done</button>
                        <button class="btn btn-ongoing"  name="action" value="Ongoing">🔄 Ongoing</button>
                        <button class="btn btn-fail"     name="action" value="Failed">❌ Fail</button>
                    <?php else: ?>
                        <span style="color:#16a34a;font-weight:600;">✅ Imekamilika</span>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<!-- MAP -->
<div class="card">
    <h3>🗺️ Collection Map</h3>
    <div id="map"></div>
</div>

<!-- PAYMENTS -->
<div class="card" style="grid-column:span 2;">
    <h3>💰 My Earnings</h3>
    <?php if(empty($payments)): ?>
        <div class="empty-msg">💸 Hakuna malipo bado.</div>
    <?php else: ?>
    <table>
        <tr>
            <th>Citizen</th>
            <th>Jumla (Tsh)</th>
            <th>Sehemu Yangu (Tsh)</th>
            <th>Njia ya Kulipa</th>
            <th>Reference</th>
            <th>Status</th>
            <th>Tarehe</th>
        </tr>
        <?php foreach($payments as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['username']) ?></td>
            <td><?= number_format($p['amount']) ?></td>
            <td><strong style="color:#16a34a;">Tsh <?= number_format($p['collector_share']) ?></strong></td>
            <td><?= htmlspecialchars(str_replace('_',' ', ucfirst($p['method'] ?? 'N/A'))) ?></td>
            <td><code><?= htmlspecialchars($p['reference'] ?? 'N/A') ?></code></td>
            <td><span class="badge badge-completed">✅ <?= $p['status'] ?></span></td>
            <td><?= $p['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div class="total-box">
        💵 Jumla ya Mapato Yangu: <span style="font-size:18px;">Tsh <?= number_format($total) ?></span>
    </div>
    <?php endif; ?>
</div>

</div><!-- end dashboard -->

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
var map = L.map('map').setView([-9.15, 33.6], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

var tasks = <?php echo json_encode($tasks); ?>;

tasks.forEach(function(task){
    if(!task.lat || !task.lng) return;

    var color = task.status == "Completed" ? "green" 
              : task.status == "Ongoing"   ? "blue" 
              : task.status == "Failed"    ? "red" 
              : "orange";

    L.circleMarker([task.lat, task.lng], {
        radius: 10,
        fillColor: color,
        color: "#fff",
        weight: 2,
        fillOpacity: 0.9
    })
    .bindPopup(
        "<b>Task #" + task.id + "</b><br>" +
        "📍 " + task.location + "<br>" +
        "Status: <b>" + task.status + "</b>"
    )
    .addTo(map);
});
</script>
</body>
</html>
