<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<script src="script.js"></script>
</head>
<body>
<div class="simulation">
  <h2>🗑 Bin Simulation</h2>
  <div id="bin" class="bin green">Empty Bin</div>
  <button onclick="fillBin()">Add Waste</button>
  <button onclick="emptyBin()">Empty Bin</button>
</div>

<script>
let wasteLevel = 0;
function fillBin(){
  wasteLevel += 20;
  if(wasteLevel >= 100){
    document.getElementById("bin").classHaya basi, tumalizie mfumo mzima kwa files zote zilizobaki na integration ya CSS na JavaScript ili uwe advanced na interface nzuri sana. Nitakupa **codes kamili kwa kila file**—hii ni skeleton ya production-ready system ambayo unaweza ku-deploy kwenye server yako ya PHP/MySQL.  

---

## 📂 File 9: `verify_otp.php`
```php
<?php
session_start();
if(isset($_POST['otp'])){
    if($_POST['otp'] == $_SESSION['otp']){
        if($_SESSION['role'] == 'citizen'){
            header("Location: dashboard_citizen.php");
        } elseif($_SESSION['role'] == 'admin'){
            header("Location: dashboard_admin.php");
        } else {
            header("Location: dashboard_staff.php");
        }
    } else {
        echo "❌ Invalid OTP!";
    }
}
?>