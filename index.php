<?php
session_start();
include 'db.php';

if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 🔒 Check login attempts
    if(isset($_SESSION['attempts']) && $_SESSION['attempts'] >= 3){
        if(time() - $_SESSION['last_attempt'] < 180){
            $error = "⏳ Account blocked for 3 minutes!";
        } else {
            $_SESSION['attempts'] = 0;
        }
    }

    // ⛔ Stop login if blocked
    if(!isset($error)){

        // ✅ Secure query
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $query = $stmt->get_result();

        if($query->num_rows > 0){
            $row = $query->fetch_assoc();

            // 🔥 PLAIN PASSWORD CHECK
            if($password === $row['password']){

                // reset attempts
                $_SESSION['attempts'] = 0;

                $_SESSION['user'] = $row['username'];
                $_SESSION['role'] = strtolower($row['role']);

                header("Location: otp.php");
                exit;

            } else {
                $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
                $_SESSION['last_attempt'] = time();
                $error = "❌ Invalid password!";
            }

        } else {
            $error = "⚠️ User not found!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Smart Waste System</title>
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
    <h2 class="form-title">Smart Waste System</h2>
    <p class="form-subtitle">Sign in to continue to your account.</p>

    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
      <label for="username">Username</label>
      <input id="username" type="text" name="username" placeholder="Enter username" required>

      <label for="password">Password</label>
      <input id="password" type="password" name="password" placeholder="Enter password" required>

      <button type="submit" name="login">Login</button>
    </form>

    <p class="small-link">Don't have an account? <a href="register.php">Create a new account</a></p>

  </div>
</div>

</body>
</html>