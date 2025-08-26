<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// ดึงร้านค้าที่รออนุมัติ
$pending_shops = [];
$sql = "SELECT s.shop_id, u.name AS owner_name, u.email, u.phone, s.shop_name, s.address, s.province, s.latitude, s.longitude, s.created_at
        FROM shops s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.is_approved = 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pending_shops[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="http://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #f4f6f8;
    margin:0; padding:20px;
}
h2, h3 { color: #34495e; }
a { text-decoration:none; color:#fff; }
a.btn { background:#1976d2; padding:8px 15px; border-radius:5px; margin-bottom:15px; display:inline-block; }
table {
    width:100%; border-collapse:collapse; background:#fff;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
th, td { padding:10px; text-align:left; border-bottom:1px solid #ddd; vertical-align:top; }
th { background:#1976d2; color:#fff; }
.map-container {
    height:250px;
    width:100%;
    margin-top:10px;
    border-radius:8px;
    background:#e0e0e0;
}
button {
    padding:6px 12px;
    margin:2px 0;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
button.approve { background:#28a745; color:#fff; }
button.reject { background:#dc3545; color:#fff; }
button.show-map-btn { background:#f39c12; color:#fff; }
input[type=text] { width:80%; padding:5px; margin:5px 0; border:1px solid #ccc; border-radius:4px; }
</style>
</head>
<body>

<h2>ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['admin_username']); ?></h2>
<p><a href="admin_logout.php" class="btn">ออกจากระบบ</a>
<a href="admin_users_manage.php" class="btn">จัดการผู้ใช้งานระบบ</a></p>
<p>
    <a href="admin_booking_manage.php" class="btn" style="
        background-color: #16a085;
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        margin-bottom: 15px;
        display: inline-block;
    ">
        จัดการการจองลูกค้า
    </a>
</p>


<h3>ร้านค้าที่รอการอนุมัติ</h3>
<?php if(empty($pending_shops)): ?>
<p>ไม่มีร้านค้าที่รอการอนุมัติ</p>
<?php else: ?>
<table>
<thead>
<tr>
<th>ID</th><th>เจ้าของ</th><th>อีเมล</th><th>โทร</th><th>ร้าน</th><th>ที่อยู่</th><th>จังหวัด</th><th>วันที่สมัคร</th><th>แผนที่</th><th>ดำเนินการ</th>
</tr>
</thead>
<tbody>
<?php foreach($pending_shops as $shop): ?>
<tr>
<td><?= htmlspecialchars($shop['shop_id']); ?></td>
<td><?= htmlspecialchars($shop['owner_name']); ?></td>
<td><?= htmlspecialchars($shop['email']); ?></td>
<td><?= htmlspecialchars($shop['phone']); ?></td>
<td><?= htmlspecialchars($shop['shop_name']); ?></td>
<td><?= htmlspecialchars($shop['address']); ?></td>
<td><?= htmlspecialchars($shop['province']); ?></td>
<td><?= htmlspecialchars($shop['created_at']); ?></td>
<td>
    <?php if(!empty($shop['latitude']) && !empty($shop['longitude'])): ?>
        <button class="show-map-btn" data-lat="<?= $shop['latitude'] ?>" data-lng="<?= $shop['longitude'] ?>" data-name="<?= htmlspecialchars($shop['shop_name'], ENT_QUOTES) ?>">ดูแผนที่</button>
        <div id="map-<?= $shop['shop_id'] ?>" class="map-container" style="display:none;"></div>
    <?php else: ?>
        ไม่มีข้อมูลตำแหน่ง
    <?php endif; ?>
</td>
<td>
<form action="admin_process.php" method="POST">
<input type="hidden" name="shop_id" value="<?= $shop['shop_id']; ?>">
<button type="submit" name="action" value="approve" class="approve">อนุมัติ</button><br>
<input type="text" name="reject_reason" placeholder="เหตุผลการปฏิเสธ">
<button type="submit" name="action" value="reject" class="reject">ปฏิเสธ</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<script src="http://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.show-map-btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            const lat = this.dataset.lat;
            const lng = this.dataset.lng;
            const name = this.dataset.name;
            const mapDiv = this.nextElementSibling;

            if(mapDiv.style.display === "none") {
                mapDiv.style.display = "block";

                const map = L.map(mapDiv.id).setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                L.marker([lat, lng]).addTo(map).bindPopup(name).openPopup();

                this.innerText = "ซ่อนแผนที่";
            } else {
                mapDiv.style.display = "none";
                this.innerText = "ดูแผนที่";
            }
        });
    });
});
</script>

</body>
</html>
