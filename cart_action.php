<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'remove' && isset($_POST['index'])) {
        $index = intval($_POST['index']);
        if (isset($_SESSION['cart'][$index])) {
            unset($_SESSION['cart'][$index]);
            // รีเซ็ต index ของ array เพื่อป้องกันช่องว่าง
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }
    }
}

header("Location: cart.php");  // เปลี่ยนเป็นหน้าตะกร้าของคุณ
exit();
