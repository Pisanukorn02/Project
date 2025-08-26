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
    $total_price    = floatval($_POST['total_price'] ?? 0);

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
        } else {
            die("ไม่สามารถอัปโหลดสลิปได้");
        }
    } else {
        die("กรุณาอัปโหลดสลิป");
    }

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

    // ดึงราคาของ service จากฐานข้อมูล
    $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
    $types = str_repeat('i', count($service_ids));
    $stmt_prices = $conn->prepare("SELECT service_id, price FROM services WHERE service_id IN ($placeholders)");
    $stmt_prices->bind_param($types, ...$service_ids);
    $stmt_prices->execute();
    $result_prices = $stmt_prices->get_result();

    $servicePrices = [];
    while ($row = $result_prices->fetch_assoc()) {
        $servicePrices[$row['service_id']] = $row['price'];
    }
    $stmt_prices->close();

    // คำนวณ total_price อีกครั้ง (เพื่อความแม่นยำ)
    $total_price = 0;
    foreach ($service_ids as $i => $sid) {
        $qty = intval($quantities[$i] ?? 1);
        $price = $servicePrices[$sid] ?? 0;
        $total_price += $price * $qty;
    }

    // Insert booking พร้อม total_price
    $stmt = $conn->prepare("INSERT INTO bookings 
        (user_id, shop_id, customer_name, customer_phone, booking_date, booking_time, address, location_lat, location_lng, payment_slip, total_price, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

    $stmt->bind_param("iissssssssd",
        $user_id, $shop_id, $customer_name, $customer_phone,
        $booking_date, $booking_time, $address, $location_lat, $location_lng,
        $slip_path, $total_price
    );
    $stmt->execute();
    $booking_id = $conn->insert_id;
    $stmt->close();

    // Insert รายการบริการลง booking_details
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

    echo "<script>alert('จองบริการทั้งหมดเรียบร้อยแล้ว'); window.location.href='user_bookings.php';</script>";

} else {
    echo "ไม่อนุญาตให้เข้าถึงโดยตรง";
}
?>
