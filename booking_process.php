<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'])) {

    $user_id        = $_SESSION['user_id'];
    $service_ids    = $_POST['service_id'] ?? [];
    $quantities     = $_POST['quantity'] ?? [];
    $shop_id        = intval($_POST['shop_id'] ?? 0);
    $customer_name  = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $booking_date   = $_POST['booking_date'] ?? '';
    $booking_time   = $_POST['booking_time'] ?? '';
    $address        = trim($_POST['address'] ?? '');
    $location_lat   = $_POST['location_lat'] ?? '';
    $location_lng   = $_POST['location_lng'] ?? '';

    if (!$service_ids || !$customer_name || !$customer_phone || !$booking_date || !$booking_time || !$address || !$location_lat || !$location_lng) {
        die("ข้อมูลไม่ครบถ้วน");
    }

    // อัปโหลดสลิป
    $slip_path = null;
    if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/slips/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('slip_', true) . '.' . $ext;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_path)) {
            $slip_path = $target_path;
        } else { die("ไม่สามารถอัปโหลดสลิปได้"); }
    } else { die("กรุณาอัปโหลดสลิป"); }

    // ตรวจสอบเวลาซ้อนกัน (2 ชั่วโมง)
    $stmt_check = $conn->prepare("SELECT booking_time FROM bookings 
        WHERE shop_id = ? AND booking_date = ? AND status IN ('pending','accepted')");
    $stmt_check->bind_param("is", $shop_id, $booking_date);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    $new_time_ts = strtotime("$booking_date $booking_time");
    while ($row = $result_check->fetch_assoc()) {
        $existing_time_ts = strtotime("$booking_date {$row['booking_time']}");
        if (abs($existing_time_ts - $new_time_ts)/3600 < 2) {
            die("<script>alert('ขออภัย เวลานี้ถูกจองแล้ว กรุณาเลือกเวลาอื่น'); window.history.back();</script>");
        }
    }
    $stmt_check->close();

    // ดึงราคาของ service และข้อมูลร้าน
    $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
    $types = str_repeat('i', count($service_ids));
    $stmt_prices = $conn->prepare("SELECT s.service_id, s.price, sh.latitude, sh.longitude, sh.service_radius, sh.extra_price_per_km
                                   FROM services s 
                                   JOIN shops sh ON s.shop_id = sh.shop_id
                                   WHERE s.service_id IN ($placeholders)");
    $stmt_prices->bind_param($types, ...$service_ids);
    $stmt_prices->execute();
    $result_prices = $stmt_prices->get_result();

    $servicePrices = [];
    $shopInfo = null;
    while ($row = $result_prices->fetch_assoc()) {
        $servicePrices[$row['service_id']] = $row['price'];
        $shopInfo = [
            'lat' => $row['latitude'],
            'lng' => $row['longitude'],
            'radius' => $row['service_radius'] ?: 30,
            'extra_price' => $row['extra_price_per_km'] ?: 50
        ];
    }
    $stmt_prices->close();

    if (!$shopInfo) die("ร้านค้าไม่ถูกต้อง");

    // ฟังก์ชันคำนวณระยะทาง
    function getDistance($lat1,$lon1,$lat2,$lon2){
        $R = 6371;
        $dLat = deg2rad($lat2-$lat1);
        $dLon = deg2rad($lon2-$lon1);
        $a = sin($dLat/2)*sin($dLat/2) + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)*sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R*$c;
    }

    // คำนวณ total_price + extra_fee
    $total_price_calc = 0;
    foreach ($service_ids as $i => $sid) {
        $qty = intval($quantities[$i] ?? 1);
        $price = $servicePrices[$sid] ?? 0;
        $total_price_calc += $price * $qty;
    }

    $distance = getDistance($shopInfo['lat'], $shopInfo['lng'], floatval($location_lat), floatval($location_lng));
    $extra_fee = 0;
    if ($distance > $shopInfo['radius']) {
        $extra_fee = ($distance - $shopInfo['radius']) * $shopInfo['extra_price'];
    }

    $total_price_final = $total_price_calc + $extra_fee;

    // Insert booking
    $sql = "INSERT INTO bookings 
(user_id, shop_id, service_id, quantity, booking_date, booking_time, 
 location_lat, location_lng, address, customer_name, customer_phone, 
 payment_slip, total_price, extra_fee) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param("iiiissddssssdd", 
    $user_id, $shop_id, $service_id, $quantity,
    $booking_date, $booking_time,
    $location_lat, $location_lng, $address,
    $customer_name, $customer_phone, $slip_path,
    $total_price_final, $extra_fee
);
    $stmt->execute();
    $booking_id = $conn->insert_id;
    $stmt->close();

    // Insert booking_details
    $stmt = $conn->prepare("INSERT INTO booking_details (booking_id, service_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($service_ids as $i => $sid) {
        $qty = intval($quantities[$i] ?? 1);
        $price = $servicePrices[$sid] ?? 0;
        $stmt->bind_param("iiid", $booking_id, $sid, $qty, $price);
        $stmt->execute();
    }
    $stmt->close();

    // ล้างตะกร้า
    unset($_SESSION['cart']);

    echo "<script>
        alert('จองบริการเรียบร้อยแล้ว\nระยะทางจากร้าน: ".number_format($distance,2)." กม.\nค่าบริการเพิ่มเติม: ".number_format($extra_fee,2)." บาท\nรวมทั้งหมด: ".number_format($total_price_final,2)." บาท');
        window.location.href='user_bookings.php';
    </script>";

} else {
    echo "ไม่อนุญาตให้เข้าถึงโดยตรง";
}
?>
