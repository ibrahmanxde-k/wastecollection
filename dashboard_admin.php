<?php
session_start();
include 'db.php';

// 🔒 AUTH
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location:index.php");
    exit;
}

// ================= HELPERS =================
function validate($data){
    return !empty(trim((string)$data));
}

function isValidRole($role){
    return in_array($role, ['driver', 'collector', 'installer'], true);
}

// ================= ADD STAFF =================
if (isset($_POST['add_staff'])) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? ''); // plain password (as requested)
    $role     = trim($_POST['role'] ?? '');

    if ($username !== '' && $email !== '' && $password !== '' && isValidRole($role)) {
        $stmt = $conn->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?,?)");
        if (!$stmt) {
            echo "<script>alert('❌ DB Prepare Error (Staff): " . addslashes($conn->error) . "');</script>";
        } else {
            $stmt->bind_param("ssss", $username, $email, $password, $role);
            if ($stmt->execute()) {
                echo "<script>alert('✅ Staff Added Successfully'); window.location='dashboard_admin.php';</script>";
                exit;
            } else {
                echo "<script>alert('❌ Staff Insert Error: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
    } else {
        echo "<script>alert('⚠️ Please fill all staff fields correctly');</script>";
    }
}

// ================= ADD BIN =================
if (isset($_POST['add_bin'])) {
    $bin_id    = trim($_POST['bin_id'] ?? '');
    $location  = trim($_POST['location'] ?? '');
    $latitude  = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    if (
        $bin_id !== '' && $location !== '' && $latitude !== '' && $longitude !== '' &&
        is_numeric($latitude) && is_numeric($longitude)
    ) {
        $lat = (float)$latitude;
        $lng = (float)$longitude;
        $status = 'Empty';

        $stmt = $conn->prepare("INSERT INTO bins (bin_id,location,latitude,longitude,status) VALUES (?,?,?,?,?)");
        if (!$stmt) {
            echo "<script>alert('❌ DB Prepare Error (Bin): " . addslashes($conn->error) . "');</script>";
        } else {
            $stmt->bind_param("ssdds", $bin_id, $location, $lat, $lng, $status);
            if ($stmt->execute()) {
                echo "<script>alert('✅ Bin Added Successfully'); window.location='dashboard_admin.php';</script>";
                exit;
            } else {
                echo "<script>alert('❌ Bin Insert Error: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
    } else {
        echo "<script>alert('⚠️ Fill all bin fields correctly');</script>";
    }
}

// ================= CREATE / ASSIGN TASK =================
if (isset($_POST['create_task'])) {
    $task_name   = trim($_POST['task_name'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $latRaw      = trim($_POST['lat'] ?? '');
    $lngRaw      = trim($_POST['lng'] ?? '');
    $assignedRaw = trim($_POST['assigned_to'] ?? '');
    $role        = trim($_POST['role'] ?? '');

    if (
        $task_name !== '' && $location !== '' &&
        $latRaw !== '' && $lngRaw !== '' &&
        $assignedRaw !== '' &&
        is_numeric($latRaw) && is_numeric($lngRaw) &&
        ctype_digit($assignedRaw) &&
        isValidRole($role)
    ) {
        $lat = (float)$latRaw;
        $lng = (float)$lngRaw;
        $assignedTo = (int)$assignedRaw;

        $checkStaff = $conn->prepare("SELECT id FROM users WHERE id=? AND role=? LIMIT 1");
        if ($checkStaff) {
            $checkStaff->bind_param("is", $assignedTo, $role);
            $checkStaff->execute();
            $staffRes = $checkStaff->get_result();

            if ($staffRes && $staffRes->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO tasks (task_name,location,lat,lng,assigned_to,role,status) VALUES (?,?,?,?,?,?,'Pending')");
                if ($stmt) {
                    $stmt->bind_param("ssddis", $task_name, $location, $lat, $lng, $assignedTo, $role);
                    if ($stmt->execute()) {
                        echo "<script>alert('✅ Task Created & Assigned'); window.location='dashboard_admin.php';</script>";
                        exit;
                    } else {
                        echo "<script>alert('❌ Failed to create task: " . addslashes($stmt->error) . "');</script>";
                    }
                    $stmt->close();
                } else {
                    echo "<script>alert('❌ DB Prepare Error (Task): " . addslashes($conn->error) . "');</script>";
                }
            } else {
                echo "<script>alert('⚠️ Selected staff does not match selected role');</script>";
            }

            $checkStaff->close();
        } else {
            echo "<script>alert('❌ DB Prepare Error (Check Staff): " . addslashes($conn->error) . "');</script>";
        }
    } else {
        echo "<script>alert('⚠️ Fill all task fields correctly');</script>";
    }
}

// ================= DELETE USER =================
if (isset($_GET['delete_user'])) {
    $id = (int)($_GET['delete_user'] ?? 0);
    if ($id > 0) {
        $conn->query("DELETE FROM users WHERE id=$id");
    }
    echo "<script>alert('🗑 User Deleted'); window.location='dashboard_admin.php';</script>";
    exit;
}

// ================= UPDATE USER =================
if (isset($_POST['update_user'])) {
    $userId   = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = trim($_POST['role'] ?? '');

    if ($userId > 0 && validate($username) && validate($email) && validate($role)) {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("sssi", $username, $email, $role, $userId);
            if ($stmt->execute()) {
                echo "<script>alert('✏️ User Updated'); window.location='dashboard_admin.php';</script>";
                $stmt->close();
                exit;
            } else {
                echo "<script>alert('❌ Update failed: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('❌ DB Prepare Error (Update User): " . addslashes($conn->error) . "');</script>";
        }
    } else {
        echo "<script>alert('⚠️ Fill all update fields correctly');</script>";
    }
}

// ================= CONFIRM PAYMENT =================
if (isset($_POST['confirm_payment'])) {
    $payment_id = (int)($_POST['payment_id'] ?? 0);
    if ($payment_id > 0) {
        $stmt = $conn->prepare("UPDATE payments SET status='Paid' WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $stmt->close();
            echo "<script>alert('✅ Payment Confirmed'); window.location='dashboard_admin.php';</script>";
            exit;
        }
    }
}

// ================= DATA =================
$citizens = mysqli_query($conn, "SELECT * FROM users WHERE role='citizen'");
$staffs   = mysqli_query($conn, "SELECT * FROM users WHERE role IN ('driver','collector','installer')");
$reports  = mysqli_query($conn, "SELECT r.*, u.username FROM reports r LEFT JOIN users u ON r.user_id=u.id");
$payments = mysqli_query($conn, "SELECT p.*, u.username FROM payments p JOIN users u ON p.user_id=u.id");

$tasksView = mysqli_query($conn, "
    SELECT t.*, u.username AS assigned_name
    FROM tasks t
    LEFT JOIN users u ON u.id = t.assigned_to
    ORDER BY t.id DESC
");

$staffForTask = mysqli_query($conn, "SELECT id,username,role FROM users WHERE role IN ('driver','collector','installer') ORDER BY username ASC");
$binsForMap   = mysqli_query($conn, "SELECT bin_id,location,latitude,longitude,status FROM bins WHERE latitude IS NOT NULL AND longitude IS NOT NULL");

// Payment summary
$summaryRes = mysqli_query($conn, "
    SELECT
        COALESCE(SUM(amount),0) AS total_amount,
        COALESCE(SUM(CASE WHEN status='Paid' THEN amount ELSE 0 END),0) AS total_paid,
        COALESCE(SUM(CASE WHEN status='Pending' THEN amount ELSE 0 END),0) AS total_pending
    FROM payments
");
$paymentSummary = mysqli_fetch_assoc($summaryRes);

// Bin summary
$binSummaryRes = mysqli_query($conn, "
    SELECT
        COUNT(*) AS total_bins,
        COALESCE(SUM(CASE WHEN status='Empty' THEN 1 ELSE 0 END),0) AS empty_bins,
        COALESCE(SUM(CASE WHEN status='Medium' THEN 1 ELSE 0 END),0) AS medium_bins,
        COALESCE(SUM(CASE WHEN status='Full' THEN 1 ELSE 0 END),0) AS full_bins
    FROM bins
");
$binSummary = mysqli_fetch_assoc($binSummaryRes);

// Edit selected user
$editUser = null;
if (isset($_GET['edit_user'])) {
    $editId = (int)($_GET['edit_user'] ?? 0);
    if ($editId > 0) {
        $editStmt = $conn->prepare("SELECT id,username,email,role FROM users WHERE id=? LIMIT 1");
        if ($editStmt) {
            $editStmt->bind_param("i", $editId);
            $editStmt->execute();
            $editRes = $editStmt->get_result();
            $editUser = $editRes->fetch_assoc();
            $editStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<style>
*{box-sizing:border-box}
body{
  margin:0;
  font-family:Segoe UI,sans-serif;
  background:linear-gradient(135deg,#eef3ff,#f7faff);
  color:#1f2937;
}
.navbar{
  background:linear-gradient(135deg,#1d4ed8,#2563eb);
  color:#fff;
  padding:14px 20px;
  position:sticky;
  top:0;
  z-index:99;
  box-shadow:0 8px 18px rgba(29,78,216,.28);
}
.navbar h2{margin:0;font-size:20px}

.summary-strip{
  display:grid;
  grid-template-columns:repeat(7,1fr);
  gap:14px;
  padding:14px 20px 0 20px;
}
.summary-card{
  background:#fff;
  border:1px solid #dbe6ff;
  border-radius:12px;
  padding:14px;
  box-shadow:0 6px 16px rgba(15,23,42,0.08);
}
.summary-title{font-size:13px;color:#64748b;margin:0 0 6px 0}
.summary-value{font-size:23px;font-weight:700;margin:0;color:#0f172a}
.summary-value.green{color:#16a34a}
.summary-value.orange{color:#f59e0b}
.summary-value.red{color:#dc2626}

.dashboard{
  display:grid;
  grid-template-columns:repeat(12,1fr);
  gap:16px;
  padding:16px 20px 20px 20px;
}
.card{
  background:#fff;
  padding:14px;
  border-radius:12px;
  border:1px solid #dbe6ff;
  box-shadow:0 6px 16px rgba(15,23,42,0.07);
}
.card h3{
  margin:0 0 10px;
  font-size:16px;
  color:#0f172a;
}
.card.form-card{grid-column:span 4}
.card.table-card{grid-column:span 6}
.card.map-card{grid-column:span 12}
.card.update-card{grid-column:span 12}

input,select{
  width:100%;
  padding:10px;
  margin:6px 0;
  border:1px solid #d1d5db;
  border-radius:8px;
}
input:focus,select:focus{
  outline:none;
  border-color:#3b82f6;
  box-shadow:0 0 0 3px rgba(59,130,246,0.16);
}
button{
  background:linear-gradient(135deg,#2563eb,#1d4ed8);
  color:#fff;
  padding:10px 12px;
  border:none;
  border-radius:8px;
  cursor:pointer;
  font-weight:600;
}
button:hover{filter:brightness(1.06)}

.table-wrap{
  max-height:260px;
  overflow:auto;
  border-radius:10px;
  border:1px solid #e5e7eb;
}
table{width:100%;border-collapse:collapse;font-size:13px;background:#fff}
th{
  background:#eff6ff;
  color:#1d4ed8;
  position:sticky;
  top:0;
  z-index:1;
}
td,th{padding:8px;border-bottom:1px solid #e5e7eb;text-align:left}

.action-btn{
  padding:5px 9px;
  border-radius:7px;
  text-decoration:none;
  color:white;
  display:inline-block;
  font-size:12px;
}
.delete{background:#dc2626}
.update{background:#16a34a}
#adminMap{height:370px;border-radius:10px}
.footer{margin-top:10px;background:#0f172a;color:#fff;text-align:center;padding:13px}

@media (max-width: 1200px){
  .card.form-card{grid-column:span 6}
  .card.table-card{grid-column:span 12}
}
@media (max-width: 900px){
  .summary-strip{grid-template-columns:1fr}
  .card.form-card,.card.table-card,.card.map-card,.card.update-card{grid-column:span 12}
}
</style>
</head>
<body>

<div class="navbar" style="display:flex;justify-content:space-between;align-items:center;">
  <h2 style="margin:0;">🛡️ Admin Dashboard</h2>
  <div style="display:flex;align-items:center;gap:12px;">
    <span style="font-size:13px;background:rgba(255,255,255,0.15);padding:5px 12px;border-radius:999px;">
      👤 <?php echo htmlspecialchars($_SESSION['user'] ?? 'Admin'); ?>
    </span>
    <a href="logout.php"
       style="background:rgba(255,255,255,0.2);color:white;padding:7px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;border:1px solid rgba(255,255,255,0.3);">
      🚪 Logout
    </a>
  </div>
</div>

<div class="summary-strip">
  <div class="summary-card">
    <p class="summary-title">Total Bins</p>
    <p class="summary-value"><?= (int)$binSummary['total_bins'] ?></p>
  </div>
  <div class="summary-card">
    <p class="summary-title">Empty Bins</p>
    <p class="summary-value green"><?= (int)$binSummary['empty_bins'] ?></p>
  </div>
  <div class="summary-card">
    <p class="summary-title">Half Bins</p>
    <p class="summary-value orange"><?= (int)$binSummary['medium_bins'] ?></p>
  </div>
  <div class="summary-card">
    <p class="summary-title">Full Bins</p>
    <p class="summary-value red"><?= (int)$binSummary['full_bins'] ?></p>
  </div>
  <div class="summary-card">
    <p class="summary-title">Total Amount</p>
    <p class="summary-value">TZS <?= number_format((float)$paymentSummary['total_amount'], 2) ?></p>
  </div>
  <div class="summary-card">
    <p class="summary-title">Total Paid</p>
    <p class="summary-value green">TZS <?= number_format((float)$paymentSummary['total_paid'], 2) ?></p>
  </div>
  <div class="summary-card">
    <p class="summary-title">Total Pending</p>
    <p class="summary-value orange">TZS <?= number_format((float)$paymentSummary['total_pending'], 2) ?></p>
  </div>
</div>

<div class="dashboard">

  <?php if($editUser): ?>
  <div class="card update-card">
    <h3>Update User #<?= (int)$editUser['id'] ?></h3>
    <form method="POST">
      <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
      <input name="username" value="<?= htmlspecialchars($editUser['username']) ?>" required>
      <input type="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required>
      <select name="role" required>
        <?php
          $roles = ['citizen','driver','collector','installer','admin'];
          foreach($roles as $r):
        ?>
        <option value="<?= $r ?>" <?= $editUser['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
      <button name="update_user" type="submit">Save Changes</button>
      <a class="action-btn" style="background:#6b7280" href="dashboard_admin.php">Cancel</a>
    </form>
  </div>
  <?php endif; ?>

  <div class="card form-card">
    <h3>Add Bin</h3>
    <form method="POST">
      <input name="bin_id" placeholder="Bin ID" required>
      <input name="location" placeholder="Location" required>
      <input type="number" step="0.0000001" name="latitude" placeholder="Latitude" required>
      <input type="number" step="0.0000001" name="longitude" placeholder="Longitude" required>
      <button name="add_bin" type="submit">Add Bin</button>
    </form>
  </div>

  <div class="card form-card">
    <h3>Add Staff</h3>
    <form method="POST">
      <input name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <select name="role" required>
        <option value="">Select Role</option>
        <option value="driver">Driver</option>
        <option value="collector">Collector</option>
        <option value="installer">Installer</option>
      </select>
      <button name="add_staff" type="submit">Add Staff</button>
    </form>
  </div>

  <div class="card form-card">
    <h3>Assign Task</h3>
    <form method="POST">
      <input name="task_name" placeholder="Task Name" required>
      <input name="location" placeholder="Location" required>
      <input type="number" step="0.0000001" name="lat" placeholder="Latitude" required>
      <input type="number" step="0.0000001" name="lng" placeholder="Longitude" required>
      <select name="assigned_to" required>
        <option value="">Select Staff</option>
        <?php while($st = mysqli_fetch_assoc($staffForTask)): ?>
          <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['username']) ?> (<?= htmlspecialchars($st['role']) ?>)</option>
        <?php endwhile; ?>
      </select>
      <select name="role" required>
        <option value="">Select Role</option>
        <option value="driver">Driver</option>
        <option value="collector">Collector</option>
        <option value="installer">Installer</option>
      </select>
      <button name="create_task" type="submit">Create & Assign Task</button>
    </form>
  </div>

  <div class="card table-card">
    <h3>🧾 Assigned Tasks</h3>
    <div class="table-wrap"><table>
      <tr><th>ID</th><th>Task</th><th>Location</th><th>Assigned To</th><th>Role</th><th>Status</th></tr>
      <?php while($t = mysqli_fetch_assoc($tasksView)): ?>
      <tr>
        <td><?= (int)$t['id'] ?></td>
        <td><?= htmlspecialchars($t['task_name']) ?></td>
        <td><?= htmlspecialchars($t['location']) ?></td>
        <td><?= htmlspecialchars($t['assigned_name'] ?? 'Unassigned') ?></td>
        <td><?= htmlspecialchars($t['role'] ?? '-') ?></td>
        <td><?= htmlspecialchars($t['status']) ?></td>
      </tr>
      <?php endwhile; ?>
    </table></div>
  </div>

  <div class="card table-card">
    <h3>👥 Citizens</h3>
    <div class="table-wrap"><table>
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Action</th></tr>
      <?php while($c=mysqli_fetch_assoc($citizens)): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['username']) ?></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td>
          <a class="action-btn update" href="?edit_user=<?= $c['id'] ?>">Update</a>
          <a class="action-btn delete" href="?delete_user=<?= $c['id'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table></div>
  </div>

  <div class="card table-card">
    <h3>👷 Staff</h3>
    <div class="table-wrap"><table>
      <tr><th>ID</th><th>Name</th><th>Role</th><th>Action</th></tr>
      <?php while($s=mysqli_fetch_assoc($staffs)): ?>
      <tr>
        <td><?= $s['id'] ?></td>
        <td><?= htmlspecialchars($s['username']) ?></td>
        <td><?= htmlspecialchars($s['role']) ?></td>
        <td>
          <a class="action-btn update" href="?edit_user=<?= $s['id'] ?>">Update</a>
          <a class="action-btn delete" href="?delete_user=<?= $s['id'] ?>" onclick="return confirm('Delete this staff?')">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table></div>
  </div>

  <div class="card table-card">
    <h3>📢 Reports</h3>
    <div class="table-wrap"><table>
      <tr><th>User</th><th>Bin</th><th>Message</th></tr>
      <?php while($r=mysqli_fetch_assoc($reports)): ?>
      <tr>
        <td><?= htmlspecialchars($r['username'] ?? 'Unknown') ?></td>
        <td><?= htmlspecialchars($r['bin_id']) ?></td>
        <td><?= htmlspecialchars($r['report_text']) ?></td>
      </tr>
      <?php endwhile; ?>
    </table></div>
  </div>

  <div class="card table-card">
    <h3>💰 Payments</h3>
    <div class="table-wrap"><table>
      <tr><th>User</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Action</th></tr>
      <?php while($p=mysqli_fetch_assoc($payments)): ?>
      <tr>
        <td><?= htmlspecialchars($p['username']) ?></td>
        <td><?= $p['amount'] ?></td>
        <td><?= htmlspecialchars($p['method'] ?? '-') ?></td>
        <td><?= htmlspecialchars($p['reference'] ?? '-') ?></td>
        <td><?= htmlspecialchars($p['status']) ?></td>
        <td>
          <?php if($p['status']=='Pending'): ?>
            <form method="POST" style="margin:0">
              <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
              <button name="confirm_payment" type="submit">Confirm</button>
            </form>
          <?php else: ?>✔<?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </table></div>
  </div>

  <div class="card map-card">
    <h3>🗺 Bins Map (Normal / Satellite)</h3>
    <div id="adminMap"></div>
  </div>

</div>

<div class="footer">
  © <?= date("Y") ?> Smart Waste Management System | Admin Panel
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
var bins = <?php echo json_encode(mysqli_fetch_all($binsForMap, MYSQLI_ASSOC)); ?>;
var map = L.map('adminMap').setView([-8.9, 33.45], 13);

var normalLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap contributors'
});
var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
  maxZoom: 19,
  attribution: 'Tiles &copy; Esri'
});

normalLayer.addTo(map);
L.control.layers(
  {"Normal Map": normalLayer, "Satellite Map": satelliteLayer},
  {},
  { collapsed: false }
).addTo(map);

var markerGroup = L.featureGroup().addTo(map);
bins.forEach(function(bin){
  var lat = parseFloat(bin.latitude), lng = parseFloat(bin.longitude);
  if (isNaN(lat) || isNaN(lng)) return;

  var color = bin.status === "Full" ? "red" : (bin.status === "Medium" ? "orange" : "green");
  var marker = L.circleMarker([lat, lng], {
    radius: 8, color: "#111", weight: 1, fillColor: color, fillOpacity: 0.9
  }).bindPopup(
    "<b>Bin:</b> " + (bin.bin_id || "-") + "<br>" +
    "<b>Location:</b> " + (bin.location || "-") + "<br>" +
    "<b>Status:</b> " + (bin.status || "-")
  );
  markerGroup.addLayer(marker);
});

if (markerGroup.getLayers().length > 0) {
  map.fitBounds(markerGroup.getBounds().pad(0.2));
}
</script>

</body>
</html>