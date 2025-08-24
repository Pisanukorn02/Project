<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart = $_SESSION['cart'] ?? [];

    if (isset($_POST['action'], $_POST['index'])) {
        $index = intval($_POST['index']);

        // ลบรายการ
        if ($_POST['action'] === 'remove') {
            if (isset($cart[$index])) {
                unset($cart[$index]);
                $cart = array_values($cart); // รีเซ็ต index ของ array
            }
        }

        // อัปเดตจำนวน
        if ($_POST['action'] === 'update' && isset($_POST['quantity'])) {
            $quantity = max(1, intval($_POST['quantity'])); // อย่างน้อย 1
            if (isset($cart[$index])) {
                $cart[$index]['quantity'] = $quantity;
            }
        }

        $_SESSION['cart'] = $cart;
    }
}

header("Location: cart.php");
exit();
