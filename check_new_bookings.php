<?php
session_start();
include 'config.php';

if (!isset($_SESSION['shop_id']) || $_SESSION['role'] !== 'shop') {
    echo json_encode(['pending' => 0]);
    exit();
}

$shop_id = $_SESSION['shop_id'];

// นับ booking ใหม่ (is_new = 1) ที่รอการยืนยัน
$stmt = $conn->prepare("SELECT COUNT(*) AS pending FROM bookings WHERE shop_id = ? AND status = 'pending' AND is_new = 1");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo json_encode(['pending' => (int)$row['pending']]);
