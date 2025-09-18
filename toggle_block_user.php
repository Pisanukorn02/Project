<?php
session_start();
include 'config.php';

if (!isset($_SESSION['shop_id']) || $_SESSION['role'] !== 'shop') {
    header("Location: login.html");
    exit();
}

$shop_id = $_SESSION['shop_id'];
$user_id = $_POST['user_id'] ?? null;

if ($user_id) {
    // ตรวจสอบว่าบล็อกอยู่แล้วหรือไม่
    $stmt = $conn->prepare("SELECT block_id FROM blocked_users WHERE shop_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $shop_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // ลบบล็อก
        $row = $result->fetch_assoc();
        $stmt_del = $conn->prepare("DELETE FROM blocked_users WHERE block_id = ?");
        $stmt_del->bind_param("i", $row['block_id']);
        $stmt_del->execute();
        $stmt_del->close();
    } else {
        // เพิ่มบล็อก
        $stmt_ins = $conn->prepare("INSERT INTO blocked_users (shop_id, user_id, created_at) VALUES (?, ?, NOW())");
        $stmt_ins->bind_param("ii", $shop_id, $user_id);
        $stmt_ins->execute();
        $stmt_ins->close();
    }

    $stmt->close();
}

header("Location: shop_board.php");
exit();
?>
