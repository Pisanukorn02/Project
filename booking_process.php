<?php
session_start();
include 'config.php';

// ====== ส่วนจัดการการเปลี่ยนสถานะ (เหมือน booking_action.php) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['action']) && !isset($_POST['service_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $action     = $_POST['action'];

    $allowed_actions = ['approve', 'reject', 'complete'];
    if (!in_array($action, $allowed_actions)) {
        die("คำสั่งไม่ถูกต้อง");
    }

    switch ($action) {
        case 'approve': $status = 'accepted'; break;
        case 'reject': $status = 'rejected'; break;
        case 'complete': $status = 'completed'; break;
    }

    $stmt = $conn->prepare("UPDATE bookings SET `status` = ? WHERE booking_id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("si", $status, $booking_id);
    if ($stmt->execute()) {
        header("Location: shop_board.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาด: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
    exit();
}

// ====== ส่วนจองหลายบริการพร้อมกัน (เหมือน booking_process.php เวอร์ชันปรับ) ======
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'])) {
    $user_id        = $_SESSION['user_id'];
    $service_ids    = $_POST['service_id'] ?? [];
    $shop_id        = intval($_POST['shop_id'] ?? 0);
    $customer_name  = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $booking_date   = $_POST['booking_date'] ?? '';
    $booking_time   = $_POST['booking_time'] ?? '';
    $address        = trim($_POST['address'] ?? '');
    $location_lat   = $_POST['location_lat'] ?? '';
    $location_lng   = $_POST['location_lng'] ?? '';

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!$service_ids || !$customer_name || !$customer_phone || !$booking_date || !$booking_time || !$address || !$location_lat || !$location_lng) {
        die("ข้อมูลไม่ครบถ้วน");
    }

    // อัปโหลดสลิปเพียงไฟล์เดียว ใช้กับทุกการจอง
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

    // เตรียม insert
    $stmt = $conn->prepare("INSERT INTO bookings 
    (user_id, service_id, shop_id, customer_name, customer_phone, booking_date, booking_time, address, location_lat, location_lng, payment_slip, status, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");


    $errors = [];
    foreach ($service_ids as $service_id) {
        $service_id = intval($service_id);
        $stmt->bind_param("iiissssssss",
            $user_id, $service_id, $shop_id, $customer_name, $customer_phone,
            $booking_date, $booking_time, $address, $location_lat, $location_lng,
            $slip_path
        );
        if (!$stmt->execute()) {
            $errors[] = "จองบริการรหัส {$service_id} ไม่สำเร็จ: " . $stmt->error;
        }
    }

    $stmt->close();
    $conn->close();

    if ($errors) {
        echo "<h3>พบข้อผิดพลาด:</h3><ul>";
        foreach ($errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>";
        echo "</ul><a href='javascript:history.back()'>กลับ</a>";
    } else {
        unset($_SESSION['cart']);
        echo "<script>alert('จองบริการทั้งหมดเรียบร้อยแล้ว'); window.location.href='user_bookings.php';</script>";
    }
} else {
    echo "ไม่อนุญาตให้เข้าถึงโดยตรง";
}
?>
