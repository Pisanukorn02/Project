<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    exit('Unauthorized');
}

if (isset($_POST['booking_id'], $_POST['action'])) {
    $booking_id = intval($_POST['booking_id']);
    $action = $_POST['action']; // 'accept' หรือ 'reject'

    if ($action === 'accept') {
        // ดึงวันที่และเวลาที่ร้านค้าเสนอ
        $stmt = $conn->prepare("SELECT proposed_date, proposed_time FROM bookings WHERE booking_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $new_date = $row['proposed_date'];
            $new_time = $row['proposed_time'];

            // อัปเดตวันที่/เวลา, proposal_status และ status
            $update = $conn->prepare("
                UPDATE bookings 
                SET booking_date = ?, booking_time = ?, proposal_status = 'accepted', status = 'accepted' 
                WHERE booking_id = ? AND user_id = ?
            ");
            $update->bind_param("ssii", $new_date, $new_time, $booking_id, $_SESSION['user_id']);
            $update->execute();

            echo "success";
        } else {
            echo "Booking not found";
        }
    } elseif ($action === 'reject') {
        // เปลี่ยนสถานะ proposal_status เป็น rejected
        $update = $conn->prepare("UPDATE bookings SET proposal_status = 'rejected' WHERE booking_id = ? AND user_id = ?");
        $update->bind_param("ii", $booking_id, $_SESSION['user_id']);
        $update->execute();

        echo "success";
    } else {
        echo "Invalid action";
    }
} else {
    echo "Missing parameters";
}
?>
