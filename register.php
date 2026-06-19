<?php
include 'db.php';
if(isset($_POST['register'])){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = 'citizen'; // Default role

    $query = "INSERT INTO users (username,password,email,role) VALUES ('$username','$password','$email','$role')";
    if(mysqli_query($conn,$query)){
        header("Location: index.php"); // Redirect to login
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
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
    <h2 class="form-title">Create Account</h2>
    <p class="form-subtitle">Create your account to start using the system.</p>
    <form method="POST">
      <label for="reg-username">Username</label>
      <input id="reg-username" type="text" name="username" placeholder="Choose a username" required>

      <label for="reg-email">Email</label>
      <input id="reg-email" type="email" name="email" placeholder="Enter email" required>

      <label for="reg-password">Password</label>
      <input id="reg-password" type="password" name="password" placeholder="Enter password" required>

      <button type="submit" name="register">Register</button>
    </form>
    <p class="small-link">Already have an account? <a href="index.php">Login here</a></p>
  </div>
</div>
</body>
</html>