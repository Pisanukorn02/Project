<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['booking_id'], $_POST['proposed_date'], $_POST['proposed_time'])) {
    $booking_id = intval($_POST['booking_id']);
    $proposed_date = $_POST['proposed_date'];
    $proposed_time = $_POST['proposed_time'];

    // อัปเดต booking
    $stmt = $conn->prepare("UPDATE bookings 
                            SET proposed_date = ?, proposed_time = ?, proposal_status = 'pending'
                            WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $proposed_date, $proposed_time, $booking_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "ส่งคำขอเปลี่ยนเวลาเรียบร้อยแล้ว";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการส่งคำขอ";
    }

    $stmt->close();
}

header("Location: user_bookings.php");
exit();
?>
