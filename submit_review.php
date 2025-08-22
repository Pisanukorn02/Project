<?php
session_start();
include 'config.php';  // เชื่อมต่อฐานข้อมูล

// เช็คว่าผู้ใช้ล็อกอินไหม
if (!isset($_SESSION['user_id'])) {
    die("กรุณาเข้าสู่ระบบก่อน");
}

$user_id = $_SESSION['user_id'];

// สมมติรับ booking_id จาก GET หรือ POST (แล้วแต่หน้า)
$booking_id = intval($_POST['booking_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

// ตรวจสอบข้อมูล
if ($booking_id <= 0 || $rating < 1 || $rating > 5) {
    die("ข้อมูลไม่ถูกต้อง");
}

// หา shop_id จาก booking_id เพื่อเก็บในรีวิวด้วย
$stmt = $conn->prepare("SELECT shop_id FROM bookings WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($shop_id);
if (!$stmt->fetch()) {
    die("ไม่พบข้อมูลการจองนี้");
}
$stmt->close();

// เตรียม insert รีวิว
$insert = $conn->prepare("INSERT INTO reviews (booking_id, user_id, shop_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
if (!$insert) {
    die("Prepare failed: " . $conn->error);
}
$insert->bind_param("iiiis", $booking_id, $user_id, $shop_id, $rating, $comment);

if ($insert->execute()) {
    // บันทึกสำเร็จ ส่งกลับหรือแสดงข้อความ
    header("Location: customer_bookings.php?msg=review_success");
    exit();
} else {
    die("เกิดข้อผิดพลาด: " . $insert->error);
}
?>
