<?php
session_start();
include 'db.php';

// Hakikisha user ame-login
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    header("Location:index.php");
    exit;
}

$user = $_SESSION['user'];
$role = strtolower($_SESSION['role']);

// Ruhusu citizen tu kulipa
if ($role !== 'citizen') {
    echo "<h3>❌ Only citizens are allowed to make payment.</h3>";
    exit;
}

// Pata user_id kwa usalama
$getUser = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
if (!$getUser) {
    die("Prepare failed (user): " . $conn->error);
}
$getUser->bind_param("s", $user);
$getUser->execute();
$userResult = $getUser->get_result();
$userRow    = $userResult->fetch_assoc();
$getUser->close();

if (!$userRow) {
    echo "<h3>❌ User not found.</h3>";
    exit;
}

$user_id = (int)$userRow['id'];

// Kagua kama ana pending payment tayari
$checkPending = $conn->prepare("SELECT id FROM payments WHERE user_id=? AND status='Pending' LIMIT 1");
if (!$checkPending) {
    die("Prepare failed (pending): " . $conn->error);
}
$checkPending->bind_param("i", $user_id);
$checkPending->execute();
$pendingResult = $checkPending->get_result();
$checkPending->close();

if ($pendingResult->num_rows > 0) {
    echo "<script>alert('⚠️ You already have a pending payment.'); window.location='dashboard_citizen.php';</script>";
    exit;
}

// Chukua method na namba kutoka form
$payment_method = trim($_POST['payment_method'] ?? '');
$payment_number = trim($_POST['payment_number'] ?? '');
$amountInput    = (int)($_POST['amount'] ?? 1000);

// Validate payment method
$allowed_methods = ['mobile_money', 'bank_account', 'cash_office'];
if (!in_array($payment_method, $allowed_methods, true)) {
    echo "<script>alert('❌ Invalid payment method selected.'); window.location='dashboard_citizen.php';</script>";
    exit;
}

// Validate payment number (kwa mobile_money / bank_account)
if (($payment_method === 'mobile_money' || $payment_method === 'bank_account') && $payment_number === '') {
    echo "<script>alert('❌ Please enter phone/account number.'); window.location='dashboard_citizen.php';</script>";
    exit;
}

// Amount fixed
$amount = ($amountInput > 0) ? $amountInput : 1000;

// Mgawanyo fixed
$driver     = 200;
$collector  = 150;
$installer  = 150;
$government = 500;

// ============================================
// Reference — inatengenezwa automatically
// Mfano: REF-20260613-A3F9K
// ============================================
$date      = date('Ymd');
$random    = strtoupper(substr(uniqid(), -5));
$reference = "REF-" . $date . "-" . $random;

// Insert payment
$stmt = $conn->prepare("
    INSERT INTO payments
    (user_id, amount, driver_share, collector_share, installer_share, government_share, status, method, reference)
    VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?)
");

if (!$stmt) {
    die("Prepare failed (insert): " . $conn->error);
}

$stmt->bind_param(
    "iiiiiiss",
    $user_id,
    $amount,
    $driver,
    $collector,
    $installer,
    $government,
    $payment_method,
    $reference
);

if ($stmt->execute()) {
    echo "<script>alert('✅ Payment request created successfully.\nReference: $reference'); window.location='dashboard_citizen.php';</script>";
} else {
    echo "<h3>❌ Payment failed: " . htmlspecialchars($stmt->error) . "</h3>";
}

$stmt->close();
?>
