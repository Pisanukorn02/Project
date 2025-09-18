<?php
session_start();
include 'config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// เลือกร้าน
$shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : 0;

// ดึงรายชื่อร้านทั้งหมด + จำนวนการจอง + จำนวนการจองยืนยัน
$sql_shops = "
    SELECT s.shop_id, s.shop_name, COUNT(b.booking_id) AS total_bookings,
           SUM(CASE WHEN b.status='completed' THEN 1 ELSE 0 END) AS completed_bookings
    FROM shops s
    LEFT JOIN bookings b ON s.shop_id = b.shop_id
    GROUP BY s.shop_id, s.shop_name
    ORDER BY total_bookings DESC
";
$result_shops = $conn->query($sql_shops);

// เตรียมตัวแปรร้านที่เลือก
$shop_data = null;
$trend_data = [];
$monthly_stats = [];
$months_all = [];
$total_completed = 0;
$total_pending = 0;
$total_cancelled = 0;
$avg_rating = 0;

if ($shop_id > 0) {
    // ดึงข้อมูลร้าน
    $stmt = $conn->prepare("SELECT * FROM shops WHERE shop_id = ?");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $shop_data = $stmt->get_result()->fetch_assoc();

    // ดึงเรตติ้งเฉลี่ยร้านจาก reviews
    $avg_stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM reviews WHERE shop_id = ?");
    $avg_stmt->bind_param("i", $shop_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->get_result()->fetch_assoc();
    $avg_rating = $avg_result['avg_rating'] ?? 0;

    // กำหนดเดือนย้อนหลัง 12 เดือน
    for ($i = 11; $i >= 0; $i--) {
        $months_all[] = date('Y-m', strtotime("-$i month"));
    }

    // ดึงข้อมูลการจองต่อเดือน พร้อมค่าเฉลี่ย rating จาก reviews
    $stmt2 = $conn->prepare("
        SELECT DATE_FORMAT(b.created_at, '%Y-%m') AS month,
               SUM(CASE WHEN b.status='completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN b.status='pending' THEN 1 ELSE 0 END) AS pending,
               SUM(CASE WHEN b.status='rejected' THEN 1 ELSE 0 END) AS cancelled,
               COUNT(*) AS total,
               AVG(r.rating) AS avg_rating
        FROM bookings b
        LEFT JOIN reviews r ON b.booking_id = r.booking_id
        WHERE b.shop_id = ?
        GROUP BY month
        ORDER BY month
    ");
    $stmt2->bind_param("i", $shop_id);
    $stmt2->execute();
    $res = $stmt2->get_result();

    // เตรียม array ให้ครบทุกเดือน
    $monthly_stats = [];
    while ($row = $res->fetch_assoc()) {
        $monthly_stats[$row['month']] = [
            'completed' => (int)$row['completed'],
            'pending' => (int)$row['pending'],
            'cancelled' => (int)$row['cancelled'],
            'total' => (int)$row['total'],
            'avg_rating' => $row['avg_rating'] ? round($row['avg_rating'], 2) : null
        ];
    }

    // เติม 0 สำหรับเดือนที่ไม่มีข้อมูล
    $trend_data = [];
    foreach ($months_all as $m) {
        if (!isset($monthly_stats[$m])) {
            $monthly_stats[$m] = [
                'completed' => 0,
                'pending' => 0,
                'cancelled' => 0,
                'total' => 0,
                'avg_rating' => null
            ];
        }
        $trend_data[$m] = $monthly_stats[$m]['total'];
        $total_completed += $monthly_stats[$m]['completed'];
        $total_pending += $monthly_stats[$m]['pending'];
        $total_cancelled += $monthly_stats[$m]['cancelled'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รายงานสรุปการใช้งาน (Admin)</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;}
.main-container{background:rgba(255,255,255,0.95);border-radius:20px;box-shadow:0 20px 40px rgba(0,0,0,0.1);padding:30px;margin:20px auto;backdrop-filter:blur(10px);}
.page-title{background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-weight:bold;text-align:center;margin-bottom:30px;font-size:2.5rem;}
.shop-card{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-radius:15px;box-shadow:0 10px 30px rgba(102,126,234,0.3);transition:transform 0.3s ease,box-shadow 0.3s ease;}
.shop-card:hover{transform:translateY(-5px);box-shadow:0 15px 40px rgba(102,126,234,0.4);}
.shop-list-item{border:none;border-radius:12px;margin-bottom:10px;box-shadow:0 5px 15px rgba(0,0,0,0.1);transition:all 0.3s ease;background:linear-gradient(135deg,#ffffff,#f8f9ff);}
.shop-list-item:hover{transform:translateX(5px);box-shadow:0 8px 25px rgba(102,126,234,0.2);}
.shop-list-item a{text-decoration:none;color:#495057;font-weight:500;}
.stats-card{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.1);margin-bottom:20px;transition:transform 0.3s ease;}
.stats-card:hover{transform:translateY(-3px);}
.chart-container{background:white;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.1);padding:20px;margin-bottom:20px;}
.chart-header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-radius:10px 10px 0 0;padding:15px;margin:-20px -20px 20px -20px;font-weight:bold;}
.badge-custom{background:linear-gradient(135deg,#667eea,#764ba2);border-radius:20px;padding:8px 15px;}
.alert-custom{background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(118,75,162,0.1));border:2px solid rgba(102,126,234,0.3);border-radius:15px;color:#495057;}
#map{border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,0.2);}
.rating-stars{color:#ffc107;}
.section-title{color:#495057;font-weight:600;margin-bottom:20px;position:relative;}
.section-title:before{content:'';position:absolute;bottom:-5px;left:0;width:50px;height:3px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:2px;}
</style>
</head>
<body>

<!-- Navbar สำหรับ Admin -->
<nav style="background: linear-gradient(135deg,#667eea,#764ba2); padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <div style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
        <span style="color:white; font-weight:bold; font-size:1.2rem;">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['admin_username']); ?></span>
        <a href="admin_dashboard.php" style="color:white; text-decoration:none; font-weight:500;">หน้าหลัก</a>
        <a href="admin_users_manage.php" style="color:white; text-decoration:none; font-weight:500;">จัดการผู้ใช้งาน</a>
        <a href="admin_booking_manage.php" style="color:white; text-decoration:none; font-weight:500;">จัดการการจอง</a>
        <a href="report_admin.php" style="color:white; text-decoration:none; font-weight:500;">ภาพรวมร้านค้า</a>
    </div>
    <div>
        <a href="admin_logout.php" style="color:white; text-decoration:none; font-weight:500;">ออกจากระบบ</a>
    </div>
</nav>








<div class="main-container">
<h1 class="page-title"><i class="fas fa-chart-line"></i> รายงานสรุปการใช้งานระบบ</h1>

<div class="row">
<div class="col-md-4">
<h4 class="section-title"><i class="fas fa-store"></i> รายชื่อร้าน</h4>
<div class="list-group">
<?php while ($shop = $result_shops->fetch_assoc()): ?>
<div class="list-group-item shop-list-item d-flex justify-content-between align-items-center">
<div>
<a href="?shop_id=<?= $shop['shop_id'] ?>" class="fw-bold"><i class="fas fa-store-alt"></i> <?= htmlspecialchars($shop['shop_name']) ?></a>
</div>
<div class="text-end">
<span class="badge badge-custom"><?= $shop['total_bookings'] ?> การจอง</span>
<div class="small text-muted mt-1"><?= $shop['completed_bookings'] ?> ยืนยัน</div>
</div>
</div>
<?php endwhile; ?>
</div>
</div>

<div class="col-md-8">
<?php if ($shop_data): ?>
<div class="shop-card p-4 mb-4">
<h3 class="mb-3"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($shop_data['shop_name']) ?></h3>
<div class="row">
<div class="col-md-6">
<p><i class="fas fa-map"></i> จังหวัด: <?= htmlspecialchars($shop_data['province']) ?></p>
<p><i class="fas fa-calendar-plus"></i> สมัครเมื่อ: <?= date('d/m/Y', strtotime($shop_data['created_at'])) ?></p>
</div>
<div class="col-md-6">
<div class="rating-stars mb-2">
<i class="fas fa-star"></i> เรตติ้งเฉลี่ย: <?= $avg_rating > 0 ? round($avg_rating,1) . '/5' : 'ไม่มี' ?>
</div>
</div>
</div>
</div>

<div class="row mb-4">
<div class="col-md-3">
<div class="stats-card p-3 text-center">
<i class="fas fa-check-circle fa-2x mb-2"></i>
<h4><?= $total_completed ?></h4>
<small>ยืนยันแล้ว</small>
</div>
</div>
<div class="col-md-3">
<div class="stats-card p-3 text-center">
<i class="fas fa-clock fa-2x mb-2"></i>
<h4><?= $total_pending ?></h4>
<small>รอยืนยัน</small>
</div>
</div>
<div class="col-md-3">
<div class="stats-card p-3 text-center">
<i class="fas fa-times-circle fa-2x mb-2"></i>
<h4><?= $total_cancelled ?></h4>
<small>ยกเลิก</small>
</div>
</div>
<div class="col-md-3">
<div class="stats-card p-3 text-center">
<i class="fas fa-star fa-2x mb-2"></i>
<h4><?= $avg_rating ? round($avg_rating,1) : '0' ?></h4>
<small>เรตติ้งเฉลี่ย</small>
</div>
</div>
</div>

<div class="chart-container">
<div class="chart-header"><i class="fas fa-map"></i> ตำแหน่งร้าน</div>
<div id="map" style="height:300px;"></div>
</div>

<div class="chart-container">
<div class="chart-header"><i class="fas fa-chart-bar"></i> สถิติการจองรายเดือน</div>
<canvas id="bookingChart" height="100"></canvas>
</div>

<div class="chart-container">
<div class="chart-header"><i class="fas fa-chart-pie"></i> สัดส่วนสถานะการจอง</div>
<canvas id="statusChart" height="100"></canvas>
</div>

<script>
<?php if (!empty($shop_data['latitude']) && !empty($shop_data['longitude'])): ?>
var map = L.map('map').setView([<?= $shop_data['latitude'] ?>, <?= $shop_data['longitude'] ?>], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OpenStreetMap' }).addTo(map);
L.marker([<?= $shop_data['latitude'] ?>, <?= $shop_data['longitude'] ?>])
.bindPopup("<?= htmlspecialchars($shop_data['shop_name']) ?>").addTo(map);
<?php else: ?>
document.getElementById("map").innerHTML='<div class="alert alert-warning text-center"><i class="fas fa-exclamation-triangle"></i> ไม่มีข้อมูลตำแหน่งร้าน</div>';
<?php endif; ?>

const months = [<?= '"' . implode('","', $months_all) . '"' ?>];
const confirmed_data = [<?= implode(',', array_map(fn($m)=>$monthly_stats[$m]['completed'],$months_all)) ?>];
const pending_data = [<?= implode(',', array_map(fn($m)=>$monthly_stats[$m]['pending'],$months_all)) ?>];
const cancelled_data = [<?= implode(',', array_map(fn($m)=>$monthly_stats[$m]['cancelled'],$months_all)) ?>];

const ctx = document.getElementById('bookingChart').getContext('2d');
new Chart(ctx, {
    type:'bar',
    data:{
        labels:months,
        datasets:[
            {label:'ยืนยันแล้ว', data:confirmed_data, backgroundColor:'rgba(102,126,234,0.8)'},
            {label:'รอยืนยัน', data:pending_data, backgroundColor:'rgba(255,193,7,0.8)'},
            {label:'ยกเลิก', data:cancelled_data, backgroundColor:'rgba(220,53,69,0.8)'}
        ]
    },
    options:{
        responsive:true,
        plugins:{title:{display:true,text:'จำนวนการจองต่อเดือน'}, legend:{position:'top'}},
        scales:{y:{beginAtZero:true, stepSize:1}}
    }
});

const ctx2 = document.getElementById('statusChart').getContext('2d');
new Chart(ctx2, {
    type:'doughnut',
    data:{
        labels:['ยืนยันแล้ว','รอยืนยัน','ยกเลิก'],
        datasets:[{data:[<?= $total_completed ?>,<?= $total_pending ?>,<?= $total_cancelled ?>],
            backgroundColor:['rgba(102,126,234,0.8)','rgba(255,193,7,0.8)','rgba(220,53,69,0.8)']
        }]
    },
    options:{
        responsive:true,
        plugins:{title:{display:true,text:'สัดส่วนสถานะการจองทั้งหมด'}, legend:{position:'bottom'}}
    }
});
</script>

<?php else: ?>
<div class="alert alert-custom text-center p-4">
<i class="fas fa-info-circle fa-3x mb-3"></i>
<h4>เลือกร้านเพื่อดูรายงาน</h4>
<p class="mb-0">กรุณาเลือกร้านจากรายการด้านซ้ายเพื่อดูข้อมูลรายละเอียดและกราฟสถิติ</p>
</div>
<?php endif; ?>

</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
