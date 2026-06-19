<?php
include 'db.php';
$bin_id = $_POST['bin_id'];
$location = $_POST['location'];

$query = "INSERT INTO bins (bin_id,location,status) VALUES ('$bin_id','$location','empty')";
if(mysqli_query($conn,$query)){
    echo "✅ Bin added successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>