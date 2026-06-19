<?php
session_start();
include 'db.php';

// Hakikisha user ame-login na form imetumwa
if (!isset($_SESSION['user']) || !isset($_POST['bin_id']) || !isset($_POST['report_text'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];
$bin_id = trim($_POST['bin_id']);
$report_text = trim($_POST['report_text']);

// Ruhusu report type sahihi tu
if ($bin_id === '' || !in_array($report_text, ['Full', 'Damage'], true)) {
    echo "<h2>❌ Invalid report data!</h2>";
    exit;
}

// Chukua user_id kutoka username ya session
$userStmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
$userStmt->bind_param("s", $user);
$userStmt->execute();
$userRes = $userStmt->get_result();
$userRow = $userRes->fetch_assoc();
$userStmt->close();

if (!$userRow) {
    echo "<h2>❌ User not found!</h2>";
    exit;
}

$user_id = (int)$userRow['id'];

// Chukua location ya bin kutoka database
$binStmt = $conn->prepare("SELECT location FROM bins WHERE bin_id=? LIMIT 1");
$binStmt->bind_param("s", $bin_id);
$binStmt->execute();
$binRes = $binStmt->get_result();
$binRow = $binRes->fetch_assoc();
$binStmt->close();

if (!$binRow) {
    echo "<h2>❌ Selected bin not found!</h2>";
    exit;
}

$location = $binRow['location'];

// Save report
$insertStmt = $conn->prepare("INSERT INTO reports (user_id, bin_id, location, report_text) VALUES (?, ?, ?, ?)");
$insertStmt->bind_param("isss", $user_id, $bin_id, $location, $report_text);

if ($insertStmt->execute()) {
    echo "<script>alert('📢 Report submitted successfully!'); window.location='dashboard_citizen.php';</script>";
} else {
    echo "<h2>❌ Failed to submit report!</h2>";
}

$insertStmt->close();
?>