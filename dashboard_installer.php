<?php
session_start();
include 'db.php';

// 🔒 AUTH
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'installer'){
    header("Location:index.php"); 
    exit;
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

// 🔹 HANDLE TASK ACTION
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

// 🔹 FETCH TASKS
$result = $conn->query("SELECT * FROM tasks WHERE assigned_to=$staff_id");
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// 🔹 FETCH INSTALLER PAYMENTS ONLY (CONFIRMED)
$payments = $conn->query("
SELECT u.username, p.installer_share AS amount, p.status, p.created_at
FROM payments p
JOIN users u ON p.user_id=u.id
WHERE p.status='Paid'
ORDER BY p.created_at DESC
");
$payments = $payments->fetch_all(MYSQLI_ASSOC);

// 🔹 CALCULATE TOTAL
$total = 0;
foreach($payments as $p){
    $total += $p['amount'];
}
$task_total = count($tasks);
$task_completed = 0;
foreach($tasks as $t){
    if($t['status'] === 'Completed'){
        $task_completed++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Installer Dashboard</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<style>
body { margin:0; font-family:'Segoe UI',sans-serif; background:#f4f8fb; }

.navbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:linear-gradient(135deg,#0f8597,#17a2b8);
    color:white;
    padding:10px 20px;
}

.dashboard {
    padding:16px 20px 20px;
    display:grid;
    grid-template-columns:1fr 1fr;
    grid-gap:20px;
}

.card {
    background:#fff;
    border-radius:12px;
    padding:15px;
    border:1px solid #d8e7eb;
    box-shadow:0 6px 16px rgba(15,23,42,0.06);
}
.summary-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;padding:14px 20px 0;}
.summary-card{background:#fff;border:1px solid #d8e7eb;border-radius:12px;padding:12px;box-shadow:0 6px 16px rgba(15,23,42,.06);}
.summary-card .label{font-size:12px;color:#64748b}
.summary-card .value{font-size:22px;font-weight:700;color:#0f172a}

button {
    background:#17a2b8;
    color:white;
    border:none;
    padding:6px 10px;
    border-radius:5px;
    cursor:pointer;
    margin:2px;
}

button:hover { background:#117a8b; }

#map { height:400px; width:100%; border-radius:10px; }

table {
    width:100%;
    border-collapse:collapse;
}

th, td {
    border:1px solid #ccc;
    padding:8px;
    text-align:left;
}

th {
    background:#e9ecef;
}

.status-paid { color:green; font-weight:bold; }

.total-box {
    margin-top:10px;
    padding:10px;
    background:#d4edda;
    border-radius:8px;
    font-weight:bold;
}
@media (max-width:900px){.summary-strip,.dashboard{grid-template-columns:1fr;}}
</style>
</head>

<body>

<div class="navbar">
<h2>🔧 Installer Dashboard</h2>
<div>Welcome, <?php echo $user; ?> 👋</div>
</div>

<div class="summary-strip">
  <div class="summary-card"><div class="label">Assigned Tasks</div><div class="value"><?= $task_total ?></div></div>
  <div class="summary-card"><div class="label">Completed Tasks</div><div class="value" style="color:#16a34a;"><?= $task_completed ?></div></div>
  <div class="summary-card"><div class="label">My Earnings</div><div class="value">Tsh <?= $total ?></div></div>
</div>

<div class="dashboard">

<!-- TASKS -->
<div class="card">
<h3>📋 Assigned Tasks</h3>
<table>
<tr><th>ID</th><th>Location</th><th>Status</th><th>Action</th></tr>

<?php foreach($tasks as $t):
$color = ($t['status']=="Completed") ? "green" : (($t['status']=="Ongoing") ? "orange" : "purple"); ?>

<tr style="color:<?php echo $color; ?>">
<td><?php echo $t['id']; ?></td>
<td><?php echo $t['location']; ?></td>
<td><?php echo $t['status']; ?></td>
<td>
<form method="POST">
<input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
<button name="action" value="Completed">Complete</button>
<button name="action" value="Ongoing">Ongoing</button>
<button name="action" value="Failed">Fail</button>
</form>
</td>
</tr>

<?php endforeach; ?>
</table>
</div>

<!-- MAP -->
<div class="card">
<h3>🗺 Installation Map</h3>
<div id="map"></div>
</div>

<!-- PAYMENTS -->
<div class="card" style="grid-column: span 2;">
<h3>💰 My Earnings (Installer)</h3>

<table>
<tr>
<th>User</th>
<th>Amount (Tsh)</th>
<th>Status</th>
<th>Date</th>
</tr>

<?php foreach($payments as $p): ?>
<tr>
<td><?php echo $p['username']; ?></td>
<td><?php echo $p['amount']; ?></td>
<td class="status-paid"><?php echo $p['status']; ?></td>
<td><?php echo $p['created_at']; ?></td>
</tr>
<?php endforeach; ?>
</table>

<!-- TOTAL -->
<div class="total-box">
💵 Total Earnings: Tsh <?php echo $total; ?>
</div>

</div>

</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
var map = L.map('map').setView([-9.15, 33.6], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

var tasks = <?php echo json_encode($tasks); ?>;

tasks.forEach(function(task){
    var color = (task.status=="Completed") ? "green" : ((task.status=="Ongoing") ? "orange" : "purple");

    L.circleMarker([task.lat, task.lng], {
        radius:8,
        fillColor:color,
        color:"#000",
        weight:1,
        fillOpacity:0.8
    })
    .bindPopup(`Task ${task.id} - ${task.location}<br>Status: ${task.status}`)
    .addTo(map);
});
</script>

</body>
</html>