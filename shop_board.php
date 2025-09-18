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

$shop_id = $_SESSION['shop_id']; // ร้านที่ล็อกอิน

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

// ดึงสถิติการจอง
$statuses = ['pending', 'accepted', 'rejected', 'completed'];
$stats = [];
foreach ($statuses as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE shop_id = ? AND status = ?");
    $stmt->bind_param("is", $shop_id, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats[$status] = $row['total'] ?? 0;
    $stmt->close();
}

// บริการทั้งหมด
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM services WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalServices = $row['total'] ?? 0;
$stmt->close();

// ดึง booking ใหม่สำหรับแจ้งเตือน (is_new=1)
$new_bookings = [];
$stmt = $conn->prepare("SELECT booking_id, customer_name FROM bookings WHERE shop_id = ? AND status='pending' AND is_new=1 ORDER BY created_at DESC");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $new_bookings[] = $row;
}
$stmt->close();

// อัปเดตสถานะ is_new = 0
if (count($new_bookings) > 0) {
    $booking_ids = array_column($new_bookings, 'booking_id');
    $ids_str = implode(",", $booking_ids);
    $conn->query("UPDATE bookings SET is_new=0 WHERE booking_id IN ($ids_str)");
}

// รับค่าจาก URL เพื่อกรองสถานะ
$status_filter = $_GET['status'] ?? 'pending';
$allowed_status = array_merge($statuses, ['all']);
if (!in_array($status_filter, $allowed_status)) $status_filter = 'pending';

// ดึง booking ตามสถานะ
if ($status_filter === 'all') {
    $sql = "SELECT b.booking_id, b.user_id, b.booking_date, b.booking_time, b.address, b.location_lat, b.location_lng,
               b.site_photos, b.status, b.extra_fee, u.name AS customer_name, u.phone AS customer_phone,
               bu.block_id,
               b.proposed_date, b.proposed_time, b.proposal_status,
               SUM(s.price * d.quantity) AS total_price,
               GROUP_CONCAT(CONCAT(d.service_id, ':', s.service_name) SEPARATOR ', ') AS services
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        LEFT JOIN blocked_users bu ON b.user_id = bu.user_id AND b.shop_id = ?
        JOIN booking_details d ON b.booking_id = d.booking_id
        JOIN services s ON d.service_id = s.service_id
        WHERE b.shop_id = ?
        GROUP BY b.booking_id
        ORDER BY b.booking_date ASC, b.booking_time ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $shop_id, $shop_id);
} else {
    $sql = "SELECT b.booking_id, b.user_id, b.booking_date, b.booking_time, b.address, b.location_lat, b.location_lng,
               b.site_photos, b.status, b.extra_fee, u.name AS customer_name, u.phone AS customer_phone,
               bu.block_id,
               b.proposed_date, b.proposed_time, b.proposal_status,
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
/* toast popup */
.new-shop-toast, .booking-alert {
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
@keyframes fadeIn { from {opacity:0; transform: translateY(-10px);} to {opacity:1; transform: translateY(0);} }
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.4;} }

table {width:100%; border-collapse:collapse;}
th, td {border:1px solid #ccc; padding:8px; text-align:left;}
th {background:#f4f4f4;}
</style>
</head>
<body>

<div class="main-content">
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> ร้านค้าของฉัน</h1>
            <?php if(isset($_SESSION['shop_name'])): ?>
                <p class="welcome-text">ยินดีต้อนรับ, ร้าน **<?= htmlspecialchars($_SESSION['shop_name']) ?>**</p>
                <?php if(count($new_bookings) > 0): ?>
                    <div class="new-shop-toast">
                        ลูกค้าได้จองใหม่: <?= implode(", ", array_map(fn($b)=>htmlspecialchars($b['customer_name']), $new_bookings)); ?>
                    </div>
                <?php endif; ?>
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

        <!-- Stats Grid -->
        <div class="stats-grid">
            <a href="" class="stat-card">
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <div class="number"><?= $totalServices ?></div>
                <div class="label">บริการทั้งหมด</div>
            </a>
            <?php foreach($statuses as $st): ?>
            <a href="?status=<?= $st ?>" class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="number"><?= $stats[$st] ?></div>
                <div class="label"><?= ucfirst($st) ?></div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Booking Table -->
        <div class="section">
            <h3><i class="fas fa-calendar-check"></i> รายการจอง</h3>
            <?php if($bookings->num_rows > 0): ?>
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
                            <th>รายงาน</th>

                        </tr>
                    </thead>
                    <tbody>
<?php while($row = $bookings->fetch_assoc()): ?>
<tr>
    <!-- ชื่อลูกค้า + badge NEW -->
    <td>
        <?= htmlspecialchars($row['customer_name']) ?>
        <?php if(in_array($row['booking_id'], array_column($new_bookings, 'booking_id'))): ?>
            <span style="color:#fff; background:#dc3545; padding:2px 5px; border-radius:3px; font-size:12px; margin-left:6px;">NEW</span>
        <?php endif; ?>
    </td>

    <!-- เบอร์ -->
    <td><?= htmlspecialchars($row['customer_phone']) ?></td>

    <!-- ปุ่มดูรายการบริการ (ซ่อนข้อมูลใน hidden input) -->
    <td>
        <button type="button" class="btn" style="background:#007bff; color:#fff; font-weight:bold;"
                onclick="showServices(<?= $row['booking_id'] ?>)">
            ดูรายการที่ลูกค้าจอง
        </button>
        <input type="hidden" id="services-<?= $row['booking_id'] ?>" value="<?= htmlspecialchars($row['services']) ?>">
    </td>

    <!-- วันเวลา -->
    <td><?= htmlspecialchars($row['booking_date']) . " " . htmlspecialchars($row['booking_time']) ?></td>
    

    <!-- ที่อยู่ -->
    <td><?= htmlspecialchars($row['address']) ?></td>

    <!-- รูปหน้างาน -->
    <td>
        <?php if(!empty($row['site_photos'])):
            $slip_path = (strpos($row['site_photos'], 'uploads/') === 0) ? $row['site_photos'] : 'uploads/slips/'.$row['site_photos'];
        ?>
            <a href="<?= htmlspecialchars($slip_path) ?>" target="_blank">ดูรูป</a>
        <?php else: ?>
            -
        <?php endif; ?>
    </td>

    <!-- สถานะ -->
    <td><?= status_th($row['status']) ?></td>

    <!-- ค่าบริการ -->
    <td>
        <?php
            $total_with_extra = $row['total_price'];
            if (!empty($row['extra_fee'])) $total_with_extra += $row['extra_fee'];
            echo number_format($total_with_extra, 2) . " บาท";
        ?>
    </td>

    
    <td>
<?php 
if($row['status'] === 'pending'): ?>
    <form method="POST" action="booking_action.php" style="display:inline-block;">
        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
        <button type="submit" class="btn btn-approve" name="action" value="approve">รับงาน</button>
    </form>
    <form method="POST" action="booking_action.php" style="display:inline-block;">
        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
        <button type="submit" class="btn btn-reject" name="action" value="reject">ปฏิเสธ</button>
    </form>
    <button class="btn btn-reject" onclick="showProposeModal(<?= $row['booking_id'] ?>)">เสนอเวลาใหม่</button>
<?php elseif($row['status'] === 'accepted'): ?>
    <form method="POST" action="booking_action.php" enctype="multipart/form-data" style="display:inline-block;">
        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
        <input type="file" name="completion_proof" accept="image/*" required>
        <button type="submit" class="btn btn-complete" name="action" value="complete">จบงาน</button>
    </form>
   
<?php elseif($row['status'] === 'rejected'): ?>
    <span style="color:#dc3545;">ปฏิเสธงานแล้ว</span>
<?php elseif($row['status'] === 'completed'): ?>
    <span style="color:#28a745;">งานเสร็จสิ้นแล้ว</span>
<?php endif; ?>
</td>


    <!-- โลเคชั่น -->
    <td>
        <?php if(!empty($row['location_lat']) && !empty($row['location_lng'])): ?>
            <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['location_lat'] ?>,<?= $row['location_lng'] ?>" target="_blank" class="btn btn-map">ดูแผนที่</a>
        <?php else: ?>-
        <?php endif; ?>
    </td>
<td>
    <!-- วางโค้ดนี้ตรงนี้ -->
    <?php if(!empty($row['proposed_date']) && $row['proposal_status'] === 'pending'): ?>
        <button type="button" style="padding:8px 16px; background:#ff9800; color:white; border:none; border-radius:5px; cursor:pointer; margin-top:5px;"
            onclick="openCustomerProposalModal(
                <?= $row['booking_id'] ?>,
                '<?= $row['proposed_date'] ?>',
                '<?= $row['proposed_time'] ?>',
                '<?= addslashes($row['services']) ?>',
                '<?= $row['booking_date'] ?>',
                '<?= $row['booking_time'] ?>'
            )">
            ลูกค้าขอเปลี่ยนเวลาเป็น: <?= $row['proposed_date'] ?> <?= $row['proposed_time'] ?>
        </button>
    <?php endif; ?>
    <!-- ถ้ามีปุ่มรายงานเดิมก็วางต่อท้ายได้ -->
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

        <!-- รายการบริการของร้าน -->
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
                            <td><?= number_format($row['price'],2) ?> บาท</td>
                            <td><?= $row['btu_range'] ?? '-' ?></td>
                            <td><?= $row['air_type'] ?? '-' ?></td>
                            <td>
                                <?php if(!empty($row['image'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="ภาพบริการ" style="max-width:100px;">
                                <?php else: ?>ไม่มีภาพ<?php endif; ?>
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
                <p>ยังไม่มีบริการที่เพิ่มไว้</p>
            <?php endif; ?>
        </div>

        <!-- Modal แสดงบริการ -->
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
<form method="POST" action="propose_time.php" class="propose-form">
    <h3>เสนอวัน-เวลาใหม่</h3>
    <input type="hidden" name="booking_id" id="propose_booking_id">
    
    <label>วันที่ใหม่:</label>
    <input type="date" name="proposed_date" required min="<?= date('Y-m-d') ?>">

    <label>เวลาที่ใหม่:</label>
    <input type="time" name="proposed_time" required>

    <button type="submit" class="btn btn-approve">ส่งข้อเสนอ</button>
</form>
    </div>
</div>

<div id="customerProposalModal" style="display:none; position:fixed; z-index:10000; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center;">
    <div style="background:white; padding:20px; border-radius:10px; max-width:420px; text-align:center; position:relative;">
        <span onclick="closeCustomerProposalModal()" style="position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px;">&times;</span>
        <h3 style="color:#ff9800;">ลูกค้าขอเปลี่ยนเวลาการให้บริการ</h3>
        <div id="customerProposalDetail" style="margin:15px 0; color:#333; font-size:1.05em;"></div>
        <form id="customerProposalForm" method="POST" action="respond_customer_propose.php">
            <input type="hidden" name="booking_id" id="customerProposalBookingId">
            <input type="hidden" name="action" id="customerProposalAction">
            <button type="button" onclick="respondCustomerProposal('accept')" style="padding:10px 20px; margin:5px; background:#28a745; color:white; border:none; border-radius:5px;">ยอมรับ</button>
            <button type="button" onclick="respondCustomerProposal('reject')" style="padding:10px 20px; margin:5px; background:#c0392b; color:white; border:none; border-radius:5px;">ปฏิเสธ</button>
        </form>
    </div>
</div>
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

function openCustomerProposalModal(booking_id, new_date, new_time, service_name, old_date, old_time) {
    document.getElementById('customerProposalBookingId').value = booking_id;
    let detail = `
        <strong>ลูกค้าขอเปลี่ยนเวลาการให้บริการ</strong><br>
        <span style="color:#1976d2;">บริการ:</span> ${service_name}<br>
        <span style="color:#1976d2;">วัน-เวลาเดิม:</span> ${old_date} ${old_time}<br>
        <span style="color:#e67e22;">เปลี่ยนเป็น:</span> ${new_date} ${new_time}<br>
        <br>
        คุณต้องการยอมรับเวลาที่ลูกค้าเสนอหรือไม่?
    `;
    document.getElementById('customerProposalDetail').innerHTML = detail;
    document.getElementById('customerProposalModal').style.display = 'flex';
}
function closeCustomerProposalModal(){
    document.getElementById('customerProposalModal').style.display = 'none';
}
function respondCustomerProposal(action){
    document.getElementById('customerProposalAction').value = action;
    document.getElementById('customerProposalForm').submit();
}


</script>

<script>
// เก็บ ID ของข้อเสนอที่แสดงแล้ว เพื่อไม่ให้เด้งซ้ำ
let shownProposals = [];

// ตรวจสอบทุก 10 วินาที
setInterval(checkCustomerProposals, 10000);
checkCustomerProposals(); // ตรวจสอบทันทีเมื่อโหลดหน้า

function checkCustomerProposals() {
    fetch("check_customer_proposals.php")
        .then(res => res.json())
        .then(data => {
            if(!data.new_proposals) return;
            data.new_proposals.forEach(p => {
                if(!shownProposals.includes(p.booking_id)){
                    // เปิด modal
                    openCustomerProposalModal(
                        p.booking_id,
                        p.proposed_date,
                        p.proposed_time,
                        p.services,
                        p.booking_date,
                        p.booking_time
                    );
                    shownProposals.push(p.booking_id);
                }
            });
        })
        .catch(err => console.error(err));
}

function openCustomerProposalModal(booking_id, new_date, new_time, service_name, old_date, old_time) {
    document.getElementById('customerProposalBookingId').value = booking_id;
    let detail = `
        <strong>ลูกค้าขอเปลี่ยนเวลาการให้บริการ</strong><br>
        <span style="color:#1976d2;">บริการ:</span> ${service_name}<br>
        <span style="color:#1976d2;">วัน-เวลาเดิม:</span> ${old_date} ${old_time}<br>
        <span style="color:#e67e22;">เปลี่ยนเป็น:</span> ${new_date} ${new_time}<br><br>
        คุณต้องการยอมรับเวลาที่ลูกค้าเสนอหรือไม่?
    `;
    document.getElementById('customerProposalDetail').innerHTML = detail;
    document.getElementById('customerProposalModal').style.display = 'flex';
}

function closeCustomerProposalModal(){
    document.getElementById('customerProposalModal').style.display = 'none';
}

function respondCustomerProposal(action){
    document.getElementById('customerProposalAction').value = action;
    document.getElementById('customerProposalForm').submit();
}

// ปิด modal เมื่อคลิกนอก modal
window.onclick = function(event){
    const modal = document.getElementById('customerProposalModal');
    if(event.target == modal){
        closeCustomerProposalModal();
    }
}
</script>

<?php
function status_th($status) {
    switch($status) {
        case 'pending': return 'รอการตอบรับ';
        case 'accepted': return 'ร้านค้ารับงานแล้ว';
        case 'rejected': return 'ปฏิเสธงาน';
        case 'completed': return 'งานเสร็จสิ้น';
        default: return $status;
    }
}
?>


</body>
</html>
