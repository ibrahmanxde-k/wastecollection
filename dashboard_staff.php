<?php
session_start();
include 'db.php';
if(!isset($_SESSION['role'])){
  header("Location:index.php");
  exit;
}
if($_SESSION['role'] === 'driver'){
  header("Location:dashboard_driver.php");
  exit;
}
if($_SESSION['role'] === 'collector'){
  header("Location:dashboard_collector.php");
  exit;
}
if($_SESSION['role'] === 'installer'){
  header("Location:dashboard_installer.php");
  exit;
}
header("Location:index.php");
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Dashboard</title>
<link rel="stylesheet" href="style.css">
<script src="script.js"></script>
<style>
body { margin:0; font-family:'Segoe UI',sans-serif; }
.navbar {
  display:flex; justify-content:space-between; align-items:center;
  background:#28a745; color:white; padding:10px 20px;
}
.navbar h2 { margin:0; }
.theme-toggle button {
  background:#fff; color:#28a745; border:none; padding:6px 12px;
  border-radius:5px; cursor:pointer; font-size:14px;
}
.dashboard {
  padding:20px;
  display:grid;
  grid-template-columns: 1fr 1fr;
  grid-gap:20px;
}
.card {
  background:#f9f9f9; border-radius:10px; padding:15px;
  box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
.card h3 { margin-top:0; }
.staff-card {
  display:flex; align-items:center; margin-bottom:10px;
}
.staff-card img { width:50px; height:50px; margin-right:10px; }
button {
  background:#28a745; color:white; border:none; padding:10px 15px;
  border-radius:5px; cursor:pointer; font-size:14px;
}
button:hover { background:#1e7e34; }
</style>
</head>
<body class="light">

<div class="navbar">
  <h2>Staff Dashboard</h2>
  <div class="theme-toggle">
    <button onclick="toggleTheme()">🌙/🌞</button>
  </div>
</div>

<div class="dashboard">
  <!-- Assigned Tasks -->
  <div class="card">
    <h3>📝 Assigned Tasks</h3>
    <?php
    $tasks = mysqli_query($conn,"SELECT * FROM tasks WHERE staff_id=".$_SESSION['user_id']);
    while($t = mysqli_fetch_assoc($tasks)){
      echo "<div class='task'>".$t['task_description']." — Status: ".$t['status']."</div>";
    }
    ?>
  </div>

  <!-- Profile -->
  <div class="card">
    <h3>👤 Profile</h3>
    <div class="staff-card">
      <img src="images/staff.png" alt="Staff">
      <span><?php echo $_SESSION['user']." (".$_SESSION['role'].")"; ?></span>
    </div>
  </div>
</div>

</body>
</html>