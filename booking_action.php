<?php
session_start();
include 'config.php';  // เชื่อมต่อฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['booking_id'], $_POST['action'])) {
        die("ข้อมูลไม่ครบถ้วน");
    }

    // แปลง booking_id เป็นตัวเลข เพื่อความปลอดภัย
    $booking_id = intval($_POST['booking_id']);
    $action = $_POST['action'];

    // เพิ่ม 'complete' ใน array ของ action ที่ยอมรับ
    if (!in_array($action, ['approve', 'reject', 'complete'])) {
        die("คำสั่งไม่ถูกต้อง");
    }

    // กำหนดสถานะตาม action
    if ($action === 'approve') {
        $status = 'accepted';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    } elseif ($action === 'complete') {
        $status = 'completed';  // ต้องเพิ่ม 'completed' ใน ENUM ของฐานข้อมูลด้วย
    }

    $stmt = $conn->prepare("UPDATE bookings SET `status` = ? WHERE booking_id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $status, $booking_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            header("Location: shop_board.php");
            exit();
        } else {
            echo "ไม่มีข้อมูลที่อัปเดต อาจเป็น booking_id ผิดหรือไม่มีในฐานข้อมูล";
        }
    } else {
        echo "เกิดข้อผิดพลาด: " . $stmt->error;
    }
}
?>
