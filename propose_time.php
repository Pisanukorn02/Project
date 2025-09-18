<?php
session_start();
include 'config.php';

// ตรวจสอบร้านล็อกอิน
if (!isset($_SESSION['shop_id']) || $_SESSION['role'] !== 'shop') {
    header("Location: login.html");
    exit();
}

$shop_id = $_SESSION['shop_id'];

// ตรวจสอบค่าที่ส่งมาจากฟอร์ม
if (isset($_POST['booking_id'], $_POST['proposed_date'], $_POST['proposed_time'])) {
    $booking_id = intval($_POST['booking_id']);
    $proposed_date = $_POST['proposed_date'];
    $proposed_time = $_POST['proposed_time'];

    // อัปเดต booking
    $stmt = $conn->prepare("UPDATE bookings 
                            SET proposed_date = ?, proposed_time = ?, proposal_status = 'pending'
                            WHERE booking_id = ? AND shop_id = ?");
    $stmt->bind_param("ssii", $proposed_date, $proposed_time, $booking_id, $shop_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "ส่งข้อเสนอเวลาใหม่เรียบร้อยแล้ว";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการส่งข้อเสนอ";
    }

    $stmt->close();
}

header("Location: shop_board.php");
exit();
?>
