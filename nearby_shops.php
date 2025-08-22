<?php
include 'config.php';

// ดึงข้อมูลร้านค้าทั้งหมดที่มีพิกัด
$sql = "SELECT shop_id, shop_name, latitude, longitude, address FROM shops WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
$result = $conn->query($sql);

$shops = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shops[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ร้านค้าใกล้เคียงบนแผนที่</title>
    <style>
        #map {
            height: 600px;
            width: 100%;
        }
    </style>
</head>
<body>

<h2>แผนที่ร้านค้าใกล้เคียง</h2>
<div id="map"></div>

<script>
// ร้านค้า array ที่มาจาก PHP
const shops = <?php echo json_encode($shops); ?>;

function initMap() {
    // กำหนดพิกัดเริ่มต้นแผนที่ (ใช้พิกัดร้านแรก หรือพิกัดกลาง ๆ)
    let centerLatLng = { lat: 16.8, lng: 100.3 };
    if (shops.length > 0) {
        centerLatLng.lat = parseFloat(shops[0].latitude);
        centerLatLng.lng = parseFloat(shops[0].longitude);
    }

    // สร้างแผนที่
    const map = new google.maps.Map(document.getElementById('map'), {
        zoom: 12,
        center: centerLatLng
    });

    // สร้าง Marker สำหรับแต่ละร้าน
    shops.forEach(shop => {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(shop.latitude), lng: parseFloat(shop.longitude) },
            map: map,
            title: shop.shop_name
        });

        // สร้าง info window แสดงข้อมูลร้าน
        const infowindow = new google.maps.InfoWindow({
            content: `<strong>ชื่อร้าน :</strong>${shop.shop_name}<br>
                    <strong>ที่อยู่ :</strong> ${shop.address}<br>
                    <strong>จังหวัด :</strong> ${shop.province}<br>
                    <strong>โทร :</strong> ${shop.phone}`
        });

        // แสดง info window เมื่อคลิก marker
        marker.addListener('click', () => {
            infowindow.open(map, marker);
        });
    });
}
</script>

<!-- โหลด Google Maps API แบบไม่ต้องใช้ API key (อาจจำกัดบางฟีเจอร์) -->
<script async defer
    src="https://maps.googleapis.com/maps/api/js?callback=initMap">
</script>

</body>
</html>
