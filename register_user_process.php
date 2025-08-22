<?php
// กำหนด error reporting สำหรับการ debug ในช่วงพัฒนา
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include ไฟล์ config.php เพื่อเชื่อมต่อฐานข้อมูล
// ใช้ require_once เพื่อป้องกันการ include ซ้ำ
require_once 'config.php';

// ตรวจสอบว่า request เป็น POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ใช้ try...catch เพื่อจัดการข้อผิดพลาดแบบรวมศูนย์
    try {
        // 1. รับค่าจากฟอร์มและเตรียมข้อมูล
        // การกำหนดตัวแปรให้ตรงกับประเภทข้อมูลจะช่วยให้โค้ดอ่านง่ายขึ้น
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password_raw = $_POST['password'];
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        $role = 'user'; // กำหนด role เป็น user โดยตรง

        // 2. ตรวจสอบข้อมูลเบื้องต้น (เช่น ค่าว่าง)
        if (empty($name) || empty($email) || empty($password_raw)) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน.");
        }

        // 3. ตรวจสอบอีเมลซ้ำ
        $stmt_check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if (!$stmt_check_email) {
            throw new Exception("Prepared statement error: " . $conn->error);
        }
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            throw new Exception("อีเมลนี้ถูกใช้งานแล้ว กรุณาใช้อีเมลอื่น.");
        }
        $stmt_check_email->close();

        // 4. Hash รหัสผ่านเพื่อความปลอดภัย
        $hashed_password = password_hash($password_raw, PASSWORD_DEFAULT);

        // 5. เพิ่มข้อมูลลงในตาราง users
        $stmt_insert_user = $conn->prepare("INSERT INTO users (name, email, password, phone, address, latitude, longitude, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt_insert_user) {
            throw new Exception("Prepared statement error: " . $conn->error);
        }
        
        // ssssssss (สำหรับ name, email, password, phone, address, latitude, longitude, role)
        // หรือจะใช้ d (double) สำหรับ latitude, longitude เพื่อให้ตรงกับประเภทข้อมูลใน DB
        // แต่ใน PHP ค่าจากฟอร์มจะเป็น string อยู่แล้ว การใช้ s จึงไม่มีปัญหา
        $stmt_insert_user->bind_param("ssssssss", $name, $email, $hashed_password, $phone, $address, $latitude, $longitude, $role);

        if ($stmt_insert_user->execute()) {
            echo "สมัครสมาชิกสำเร็จ! <a href='login.html'>เข้าสู่ระบบ</a>";
        } else {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt_insert_user->error);
        }
        $stmt_insert_user->close();

    } catch (Exception $e) {
        // จัดการข้อผิดพลาดที่เกิดขึ้น
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    } finally {
        // ปิดการเชื่อมต่อฐานข้อมูลเสมอ ไม่ว่าจะสำเร็จหรือไม่
        $conn->close();
    }
} else {
    // กรณีเข้าถึงโดยตรง
    echo "ไม่สามารถเข้าถึงหน้านี้โดยตรง (ต้องส่งข้อมูลแบบ POST).";
    $conn->close();
}