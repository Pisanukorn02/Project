<?php
session_start();
include 'config.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// --- ดึงข้อมูลผู้ใช้ ---
$stmt = $conn->prepare("SELECT name, phone, address, latitude, longitude FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $phone, $address, $latitude, $longitude);
$stmt->fetch();
$stmt->close();

// --- ดึงรีวิวของผู้ใช้ ---
$reviews = [];
$stmt = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, s.service_name, sh.shop_name
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN services s ON b.service_id = s.service_id
    JOIN shops sh ON s.shop_id = sh.shop_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();

// --- ดึงประวัติการจองของผู้ใช้ ---
$bookings = [];
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_date, b.booking_time, b.status,
           s.service_name, sh.shop_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN shops sh ON b.shop_id = sh.shop_id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>บัญชีของฉัน - Project Air</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .back-button {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%) translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .back-button i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .back-button:hover i {
            transform: translateX(-5px);
        }

        .header h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .header .subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
            font-weight: 300;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .profile-section, .reviews-section, .bookings-section {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 25px 30px;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-content {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .form-group input, 
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Kanit', sans-serif;
        }

        .form-group input:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }

        .map-container {
            margin-top: 20px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        #map { 
            height: 350px; 
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            font-family: 'Kanit', sans-serif;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.6);
        }

        .reviews-grid, .bookings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .review-card, .booking-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #4facfe;
            transition: all 0.3s ease;
        }

        .review-card:hover, .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .review-header, .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .service-name {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }

        .shop-name {
            color: #666;
            font-size: 0.9rem;
            font-weight: 400;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #ffa500;
            font-weight: 600;
        }

        .review-comment {
            color: #555;
            line-height: 1.6;
            margin: 15px 0;
        }

        .review-date, .booking-date {
            color: #888;
            font-size: 0.9rem;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status.pending { background: #fff3cd; color: #856404; }
        .status.confirmed { background: #d4edda; color: #155724; }
        .status.completed { background: #d1ecf1; color: #0c5460; }
        .status.cancelled { background: #f8d7da; color: #721c24; }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .booking-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .booking-detail {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
        }

        .booking-detail-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }

        .booking-detail-value {
            font-weight: 600;
            color: #333;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .section-content {
                padding: 20px;
            }
            
            .reviews-grid, .bookings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <a href="index.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        กลับหน้าหลัก
    </a>
    <h1><i class="fas fa-user-circle"></i> บัญชีของฉัน</h1>
    <p class="subtitle">จัดการข้อมูลส่วนตัวและดูประวัติการใช้บริการ</p>
</div>

<div class="container">
    <!-- Profile Section -->
    <div class="profile-section">
        <div class="section-header">
            <i class="fas fa-user-edit"></i>
            <span>แก้ไขข้อมูลส่วนตัว</span>
        </div>
        <div class="section-content">
            <form action="user_update.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> ชื่อ-นามสกุล</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> ที่อยู่</label>
                    <textarea name="address" rows="3"><?= htmlspecialchars($address) ?></textarea>
                </div>

                
                    <label>ตำแหน่งที่ตั้ง (ลากหมุดเพื่อเปลี่ยนตำแหน่ง)</label>
    <div id="map"></div>
    <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($latitude) ?>">
    <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($longitude) ?>">


                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> บันทึกข้อมูล
                </button>
            </form>
        </div>
    </div>
<!-- Reviews Section -->
<div class="reviews-section">
    <div class="section-header">
        <i class="fas fa-star"></i>
        <span>รีวิวของฉัน</span>
    </div>
    <div class="section-content">
        <?php if (count($reviews) > 0): ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $r): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <div class="service-name"><?= htmlspecialchars($r['service_name']) ?></div>
                                <div class="shop-name"><?= htmlspecialchars($r['shop_name']) ?></div>
                            </div>
                            <div class="rating">
                                <?php 
                                    $fullStars = floor($r['rating']);
                                    $halfStars = ($r['rating'] - $fullStars) >= 0.5 ? 1 : 0;
                                    $emptyStars = 5 - $fullStars - $halfStars;

                                    for ($i=0; $i<$fullStars; $i++) echo '<i class="fas fa-star"></i>';
                                    if ($halfStars) echo '<i class="fas fa-star-half-alt"></i>';
                                    for ($i=0; $i<$emptyStars; $i++) echo '<i class="far fa-star"></i>';
                                ?>
                                <?= $r['rating'] ?>/5
                            </div>
                        </div>
                        <div class="review-comment">
                            <?= nl2br(htmlspecialchars($r['comment'])) ?>
                        </div>
                        <div class="review-date">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($r['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <p>ยังไม่มีรีวิวของคุณ</p>
            </div>
        <?php endif; ?>
    </div>
</div>


   <!-- Bookings Section -->
<div class="bookings-section">
    <div class="section-header">
        <i class="fas fa-calendar-check"></i>
        <span>ประวัติการจอง</span>
    </div>
    <div class="section-content">
        <?php if (count($bookings) > 0): ?>
            <div class="bookings-grid">
                <?php foreach ($bookings as $b): ?>
                    <?php
                        // แปลงสถานะเป็น class CSS
                        $statusClass = '';
                        $statusText = '';
                        switch ($b['status']) {
                            case 'pending':
                                $statusClass = 'pending';
                                $statusText = 'รอดำเนินการ';
                                break;
                            case 'confirmed':
                                $statusClass = 'confirmed';
                                $statusText = 'ยืนยันแล้ว';
                                break;
                            case 'completed':
                                $statusClass = 'completed';
                                $statusText = 'เสร็จสิ้น';
                                break;
                            case 'cancelled':
                                $statusClass = 'cancelled';
                                $statusText = 'ยกเลิก';
                                break;
                        }
                    ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div>
                                <div class="service-name"><?= htmlspecialchars($b['service_name']) ?></div>
                                <div class="shop-name"><?= htmlspecialchars($b['shop_name']) ?></div>
                            </div>
                            <div class="status <?= $statusClass ?>"><?= $statusText ?></div>
                        </div>
                        <div class="booking-info">
                            <div class="booking-detail">
                                <div class="booking-detail-label">วันที่</div>
                                <div class="booking-detail-value"><?= date('d/m/Y', strtotime($b['booking_date'])) ?></div>
                            </div>
                            <div class="booking-detail">
                                <div class="booking-detail-label">เวลา</div>
                                <div class="booking-detail-value"><?= htmlspecialchars($b['booking_time']) ?></div>
                            </div>
                            <div class="booking-detail">
                                <div class="booking-detail-label">สถานะ</div>
                                <div class="booking-detail-value"><?= $statusText ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>คุณยังไม่มีการจอง</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
    // Initialize map with Phitsanulok coordinates
    var lat = <?= $latitude ?: 13.7563 ?>;
var lng = <?= $longitude ?: 100.5018 ?>;
var map = L.map('map').setView([lat, lng], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// สร้าง marker draggable
var marker = L.marker([lat, lng], {draggable:true}).addTo(map);

// ฟังก์ชันอัปเดตค่าลง input hidden
function updateInputs(latlng){
    document.getElementById('latitude').value = latlng.lat;
    document.getElementById('longitude').value = latlng.lng;
}

// อัปเดตค่าตอนเริ่มต้น
updateInputs(marker.getLatLng());

// ถ้าลาก marker ให้ update
marker.on('dragend', function(e){
    updateInputs(e.target.getLatLng());
});

// ฟังก์ชันสำหรับดึงที่อยู่จากพิกัด
function getAddress(lat, lng) {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const addressField = document.querySelector('textarea[name="address"]');
            if (data && data.display_name) {
                addressField.value = data.display_name;
            } else {
                addressField.value = "ไม่พบข้อมูลที่อยู่";
            }
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูลที่อยู่:', error);
            document.querySelector('textarea[name="address"]').value = "ไม่สามารถดึงข้อมูลที่อยู่ได้";
        });
}

// ถ้าคลิกบน map ให้ marker ย้ายไปตำแหน่งนั้น
map.on('click', function(e){
    marker.setLatLng(e.latlng);
    updateInputs(e.latlng);
    getAddress(e.latlng.lat, e.latlng.lng);
});

// Drag marker
marker.on('dragend', function(e) {
    var pos = e.target.getLatLng();
    document.getElementById('latitude').value = pos.lat;
    document.getElementById('longitude').value = pos.lng;
    getAddress(pos.lat, pos.lng);
});

    // Add smooth animations on page load
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.review-card, .booking-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>

</body>
</html>