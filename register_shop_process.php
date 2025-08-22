<?php
// กำหนด error reporting สำหรับการ debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include ไฟล์ config.php เพื่อเชื่อมต่อฐานข้อมูล
include 'config.php';

// ตรวจสอบว่า request เป็น POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. รับค่าจาก Form และเตรียมข้อมูล
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password']; 
    $phone = trim($_POST['phone']);
    $shop_name = trim($_POST['shop_name']);
    $address = trim($_POST['address']);
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $province = trim($_POST['province']);

    // Hash รหัสผ่าน
    $hashed_password = password_hash($password_raw, PASSWORD_DEFAULT);

    // 2. จัดการการอัปโหลดไฟล์รูปภาพ
    $target_dir = "uploads/shop_images/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_name = "";
    
    if (isset($_FILES["shop_image"]) && $_FILES["shop_image"]["error"] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES["shop_image"]["tmp_name"];
        $file_original_name = basename($_FILES["shop_image"]["name"]);
        $file_size = $_FILES["shop_image"]["size"];
        $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));

        $allowed_extensions = array("jpg", "jpeg", "png", "gif");
        if (!in_array($file_ext, $allowed_extensions)) {
            echo "ขออภัย, อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG, GIF เท่านั้น.";
            exit();
        }

        $max_file_size = 5 * 1024 * 1024; 
        if ($file_size > $max_file_size) {
            echo "ขออภัย, ไฟล์ของคุณมีขนาดใหญ่เกินไป (สูงสุด 5MB).";
            exit();
        }

        $new_file_name = uniqid('shop_') . '.' . $file_ext;
        $target_file_path = $target_dir . $new_file_name;

        if (move_uploaded_file($file_tmp_name, $target_file_path)) {
            $image_name = $new_file_name;
        } else {
            echo "เกิดข้อผิดพลาดในการอัปโหลดไฟล์.";
            exit();
        }
    } else {
        echo "กรุณาอัปโหลดรูปร้านค้า.";
        exit();
    }

    // 3. เริ่ม Transaction
    $conn->begin_transaction();

    try {
        // ตรวจสอบอีเมลซ้ำ
        $stmt_check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            throw new Exception("อีเมลนี้ถูกใช้งานแล้ว กรุณาใช้อีเมลอื่น.");
        }
        $stmt_check_email->close();

        // insert users
        $stmt_user = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'shop')");
        $stmt_user->bind_param("ssss", $name, $email, $hashed_password, $phone);
        
        if (!$stmt_user->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูลผู้ใช้: " . $stmt_user->error);
        }
        $user_id = $conn->insert_id; 

        // insert shops (ใส่ reject_reason = NULL ตอนแรก)
        $stmt_shop = $conn->prepare("
            INSERT INTO shops 
            (user_id, shop_name, address, province, latitude, longitude, phone, shop_image, is_approved, reject_reason) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $is_approved_default = 1; // 1 = ยังไม่อนุมัติ
        $reject_reason = null;    // สมัครใหม่ยังไม่มีเหตุผลถูก reject

        $stmt_shop->bind_param(
            "isssddssis", 
            $user_id, 
            $shop_name, 
            $address, 
            $province, 
            $latitude, 
            $longitude, 
            $phone, 
            $image_name, 
            $is_approved_default, 
            $reject_reason
        );

        if (!$stmt_shop->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูลร้านค้า: " . $stmt_shop->error);
        }

        $conn->commit();
        echo "สมัครร้านค้าสำเร็จ! กรุณารอการอนุมัติจากแอดมิน.";

    } catch (Exception $e) {
        $conn->rollback();
        if (!empty($image_name) && file_exists($target_file_path)) {
            unlink($target_file_path);
        }
        echo "เกิดข้อผิดพลาดในการสมัคร: " . $e->getMessage();
    } finally {
        if (isset($stmt_user)) $stmt_user->close();
        if (isset($stmt_shop)) $stmt_shop->close();
    }

} else {
    echo "ไม่สามารถเข้าถึงหน้านี้โดยตรง. (ต้องส่งข้อมูลแบบ POST).";
}

$conn->close();
?>
