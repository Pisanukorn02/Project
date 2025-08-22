<?php
session_start();
include 'config.php';

// Basic authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_id = $_POST['shop_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($shop_id && ($action === 'approve' || $action === 'reject')) {
        if ($action === 'approve') {
            // อนุมัติร้านค้า (is_approved = 0)
            $stmt = $conn->prepare("UPDATE shops SET is_approved = 0 WHERE shop_id = ?");
            $stmt->bind_param("i", $shop_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "อนุมัติร้านค้า ID " . $shop_id . " สำเร็จ";
            } else {
                $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการอนุมัติร้านค้า: " . $stmt->error;
            }
            $stmt->close();

        } elseif ($action === 'reject') {
            // รับเหตุผลการปฏิเสธ (ถ้ามี)
            $reject_reason = trim($_POST['reject_reason'] ?? '');

            // ตรวจสอบว่าร้านค้ามีอยู่จริงไหม
            $user_id_stmt = $conn->prepare("SELECT user_id FROM shops WHERE shop_id = ?");
            $user_id_stmt->bind_param("i", $shop_id);
            $user_id_stmt->execute();
            $user_id_result = $user_id_stmt->get_result();
            $user_row = $user_id_result->fetch_assoc();
            $user_id_stmt->close();

            if ($user_row) {
                // อัปเดตสถานะร้านค้าเป็นปฏิเสธ พร้อมบันทึกเหตุผล
                $stmt_update_shop = $conn->prepare("UPDATE shops SET is_approved = 2, reject_reason = ? WHERE shop_id = ?");
                $stmt_update_shop->bind_param("si", $reject_reason, $shop_id);

                if ($stmt_update_shop->execute()) {
                    $_SESSION['message'] = "ปฏิเสธร้านค้า ID " . $shop_id . " สำเร็จ";
                } else {
                    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการปฏิเสธร้านค้า: " . $stmt_update_shop->error;
                }
                $stmt_update_shop->close();

            } else {
                $_SESSION['error_message'] = "ไม่พบร้านค้า ID " . $shop_id . " ที่จะปฏิเสธ";
            }
        }
    } else {
        $_SESSION['error_message'] = "การดำเนินการไม่ถูกต้อง";
    }
}

$conn->close();
header("Location: admin_dashboard.php");
exit();
?>