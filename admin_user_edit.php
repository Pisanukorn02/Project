<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// รับ user_id
if (!isset($_GET['user_id'])) {
    header("Location: admin_users_manage.php");
    exit();
}
$user_id = intval($_GET['user_id']);

// ดึงข้อมูลผู้ใช้
$stmt = $conn->prepare("SELECT user_id, name, email, phone, role, address, latitude, longitude FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "ไม่พบผู้ใช้นี้";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แก้ไขผู้ใช้งาน</title>
<style>
body { font-family:sans-serif; padding:20px; background:#f7f7f7; }
form { background:#fff; padding:20px; border-radius:8px; max-width:500px; }
input, select, button { width:100%; padding:10px; margin:5px 0; }
</style>
</head>
<body>
<h2>แก้ไขผู้ใช้งาน</h2>
<p><a href="admin_users_manage.php">กลับหน้าจัดการผู้ใช้งาน</a></p>

<form method="POST" action="admin_user_update.php">
    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">

    <label>ชื่อ</label>
    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

    <label>อีเมล</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label>เบอร์โทร</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

    <label>บทบาท</label>
    <select name="role">
        <option value="user" <?= $user['role']=='user'?'selected':'' ?>>user</option>
        <option value="shop" <?= $user['role']=='shop'?'selected':'' ?>>shop</option>
        <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>admin</option>
    </select>

    <label>ที่อยู่</label>
    <input type="text" name="address" value="<?= htmlspecialchars($user['address']) ?>">

    <label>Latitude</label>
    <input type="text" name="latitude" value="<?= htmlspecialchars($user['latitude']) ?>">

    <label>Longitude</label>
    <input type="text" name="longitude" value="<?= htmlspecialchars($user['longitude']) ?>">

    <button type="submit">อัปเดตผู้ใช้งาน</button>
</form>
</body>
</html>
