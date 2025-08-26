<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// ตรวจสอบ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $address = $_POST['address'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=?, address=?, latitude=?, longitude=? WHERE user_id=?");
    $stmt->bind_param("sssssssi", $name, $email, $phone, $role, $address, $latitude, $longitude, $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: admin_users_manage.php");
exit();
