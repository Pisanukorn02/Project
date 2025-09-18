<?php
session_start();
include 'config.php';

// ตรวจสอบการล็อกอินร้าน
if (!isset($_SESSION['shop_id']) || $_SESSION['role'] !== 'shop') {
    header("Location: login.html");
    exit();
}

$shop_id = $_SESSION['shop_id'];

// รับค่าจากฟอร์ม
$booking_id = $_POST['booking_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$booking_id || !in_array($action, ['accept','reject'])) {
    die("ข้อมูลไม่ครบถ้วน");
}

// ตรวจสอบว่า booking นี้เป็นของร้านนี้จริง และมีข้อเสนอ pending
$stmt = $conn->prepare("SELECT proposed_date, proposed_time, proposal_status FROM bookings WHERE booking_id=? AND shop_id=?");
$stmt->bind_param("ii", $booking_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking || $booking['proposal_status'] !== 'pending') {
    die("ไม่พบข้อมูลข้อเสนอหรือข้อเสนอหมดอายุแล้ว");
}

// ดำเนินการตาม action
if ($action === 'accept') {
    $stmt = $conn->prepare("UPDATE bookings SET booking_date=?, booking_time=?, proposal_status='accepted' WHERE booking_id=? AND shop_id=?");
    $stmt->bind_param("ssii", $booking['proposed_date'], $booking['proposed_time'], $booking_id, $shop_id);
    $stmt->execute();
    $stmt->close();

    // สามารถส่งการแจ้งเตือนไปยังลูกค้าได้ที่นี่ (ถ้ามีระบบ email/notification)
    $msg = "คุณได้ยอมรับเวลาที่ลูกค้าเสนอเรียบร้อยแล้ว";
} else { // reject
    $stmt = $conn->prepare("UPDATE bookings SET proposal_status='rejected' WHERE booking_id=? AND shop_id=?");
    $stmt->bind_param("ii", $booking_id, $shop_id);
    $stmt->execute();
    $stmt->close();
    $msg = "คุณได้ปฏิเสธเวลาที่ลูกค้าเสนอแล้ว";
}

// กลับไปหน้าร้าน
header("Location: shop_board.php?msg=" . urlencode($msg));
exit();
?>
