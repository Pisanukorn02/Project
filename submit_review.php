<?php
session_start();
include 'config.php';  // เชื่อมต่อฐานข้อมูล

if (!isset($_SESSION['user_id'])) {
    die("กรุณาเข้าสู่ระบบก่อน");
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_POST['booking_id'] ?? 0);
$shop_id = intval($_POST['shop_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($booking_id <= 0 || $rating < 1 || $rating > 5 || $shop_id <= 0) {
    die("ข้อมูลไม่ถูกต้อง");
}

// ตรวจสอบ booking_id เป็นของ user นี้
$stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_id = ? AND user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    die("ไม่พบข้อมูลการจองนี้ หรือคุณไม่มีสิทธิ์รีวิว");
}
$stmt->close();

// insert รีวิว
$insert = $conn->prepare("INSERT INTO reviews (booking_id, user_id, shop_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$insert->bind_param("iiiis", $booking_id, $user_id, $shop_id, $rating, $comment);

if ($insert->execute()) {
    // ส่งกลับไปหน้าร้าน
    header("Location: shop.php?shop_id=" . $shop_id . "&msg=review_success");
    exit();
} else {
    die("เกิดข้อผิดพลาด: " . $insert->error);
}
$insert->close();
$conn->close();