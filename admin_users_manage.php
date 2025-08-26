<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// รับค่า search
$search = $_GET['search'] ?? '';
$search_sql = "";
$params = [];
$types = "";

// ถ้ามีการค้นหา
if (!empty($search)) {
    $search_sql = "WHERE user_id LIKE ? OR name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types = "ssss";
}

// ดึงข้อมูลผู้ใช้
$sql = "SELECT user_id, name, email, phone, created_at, role, address, latitude, longitude FROM users $search_sql ORDER BY user_id DESC";
$stmt = $conn->prepare($sql);

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการผู้ใช้งาน</title>
<style>
body {
    font-family:'Segoe UI', sans-serif;
    background:#f4f6f8;
    margin:0; padding:20px;
}
h2 { color:#34495e; }
a.btn { 
    text-decoration:none; 
    display:inline-block; 
    padding:8px 15px; 
    background:#1976d2; 
    color:#fff; 
    border-radius:5px; 
    margin-bottom:15px; 
}
form.search-form { margin-bottom:15px; }
form.search-form input[type=text] { padding:6px; width:250px; border:1px solid #ccc; border-radius:4px; }
form.search-form button { padding:6px 12px; border:none; border-radius:4px; background:#1976d2; color:#fff; cursor:pointer; }
table {
    width:100%; 
    border-collapse:collapse; 
    background:#fff;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
th, td { padding:10px; border-bottom:1px solid #ddd; text-align:left; vertical-align:middle; }
th { background:#1976d2; color:#fff; }
td form { display:inline-block; margin:0 2px; }
button.action-btn { padding:5px 10px; border:none; border-radius:4px; cursor:pointer; }
button.edit { background:#f39c12; color:#fff; }
button.delete { background:#dc3545; color:#fff; }
</style>
</head>
<body>

<h2>จัดการผู้ใช้งาน</h2>
<p>
    <a href="admin_dashboard.php" class="btn">กลับหน้าหลัก</a> 
    <a href="admin_logout.php" class="btn">ออกจากระบบ</a>
</p>

<form method="GET" class="search-form">
    <input type="text" name="search" placeholder="ค้นหา ID, ชื่อ, อีเมล, เบอร์" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">ค้นหา</button>
</form>

<?php if(empty($users)): ?>
<p>ไม่พบผู้ใช้งาน</p>
<?php else: ?>
<table>
<thead>
<tr>
<th>ID</th>
<th>ชื่อ</th>
<th>อีเมล</th>
<th>เบอร์</th>
<th>บทบาท</th>
<th>ที่อยู่</th>
<th>Latitude</th>
<th>Longitude</th>
<th>วันที่สร้าง</th>
<th>จัดการ</th>
</tr>
</thead>
<tbody>
<?php foreach($users as $user): ?>
<tr>
<td><?= htmlspecialchars($user['user_id']); ?></td>
<td><?= htmlspecialchars($user['name']); ?></td>
<td><?= htmlspecialchars($user['email']); ?></td>
<td><?= htmlspecialchars($user['phone']); ?></td>
<td><?= htmlspecialchars($user['role']); ?></td>
<td><?= htmlspecialchars($user['address']); ?></td>
<td><?= htmlspecialchars($user['latitude']); ?></td>
<td><?= htmlspecialchars($user['longitude']); ?></td>
<td><?= htmlspecialchars($user['created_at']); ?></td>
<td>
    <form action="admin_user_edit.php" method="GET">
        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
        <button type="submit" class="action-btn edit">แก้ไข</button>
    </form>
    <form action="admin_user_action.php" method="POST" onsubmit="return confirm('คุณแน่ใจว่าต้องการลบผู้ใช้นี้?');">
        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
        <button type="submit" name="action" value="delete" class="action-btn delete">ลบ</button>
    </form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

</body>
</html>
