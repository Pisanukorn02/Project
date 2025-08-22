<?php
//$base_url = 'http://localhost/Project_Air'; // Base URL ของโปรเจกต์

// กำหนดข้อมูลการเชื่อมต่อฐานข้อมูล
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'air_service_db';
date_default_timezone_set('Asia/Bangkok');


// เชื่อมต่อกับฐานข้อมูล
$conn = mysqli_connect($host, $username, $password, $dbname,3306);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ! " . Mysqli_connect_error());
} else {
    echo "เชื่อมต่อฐานข้อมูลสำเร็จ!";
}

// ปิดการเชื่อมต่อฐานข้อมูล
//$conn->close();
?>
