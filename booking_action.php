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
        $status = 'completed';  // ต้องมี 'completed' ใน ENUM ของฐานข้อมูล
    }

    // กรณี complete + มีการอัปโหลดไฟล์
    if ($action === 'complete') {
        if (isset($_FILES['completion_proof']) && $_FILES['completion_proof']['error'] === 0) {
            $upload_dir = "uploads/completions/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = time() . "_" . basename($_FILES['completion_proof']['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['completion_proof']['tmp_name'], $target_file)) {
                // บันทึก path ลง DB และอัพเดตสถานะเป็น completed
                $stmt = $conn->prepare("UPDATE bookings SET status = ?, complete_image = ? WHERE booking_id = ?");
                $stmt->bind_param("ssi", $status, $file_name, $booking_id);

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: shop_board.php"); // ✅ กลับไปหน้า shop_board
                    exit();
                } else {
                    echo "เกิดข้อผิดพลาด: " . $stmt->error;
                }
            } else {
                echo "<script>alert('อัพโหลดไฟล์ไม่สำเร็จ'); history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('กรุณาอัพโหลดรูปหลักฐานก่อนจบงาน'); history.back();</script>";
            exit();
        }
    }

    // กรณี approve / reject
    $stmt = $conn->prepare("UPDATE bookings SET `status` = ? WHERE booking_id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $status, $booking_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            header("Location: shop_board.php"); // ✅ กลับไปหน้า shop_board
            exit();
        } else {
            echo "ไม่มีข้อมูลที่อัปเดต อาจเป็น booking_id ผิดหรือไม่มีในฐานข้อมูล";
        }
    } else {
        echo "เกิดข้อผิดพลาด: " . $stmt->error;
    }
    $stmt->close();
}
?>
