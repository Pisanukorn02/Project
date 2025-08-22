<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // ดึงข้อมูลจาก users
    $sql = "SELECT user_id, name, password, role FROM users WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // ถ้าเป็นร้านค้า ต้องเช็กการอนุมัติ
if ($user['role'] === 'shop') {
    $stmt2 = $conn->prepare("SELECT shop_id, is_approved, reject_reason FROM shops WHERE user_id = ?");
    $stmt2->bind_param("i", $user['user_id']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2->num_rows > 0) {
        $shop = $result2->fetch_assoc();

        if ($shop['is_approved'] == 1) {
            echo "บัญชีร้านค้าของคุณยังไม่ได้รับการอนุมัติจากผู้ดูแลระบบ";
            exit();
        } elseif ($shop['is_approved'] == 2) {
            echo "บัญชีร้านค้าของคุณถูกปฏิเสธโดยผู้ดูแลระบบ<br>";
            echo "เหตุผล: " . htmlspecialchars($shop['reject_reason']);
            exit();
        }

        $_SESSION['shop_id'] = $shop['shop_id'];
    } else {
        echo "ไม่พบข้อมูลร้านค้าในระบบ";
        exit();
                }

                $stmt2->close();
            }

            // นำทางตาม role
            if ($user['role'] === 'admin') {
                header("Location: admin_approve_shops.php");
            } elseif ($user['role'] === 'shop') {
                header("Location: shop_board.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            echo "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        echo "ไม่พบผู้ใช้ในระบบ";
    }

    $stmt->close();
}

$conn->close();
?>
