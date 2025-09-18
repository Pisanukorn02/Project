<?php
session_start();
include 'config.php';

if (!isset($_SESSION['shop_id'])) {
    header('Location: login.html');
    exit();
}

$shop_id = $_SESSION['shop_id'];

// ดึงข้อมูลจากฟอร์ม
$shop_name          = $_POST['shop_name'] ?? '';
$phone              = $_POST['phone'] ?? '';
$address            = $_POST['address'] ?? '';
$province           = $_POST['province'] ?? '';
$latitude           = $_POST['latitude'] ?? '';
$longitude          = $_POST['longitude'] ?? '';
$service_radius     = $_POST['service_radius'] ?? 30; // ค่า default 30 กม.
$extra_price_per_km = $_POST['extra_price_per_km'] ?? 50; // ค่า default 50 บาท

// ตรวจสอบและอัปโหลดรูปถ้ามี
$shop_image = '';
if (isset($_FILES['shop_image']) && $_FILES['shop_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $tmp_name = $_FILES['shop_image']['tmp_name'];
    $filename = time() . '_' . basename($_FILES['shop_image']['name']);
    $target = $upload_dir . $filename;

    if (move_uploaded_file($tmp_name, $target)) {
        $shop_image = $filename;
    } else {
        echo "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
        exit();
    }
}

// อัปเดตข้อมูลร้านค้า
if ($shop_image) {
    // ถ้ามีรูปใหม่
    $stmt = $conn->prepare("UPDATE shops 
        SET shop_name=?, phone=?, address=?, province=?, latitude=?, longitude=?, shop_image=?, service_radius=?, extra_price_per_km=? 
        WHERE shop_id=?");
    $stmt->bind_param(
        "ssssdddddi",
        $shop_name, $phone, $address, $province, $latitude, $longitude, $shop_image, $service_radius, $extra_price_per_km, $shop_id
    );
} else {
    // ถ้าไม่มีรูปใหม่
    $stmt = $conn->prepare("UPDATE shops 
        SET shop_name=?, phone=?, address=?, province=?, latitude=?, longitude=?, service_radius=?, extra_price_per_km=? 
        WHERE shop_id=?");
    $stmt->bind_param(
        "ssssddddi",
        $shop_name, $phone, $address, $province, $latitude, $longitude, $service_radius, $extra_price_per_km, $shop_id
    );
}

if ($stmt->execute()) {
    $stmt->close();
    header('Location: edit_shop.php?success=1');
    exit();
} else {
    echo "เกิดข้อผิดพลาด: " . $stmt->error;
    $stmt->close();
}
?>
