<?php
session_start();
include 'db.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'citizen'){
    header("Location:index.php");
    exit;
}

// BIN STATUS: ONYESHA BINS 3 TU (kutoka DB, statuses halali 3 tu)
$bins_sample = mysqli_query(
    $conn,
    "SELECT bin_id, location, status
     FROM bins
     WHERE status IN ('Empty','Medium','Full')
     ORDER BY id DESC
     LIMIT 3"
);

// ALL BINS FOR MAP
$bins = mysqli_query(
    $conn,
    "SELECT bin_id, location, latitude, longitude, status
     FROM bins
     WHERE status IN ('Empty','Medium','Full')"
);

// BINS FOR REPORT DROPDOWN
$bins_for_report = mysqli_query($conn, "SELECT bin_id, location FROM bins ORDER BY bin_id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Citizen Dashboard</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<style>
html,body{
  height:100%;
  margin:0;
  display:flex;
  flex-direction:column;
  font-family:'Segoe UI',sans-serif;
  background:linear-gradient(135deg,#eef3ff,#f8fbff);
}

.navbar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  background:linear-gradient(135deg,#1d4ed8,#2563eb);
  color:white;
  padding:12px 20px;
  position:sticky;
  top:0;
  z-index:20;
  box-shadow:0 8px 20px rgba(37,99,235,.25);
}

.dashboard{
  flex:1;
  padding:18px;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:16px;
  max-width:1280px;
  width:100%;
  margin:0 auto;
}

.card{
  background:#ffffff;
  border-radius:16px;
  padding:18px;
  border:1px solid #dbeafe;
  box-shadow:0 8px 22px rgba(15,23,42,0.08);
}

.card h3{
  margin:0 0 12px 0;
  font-size:18px;
  color:#0f172a;
}

.map-card{
  grid-column:1 / -1;
}

.bin-card{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:10px;
  padding:10px;
  border:1px solid #e5e7eb;
  border-radius:10px;
}

.bin-meta{
  display:flex;
  align-items:center;
  gap:10px;
}

.status-badge{
  font-size:12px;
  font-weight:700;
  padding:4px 10px;
  border-radius:999px;
  color:#fff;
}

/* Rangi ziendane na admin dashboard */
.status-empty{background:#16a34a;}   /* Empty = Green */
.status-medium{background:#f59e0b;}  /* Medium = Orange */
.status-full{background:#dc2626;}    /* Full = Red */

.muted{
  color:#6b7280;
  font-size:12px;
}

.bin-card img{
  width:50px;
  height:50px;
  margin-right:10px;
}

button{
  background:linear-gradient(135deg, #2563eb, #1d4ed8);
  color:white;
  border:none;
  padding:11px 14px;
  border-radius:10px;
  cursor:pointer;
  font-weight:600;
}
button:hover{filter:brightness(1.08);}

button:active{transform:translateY(1px);}

input, textarea, select{
  width:100%;
  padding:11px 12px;
  border-radius:10px;
  border:1px solid #d7deee;
  margin-bottom:10px;
  font-size:14px;
}

.form-panel{
  background:#f8fbff;
  border:1px solid #dbeafe;
  border-radius:12px;
  padding:10px;
}

.form-title{
  margin:2px 0 6px;
  font-size:13px;
  color:#334155;
  font-weight:600;
}

input:focus, textarea:focus, select:focus{
  outline:none;
  border-color:#2563eb;
  box-shadow:0 0 0 3px rgba(37, 99, 235, 0.15);
}

#map{
  height:420px;
  border-radius:10px;
}

/* ADVANCED PAYMENT UI */
.payment-card{
  background:linear-gradient(180deg,#ffffff,#f8fbff);
  border:1px solid #dbeafe;
}

.payment-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:10px;
}

.payment-title{
  margin:0;
}

.payment-badge{
  font-size:11px;
  font-weight:700;
  color:#1d4ed8;
  background:#dbeafe;
  border-radius:999px;
  padding:4px 9px;
}

.payment-hint{
  font-size:12px;
  color:#6b7280;
  margin:0 0 10px 0;
}

.method-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:8px;
  margin-bottom:10px;
}

.method-item{
  border:1px solid #dbeafe;
  background:#fff;
  border-radius:10px;
  padding:9px;
}

.method-item strong{
  display:block;
  font-size:13px;
  margin-bottom:3px;
}

.method-item span{
  font-size:11px;
  color:#64748b;
}

.pay-amount{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin:8px 0 10px 0;
  background:#eff6ff;
  border:1px solid #dbeafe;
  border-radius:10px;
  padding:8px 10px;
}

.pay-amount .label{
  font-size:12px;
  color:#475569;
}

.pay-amount .value{
  font-size:16px;
  font-weight:700;
  color:#1d4ed8;
}

.pay-btn{
  width:100%;
  background:linear-gradient(135deg,#10b981,#059669);
}

/* FOOTER */
.footer{
  margin-top:auto;
  background: linear-gradient(135deg,#0f172a,#1e293b,#334155);
  color:#fff;
}

.footer-container{
  display:flex;
  justify-content:space-between;
  flex-wrap:wrap;
  padding:20px;
}

.footer-section{
  flex:1;
  min-width:200px;
}

.footer-bottom{
  text-align:center;
  padding:10px;
  background:rgba(0,0,0,0.2);
}

@media (max-width: 900px){
  .dashboard{grid-template-columns:1fr;}
  #map{height:340px;}
}
</style>
</head>

<body>

<div class="navbar">
  <h2>Citizen Dashboard</h2>
  <div style="display:flex;align-items:center;gap:12px;">
    <span style="font-size:12px;background:rgba(255,255,255,0.2);padding:4px 10px;border-radius:999px;">
      Smart Waste Portal
    </span>
    <a href="logout.php" 
       style="background:rgba(255,255,255,0.2);color:white;padding:7px 14px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;border:1px solid rgba(255,255,255,0.3);">
      🚪 Logout
    </a>
  </div>
</div>

<div class="dashboard">

  <!-- BIN STATUS: BINS 3 TU -->
  <div class="card map-card">
    <h3>🗑 Bin Status</h3>

    <?php while($b = mysqli_fetch_assoc($bins_sample)): 
      $status = strtolower($b['status']);

      $img = "images/bin_empty.png";
      if($status === "medium") $img = "images/bin_half.png";
      if($status === "full")   $img = "images/bin_full.png";
    ?>
      <div class="bin-card">
        <div class="bin-meta">
          <img src="<?= $img ?>" alt="Bin">
          <div>
            <div><strong><?= htmlspecialchars($b['bin_id']) ?></strong></div>
            <div class="muted"><?= htmlspecialchars($b['location']) ?></div>
          </div>
        </div>
        <span class="status-badge status-<?= $status ?>">
          <?= htmlspecialchars($b['status']) ?>
        </span>
      </div>
    <?php endwhile; ?>
  </div>

  <!-- REPORT -->
  <div class="card">
    <h3>📢 Report a Bin</h3>
    <form method="POST" action="report.php" class="form-panel">
      <div class="form-title">Select Bin</div>
      <select id="bin_id" name="bin_id" required>
        <option value="">Select Bin</option>
        <?php while($rb = mysqli_fetch_assoc($bins_for_report)): ?>
          <option value="<?= htmlspecialchars($rb['bin_id']) ?>" data-location="<?= htmlspecialchars($rb['location']) ?>">
            <?= htmlspecialchars($rb['bin_id']) ?> - <?= htmlspecialchars($rb['location']) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <div class="form-title">Location</div>
      <input type="text" id="location" name="location" placeholder="Location" readonly required>

      <div class="form-title">Issue Type</div>
      <select name="report_text" required>
        <option value="">Select Issue Type</option>
        <option value="Full">Full</option>
        <option value="Damage">Damage</option>
      </select>

      <button type="submit">Submit Report</button>
    </form>
  </div>

  <!-- PAYMENT: ADVANCED -->
  <div class="card payment-card">
    <div class="payment-header">
      <h3 class="payment-title">💳 Smart Payment</h3>
      <span class="payment-badge">Secure Checkout</span>
    </div>
    <p class="payment-hint">Select your preferred payment channel and complete your monthly waste service fee instantly.</p>

    <div class="method-grid">
      <div class="method-item">
        <strong>Mobile Money</strong>
        <span>M-Pesa, TigoPesa, Airtel Money</span>
      </div>
      <div class="method-item">
        <strong>Bank Account</strong>
        <span>Local bank transfer or card channel</span>
      </div>
    </div>

    <form method="POST" action="pay.php" class="form-panel">
      <div class="form-title">Payment Method</div>
      <select id="payment_method" name="payment_method" required>
        <option value="">Select Payment Method</option>
        <option value="mobile_money">Mobile Money (M-Pesa, TigoPesa, Airtel Money)</option>
        <option value="bank_account">Bank Account (Visa/MasterCard)</option>
      </select>

      <div class="form-title">Phone / Account Number</div>
      <input
        type="text"
        id="payment_number"
        name="payment_number"
        placeholder="Phone number or bank account"
        required
      >

      <div class="pay-amount">
        <span class="label">Monthly Service Fee</span>
        <span class="value">TZS 1,000</span>
      </div>

      <input type="hidden" name="amount" value="1000">

      <button type="submit" class="pay-btn">Pay Now</button>
    </form>
  </div>

  <!-- MAP -->
  <div class="card">
    <h3>🗺 Nearby Bins</h3>
    <div id="map"></div>
  </div>

</div>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-section">
      <h3>♻ Smart Waste</h3>
      <p>Clean city for better living.</p>
    </div>

    <div class="footer-section">
      <h4>Citizen Panel</h4>
      <p>Report issues easily</p>
      <p>Track bin status</p>
    </div>

    <div class="footer-section">
      <h4>Status</h4>
      <p style="color:#00ffcc;">● System Online</p>
    </div>
  </div>

  <div class="footer-bottom">
    © <?= date("Y") ?> Smart Waste System Tanzania 🇹🇿
  </div>
</footer>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
// MAP (Simple + Satellite)
var map = L.map('map').setView([-8.9, 33.45], 13);

var normalLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap contributors'
});

var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
  maxZoom: 19,
  attribution: 'Tiles &copy; Esri'
});

normalLayer.addTo(map);

L.control.layers(
  {
    "Simple Map": normalLayer,
    "Satellite Map": satelliteLayer
  },
  {},
  { collapsed: false }
).addTo(map);

// LOAD BINS FROM DB
var bins = <?php echo json_encode(mysqli_fetch_all($bins, MYSQLI_ASSOC)); ?>;

var markerGroup = L.featureGroup().addTo(map);
bins.forEach(function(bin){
  var status = String(bin.status || '').toLowerCase();
  // Rangi ziendane na admin dashboard: Full=red, Medium=orange, Empty=green
  var color = (status === "full") ? "#dc2626" : (status === "medium" ? "#f59e0b" : "#16a34a");

  var marker = L.circleMarker([bin.latitude, bin.longitude], {
    radius: 8,
    fillColor: color,
    color: "#000",
    weight: 1,
    fillOpacity: 0.8
  }).bindPopup(
    "Bin: " + bin.bin_id + "<br>Location: " + bin.location + "<br>Status: " + bin.status
  );

  markerGroup.addLayer(marker);
});

if (markerGroup.getLayers().length > 0) {
  map.fitBounds(markerGroup.getBounds().pad(0.2));
}

// Auto-fill location by selected bin
var binSelect = document.getElementById('bin_id');
var locationInput = document.getElementById('location');
if (binSelect && locationInput) {
  binSelect.addEventListener('change', function(){
    var selected = this.options[this.selectedIndex];
    locationInput.value = selected ? (selected.getAttribute('data-location') || '') : '';
  });
}

// Dynamic payment input label
var methodSelect = document.getElementById('payment_method');
var paymentNumberInput = document.getElementById('payment_number');
if (methodSelect && paymentNumberInput) {
  methodSelect.addEventListener('change', function(){
    if (this.value === 'bank_account') {
      paymentNumberInput.placeholder = 'Enter bank account number';
    } else if (this.value === 'mobile_money') {
      paymentNumberInput.placeholder = 'Enter phone number';
    } else {
      paymentNumberInput.placeholder = 'Phone number or bank account';
    }
  });
}
</script>

</body>
</html>