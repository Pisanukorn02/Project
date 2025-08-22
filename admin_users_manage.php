<?php
session_start();
include 'config.php';

// ตรวจสอบว่าเป็นแอดมินหรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้</title>
    <style>
        body { font-family: sans-serif; background: #f7f7f7; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #1976d2; color: white; }
        h2 { color: #1976d2; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>จัดการผู้ใช้งาน</h2>

    <?php
    $sql = "SELECT user_id, name, email, phone, role FROM users ORDER BY user_id DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>ชื่อ</th><th>อีเมล</th><th>เบอร์โทร</th><th>บทบาท</th><th>จัดการ</th></tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "<td><a class='btn-delete' href='delete_user.php?user_id=" . $row['user_id'] . "' onclick='return confirm(\"คุณแน่ใจว่าต้องการลบผู้ใช้นี้?\")'>ลบ</a></td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>ยังไม่มีผู้ใช้งานในระบบ</p>";
    }

    $conn->close();
    ?>
</body>
</html>
