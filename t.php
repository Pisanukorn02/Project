<?php
session_start();
include 'config.php';

// เปิด error ตอนพัฒนา
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบการล็อกอินร้าน
if (!isset($_SESSION['shop_id']) || $_SESSION['role'] !== 'shop') {
    header("Location: login.html");
    exit();
}



$stmt = $conn->prepare("SELECT booking_id, customer_name, customer_phone, booking_date, booking_time, address, status, total_price, is_new 
                        FROM bookings 
                        WHERE shop_id = ? 
                        ORDER BY created_at DESC");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();


$shop_id = $_SESSION['shop_id']; // ร้านที่ล็อกอิน
$new_bookings = [];
$sql = "SELECT * FROM bookings 
        WHERE shop_id = ? AND status='pending' AND is_new=1
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $new_bookings[] = $row;
}
$stmt->close();


if(count($new_bookings) > 0){
    $booking_ids = array_column($new_bookings, 'booking_id');
    $ids_str = implode(",", $booking_ids);
    $conn->query("UPDATE bookings SET is_new=0 WHERE booking_id IN ($ids_str)");
}


$sql = "SELECT b.booking_id, b.booking_date, b.booking_time, b.address, b.status,
               u.name AS customer_name, u.phone AS customer_phone,
               s.service_name, d.quantity, d.price
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN booking_details d ON b.booking_id = d.booking_id
        JOIN services s ON d.service_id = s.service_id
        WHERE b.shop_id = ?
        ORDER BY b.booking_id, d.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['shop_id']);
$stmt->execute();
$result = $stmt->get_result();


$sql = "SELECT b.*, u.name AS customer_name, u.phone AS customer_phone,
       bu.block_id
FROM bookings b
JOIN users u ON b.user_id = u.user_id
LEFT JOIN blocked_users bu ON b.user_id = bu.user_id AND b.shop_id = bu.shop_id
WHERE b.shop_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['shop_id']);
$stmt->execute();
$bookings = $stmt->get_result();


$shop_id = $_SESSION['shop_id'];

// ดึงข้อมูลร้าน
$stmt = $conn->prepare("SELECT shop_name, shop_image FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$shop = $result->fetch_assoc();
$stmt->close();

// เก็บชื่อร้านใน session
if (!isset($_SESSION['shop_name']) && $shop) {
    $_SESSION['shop_name'] = $shop['shop_name'];
}

// ดึงสถิติ
// บริการทั้งหมด
$totalServices = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM services WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $totalServices = $row['total'];
}
$stmt->close();

// pending
$pendingBookings = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE shop_id = ? AND status = 'pending'");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pendingBookings = $row['total'];
}
$stmt->close();

// accepted
$acceptedBookings = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE shop_id = ? AND status = 'accepted'");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $acceptedBookings = $row['total'];
}
$stmt->close();

// rejected
$rejectedBookings = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE shop_id = ? AND status = 'rejected'");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $rejectedBookings = $row['total'];
}
$stmt->close();

// completed
$completedBookings = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE shop_id = ? AND status = 'completed'");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $completedBookings = $row['total'];
}
$stmt->close();

// รับค่าจาก URL เพื่อกรองสถานะ
$status_filter = $_GET['status'] ?? 'pending';
$allowed_status = ['pending', 'accepted', 'rejected', 'completed', 'all'];
if (!in_array($status_filter, $allowed_status)) {
    $status_filter = 'pending';
}

// ดึง booking ตามสถานะ
if ($status_filter === 'all') {
    $sql = "SELECT b.booking_id, b.user_id, b.booking_date, b.booking_time, b.address, b.location_lat, b.location_lng,
               b.payment_slip, b.status, b.extra_fee, u.name AS customer_name, u.phone AS customer_phone,
               bu.block_id,
               SUM(s.price * d.quantity) AS total_price,
               GROUP_CONCAT(CONCAT(d.service_id, ':', s.service_name) SEPARATOR ', ') AS services
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        LEFT JOIN blocked_users bu ON b.user_id = bu.user_id AND b.shop_id = ?
        JOIN booking_details d ON b.booking_id = d.booking_id
        JOIN services s ON d.service_id = s.service_id
        WHERE b.shop_id = ? AND b.status = ?
        GROUP BY b.booking_id
        ORDER BY b.booking_date ASC, b.booking_time ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $shop_id, $shop_id);
} else {
    $sql = "SELECT b.booking_id, b.user_id, b.booking_date, b.booking_time, b.address, b.location_lat, b.location_lng,
               b.payment_slip, b.status, b.extra_fee, u.name AS customer_name, u.phone AS customer_phone,
               bu.block_id,
               SUM(s.price * d.quantity) AS total_price,
               GROUP_CONCAT(CONCAT(d.service_id, ':', s.service_name) SEPARATOR ', ') AS services
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        LEFT JOIN blocked_users bu ON b.user_id = bu.user_id AND b.shop_id = ?
        JOIN booking_details d ON b.booking_id = d.booking_id
        JOIN services s ON d.service_id = s.service_id
        WHERE b.shop_id = ? AND b.status = ?
        GROUP BY b.booking_id
        ORDER BY b.booking_date ASC, b.booking_time ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $shop_id, $shop_id, $status_filter);
}

$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();



// ดึงรายการบริการทั้งหมดของร้าน
$sql_services = "SELECT * FROM services WHERE shop_id = ?";
$stmt_services = $conn->prepare($sql_services);
$stmt_services->bind_param("i", $shop_id);
$stmt_services->execute();
$services_result = $stmt_services->get_result();
$stmt_services->close();

// ดึง booking ใหม่สำหรับแจ้งเตือน
$pendingBookingsList = [];
$stmt = $conn->prepare("SELECT b.booking_id, b.customer_name, b.customer_phone, b.created_at, b.is_new
                        FROM bookings b
                        WHERE b.status = 'pending' AND b.shop_id = ?
                        ORDER BY b.created_at DESC");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pendingBookingsList[] = $row;
    }
}
$stmt->close();

// แจ้งเตือน booking ใหม่
$newBookings = [];
if (!isset($_SESSION['notified_bookings'])) {
    $_SESSION['notified_bookings'] = [];
}
foreach ($pendingBookingsList as $b) {
    if ($b['is_new']==1 && !in_array($b['booking_id'], $_SESSION['notified_bookings'])) {
        $newBookings[] = $b;
        $_SESSION['notified_bookings'][] = $b['booking_id'];
    }
}


?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ร้านค้าของฉัน</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="shop_board.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    .booking-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #e74c3c;
        color: #fff;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: bold;
        z-index: 10000;
        box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        animation: fadeIn 0.5s ease-in-out, blink 1s infinite;
    }
    @keyframes fadeIn {
        from {opacity: 0; transform: translateY(-10px);}
        to {opacity: 1; transform: translateY(0);}
    }
    @keyframes blink {
        0%, 100% {opacity: 1;}
        50% {opacity: 0.4;}
    }
    /* modal */
    .modal {
        position: fixed;
        z-index: 1000;
        left:0;
        top:0;
        width:100%;
        height:100%;
        overflow:auto;
        background-color: rgba(0,0,0,0.4);
    }
    .modal-content {
        background-color:#fff;
        margin:10% auto;
        padding:20px;
        border:1px solid #888;
        width:80%;
        max-width:600px;
        border-radius:8px;
        position:relative;
    }
    .close {
        position:absolute;
        right:15px;
        top:10px;
        font-size:28px;
        font-weight:bold;
        cursor:pointer;
    }
    
/* ไอคอนแจ้งเตือน */
.booking-alert {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #e74c3c;
    color: #fff;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: bold;
    z-index: 9999;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
    animation: fadeIn 0.5s ease-in-out, blink 1s infinite;
}
@keyframes fadeIn {
    from {opacity: 0; transform: translateY(-10px);}
    to {opacity: 1; transform: translateY(0);}
}
@keyframes blink {
    0%, 100% {opacity:1;}
    50% {opacity:0.4;}
}
table {width:100%; border-collapse:collapse;}
th, td {border:1px solid #ccc; padding:8px; text-align:left;}
th {background:#f4f4f4;}
td i {margin-left:5px;}
</style>

</head>
<body>
<div class="main-content">
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> ร้านค้าของฉัน</h1>
            <?php if (isset($_SESSION['shop_name'])): ?>

                <?php if(count($new_bookings) > 0): ?>
<div class="new-shop-toast" id="new-shop-toast">
    ลูกค้าได้จองใหม่: <?= implode(", ", array_map(fn($b)=>htmlspecialchars($b['customer_name']), $new_bookings)); ?>
</div>
<?php endif; ?>
                <p class="welcome-text">ยินดีต้อนรับ, ร้าน **<?= htmlspecialchars($_SESSION['shop_name']) ?>**</p>
            <?php endif; ?>
            <a href="edit_shop.php" class="btn-primary" style="margin-top:20px; display:inline-block;"><i class="fas fa-edit"></i> แก้ไขข้อมูลร้าน</a>
            <div class="shop-image-box">
            <?php 
            $shop_image_path = 'uploads/' . $shop['shop_image'];
            if (!empty($shop['shop_image']) && file_exists($shop_image_path)): ?>
                <img src="<?= $shop_image_path ?>" alt="รูปภาพร้าน" class="shop-image">
            <?php else: ?>
                <img src="assets/default-shop.png" alt="ยังไม่มีรูปภาพร้าน" class="shop-image">
            <?php endif; ?>
            </div>
        </div>

        <div class="stats-grid">
            <a href="" class="stat-card">
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <div class="number"><?= $totalServices ?></div>
                <div class="label">บริการทั้งหมด</div>
            </a>
            <a href="?status=pending" class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="number"><?= $pendingBookings ?></div>
                <div class="label">รอการยืนยัน</div>
            </a>
            <a href="?status=accepted" class="stat-card">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="number"><?= $acceptedBookings ?></div>
                <div class="label">งานที่รับแล้ว</div>
            </a>
            <a href="?status=rejected" class="stat-card">
                <div class="icon"><i class="fas fa-times-circle"></i></div>
                <div class="number"><?= $rejectedBookings ?></div>
                <div class="label">ปฏิเสธ</div>
            </a>
            <a href="?status=completed" class="stat-card">
                <div class="icon"><i class="fas fa-star"></i></div>
                <div class="number"><?= $completedBookings ?></div>
                <div class="label">งานเสร็จสิ้น</div>
            </a>
        </div>

        <div class="section">
            <h3><i class="fas fa-calendar-check"></i> รายการจอง</h3>
            <div class="status-filter-menu">
                <a href="?status=pending" class="<?= $status_filter==='pending'?'active':'' ?>">รอดำเนินการ (<?= $pendingBookings ?>)</a>
                <a href="?status=accepted" class="<?= $status_filter==='accepted'?'active':'' ?>">รับงานแล้ว (<?= $acceptedBookings ?>)</a>
                <a href="?status=rejected" class="<?= $status_filter==='rejected'?'active':'' ?>">ปฏิเสธ (<?= $rejectedBookings ?>)</a>
                <a href="?status=completed" class="<?= $status_filter==='completed'?'active':'' ?>">เสร็จสิ้น (<?= $completedBookings ?>)</a>
            </div>

            <?php if ($bookings->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อลูกค้า</th>
                            <th>เบอร์</th>
                            <th>บริการ</th>
                            <th>วันเวลาจอง</th>
                            
                            <th>ที่อยู่</th>
                            <th>รูปหน้างาน</th>
                            <th>สถานะ</th>
                            <th>ค่าบริการ</th>
                            <th>การจัดการ</th>
                            <th>โลเคชั่น</th>
                            <th>อื่นๆ</th>
                           
                            

                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $bookings->fetch_assoc()): ?>
                        <tr>
                            
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['customer_phone']) ?></td>
                            <td>
                                <button type="button" class="btn" style="background:#007bff; color:#fff; font-weight:bold;" 
        onclick="showServices(<?= $row['booking_id'] ?>)">
    ดูรายการที่ลูกค้าจอง
</button>

<input type="hidden" id="services-<?= $row['booking_id'] ?>" value="<?= htmlspecialchars($row['services']) ?>">

                            </td>
                            <td><?= htmlspecialchars($row['booking_date']) . " " . htmlspecialchars($row['booking_time']) ?></td>
                            <td><?= htmlspecialchars($row['address']) ?></td>
                            <td>
                                <?php if (!empty($row['payment_slip'])): 
                                    $slip_path = (strpos($row['payment_slip'], 'uploads/') === 0) ? 
                                                  $row['payment_slip'] : 'uploads/slips/' . $row['payment_slip'];
                                ?>
                                    <a href="<?= htmlspecialchars($slip_path) ?>" target="_blank">ดูรูป</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
<?php 
    // รวมค่าบริการกับค่าพิเศษ/ค่าส่ง
    $total_with_extra = $row['total_price'];
    if (!empty($row['extra_fee']) && $row['extra_fee'] > 0) {
        $total_with_extra += $row['extra_fee'];
    }
    echo number_format($total_with_extra, 2) . " บาท";
?>
</td>

                            <td>
                                <?php if($row['status']==='pending'): ?>
    <!-- ปุ่มรับงาน -->
    <form method="POST" action="booking_action.php" style="display:inline-block;" onsubmit="return confirm('คุณต้องการยืนยันการรับงานใช่หรือไม่?');">
        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
        <button type="submit" class="btn btn-approve" name="action" value="approve">รับงาน</button>
    </form>

    <!-- ปุ่มปฏิเสธงาน -->
    <form method="POST" action="booking_action.php" style="display:inline-block;" onsubmit="return confirm('คุณต้องการปฏิเสธงานนี้ใช่หรือไม่?');">
        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
        <button type="submit" class="btn btn-reject" name="action" value="reject">ปฏิเสธ</button>
    </form>

    <!-- ปุ่มเสนอเวลาใหม่ -->
    <button class="btn btn-reject" onclick="showProposeModal(<?= $row['booking_id'] ?>)">เสนอเวลาใหม่</button>


                                    


                                <?php elseif($row['status']==='accepted'): ?>
                                    <form method="POST" action="booking_action.php" enctype="multipart/form-data" style="display:inline-block;" onsubmit="return confirm('คุณต้องการอัปโหลดรูปและยืนยันการจบงาน?');">
                                        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                                        <input type="file" name="completion_proof" accept="image/*" required style="display:block; margin-bottom:5px;">
                                        <button type="submit" class="btn btn-complete" name="action" value="complete">จบงาน</button>
                                    </form>
                                <?php elseif($row['status']==='rejected'): ?>
                                    <span style="color:#dc3545;">ปฏิเสธงานแล้ว</span>
                                <?php elseif($row['status']==='completed'): ?>
                                    <span style="color:#28a745;">งานเสร็จสิ้นแล้ว</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($row['location_lat']) && !empty($row['location_lng'])): ?>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['location_lat'] ?>,<?= $row['location_lng'] ?>" target="_blank" class="btn btn-map">ดูแผนที่</a>
                                <?php else: ?>-
                                <?php endif; ?>
                            </td>

                            <td>
    <form method="POST" action="toggle_block_user.php" style="display:inline-block;"
      onsubmit="return confirm('คุณต้องการเปลี่ยนสถานะบล็อกลูกค้าคนนี้ใช่หรือไม่?');">
    <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
    <button type="submit" 
            class="btn <?= isset($row['block_id']) ? 'btn btn-reject' : 'btn btn-reject' ?>">
        <?= isset($row['block_id']) ? 'ปลดบล็อก' : 'บล็อก' ?>
    </button>
</form>

</td>




                        </tr>

  



                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>ยังไม่มีการจองจากลูกค้าในสถานะนี้</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>รายการบริการของร้าน</h3>
            <div class="add-btn-container">
                <a href="add_service.php" class="add-btn"><i class="fas fa-plus-circle"></i> เพิ่มบริการใหม่</a>
            </div>
            <?php if($services_result->num_rows>0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อบริการ</th>
                            <th>รายละเอียด</th>
                            <th>ราคา</th>
                            <th>BTU</th>
                            <th>ชนิดแอร์</th>
                            <th>ภาพ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row=$services_result->fetch_assoc()): ?>
                        <tr>
    <td><?= htmlspecialchars($row['service_name']) ?></td>
    <td><?= htmlspecialchars($row['description']) ?></td>
    <td><?= number_format($row['price'], 2) ?> บาท</td>
    <td><?= isset($row['btu_range']) && $row['btu_range'] != '' ? htmlspecialchars($row['btu_range']) : '-' ?></td>
    <td><?= isset($row['air_type']) && $row['air_type'] != '' ? htmlspecialchars($row['air_type']) : '-' ?></td>
    <td>
        <?php if(!empty($row['image'])): ?>
            <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="ภาพบริการ" style="max-width:100px;">
        <?php else: ?>
            ไม่มีภาพ
        <?php endif; ?>
    </td>
    <td>
        <a href="edit_service.php?service_id=<?= $row['service_id'] ?>" class="btn btn-edit">แก้ไข</a>
        <a href="delete_service.php?service_id=<?= $row['service_id'] ?>" class="btn btn-delete" onclick="return confirm('ยืนยันการลบ?');">ลบ</a>
    </td>
</tr>

                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>ยังไม่มีบริการที่เพิ่มไว้ในร้านของคุณ</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal แสดงบริการที่ลูกค้าจอง -->
        <div id="serviceModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h3>รายละเอียดบริการที่ลูกค้าจอง</h3>
                <div id="serviceDetails"></div>
            </div>
        </div>

        <div class="section text-center">
            <a href="report.php" class="add-btn" style="background-color: #34495e;">
                <i class="fas fa-chart-line"></i> ดูรายงานรายได้
            </a>
        </div>

    </div>
</div>

<div id="proposeModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeProposeModal()">&times;</span>
        <h3>เสนอเวลาทำงานใหม่</h3>
        <form method="POST" action="propose_time.php">
            <input type="hidden" name="booking_id" id="propose_booking_id">
            <label>วันที่ใหม่:</label>
            <input type="date" name="proposed_date" required>
            <label>เวลาที่ใหม่:</label>
            <input type="time" name="proposed_time" required>
            <button type="submit" class="btn btn-primary">ส่งข้อเสนอ</button>
        </form>
    </div>
</div>



<!-- ตรงส่วน body -->
<div id="booking-toast" class="new-shop-toast"></div>
<audio id="bookingSound" src="assets/notify.mp3" preload="auto"></audio>

<audio id="notificationSound" src="assets/notify.mp3" preload="auto"></audio>

<script>
let lastPendingCount = 0; // เริ่มต้นเป็น 0
checkNewBookings(); // ตรวจสอบทันทีเมื่อโหลดหน้า

// ตรวจสอบทุก 10 วินาที
setInterval(checkNewBookings, 10000);

function checkNewBookings(){
    fetch("check_new_bookings.php")
        .then(res => res.json())
        .then(data => {
            if(!data.pending) return;
            let newPendingCount = data.pending;
            if(newPendingCount > 0){
                showBookingNotification(newPendingCount);
                document.getElementById("notificationSound").play();
                lastPendingCount = newPendingCount;
            }
        })
        .catch(err => console.error(err));
}


function showBookingNotification(diff){
    let alertBox = document.createElement("div");
    alertBox.className = "booking-alert";
    alertBox.style = "position:fixed; top:20px; right:20px; background:#dc3545; color:#fff; padding:15px 20px; border-radius:8px; z-index:9999; font-weight:bold;";
    alertBox.innerHTML = `<i class="fas fa-bell"></i> มีการจองใหม่ ${diff} รายการ!`;
    document.body.appendChild(alertBox);
    setTimeout(()=> alertBox.remove(), 5000);
}

function showProposeModal(bookingId){
    document.getElementById('propose_booking_id').value = bookingId;
    document.getElementById('proposeModal').style.display = 'block';
}

function closeProposeModal(){
    document.getElementById('proposeModal').style.display = 'none';
}

// ปิด modal เมื่อคลิกนอก modal
window.onclick = function(event){
    if(event.target == document.getElementById('proposeModal')){
        closeProposeModal();
    }
}




function closeModal() {
    document.getElementById('serviceModal').style.display = 'none';
}



</script>


<script>
function showServices(bookingId) {
    fetch("get_booking_services.php?booking_id=" + bookingId)
        .then(response => response.text())
        .then(data => {
            document.getElementById("serviceDetails").innerHTML = data;
            document.getElementById("serviceModal").style.display = "block";
        })
        .catch(err => {
            document.getElementById("serviceDetails").innerHTML = "เกิดข้อผิดพลาด: " + err;
            document.getElementById("serviceModal").style.display = "block";
        });
}

function closeModal() {
    document.getElementById("serviceModal").style.display = "none";
}
</script>




</body>
</html>
