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

        $stmt = $conn->prepare("SELECT s.*, sh.shop_name, sh.latitude AS shop_lat, sh.longitude AS shop_lng 
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
    } else {
        echo "ไม่มีบริการในตะกร้า";
        exit();
    }
} else {
    if (!isset($_GET['service_id'])) {
        echo "ไม่พบบริการที่เลือก";
        exit();
    }

    $service_id = intval($_GET['service_id']);
    $stmt = $conn->prepare("SELECT s.*, sh.shop_name, sh.latitude AS shop_lat, sh.longitude AS shop_lng 
                            FROM services s 
                            JOIN shops sh ON s.shop_id = sh.shop_id 
                            WHERE s.service_id = ? AND s.is_approved = 1");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
    $stmt->close();

    if (!$service) {
        echo "บริการไม่ถูกต้องหรือถูกลบ";
        exit();
    }
    $services = [$service];
}

// ตรวจสอบว่าบริการทั้งหมดมาจากร้านเดียวกันไหม
$shop_ids = array_unique(array_column($services, 'shop_id'));
if (count($shop_ids) > 1) {
    echo "บริการที่เลือกมาจากร้านหลายร้าน ไม่รองรับการจองพร้อมกัน";
    exit();
}
$shop = $services[0]; 

// แม็ปจำนวนจากตะกร้า
$cartQuantities = [];
foreach ($cart as $c) {
    $cartQuantities[$c['service_id']] = $c['quantity'] ?? 1;
}

// คำนวณราคาทั้งหมดตามจำนวน
$total_price = 0;
foreach ($services as &$s) {
    $s['quantity'] = $cartQuantities[$s['service_id']] ?? 1;
    $total_price += $s['price'] * $s['quantity'];
}
unset($s);

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
<title>จองบริการชุดเดียว</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<style>
body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
.form-container { max-width: 700px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
label { display: block; margin-bottom: 5px; font-weight: bold; }
input, textarea, select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; }
input[type="submit"] { background: #28a745; color: white; cursor: pointer; }
input[type="submit"]:hover { background: #218838; }
#map { height: 350px; margin-bottom: 15px; }
ul.service-list { margin-bottom: 15px; }
ul.service-list li { margin-bottom: 5px; }
#distance_info { font-weight: bold; margin-bottom: 15px; }
</style>
</head>
<body>

<div class="form-container">
<h2>จองบริการชุดเดียว</h2>

<h3>รายการบริการที่เลือก</h3>
<ul class="service-list">
<?php $i = 1; foreach ($services as $s): ?>
    <li>
        <?= $i ?>. <?= htmlspecialchars($s['service_name']) ?> - 
        <?= number_format($s['price'],2) ?> x <?= $s['quantity'] ?> = <?= number_format($s['price']*$s['quantity'],2) ?> บาท
    </li>
<?php $i++; endforeach; ?>
</ul>
<p><strong>รวมราคา: <?= number_format($total_price,2) ?> บาท</strong></p>
<p><strong>ร้าน: <?= htmlspecialchars($shop['shop_name']) ?></strong></p>

<form action="booking_process.php" method="POST" enctype="multipart/form-data" id="bookingForm">
<input type="hidden" name="shop_id" value="<?= $shop['shop_id'] ?>">
<input type="hidden" name="total_price" value="<?= $total_price ?>">

<?php foreach ($services as $s): ?>
<input type="hidden" name="service_id[]" value="<?= $s['service_id'] ?>">
<input type="hidden" name="quantity[]" value="<?= $s['quantity'] ?>">
<?php endforeach; ?>

<label>ชื่อลูกค้า</label>
<input type="text" name="customer_name" value="<?= htmlspecialchars($user['name']) ?>" readonly>

<label>เบอร์โทร</label>
<input type="text" name="customer_phone" value="<?= htmlspecialchars($user['phone']) ?>" readonly>

<label for="booking_date">วันที่ต้องการใช้บริการ</label>
<input type="date" name="booking_date" id="booking_date" required>

<label for="booking_time">เวลาที่ต้องการใช้บริการ</label>
<select id="booking_time" name="booking_time" required></select>

<script>
const bookingTimeSelect = document.getElementById('booking_time');
for (let h = 8; h <= 17; h++) {
    for (let m = 0; m < 60; m += 5) {
        const option = document.createElement('option');
        option.value = `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}`;
        option.textContent = option.value;
        bookingTimeSelect.appendChild(option);
    }
}
</script>

<label>ที่อยู่</label>
<textarea name="address" rows="3" readonly><?= htmlspecialchars($user['address']) ?></textarea>

<label>ตำแหน่งสถานที่ให้บริการ (ลากหมุดหรือคลิกบนแผนที่)</label>
<div id="map"></div>
<div id="distance_info">คลิกบนแผนที่เพื่อเลือกตำแหน่งและดูระยะทางจากร้าน</div>
<input type="hidden" name="location_lat" id="lat" required>
<input type="hidden" name="location_lng" id="lng" required>

<label for="payment_slip">รูปสถานที่ที่ต้องการใช้บริการ</label>
<input type="file" name="payment_slip" id="payment_slip" accept="image/*" required>

<input type="submit" value="ยืนยันการจอง">
</form>
</div>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
const shopLat = <?= $shop['shop_lat'] ?>;
const shopLng = <?= $shop['shop_lng'] ?>;
const userLat = <?= $userLat ?>;
const userLng = <?= $userLng ?>;

var map = L.map('map').setView([userLat, userLng], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var shopMarker = L.marker([shopLat, shopLng]).addTo(map)
    .bindPopup("ร้าน <?= htmlspecialchars($shop['shop_name']) ?>").openPopup();

var userMarker = L.marker([userLat, userLng], { draggable: true }).addTo(map)
    .bindPopup("ตำแหน่งคุณ (ตำแหน่งที่ต้องการใช้บริการ)").openPopup();

function updateDistance(latlng) {
    const distance = getDistanceFromLatLonInKm(shopLat, shopLng, latlng.lat, latlng.lng);
    document.getElementById('distance_info').textContent = `ระยะทางจากร้าน: ${distance.toFixed(2)} กม. (สูงสุด 30 กม.)`;

    if(distance > 30) {
        alert("ขออภัย พื้นที่อยู่นอกเขตให้บริการของร้าน (เกิน 30 กม.)");
        userMarker.setLatLng([shopLat, shopLng]);
        document.getElementById('lat').value = shopLat;
        document.getElementById('lng').value = shopLng;
        document.getElementById('distance_info').textContent = "คลิกบนแผนที่เพื่อเลือกตำแหน่งและดูระยะทางจากร้าน";
    } else {
        document.getElementById('lat').value = latlng.lat;
        document.getElementById('lng').value = latlng.lng;
    }
}

function getDistanceFromLatLonInKm(lat1,lon1,lat2,lon2){
    const R=6371;
    const dLat=deg2rad(lat2-lat1);
    const dLon=deg2rad(lon2-lon1);
    const a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(deg2rad(lat1))*Math.cos(deg2rad(lat2))*Math.sin(dLon/2)*Math.sin(dLon/2);
    const c=2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
    return R*c;
}
function deg2rad(deg){ return deg*(Math.PI/180); }

document.getElementById('lat').value = userLat;
document.getElementById('lng').value = userLng;

// ลากหมุด
userMarker.on('dragend', function(e) { updateDistance(e.target.getLatLng()); });

// คลิกเปลี่ยนตำแหน่ง
map.on('click', function(e) {
    userMarker.setLatLng(e.latlng);
    updateDistance(e.latlng);
    userMarker.openPopup();
});

// ตรวจสอบตำแหน่งเริ่มต้น
const initialDistance = getDistanceFromLatLonInKm(shopLat, shopLng, userLat, userLng);
if(initialDistance > 30){
    alert("ตำแหน่งเริ่มต้นของคุณอยู่นอกพื้นที่ให้บริการของร้าน (เกิน 30 กม.)");
    userMarker.setLatLng([shopLat, shopLng]);
    document.getElementById('lat').value = shopLat;
    document.getElementById('lng').value = shopLng;
    document.getElementById('distance_info').textContent = "คลิกบนแผนที่เพื่อเลือกตำแหน่งและดูระยะทางจากร้าน";
} else {
    updateDistance(userMarker.getLatLng());
}
</script>

</body>
</html>
