<?php
session_start();
include 'config.php';

// ตรวจสอบการล็อกอินร้านค้า
if (!isset($_SESSION['shop_id'])) {
    header('Location: login.html');
    exit();
}

$shop_id = $_SESSION['shop_id'];

// ดึงข้อมูลร้านค้า
$stmt = $conn->prepare("SELECT shop_name, address, province, latitude, longitude, phone, shop_image, service_radius, extra_price_per_km FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$stmt->bind_result($shop_name, $address, $province, $latitude, $longitude, $phone, $shop_image, $service_radius, $extra_price_per_km);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>แก้ไขข้อมูลร้านค้า</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Kanit', sans-serif; padding: 20px; background: #f0f2f5; }
.container { max-width: 900px; margin: 0 auto; }
.profile-section { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.form-group { margin-bottom: 20px; display: flex; flex-direction: column; }
.form-group label { margin-bottom: 5px; font-weight: 500; }
.form-group input, .form-group textarea { padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-size: 1rem; }
#map { height: 300px; border-radius: 10px; margin-bottom: 20px; }
.btn-primary { padding: 12px 25px; background: #667eea; color: white; border: none; border-radius: 50px; cursor: pointer; font-weight: 600; }
</style>
</head>
<body>

<div class="container">
    <div class="profile-section">
        <h2><i class="fas fa-store"></i> แก้ไขข้อมูลร้านค้า</h2>
        <form action="shop_update.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="shop_id" value="<?= $shop_id ?>">

            <div class="form-group">
                <label>ชื่อร้าน</label>
                <input type="text" name="shop_name" value="<?= htmlspecialchars($shop_name) ?>" required>
            </div>

            <div class="form-group">
                <label>เบอร์โทรศัพท์</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" maxlength="10" pattern="\d{10}" required>
            </div>

            <div class="form-group">
                <label>ที่อยู่</label>
                <textarea name="address" rows="3"><?= htmlspecialchars($address) ?></textarea>
            </div>

            <div class="form-group">
                <label>จังหวัด</label>
                <input type="text" name="province" value="<?= htmlspecialchars($province) ?>">
            </div>

            <div class="form-group">
                <label>ระยะให้บริการพื้นฐาน (กม.)</label>
                <input type="number" step="0.01" name="service_radius" value="<?= htmlspecialchars($service_radius) ?>" required>
            </div>

            <div class="form-group">
                <label>ค่าบริการต่อ กม. เกินระยะพื้นฐาน (บาท)</label>
                <input type="number" step="0.01" name="extra_price_per_km" value="<?= htmlspecialchars($extra_price_per_km) ?>" required>
            </div>

            <div class="form-group">
                <label>รูปภาพร้านค้า (ถ้าไม่เลือกจะไม่เปลี่ยน)</label>
                <input type="file" name="shop_image" accept="image/*">
                <?php if($shop_image && file_exists('uploads/'.$shop_image)): ?>
                    <img src="uploads/<?= htmlspecialchars($shop_image) ?>" alt="รูปร้าน" style="width:150px;margin-top:10px;">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>ตำแหน่งร้าน (ลากหมุดเพื่อเปลี่ยนตำแหน่ง)</label>
                <div id="map"></div>
                <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($latitude) ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($longitude) ?>">
            </div>

            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
var lat = <?= $latitude ?: 13.7563 ?>;
var lng = <?= $longitude ?: 100.5018 ?>;
var map = L.map('map').setView([lat, lng], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var marker = L.marker([lat, lng], {draggable:true}).addTo(map);

function updateInputs(latlng){
    document.getElementById('latitude').value = latlng.lat;
    document.getElementById('longitude').value = latlng.lng;
}

marker.on('dragend', function(e){
    updateInputs(e.target.getLatLng());
});

map.on('click', function(e){
    marker.setLatLng(e.latlng);
    updateInputs(e.latlng);
});
</script>

</body>
</html>
