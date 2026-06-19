<?php
session_start();
$otp = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"),0,6);
$_SESSION['otp'] = $otp;
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<script src="script.js"></script>
</head>
<body class="dark">
<div class="theme-toggle">
  <button type="button" onclick="toggleTheme()">Toggle Theme</button>
  <p id="themeStatus">🌙 Dark Mode</p>
</div>
<div class="page-wrap">
  <div class="form-container">
    <h2 class="form-title">OTP Verification</h2>
    <p class="form-subtitle">Enter your OTP to verify sign in.</p>
    <p id="timer" class="status-chip"></p>
    <p id="otp" class="hidden"><?php echo $_SESSION['otp']; ?></p>
    <button type="button" onclick="revealOTP()">View OTP</button>
    <form method="POST" action="verify_otp.php">
      <label for="otp-code">OTP Code</label>
      <input id="otp-code" type="text" name="otp" placeholder="Enter OTP code" required>
      <button type="submit">Verify</button>
    </form>
  </div>
</div>
<script>startCountdown();</script>
</body>
</html>