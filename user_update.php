<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;

    $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, latitude=?, longitude=? WHERE user_id=?");
    $stmt->bind_param("ssssdi", $name, $phone, $address, $latitude, $longitude, $user_id);

    if ($stmt->execute()) {
        header('Location: user_profile.php?success=1');
        exit();
    } else {
        echo "เกิดข้อผิดพลาด: " . $stmt->error;
    }
    $stmt->close();
}
?>
