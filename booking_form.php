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

// ตรวจสอบว่าบริการทั้งหมดมาจากร้านเดียวกันไหม (เพื่อกำหนดพิกัดร้านเดียวกัน)
$shop_ids = array_unique(array_column($services, 'shop_id'));
if (count($shop_ids) > 1) {
    echo "บริการที่เลือกมาจากร้านหลายร้าน ไม่รองรับการจองพร้อมกัน";
    exit();
}
$shop = $services[0]; // เอาข้อมูลร้านจากบริการแรก (สมมุติเดียวกันหมด)

$total_price = 0;
foreach ($services as $s) {
    $total_price += $s['price'];
}
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
        <li><?= $i ?>. <?= htmlspecialchars($s['service_name']) ?> - <?= number_format($s['price'], 2) ?> บาท</li>
    <?php $i++; endforeach; ?>
    </ul>
    <p><strong>รวมราคา: <?= number_format($total_price, 2) ?> บาท</strong></p>
    <p><strong>ร้าน: <?= htmlspecialchars($shop['shop_name']) ?></strong></p>

    <form action="booking_process.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="shop_id" value="<?= $shop['shop_id'] ?>">
        
        <?php foreach ($services as $s): ?>
            <input type="hidden" name="service_id[]" value="<?= $s['service_id'] ?>">
        <?php endforeach; ?>

        <label for="customer_name">ชื่อลูกค้า</label>
        <input type="text" name="customer_name" id="customer_name" required>

        <label for="customer_phone">เบอร์โทร</label>
        <input type="text" name="customer_phone" id="customer_phone" required>

        <label for="booking_date">วันที่ต้องการใช้บริการ</label>
        <input type="date" name="booking_date" id="booking_date" required>

        <label for="booking_time">เวลาที่ต้องการใช้บริการ</label>
        <input type="time" name="booking_time" id="booking_time" required>

        <label for="address">ที่อยู่ (รายละเอียดสถานที่ติดตั้ง/ล้างแอร์)</label>
        <textarea name="address" id="address" rows="3" required></textarea>

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
    var map = L.map('map').setView([shopLat, shopLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var shopMarker = L.marker([shopLat, shopLng]).addTo(map)
        .bindPopup("ร้าน <?= htmlspecialchars($shop['shop_name']) ?>")
        .openPopup();

    // กำหนดตำแหน่งเริ่มต้นหมุดผู้ใช้เป็นตำแหน่งร้าน
    var defaultLatLng = L.latLng(shopLat, shopLng);
    var userMarker = L.marker(defaultLatLng, { draggable: true }).addTo(map)
        .bindPopup("ตำแหน่งคุณ (ตำแหน่งที่ต้องการใช้บริการ)").openPopup();

    // ตั้งค่า input hidden lat lng เป็นค่าเริ่มต้น
    document.getElementById('lat').value = defaultLatLng.lat;
    document.getElementById('lng').value = defaultLatLng.lng;

    // ฟังก์ชันคำนวณระยะทางและแสดงผล พร้อมเช็คระยะทางเกิน 30 กม.
    function updateDistance(latlng) {
        const distance = getDistanceFromLatLonInKm(shopLat, shopLng, latlng.lat, latlng.lng);
        document.getElementById('distance_info').textContent = `ระยะทางจากร้าน: ${distance.toFixed(2)} กิโลเมตร (พื้นที่ให้บริการ: สูงสุด 30 กม.)`;

        if (distance > 30) {
            alert("ขออภัย พื้นที่อยู่นอกเขตให้บริการของร้าน (เกิน 30 กม.)");
            // รีเซ็ตตำแหน่งหมุดกลับไปที่ร้าน
            userMarker.setLatLng(defaultLatLng);
            document.getElementById('lat').value = defaultLatLng.lat;
            document.getElementById('lng').value = defaultLatLng.lng;
            document.getElementById('distance_info').textContent = "คลิกบนแผนที่เพื่อเลือกตำแหน่งและดูระยะทางจากร้าน";
        } else {
            // กำหนดค่าพิกัดใหม่ใน hidden input
            document.getElementById('lat').value = latlng.lat;
            document.getElementById('lng').value = latlng.lng;
        }
    }

    // แสดงระยะทางตั้งแต่โหลดหน้า
    updateDistance(defaultLatLng);

    // เมื่อหมุดถูกลากเสร็จ ให้คำนวณระยะทางใหม่
    userMarker.on('dragend', function(e) {
        updateDistance(e.target.getLatLng());
    });

    // ถ้าต้องการให้คลิกบนแผนที่ก็เปลี่ยนตำแหน่งหมุดได้
    map.on('click', function(e) {
        userMarker.setLatLng(e.latlng);
        updateDistance(e.latlng);
        userMarker.openPopup();
    });

    // ฟังก์ชันคำนวณระยะทางระหว่าง 2 จุด (กิโลเมตร)
    function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
        const R = 6371; // รัศมีโลก กม.
        const dLat = deg2rad(lat2 - lat1);
        const dLon = deg2rad(lon2 - lon1);
        const a = Math.sin(dLat/2)*Math.sin(dLat/2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
            Math.sin(dLon/2)*Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    function deg2rad(deg) {
        return deg * (Math.PI/180);
    }
</script>

</body>
</html>
