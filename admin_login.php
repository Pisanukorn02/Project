<?php
session_start();
include 'config.php'; // เชื่อมต่อ DB

// ถ้าแอดมินล็อกอินแล้ว ให้ไป dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

// ตรวจสอบ form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT admin_id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            // login สำเร็จ
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];

            header("Location: admin_dashboard.php");
            exit();
        }
    }
    $_SESSION['error_message'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เข้าสู่ระบบผู้ดูแลระบบ</title>
<style>
body { font-family: sans-serif; background:#f7f7f7; padding:20px; }
.login-container { background:#fff; padding:20px; max-width:400px; margin:50px auto; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
input, button { width:100%; padding:10px; margin:5px 0; }
.error-message { color:red; }
</style>
</head>
<body>
<div class="login-container">
<h2>เข้าสู่ระบบผู้ดูแลระบบ</h2>
<form method="POST">
    <label>ชื่อผู้ใช้</label>
    <input type="text" name="username" required>
    <label>รหัสผ่าน</label>
    <input type="password" name="password" required>
    <button type="submit">เข้าสู่ระบบ</button>
</form>
<?php
if (isset($_SESSION['error_message'])) {
    echo '<p class="error-message">' . $_SESSION['error_message'] . '</p>';
    unset($_SESSION['error_message']);
}
?>
</div>
</body>
</html>
