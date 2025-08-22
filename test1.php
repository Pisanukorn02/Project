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
                        <input type="text" name="name" value="สมชาย ใจดี" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" value="081-234-5678" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> ที่อยู่</label>
                    <textarea name="address" rows="3" placeholder="กรุณากรอกที่อยู่ของคุณ">123 หมู่ 5 ตำบลในเมือง อำเภอเมือง จังหวัดพิษณุโลก 65000</textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map"></i> ตำแหน่งที่ตั้ง (คลิกหรือลากหมุดเพื่อเปลี่ยนตำแหน่ง)</label>
                    <div class="map-container">
                        <div id="map"></div>
                    </div>
                    <input type="hidden" name="latitude" id="latitude" value="16.8197">
                    <input type="hidden" name="longitude" id="longitude" value="100.2642">
                </div>

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
            <div class="reviews-grid">
                <div class="review-card">
                    <div class="review-header">
                        <div>
                            <div class="service-name">ล้างแอร์บ้าน</div>
                            <div class="shop-name">ร้าน Cool Air Service</div>
                        </div>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            5/5
                        </div>
                    </div>
                    <div class="review-comment">
                        บริการดีมาก ช่างมาตรงเวลา ทำความสะอาดละเอียด แอร์เย็นดีขึ้นมาก แนะนำเลยครับ
                    </div>
                    <div class="review-date">
                        <i class="fas fa-calendar"></i> 15/08/2025
                    </div>
                </div>

                <div class="review-card">
                    <div class="review-header">
                        <div>
                            <div class="service-name">ซ่อมแอร์</div>
                            <div class="shop-name">ร้าน Fix Air Pro</div>
                        </div>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                            4/5
                        </div>
                    </div>
                    <div class="review-comment">
                        ช่างมีความรู้ดี แก้ปัญหาได้ แต่มาช้ากว่านัดหมายเล็กน้อย โดยรวมพอใจ
                    </div>
                    <div class="review-date">
                        <i class="fas fa-calendar"></i> 02/08/2025
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bookings Section -->
    <div class="bookings-section">
        <div class="section-header">
            <i class="fas fa-calendar-check"></i>
            <span>ประวัติการจอง</span>
        </div>
        <div class="section-content">
            <div class="bookings-grid">
                <div class="booking-card">
                    <div class="booking-header">
                        <div>
                            <div class="service-name">ติดตั้งแอร์ใหม่</div>
                            <div class="shop-name">ร้าน Air Master</div>
                        </div>
                        <div class="status confirmed">ยืนยันแล้ว</div>
                    </div>
                    <div class="booking-info">
                        <div class="booking-detail">
                            <div class="booking-detail-label">วันที่</div>
                            <div class="booking-detail-value">25/08/2025</div>
                        </div>
                        <div class="booking-detail">
                            <div class="booking-detail-label">เวลา</div>
                            <div class="booking-detail-value">09:00</div>
                        </div>
                        <div class="booking-detail">
                            <div class="booking-detail-label">สถานะ</div>
                            <div class="booking-detail-value">รอดำเนินการ</div>
                        </div>
                    </div>
                </div>

                <div class="booking-card">
                    <div class="booking-header">
                        <div>
                            <div class="service-name">ล้างแอร์บ้าน</div>
                            <div class="shop-name">ร้าน Cool Air Service</div>
                        </div>
                        <div class="status completed">เสร็จสิ้น</div>
                    </div>
                    <div class="booking-info">
                        <div class="booking-detail">
                            <div class="booking-detail-label">วันที่</div>
                            <div class="booking-detail-value">15/08/2025</div>
                        </div>
                        <div class="booking-detail">
                            <div class="booking-detail-label">เวลา</div>
                            <div class="booking-detail-value">14:00</div>
                        </div>
                        <div class="booking-detail">
                            <div class="booking-detail-label">สถานะ</div>
                            <div class="booking-detail-value">เสร็จสิ้น</div>
                        </div>
                    </div>
                </div>

                <div class="booking-card">
                    <div class="booking-header">
                        <div>
                            <div class="service-name">ซ่อมแอร์</div>
                            <div class="shop-name">ร้าน Fix Air Pro</div>
                        </div>
                        <div class="status completed">เสร็จสิ้น</div>
                    </div>
                    <div class="booking-info">
                        <div class="booking-detail">
                            <div class="booking-detail-label">วันที่</div>
                            <div class="booking-detail-value">02/08/2025</div>
                        </div>
                        <div class="booking-detail">
                            <div class="booking-detail-label">เวลา</div>
                            <div class="booking-detail-value">10:30</div>
                        </div>
                        <div class="booking-detail">
                            <div class="booking-detail-label">สถานะ</div>
                            <div class="booking-detail-value">เสร็จสิ้น</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
    // Initialize map with Phitsanulok coordinates
    var lat = 16.8197;
    var lng = 100.2642;

    var map = L.map('map').setView([lat, lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var customIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);"><i class="fas fa-map-marker-alt"></i></div>',
        iconSize: [30, 30],
        iconAnchor: [15, 30]
    });

    var marker = L.marker([lat, lng], {
        draggable: true,
        icon: customIcon
    }).addTo(map);

    // Click on map to move marker
    map.on('click', function(e) {
        var newLatLng = e.latlng;
        marker.setLatLng(newLatLng);
        document.getElementById('latitude').value = newLatLng.lat;
        document.getElementById('longitude').value = newLatLng.lng;
    });

    // Drag marker
    marker.on('dragend', function(e) {
        var pos = e.target.getLatLng();
        document.getElementById('latitude').value = pos.lat;
        document.getElementById('longitude').value = pos.lng;
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