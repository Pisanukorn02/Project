<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop') {
    header("Location: login.html");
    exit();
}

$shop_id = $_SESSION['shop_id'];

if (!isset($_GET['service_id'])) {
    echo "ไม่พบบริการนี้";
    exit();
}
$service_id = intval($_GET['service_id']);

// ดึงข้อมูลรูปภาพก่อนลบ
$stmt = $conn->prepare("SELECT image FROM services WHERE service_id = ? AND shop_id = ?");
$stmt->bind_param("ii", $service_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$service = $result->fetch_assoc();
$stmt->close();

if (!$service) {
    echo "ไม่พบบริการนี้หรือคุณไม่มีสิทธิ์ลบ";
    exit();
}

// ดึง booking_id ทั้งหมดที่มี service_id นี้
$stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE service_id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();
$booking_ids = [];
while ($row = $result->fetch_assoc()) {
    $booking_ids[] = $row['booking_id'];
}
$stmt->close();

if (count($booking_ids) > 0) {
    // สร้าง query ลบ reviews ที่ booking_id อยู่ใน list นี้
    $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
    $types = str_repeat('i', count($booking_ids));

    $sql = "DELETE FROM reviews WHERE booking_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);

    // ผูกพารามิเตอร์แบบไดนามิก
    $stmt->bind_param($types, ...$booking_ids);
    $stmt->execute();
    $stmt->close();
}

// ลบ bookings ที่ใช้ service_id นี้
$stmt = $conn->prepare("DELETE FROM bookings WHERE service_id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->close();

// ลบไฟล์รูปภาพเก่า (ถ้ามี)
$uploadDir = 'uploads/';
if ($service['image'] && file_exists($uploadDir . $service['image'])) {
    unlink($uploadDir . $service['image']);
}

// ลบ service
$stmt = $conn->prepare("DELETE FROM services WHERE service_id = ? AND shop_id = ?");
$stmt->bind_param("ii", $service_id, $shop_id);
if ($stmt->execute()) {
    header("Location: shop_board.php?msg=delete_success");
    exit();
} else {
    echo "เกิดข้อผิดพลาดในการลบ: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>
