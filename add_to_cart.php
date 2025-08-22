<?php
session_start();
include 'config.php';

if (isset($_GET['service_id'])) {
    $service_id = (int) $_GET['service_id'];

    // ดึงข้อมูลบริการจาก DB
    $stmt = $conn->prepare("SELECT s.service_id, s.service_name, s.price, s.image, sh.shop_id, sh.shop_name 
                            FROM services s 
                            JOIN shops sh ON s.shop_id = sh.shop_id 
                            WHERE s.service_id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();

    if ($service) {
        // เพิ่มลง session cart
        $_SESSION['cart'][] = $service;
        header("Location: cart.php");
        exit();
    } else {
        echo "ไม่พบบริการที่เลือก";
    }
}
?>
