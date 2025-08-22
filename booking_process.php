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

// ====== ส่วนจองหลายบริการพร้อมกัน ======
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

    // ตรวจสอบเวลาซ้อนกัน (2 ชั่วโมง)
    $can_book = true;
    $stmt_check = $conn->prepare("SELECT booking_time FROM bookings 
        WHERE shop_id = ? AND booking_date = ? AND status IN ('pending','accepted')");
    $stmt_check->bind_param("is", $shop_id, $booking_date);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    $new_time_ts = strtotime("$booking_date $booking_time");

    while ($row = $result_check->fetch_assoc()) {
        $existing_time_ts = strtotime("$booking_date {$row['booking_time']}");
        $diff_hours = abs($existing_time_ts - $new_time_ts) / 3600; // ชั่วโมง
        if ($diff_hours < 2) { // ถ้าน้อยกว่า 2 ชั่วโมง
            $can_book = false;
            break;
        }
    }
    $stmt_check->close();

    if (!$can_book) {
        die("<script>alert('ขออภัย เวลานี้ถูกจองแล้ว กรุณาเลือกเวลาอื่น'); window.history.back();</script>");
    }

    // ถ้าเวลาผ่านแล้วค่อย insert
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
        // ถ้าสำเร็จ ส่งไปหน้า user_bookings.php พร้อม alert
        echo "<script>
            alert('จองบริการทั้งหมดเรียบร้อยแล้ว');
            window.location.href='http://localhost/Project/user_bookings.php';
        </script>";
    }

} else {
    echo "ไม่อนุญาตให้เข้าถึงโดยตรง";
}
?>
