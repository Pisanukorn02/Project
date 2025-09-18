<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

$from_cart = isset($_GET['from_cart']) && $_GET['from_cart'] == '1';
$cart = $_SESSION['cart'] ?? [];

if ($from_cart) {
    $services = [];
    $service_ids = array_column($cart, 'service_id');
    if (count($service_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
        $types = str_repeat('i', count($service_ids));
        $stmt = $conn->prepare("SELECT s.*, sh.shop_name, sh.latitude AS shop_lat, sh.longitude AS shop_lng,
                                sh.service_radius, sh.extra_price_per_km
                                FROM services s 
                                JOIN shops sh ON s.shop_id = sh.shop_id 
                                WHERE s.service_id IN ($placeholders) AND s.is_approved = 1");
        $stmt->bind_param($types, ...$service_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        $stmt->close();
    } else { echo "ไม่มีบริการในตะกร้า"; exit(); }
} else {
    if (!isset($_GET['service_id'])) { echo "ไม่พบบริการที่เลือก"; exit(); }
    $service_id = intval($_GET['service_id']);
    $stmt = $conn->prepare("SELECT s.*, sh.shop_name, sh.latitude AS shop_lat, sh.longitude AS shop_lng,
                            sh.service_radius, sh.extra_price_per_km
                            FROM services s 
                            JOIN shops sh ON s.shop_id = sh.shop_id 
                            WHERE s.service_id = ? AND s.is_approved = 1");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
    $stmt->close();
    if (!$service) { echo "บริการไม่ถูกต้องหรือถูกลบ"; exit(); }
    $services = [$service];
}

// ตรวจสอบร้านเดียว
$shop_ids = array_unique(array_column($services, 'shop_id'));
if (count($shop_ids) > 1) { echo "บริการมาจากหลายร้าน"; exit(); }
$shop = $services[0]; 

// จำนวน
$cartQuantities = [];
foreach ($cart as $c) { $cartQuantities[$c['service_id']] = $c['quantity'] ?? 1; }

// ราคาพื้นฐาน
$total_price = 0;
foreach ($services as &$s) {
    $s['quantity'] = $cartQuantities[$s['service_id']] ?? 1;
    $total_price += $s['price'] * $s['quantity'];
}
unset($s);

// ลูกค้า
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, phone, address, latitude, longitude FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$userLat = $user['latitude'] ?: 13.7563;
$userLng = $user['longitude'] ?: 100.5018;
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จองบริการ</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<style>
body { font-family: sans-serif; background:#f4f4f4; padding:20px; }
.form-container { max-width:700px; margin:auto; background:white; padding:30px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
label{display:block;margin-bottom:5px;font-weight:bold;}
input,textarea,select{width:100%;padding:10px;margin-bottom:15px;border:1px solid #ccc;border-radius:5px;}
input[type=submit]{background:#28a745;color:white;cursor:pointer;}
input[type=submit]:hover{background:#218838;}
#map{height:350px;margin-bottom:15px;}
ul.service-list{margin-bottom:15px;}
ul.service-list li{margin-bottom:5px;}
#distance_info{font-weight:bold;margin-bottom:15px;}
#extra_fee_info{font-weight:bold;margin-bottom:15px;color:red;}
</style>
</head>
<body>
<div class="form-container">
<h2>จองบริการ</h2>
<h3>รายการบริการ</h3>
<ul class="service-list">
<?php $i=1; foreach($services as $s): ?>
<li><?= $i ?>. <?= htmlspecialchars($s['service_name']) ?> - <?= number_format($s['price'],2) ?> x <?= $s['quantity'] ?> = <?= number_format($s['price']*$s['quantity'],2) ?> บาท</li>
<?php $i++; endforeach; ?>

<li><div id="extra_fee_info">ค่าบริการเพิ่มเติม: 0 บาท</div></li>
</ul>

<p><strong>ราคาพื้นฐานรวมค่าบริการเพิ่มเติม: <span id="total_price_display"><?= number_format($total_price,2) ?></span> บาท</strong></p>

<p><strong>ร้าน: <?= htmlspecialchars($shop['shop_name']) ?></strong></p>

<form action="booking_process.php" method="POST" enctype="multipart/form-data" id="bookingForm">
<input type="hidden" name="shop_id" value="<?= $shop['shop_id'] ?>">
<input type="hidden" name="total_price" id="total_price" value="<?= $total_price ?>">
<?php foreach($services as $s): ?>
<input type="hidden" name="service_id[]" value="<?= $s['service_id'] ?>">
<input type="hidden" name="quantity[]" value="<?= $s['quantity'] ?>">
<?php endforeach; ?>

<label>ชื่อลูกค้า</label>
<input type="text" name="customer_name" value="<?= htmlspecialchars($user['name']) ?>" readonly>
<label>เบอร์โทร</label>
<input type="text" name="customer_phone" value="<?= htmlspecialchars($user['phone']) ?>" readonly>

<label for="booking_date">วันที่</label>
<input type="date" name="booking_date" id="booking_date" required min="<?= date('Y-m-d') ?>">
<label for="booking_time">เวลา</label>
<select id="booking_time" name="booking_time" required></select>
<script>
const bookingTimeSelect = document.getElementById('booking_time');
for(let h=8; h<=17; h++){ for(let m=0; m<60; m+=5){
    const option=document.createElement('option');
    option.value=`${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}`;
    option.textContent=option.value;
    bookingTimeSelect.appendChild(option);
}}
</script>

<label>ที่อยู่</label>
<textarea name="address" rows="3" readonly><?= htmlspecialchars($user['address']) ?></textarea>

<label for="payment_slip">รูปสถานที่ที่ต้องการใช้บริการ</label>
<input type="file" name="payment_slip" id="payment_slip" accept="image/*" required>

<label>ตำแหน่งบริการ</label>
<div id="map"></div>
<div id="distance_info">คลิกบนแผนที่เพื่อเลือกตำแหน่ง</div>
<input type="hidden" name="location_lat" id="lat" required>
<input type="hidden" name="location_lng" id="lng" required>
<input type="submit" value="ยืนยันการจอง">
</form>
</div>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
const shopLat = <?= $shop['shop_lat'] ?>;
const shopLng = <?= $shop['shop_lng'] ?>;
const userLat = <?= $userLat ?>;
const userLng = <?= $userLng ?>;
const service_radius = <?= $shop['service_radius'] ?: 30 ?>;
const extra_per_km = <?= $shop['extra_price_per_km'] ?: 50 ?>;
let base_price = <?= $total_price ?>;

var map = L.map('map').setView([userLat,userLng],13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'&copy; OpenStreetMap contributors'
}).addTo(map);

var shopMarker = L.marker([shopLat,shopLng]).addTo(map)
.bindPopup("ร้าน <?= htmlspecialchars($shop['shop_name']) ?>").openPopup();

var userMarker = L.marker([userLat,userLng],{draggable:true}).addTo(map)
.bindPopup("ตำแหน่งคุณ").openPopup();

function deg2rad(deg){return deg*(Math.PI/180);}
function getDistance(lat1,lon1,lat2,lon2){
    const R=6371;
    const dLat=deg2rad(lat2-lat1);
    const dLon=deg2rad(lon2-lon1);
    const a=Math.sin(dLat/2)**2 + Math.cos(deg2rad(lat1))*Math.cos(deg2rad(lat2))*Math.sin(dLon/2)**2;
    return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function updateDistance(latlng){
    const distance = getDistance(shopLat,shopLng,latlng.lat,latlng.lng);
    document.getElementById('lat').value = latlng.lat;
    document.getElementById('lng').value = latlng.lng;
    document.getElementById('distance_info').textContent = `ระยะทางจากร้าน: ${distance.toFixed(2)} กม. (สูงสุด ${service_radius} กม.)`;

    let extra_fee = 0;
    if(distance>service_radius){
        extra_fee = (distance-service_radius)*extra_per_km;
    }
    document.getElementById('extra_fee_info').textContent = `ค่าบริการเพิ่มเติม: ${extra_fee.toFixed(2)} บาท`;
    document.getElementById('total_price').value = (base_price+extra_fee).toFixed(2);
}

userMarker.on('dragend', e=>updateDistance(e.target.getLatLng()));
map.on('click', e=>{userMarker.setLatLng(e.latlng); updateDistance(e.latlng); userMarker.openPopup();});
updateDistance(userMarker.getLatLng());

function updateDistance(latlng){
    const distance = getDistance(shopLat,shopLng,latlng.lat,latlng.lng);
    document.getElementById('lat').value = latlng.lat;
    document.getElementById('lng').value = latlng.lng;
    document.getElementById('distance_info').textContent = `ระยะทางจากร้าน: ${distance.toFixed(2)} กม. (สูงสุด ${service_radius} กม.)`;

    let extra_fee = 0;
    if(distance>service_radius){
        extra_fee = (distance-service_radius)*extra_per_km;
    }
    document.getElementById('extra_fee_info').textContent = `ค่าบริการเพิ่มเติม: ${extra_fee.toFixed(2)} บาท`;

    const total = base_price + extra_fee;
    document.getElementById('total_price').value = total.toFixed(2); // สำหรับส่งไป booking_process.php
    document.getElementById('total_price_display').textContent = total.toFixed(2); // อัปเดตบนหน้าเว็บ
}

</script>
</body>
</html>
