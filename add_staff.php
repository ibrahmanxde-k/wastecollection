<?php
include 'db.php';
$username = $_POST['username'];
$email = $_POST['email'];
$role = $_POST['role'];
$password = password_hash("default123", PASSWORD_DEFAULT);

$query = "INSERT INTO users (username,password,email,role) VALUES ('$username','$password','$email','$role')";
if(mysqli_query($conn,$query)){
    echo "✅ Staff added successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>