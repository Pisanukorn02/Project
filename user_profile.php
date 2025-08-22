<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้ พร้อม latitude longitude
$stmt = $conn->prepare("SELECT name, phone, address, latitude, longitude FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $phone, $address, $latitude, $longitude);
$stmt->fetch();
$stmt->close();

// ดึงรีวิวของผู้ใช้
$reviews = [];
$stmt = $conn->prepare("
    SELECT 
        r.rating, r.comment, r.created_at, 
        s.service_name, sh.shop_name
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN services s ON b.service_id = s.service_id
    JOIN shops sh ON s.shop_id = sh.shop_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();

// ดึงคำสั่งซื้อ (booking) ของผู้ใช้
$bookings = [];
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_date, b.booking_time, b.address, b.status, s.service_name, sh.shop_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN shops sh ON b.shop_id = sh.shop_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>บัญชีของฉัน</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
        #map { height: 300px; margin-top: 10px; margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-top: 15px; }
        input, textarea, button { width: 100%; padding: 8px; margin-top: 5px; }
        button { background-color: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>

<h2>บัญชีของฉัน</h2>

<form action="user_update.php" method="POST">
    <label>ชื่อ</label>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

    <label>เบอร์โทร</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" required>

    <label>ที่อยู่</label>
    <textarea name="address" rows="3"><?= htmlspecialchars($address) ?></textarea>

    <label>ตำแหน่งที่ตั้ง (ลากหมุดเพื่อเปลี่ยนตำแหน่ง)</label>
    <div id="map"></div>
    <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($latitude) ?>">
    <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($longitude) ?>">

    <button type="submit">บันทึกข้อมูล</button>
</form>

<h2>รีวิวของฉัน</h2>
<?php if (count($reviews) > 0): ?>
    <ul>
    <?php foreach ($reviews as $r): ?>
        <li>
            บริการ: <?= htmlspecialchars($r['service_name']) ?> ที่ร้าน <?= htmlspecialchars($r['shop_name']) ?><br>
            คะแนน: <?= $r['rating'] ?>/5<br>
            ความคิดเห็น: <?= nl2br(htmlspecialchars($r['comment'])) ?><br>
            วันที่: <?= date('d/m/Y', strtotime($r['created_at'])) ?>
        </li>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>ยังไม่มีรีวิวของคุณ</p>
<?php endif; ?>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
    var lat = <?= $latitude ? $latitude : 13.7563 ?>; // default กรุงเทพฯ ถ้าไม่มี
    var lng = <?= $longitude ? $longitude : 100.5018 ?>;

    var map = L.map('map').setView([lat, lng], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var marker = L.marker([lat, lng], {draggable: true}).addTo(map);

// เมื่อผู้ใช้คลิกบนแผนที่ ให้ย้าย marker ไปตำแหน่งที่คลิก
map.on('click', function(e) {
    var newLatLng = e.latlng;
    marker.setLatLng(newLatLng); // ย้ายหมุด
    document.getElementById('latitude').value = newLatLng.lat;
    document.getElementById('longitude').value = newLatLng.lng;
});

// หากผู้ใช้ยังลากหมุดด้วย ก็อัปเดตค่าด้วยเช่นกัน
marker.on('dragend', function(e) {
    var pos = e.target.getLatLng();
    document.getElementById('latitude').value = pos.lat;
    document.getElementById('longitude').value = pos.lng;
});

</script>

</body>
</html>
