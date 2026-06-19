<?php
session_start();
include 'db.php';

// 🔒 Hakikisha ni driver tu
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'driver'){
    header("Location:index.php"); exit;
}

$user = $_SESSION['user'];
$staff_stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
$staff_stmt->bind_param("s", $user);
$staff_stmt->execute();
$staff_result = $staff_stmt->get_result();
$staff_data = $staff_result->fetch_assoc();
$staff_stmt->close();

if(!$staff_data){
    header("Location:index.php");
    exit;
}

$staff_id = (int)$staff_data['id'];

// ================= TASK ACTION =================
if(isset($_POST['task_id']) && isset($_POST['action'])){
    $task_id = intval($_POST['task_id']);
    $action = $_POST['action'];

    if(in_array($action, ["Completed","Ongoing","Failed"])){
        $stmt = $conn->prepare("UPDATE tasks SET status=? WHERE id=? AND assigned_to=?");
        $stmt->bind_param("sii", $action, $task_id, $staff_id);
        $stmt->execute(); 
        $stmt->close();
    }
}

// ================= FETCH TASKS (na taarifa za bin) =================
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

// ================= FETCH PAYMENTS =================
$payments_result = $conn->query("
SELECT u.username, p.driver_share AS amount, p.method, p.reference, p.status, p.created_at
FROM payments p
JOIN users u ON p.user_id=u.id
WHERE p.driver_share > 0
ORDER BY p.created_at DESC
");

$payments = [];
if($payments_result){
    $payments = $payments_result->fetch_all(MYSQLI_ASSOC);
}

// ================= TOTAL EARNINGS =================
$total_earnings = 0;
foreach($payments as $p){
    if($p['status'] == 'Paid'){
        $total_earnings += $p['amount'];
    }
}
$task_total = count($tasks);
$task_completed = 0;
$task_ongoing = 0;
$task_pending = 0;
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
<title>Driver Dashboard</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<style>
* { box-sizing:border-box; }
body { margin:0; font-family:'Segoe UI',sans-serif; background:#f4f8fb; }

.navbar {
    display:flex; justify-content:space-between; align-items:center;
    background:linear-gradient(135deg,#1f8f4d,#2cae61);
    color:white; padding:12px 20px;
    box-shadow:0 8px 20px rgba(31,143,77,.25);
}
.navbar a {
    background:rgba(255,255,255,0.2);
    color:white;
    padding:7px 16px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    border:1px solid rgba(255,255,255,0.3);
}

.summary-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:14px 20px 0;}
.summary-card{background:#fff;border:1px solid #dbe7df;border-radius:12px;padding:12px;box-shadow:0 6px 16px rgba(15,23,42,.06);}
.summary-card .label{font-size:12px;color:#64748b}
.summary-card .value{font-size:22px;font-weight:700;color:#0f172a}

.dashboard { padding:16px 20px 20px; display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.card { background:#fff; border:1px solid #dbe7df; border-radius:12px; padding:15px; box-shadow:0 6px 16px rgba(15,23,42,0.06); }
.card h3 { margin:0 0 12px; font-size:16px; color:#0f172a; }

button { border:none; padding:6px 10px; border-radius:7px; cursor:pointer; margin:2px; font-weight:600; font-size:12px; color:white;}
button:hover { filter:brightness(1.05); }
.btn-complete{background:#16a34a;}
.btn-ongoing{background:#2563eb;}
.btn-fail{background:#dc2626;}

#map { height:360px; border-radius:10px; }

/* TABLE */
table { width:100%; border-collapse:collapse; font-size:13px; }
table, th, td { border:1px solid #e5e7eb; }
th, td { padding:8px; text-align:left; }
th { background:#f0fdf4; color:#166534; }

/* BADGES */
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

.empty-msg { text-align:center; padding:30px; color:#94a3b8; font-size:14px; }

/* PAYMENT STYLE */
.table-container {
    max-height: 270px;
    overflow-y: auto;
    border:1px solid #e5e7eb;
    border-radius:10px;
}
.modern-table th { background:#28a745; color:white; position:sticky; top:0; }
.modern-table tr:hover { background:#f1f1f1; }
.amount { font-weight:bold; color:#28a745; }

.status { padding:5px 10px; border-radius:20px; font-size:12px; color:white; }
.status.Paid { background:#28a745; }
.status.Pending { background:orange; }

.total-box { margin-top:10px; text-align:right; font-size:18px; font-weight:bold; }

@media (max-width:900px){.summary-strip,.dashboard{grid-template-columns:1fr;}}

form { display:inline; }
</style>
</head>

<body>

<div class="navbar">
<h2 style="margin:0;">🚛 Driver Dashboard</h2>
<div style="display:flex;align-items:center;gap:12px;">
<span>Welcome, <strong><?php echo htmlspecialchars($user); ?></strong> 👋</span>
<a href="logout.php">🚪 Logout</a>
</div>
</div>

<div class="summary-strip">
  <div class="summary-card"><div class="label">📋 Assigned Tasks</div><div class="value"><?= $task_total ?></div></div>
  <div class="summary-card"><div class="label">✅ Completed</div><div class="value" style="color:#16a34a;"><?= $task_completed ?></div></div>
  <div class="summary-card"><div class="label">🔄 Ongoing</div><div class="value" style="color:#2563eb;"><?= $task_ongoing ?></div></div>
  <div class="summary-card"><div class="label">💰 My Earnings</div><div class="value">Tsh <?= number_format($total_earnings) ?></div></div>
</div>

<div class="dashboard">

<!-- TASKS -->
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
<form method="POST">
<input type="hidden" name="task_id" value="<?= $t['id'] ?>">
<?php if($t['status'] != 'Completed'): ?>
    <button class="btn-complete" name="action" value="Completed">✅ Done</button>
    <button class="btn-ongoing" name="action" value="Ongoing">🔄 Ongoing</button>
    <button class="btn-fail" name="action" value="Failed">❌ Fail</button>
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
<h3>🗺 Collection Map</h3>
<div id="map"></div>
</div>

<!-- PAYMENTS -->
<div class="card" style="grid-column: span 2;">
<h3>💰 My Earnings</h3>

<div class="table-container">
<table class="modern-table">
<tr>
<th>User</th>
<th>Amount</th>
<th>Njia ya Kulipa</th>
<th>Reference</th>
<th>Status</th>
<th>Time</th>
</tr>

<?php if(count($payments) > 0): ?>
<?php foreach($payments as $p): ?>
<tr>
<td><?= htmlspecialchars($p['username']) ?></td>
<td class="amount"><?= number_format($p['amount']) ?> Tsh</td>
<td><?= htmlspecialchars(str_replace('_',' ', ucfirst($p['method'] ?? 'N/A'))) ?></td>
<td><code><?= htmlspecialchars($p['reference'] ?? 'N/A') ?></code></td>
<td>
<span class="status <?= $p['status'] ?>">
<?= $p['status'] ?>
</span>
</td>
<td><?= $p['created_at'] ?></td>
</tr>
<?php endforeach; ?>

<?php else: ?>
<tr>
<td colspan="6" style="text-align:center; color:red;">
No payments available
</td>
</tr>
<?php endif; ?>

</table>
</div>

<!-- TOTAL -->
<div class="total-box">
Total Earnings: 
<span style="color:#28a745;">
Tsh <?= number_format($total_earnings) ?>
</span>
</div>

</div>

</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
var map = L.map('map').setView([-9.15, 33.6], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

var tasks = <?php echo json_encode($tasks); ?>;
tasks.forEach(function(task){
    if(!task.lat || !task.lng) return;

    var color = (task.status=="Completed") ? "green" : ((task.status=="Ongoing") ? "blue" : (task.status=="Failed" ? "red" : "orange"));

    L.circleMarker([task.lat, task.lng], {
        radius:10,
        fillColor:color,
        color:"#fff",
        weight:2,
        fillOpacity:0.9
    }).bindPopup(
        "<b>Task #"+task.id+"</b><br>📍 "+task.location+"<br>Status: <b>"+task.status+"</b>"
    ).addTo(map);
});
</script>

</body>
</html>
