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
$sql = "SELECT s.shop_id, u.name AS owner_name, u.email, u.phone, s.shop_name, s.address, s.province, s.latitude, s.longitude, s.created_at, s.is_new
        FROM shops s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.is_approved = 1
        ORDER BY s.created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pending_shops[] = $row;
    }
}

// หาเฉพาะร้านใหม่ที่ยังไม่แจ้ง
$shops_to_notify = [];
foreach($pending_shops as $shop) {
    if($shop['is_new']==1){
        $shops_to_notify[] = $shop;
    }
}

// อัปเดตฐานข้อมูลให้ร้านที่แจ้งแล้วเป็น is_new=0
if(count($shops_to_notify) > 0){
    $shop_ids = array_column($shops_to_notify, 'shop_id');
    $ids_str = implode(",", $shop_ids);
    $conn->query("UPDATE shops SET is_new=0 WHERE shop_id IN ($ids_str)");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="http://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<style>
body { font-family:'Segoe UI', sans-serif; background:#f4f6f8; margin:0; padding:20px; }
h2,h3{color:#34495e;}
a{ text-decoration:none; color:#fff; }
a.btn{background:#1976d2; padding:8px 15px; border-radius:5px; margin-bottom:15px; display:inline-block;}
table{width:100%; border-collapse:collapse; background:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
th,td{padding:10px; text-align:left; border-bottom:1px solid #ddd; vertical-align:top;}
th{background:#1976d2; color:#fff;}
.map-container{height:250px;width:100%;margin-top:10px;border-radius:8px;background:#e0e0e0;}
button{padding:6px 12px;margin:2px 0;border:none;border-radius:5px; cursor:pointer;}
button.approve{background:#28a745;color:#fff;}
button.reject{background:#dc3545;color:#fff;}
button.show-map-btn{background:#f39c12;color:#fff;}
input[type=text]{width:80%;padding:5px;margin:5px 0;border:1px solid #ccc;border-radius:4px;}

/* badge กระพริบ */
@keyframes blink {
  0% {opacity:1;}
  50% {opacity:0.2;}
  100% {opacity:1;}
}
.new-badge {
    background:#dc3545; 
    color:#fff; 
    font-size:12px; 
    padding:2px 5px; 
    border-radius:3px; 
    font-weight:bold; 
    margin-right:5px;
    vertical-align:middle;
    animation: blink 1s infinite;
}

/* toast popup */
.new-shop-toast {
    position:fixed;
    top:20px;
    right:20px;
    background:#dc3545;
    color:#fff;
    padding:15px 20px;
    border-radius:8px;
    font-weight:bold;
    z-index:9999;
    box-shadow:0 2px 10px rgba(0,0,0,0.3);
    animation: fadein 0.5s, fadeout 0.5s 5s;
}
@keyframes fadein { from {opacity:0; top:0;} to {opacity:1; top:20px;} }
@keyframes fadeout { from {opacity:1; top:20px;} to {opacity:0; top:0;} }

</style>
</head>
<body>

<h2>ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['admin_username']); ?></h2>
<p>
<a href="admin_logout.php" class="btn">ออกจากระบบ</a>
<a href="admin_users_manage.php" class="btn">จัดการผู้ใช้งานระบบ</a>
<a href="admin_booking_manage.php" class="btn">จัดการการจองลูกค้า</a>
<a href="report_admin.php" class="btn">ภาพรวมร้านค้า</a>
</p>

<h3>ร้านค้าที่รอการอนุมัติ</h3>
<?php if(empty($pending_shops)): ?>
<p>ไม่มีร้านค้าที่รอการอนุมัติ</p>
<?php else: ?>
<table>
<thead>
<tr>
<th>ID</th><th>เจ้าของ</th><th>ร้าน</th><th>อีเมล</th><th>โทร</th><th>ที่อยู่</th><th>จังหวัด</th><th>วันที่สมัคร</th><th>แผนที่</th><th>ดำเนินการ</th>
</tr>
</thead>
<tbody>
<?php foreach($pending_shops as $shop): ?>
<tr>
    <td>
        <?php if($shop['is_new']==1): ?>
            <span class="new-badge">NEW</span>
        <?php endif; ?>
        <?= htmlspecialchars($shop['shop_id']); ?>
    </td>
    <td><?= htmlspecialchars($shop['owner_name']); ?></td>
    <td><?= htmlspecialchars($shop['shop_name']); ?> </td>
    <td><?= htmlspecialchars($shop['email']); ?></td>
    <td><?= htmlspecialchars($shop['phone']); ?></td>
    <td><?= htmlspecialchars($shop['address']); ?></td>
    <td><?= htmlspecialchars($shop['province']); ?></td>
    <td><?= htmlspecialchars($shop['created_at']); ?></td>
    <td>
        <?php if(!empty($shop['latitude']) && !empty($shop['longitude'])): ?>
            <button class="show-map-btn" data-lat="<?= $shop['latitude'] ?>" data-lng="<?= $shop['longitude'] ?>" data-name="<?= htmlspecialchars($shop['shop_name'], ENT_QUOTES) ?>">ดูแผนที่</button>
            <div id="map-<?= $shop['shop_id'] ?>" class="map-container" style="display:none;"></div>
        <?php else: ?>ไม่มีข้อมูลตำแหน่ง<?php endif; ?>
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

<!-- toast popup -->
<?php if(count($shops_to_notify) > 0): ?>
<div class="new-shop-toast" id="new-shop-toast">
    ร้านใหม่มาสมัคร: <?= implode(", ", array_map(fn($s)=>htmlspecialchars($s['shop_name']), $shops_to_notify)); ?>
</div>
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
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OpenStreetMap contributors' }).addTo(map);
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
