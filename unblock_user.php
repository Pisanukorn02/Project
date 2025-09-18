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
    $stmt = $conn->prepare("DELETE FROM blocked_users WHERE shop_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $shop_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: shop_board.php");
exit();
?>
