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
    $stmt = $conn->prepare("INSERT IGNORE INTO blocked_users (shop_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $shop_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: shop_board.php"); // กลับไปหน้า dashboard ร้าน
exit();
?>
