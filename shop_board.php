<?php
session_start();
include 'config.php';

// เปิด error ตอนพัฒนา
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['shop_id']) || $_SESSION['role'] !== 'shop') {
    header("Location: login.html");
    exit();
}

$shop_id = $_SESSION['shop_id'];

// ดึงข้อมูลร้านจากฐานข้อมูล
$stmt = $conn->prepare("SELECT shop_name, shop_image FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$shop = $result->fetch_assoc();
$stmt->close();


// เซ็ตชื่อร้านใน session ถ้ายังไม่มี
if (!isset($_SESSION['shop_name']) && $shop) {
    $_SESSION['shop_name'] = $shop['shop_name'];
}

// ดึงข้อมูลสถิติ
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
$allowed_status = ['pending', 'accepted', 'rejected', 'completed'];
if (!in_array($status_filter, $allowed_status)) {
    $status_filter = 'pending';
}

// ดึงข้อมูลจองตามสถานะ
$sql = "SELECT b.*, u.name AS customer_name, u.phone AS customer_phone, s.service_name , s.service_type
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN services s ON b.service_id = s.service_id
        WHERE b.shop_id = ? AND b.status = ?
        ORDER BY b.booking_date ASC, b.booking_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $shop_id, $status_filter);
$stmt->execute();
$bookings = $stmt->get_result();

// ดึงรายการบริการทั้งหมด
$sql_services = "SELECT * FROM services WHERE shop_id = ?";
$stmt_services = $conn->prepare($sql_services);
$stmt_services->bind_param("i", $shop_id);
$stmt_services->execute();
$services_result = $stmt_services->get_result();
$stmt_services->close();
?>


<style>.shop-image-box {
    width: 120px;
    height: 120px;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid #ddd;
    background: #f9f9f9;
}

.shop-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ร้านค้าของฉัน</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="shop_board.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
<div class="main-content">

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> ร้านค้าของฉัน</h1>
            <?php if (isset($_SESSION['shop_name'])): ?>
                <p class="welcome-text">ยินดีต้อนรับ, ร้าน **<?= htmlspecialchars($_SESSION['shop_name']) ?>**</p>
            <?php endif; ?>

            <div class="shop-image-box">
        <?php if (!empty($shop['shop_image'])): ?>
            <img src="uploads/shop_images/<?= htmlspecialchars($shop['shop_image']) ?>" 
     alt="รูปภาพร้าน"  class="shop-image">
        <?php else: ?>
            <img src="assets/default-shop.png" alt="ยังไม่มีรูปภาพร้าน" class="shop-image">
        <?php endif; ?>
    </div>
        </div>
    



        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <div class="number"><?= $totalServices ?></div>
                <div class="label">บริการทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="number"><?= $pendingBookings ?></div>
                <div class="label">รอการยืนยัน</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="number"><?= $acceptedBookings ?></div>
                <div class="label">งานที่รับแล้ว</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-star"></i></div>
                <div class="number"><?= $completedBookings ?></div>
                <div class="label">งานเสร็จสิ้น</div>
            </div>
        </div>

        <div class="section">
            <h3>รายการบริการของร้าน</h3>
            <div class="add-btn-container">
                <a href="add_service.php" class="add-btn">
                    <i class="fas fa-plus-circle"></i> เพิ่มบริการใหม่
                </a>
            </div>
            <?php if ($services_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อบริการ</th>
                            <th>รายละเอียด</th>
                            <th>ราคา</th>
                            <th>ภาพ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $services_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['service_name']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td><?= number_format($row['price'], 2) ?> บาท</td>
                            <td>
                                <?php if (!empty($row['image'])): ?>
                                    <img src='uploads/<?= htmlspecialchars($row['image']) ?>' alt='ภาพบริการ'>
                                <?php else: ?>
                                    ไม่มีภาพ
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href='edit_service.php?service_id=<?= htmlspecialchars($row['service_id']) ?>' class='btn btn-edit'>แก้ไข</a>
                                <a href='delete_service.php?service_id=<?= htmlspecialchars($row['service_id']) ?>' class='btn btn-delete' onclick='return confirm("ยืนยันการลบ?");'>ลบ</a>
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

        <div class="section">
            <h3><i class="fas fa-calendar-check"></i> รายการจอง</h3>
            <div class="status-filter-menu">
                <a href="?status=pending" class="<?= $status_filter==='pending'?'active':'' ?>">รอดำเนินการ (<?= $pendingBookings ?>)</a>
                <a href="?status=accepted" class="<?= $status_filter==='accepted'?'active':'' ?>">รับงานแล้ว (<?= $acceptedBookings ?>)</a>
                <a href="?status=rejected" class="<?= $status_filter==='rejected'?'active':'' ?>">ปฏิเสธ (<?= $status_counts['rejected'] ?? 0 ?>)</a>
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
                            <th>การจัดการ</th>
                            <th>โลเคชั่น</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['customer_phone']) ?></td>
                            <td><?= htmlspecialchars($row['service_name']) ?></td>
                            <td><?= htmlspecialchars($row['booking_date']) . " " . htmlspecialchars($row['booking_time']) ?></td>
                            <td><?= htmlspecialchars($row['address']) ?></td>
                            <td>
                                <?php if (!empty($row['payment_slip'])): ?>
                                    <?php
                                        // ป้องกันปัญหา path ซ้ำซ้อน
                                        $slip_path = (strpos($row['payment_slip'], 'uploads/') === 0) ? $row['payment_slip'] : 'uploads/slips/' . $row['payment_slip'];
                                    ?>
                                    <a href='<?= htmlspecialchars($slip_path) ?>' target='_blank'>ดูรูป</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
    <form method='POST' action='booking_action.php' style='display:inline-block;' 
          onsubmit="return confirm('คุณต้องการยืนยันการรับงานใช่หรือไม่?');">
        <input type='hidden' name='booking_id' value='<?= htmlspecialchars($row['booking_id']) ?>'>
        <button type='submit' class='btn btn-approve' name='action' value='approve'>รับงาน</button>
    </form>

    <form method='POST' action='booking_action.php' style='display:inline-block;' 
          onsubmit="return confirm('คุณต้องการปฏิเสธงานนี้ใช่หรือไม่?');">
        <input type='hidden' name='booking_id' value='<?= htmlspecialchars($row['booking_id']) ?>'>
        <button type='submit' class='btn btn-reject' name='action' value='reject'>ปฏิเสธ</button>
    </form>
<?php elseif ($row['status'] === 'accepted'): ?>
    <form method="POST" action="booking_action.php" enctype="multipart/form-data" 
          onsubmit="return confirm('อัพโหลดรูปหลักฐานและยืนยันการจบงาน?');" 
          style="display:inline-block;">
        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($row['booking_id']) ?>">
        <input type="file" name="completion_proof" accept="image/*" required style="margin-bottom:5px; display:block;">
        <button type="submit" class="btn btn-complete" name="action" value="complete">จบงาน</button>
    </form>
<?php elseif ($row['status'] === 'rejected'): ?>
    <span style="color:#dc3545;">ปฏิเสธงานแล้ว</span>
<?php elseif ($row['status'] === 'completed'): ?>
    <span style="color:#28a745;">งานเสร็จสิ้นแล้ว</span>



                                    </form>
                                <?php elseif ($row['status'] === 'rejected'): ?>
                                    <span style="color:#dc3545;">ปฏิเสธงานแล้ว</span>
                                <?php elseif ($row['status'] === 'completed'): ?>
                                    <span style="color:#28a745;">งานเสร็จสิ้นแล้ว</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['location_lat']) && !empty($row['location_lng'])): ?>
                                    <a href='https://www.google.com/maps/search/?api=1&query=<?= htmlspecialchars($row['location_lat']) ?>,<?= htmlspecialchars($row['location_lng']) ?>' target='_blank' class='btn btn-map'>ดูแผนที่</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
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
        
        <div class="section text-center">
            <a href="report.php" class="add-btn" style="background-color: #34495e;">
                <i class="fas fa-chart-line"></i> ดูรายงานรายได้
            </a>
        </div>
    </div>

</body>
</html>