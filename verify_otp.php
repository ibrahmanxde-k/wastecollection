<?php
session_start();

// Ensure user is logged in
if(!isset($_SESSION['user'])){
    header("Location: index.php");
    exit;
}

if(isset($_POST['verify'])){
    $otp = trim($_POST['otp']);

    // Demo OTP (you can change this later)
    if($otp == "1234"){

        $role = strtolower($_SESSION['role']);

        if($role == 'admin'){
            header("Location: dashboard_admin.php");
            exit;

        } elseif($role == 'citizen'){
            header("Location: dashboard_citizen.php");
            exit;

        } elseif($role == 'driver'){
            header("Location: dashboard_driver.php");
            exit;

        } elseif($role == 'collector'){
            header("Location: dashboard_collector.php");
            exit;

        } elseif($role == 'installer'){
            header("Location: dashboard_installer.php");
            exit;

        } else {
            $error = "❌ Role not recognized!";
        }

    } else {
        $error = "❌ Invalid OTP!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="dark">
<div class="page-wrap">
  <div class="form-container">
      <h2 class="form-title">OTP Verification</h2>
      <p class="form-subtitle">Welcome, <?php echo $_SESSION['user']; ?>. Enter your OTP to continue.</p>

      <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

      <form method="POST">
          <label for="verify-otp">OTP Code</label>
          <input id="verify-otp" type="text" name="otp" placeholder="Enter OTP (1234)" required>
          <button type="submit" name="verify">Verify</button>
      </form>
  </div>
</div>

</body>
</html>