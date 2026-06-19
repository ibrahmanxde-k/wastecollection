<?php
include 'db.php';
$task_id = $_POST['task_id'];
mysqli_query($conn,"DELETE FROM reports WHERE id='$task_id'");
echo "✔ Task approved and marked as completed!";
?>