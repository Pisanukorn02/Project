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
    $search_sql = "WHERE b.customer_name LIKE ? OR b.customer_phone LIKE ? OR b.address LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

// ดึงข้อมูล bookings พร้อม join services และ shops
$sql = "SELECT b.*, 
               s.shop_name, s.address AS shop_address, s.province AS shop_province, s.latitude AS shop_latitude, s.longitude AS shop_longitude, s.phone AS shop_phone, s.shop_image,
               sv.service_name, sv.description AS service_desc, sv.price AS service_price, sv.image AS service_image
        FROM bookings b
        LEFT JOIN shops s ON b.shop_id = s.shop_id
        LEFT JOIN services sv ON b.service_id = sv.service_id
        $search_sql
        ORDER BY b.booking_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการการจองลูกค้า</title>
<link rel="stylesheet" href="http://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
body { font-family:'Segoe UI', sans-serif; background:#f4f6f8; padding:20px; }
h2 { color:#34495e; }
a.btn { 
    text-decoration:none; 
    display:inline-block; 
    padding:8px 15px; 
    background:#1976d2; color:#fff; border-radius:5px; margin-bottom:15px; 
}
form.search-form { margin-bottom:15px; }
form.search-form input[type=text] { padding:6px; width:250px; border:1px solid #ccc; border-radius:4px; }
form.search-form button { padding:6px 12px; border:none; border-radius:4px; background:#1976d2; color:#fff; cursor:pointer; }

table { width:100%; border-collapse:collapse; background:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
th, td { padding:10px; border-bottom:1px solid #ddd; text-align:left; vertical-align:middle; }
th { background:#1976d2; color:#fff; }
button.action-btn { padding:5px 10px; border:none; border-radius:4px; cursor:pointer; }

.show-modal-btn { background:#16a085; color:#fff; }

.modal {
    display:none;
    position:fixed;
    z-index:9999;
    left:0; top:0;
    width:100%; height:100%;
    overflow:auto;
    background:rgba(0,0,0,0.5);
}
.modal-content {
    background:#fff;
    margin:50px auto;
    padding:20px;
    border-radius:8px;
    width:80%;
    max-width:900px;
    position:relative;
}
.modal-close {
    position:absolute;
    top:10px; right:15px;
    font-size:22px; font-weight:bold;
    cursor:pointer;
    color:#555;
}
.modal-content h3 { margin-top:0; color:#1976d2; }
.map-container { height:250px; width:100%; margin-top:10px; border-radius:8px; }
img.detail-img { max-width:150px; max-height:150px; border-radius:5px; margin-right:5px; }
button.update-status { background:#f39c12; color:#fff; margin-top:10px; }
button.delete-booking { background:#e74c3c; color:#fff; margin-top:10px; }

/* --- General Modal Styles --- */
.modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1050;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow-y: auto;
    background-color: rgba(0, 0, 0, 0.5);
    transition: opacity 0.3s ease;
}

.modal-content {
    background-color: #f8f9fa;
    margin: 5% auto;
    padding: 20px 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    font-family: 'Sarabun', sans-serif; /* Recommended for Thai fonts */
}

.modal-header h3 {
    margin-top: 0;
    color: #333;
    font-size: 1.8em;
    border-bottom: 2px solid #eee;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

/* --- Detail Section (Fieldset) Styling --- */
.detail-section {
    margin-bottom: 25px;
    padding: 15px 20px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #ffffff;
}

.detail-section legend {
    font-weight: 600;
    font-size: 1.3em;
    color: #0056b3;
    padding: 0 10px;
    margin-left: 5px;
}

.detail-section p {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    margin: 0;
    border-bottom: 1px solid #f0f0f0;
    line-height: 1.6;
}

.detail-section p:last-child {
    border-bottom: none;
}

.detail-section p strong {
    color: #555;
    white-space: nowrap;
    margin-right: 20px;
}

.detail-section p span {
    color: #222;
    text-align: right;
}

/* --- Status Badge --- */
.status-badge {
    padding: 4px 12px;
    border-radius: 15px;
    font-weight: 500;
    color: white;
}

/* Add classes for different statuses */
.status-pending { background-color: #ffc107; color: #333; }
.status-confirmed { background-color: #17a2b8; }
.status-completed { background-color: #28a745; }
.status-cancelled { background-color: #dc3545; }

/* --- Image Styling --- */
.detail-img {
    max-width: 150px; /* Adjust size as needed */
    height: auto;
    border-radius: 8px;
    border: 1px solid #ddd;
    margin-top: 8px;
    cursor: pointer;
    transition: transform 0.2s;
}

.detail-img:hover {
    transform: scale(1.05);
}

/* --- Map Container --- */
.map-container {
    height: 350px;
    width: 100%;
    margin-top: 15px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

/* --- Responsive for Mobile --- */
@media (max-width: 600px) {
    .modal-content {
        padding: 15px;
    }
    .detail-section p {
        flex-direction: column;
        align-items: flex-start;
        border-bottom: 1px solid #e0e0e0;
    }
    .detail-section p span {
        text-align: left;
        margin-top: 4px;
    }
}
</style>
</head>
<body>

<h2>จัดการการจองลูกค้า</h2>
<p>
<a href="admin_dashboard.php" class="btn">กลับหน้าหลัก</a> 
<a href="admin_logout.php" class="btn">ออกจากระบบ</a>
</p>

<form method="GET" class="search-form">
<input type="text" name="search" placeholder="ค้นหาชื่อลูกค้า, เบอร์, ที่อยู่" value="<?= htmlspecialchars($search) ?>">
<button type="submit">ค้นหา</button>
</form>

<?php if(empty($bookings)): ?>
<p>ไม่พบการจองลูกค้า</p>
<?php else: ?>
<table>
<thead>
<tr>
<th>ID</th>
<th>ลูกค้า</th>
<th>เบอร์</th>
<th>บริการ</th>
<th>ร้านค้า</th>
<th>วันเวลา</th>
<th>สถานะ</th>
<th>จัดการ</th>
</tr>
</thead>
<tbody>
<?php foreach($bookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['booking_id']); ?></td>
<td><?= htmlspecialchars($b['customer_name']); ?></td>
<td><?= htmlspecialchars($b['customer_phone']); ?></td>
<td><?= htmlspecialchars($b['service_name']); ?></td>
<td><?= htmlspecialchars($b['shop_name']); ?></td>
<td><?= htmlspecialchars($b['booking_date'])." ".htmlspecialchars($b['booking_time']); ?></td>
<td><?= htmlspecialchars($b['status']); ?></td>
<td>
<button class="show-modal-btn" data-id="<?= $b['booking_id'] ?>">ดูรายละเอียด</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- Modal Template -->
<div id="booking-modal" class="modal">
<div class="modal-content">
<span class="modal-close">&times;</span>
<div id="modal-body"></div>
</div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const modal = document.getElementById('booking-modal');
const modalBody = document.getElementById('modal-body');
const closeBtn = document.querySelector('.modal-close');

closeBtn.onclick = () => { modal.style.display = 'none'; modalBody.innerHTML = ''; }
window.onclick = (e) => { if (e.target == modal) { modal.style.display = 'none'; modalBody.innerHTML = ''; } }

const bookings = <?= json_encode($bookings) ?>;

// ฟังก์ชันสำหรับสร้าง Status Badge (เผื่อต้องใช้ logic เพิ่มเติม)
function createStatusBadge(status) {
    // แปลง status text เป็น class (ตัวอย่าง)
    const statusClass = status.toLowerCase().replace(' ', '-');
    return `<span class="status-badge status-${statusClass}">${status}</span>`;
}

document.querySelectorAll('.show-modal-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const bookingId = this.dataset.id;
        const booking = bookings.find(b => b.booking_id == bookingId);
        if (!booking) return;

        let html = `<div class="modal-header"><h3>รายละเอียดการจอง #${booking.booking_id}</h3></div>`;

        // ข้อมูลลูกค้า
        html += `<fieldset class="detail-section">
                    <legend>ข้อมูลลูกค้า</legend>
                    <p><strong>ชื่อ:</strong> <span>${booking.customer_name}</span></p>
                    <p><strong>เบอร์:</strong> <span>${booking.customer_phone}</span></p>
                    <p><strong>ที่อยู่:</strong> <span>${booking.address}</span></p>
                    <p><strong>จำนวน:</strong> <span>${booking.quantity}</span></p>
                    <p><strong>วันเวลานัด:</strong> <span>${booking.booking_date} ${booking.booking_time}</span></p>
                    <p><strong>สถานะ:</strong> ${createStatusBadge(booking.status)}</p>
                    <p><strong>วันที่สร้าง:</strong> <span>${booking.created_at}</span></p>
                    ${booking.payment_slip ? `<p><strong>รูปสลิป:</strong> <a href="${booking.payment_slip}" target="_blank"><img src="${booking.payment_slip}" class="detail-img"></a></p>` : ''}
                 </fieldset>`;

        // ข้อมูลร้านค้า / บริการ
        html += `<fieldset class="detail-section">
                    <legend>ข้อมูลร้านค้า / บริการ</legend>
                    <p><strong>ร้านค้า:</strong> <span>${booking.shop_name}</span></p>
                    <p><strong>ที่อยู่ร้าน:</strong> <span>${booking.shop_address}, ${booking.shop_province}</span></p>
                    <p><strong>เบอร์ร้าน:</strong> <span>${booking.shop_phone}</span></p>
                    ${booking.shop_image ? `<p><strong>รูปหน้าร้าน:</strong> <img src="uploads/shop_images/${booking.shop_image}" class="detail-img"></p>` : ''}
                    <p><strong>บริการ:</strong> <span>${booking.service_name} (${booking.service_desc})</span></p>
                    <p><strong>ราคา:</strong> <span>${Number(booking.service_price).toLocaleString()} บาท</span></p>
                    ${booking.service_image ? `<p><strong>รูปบริการ:</strong> <img src="uploads/${booking.service_image}" class="detail-img"></p>` : ''}
                    ${booking.complete_image ? `<p><strong>รูปจบงาน:</strong> <a href="uploads/completions/${booking.complete_image}" target="_blank"><img src="uploads/completions/${booking.complete_image}" class="detail-img"></a></p>` : ''}
                 </fieldset>`;

        // แผนที่สองจุด
        if (booking.location_lat && booking.location_lng && booking.shop_latitude && booking.shop_longitude) {
            html += `<div id="modal-map-${booking.booking_id}" class="map-container"></div>`;
        }

        modalBody.innerHTML = html;
        modal.style.display = 'block';

        // แผนที่สองจุด (ส่วนนี้เหมือนเดิม)
        if (booking.location_lat && booking.location_lng && booking.shop_latitude && booking.shop_longitude) {
            // ใช้ setTimeout เพื่อให้แน่ใจว่า element ของแผนที่ถูก render ใน DOM เรียบร้อยแล้ว
            setTimeout(() => {
                const mapDiv = document.getElementById(`modal-map-${booking.booking_id}`);
                if (!mapDiv) return;

                const map = L.map(mapDiv).fitBounds([
                    [booking.location_lat, booking.location_lng],
                    [booking.shop_latitude, booking.shop_longitude]
                ], { padding: [50, 50] }); // เพิ่ม padding ให้ marker ไม่ชิดขอบ

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Marker ร้านค้า (สีน้ำเงิน)
                L.marker([booking.shop_latitude, booking.shop_longitude], { icon: L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png', iconSize: [32, 32], iconAnchor: [16, 32] }) })
                    .addTo(map)
                    .bindPopup(`<b>ร้านค้า:</b> ${booking.shop_name}`);

                // Marker ลูกค้า (สีแดง)
                L.marker([booking.location_lat, booking.location_lng], { icon: L.icon({ iconUrl: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png', iconSize: [32, 32], iconAnchor: [16, 32] }) })
                    .addTo(map)
                    .bindPopup(`<b>ลูกค้า:</b> ${booking.customer_name}`);
            }, 100);
        }
    });
});
</script>


</body>
</html>
