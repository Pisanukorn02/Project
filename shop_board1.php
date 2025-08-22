<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านค้าของฉัน</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .header h1 {
            color: #4a6cf7;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.5);
        }

        .stat-card .icon {
            font-size: 3em;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #4a6cf7;
            margin-bottom: 10px;
        }

        .stat-card .label {
            color: #666;
            font-size: 1.1em;
        }

        .section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e8f2ff;
        }

        .section-title {
            color: #4a6cf7;
            font-size: 1.8em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 1.1em;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f8ff;
            vertical-align: middle;
        }

        tr:hover {
            background: #f8fbff;
        }

        .service-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            margin: 2px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
        }

        .btn-approve {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
        }

        .btn-reject {
            background: linear-gradient(135deg, #dc3545, #e85a5a);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.6);
        }

        .btn-edit {
            background: linear-gradient(135deg, #007bff, #6610f2);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.6);
        }

        .btn-delete {
            background: linear-gradient(135deg, #e53935, #d32f2f);
            box-shadow: 0 4px 15px rgba(229, 57, 53, 0.4);
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 57, 53, 0.6);
        }

        .btn-map {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: #333;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
        }

        .btn-map:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.6);
        }

        .btn-complete {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            text-align: center;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 1.2em;
        }

        .no-data i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #ccc;
        }

        .payment-slip {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .payment-slip:hover {
            transform: scale(1.1);
        }

        .form-inline {
            display: inline-flex;
            gap: 5px;
            margin: 2px;
        }

        .price {
            font-weight: 600;
            color: #28a745;
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                font-size: 0.9em;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.18);
            padding: 20px;
            z-index: 1000;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2em;
            z-index: 1001;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <h3 style="color: #4a6cf7; margin-bottom: 20px;">เมนู</h3>
        <ul style="list-style: none;">
            <li style="margin-bottom: 10px;">
                <a href="#" style="color: #666; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; transition: background 0.3s;">
                    <i class="fas fa-home"></i> หน้าหลัก
                </a>
            </li>
            <li style="margin-bottom: 10px;">
                <a href="#services" style="color: #666; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; transition: background 0.3s;">
                    <i class="fas fa-cog"></i> บริการ
                </a>
            </li>
            <li style="margin-bottom: 10px;">
                <a href="#bookings" style="color: #666; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; transition: background 0.3s;">
                    <i class="fas fa-calendar"></i> การจอง
                </a>
            </li>
            <li style="margin-bottom: 10px;">
                <a href="shop_income.php" style="color: #666; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; transition: background 0.3s;">
                    <i class="fas fa-chart-line"></i> รายได้
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-store"></i> ร้านค้าของฉัน</h1>
                <p style="text-align: center; color: #666; margin-top: 10px;">จัดการร้านค้าและบริการของคุณ</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-cogs"></i></div>
                    <div class="number" id="totalServices">0</div>
                    <div class="label">บริการทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-clock"></i></div>
                    <div class="number" id="pendingBookings">0</div>
                    <div class="label">รอการยืนยัน</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <div class="number" id="acceptedBookings">0</div>
                    <div class="label">งานที่รับแล้ว</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-star"></i></div>
                    <div class="number" id="completedBookings">0</div>
                    <div class="label">งานเสร็จสิ้น</div>
                </div>
            </div>

            <div class="section" id="services">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-cogs"></i>
                        บริการของร้าน
                    </h3>
                    <a href="add_service.php" class="add-btn">
                        <i class="fas fa-plus"></i>
                        เพิ่มบริการใหม่
                    </a>
                </div>

                <div class="table-container">
                    <?php
                    // PHP code สำหรับแสดงบริการจะอยู่ตรงนี้
                    // ใช้โค้ดเดิมแต่เปลี่ยน HTML structure
                    ?>
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-tag"></i> ชื่อบริการ</th>
                                <th><i class="fas fa-info-circle"></i> รายละเอียด</th>
                                <th><i class="fas fa-money-bill"></i> ราคา</th>
                                <th><i class="fas fa-image"></i> ภาพ</th>
                                <th><i class="fas fa-tools"></i> จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ตัวอย่างข้อมูล -->
                            <tr>
                                <td>ซ่อมคอมพิวเตอร์</td>
                                <td>บริการซ่อมคอมพิวเตอร์ทุกชนิด</td>
                                <td><span class="price">500.00 บาท</span></td>
                                <td>
                                    <img src="https://via.placeholder.com/80x80/667eea/ffffff?text=PC" alt="ภาพบริการ" class="service-image">
                                </td>
                                <td>
                                    <a href="#" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </a>
                                    <a href="#" class="btn btn-delete" onclick="return confirm('ยืนยันการลบบริการนี้?');">
                                        <i class="fas fa-trash"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>ติดตั้งซอฟต์แวร์</td>
                                <td>บริการติดตั้งและปรับแต่งซอฟต์แวร์</td>
                                <td><span class="price">300.00 บาท</span></td>
                                <td>
                                    <img src="https://via.placeholder.com/80x80/764ba2/ffffff?text=SW" alt="ภาพบริการ" class="service-image">
                                </td>
                                <td>
                                    <a href="#" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </a>
                                    <a href="#" class="btn btn-delete" onclick="return confirm('ยืนยันการลบบริการนี้?');">
                                        <i class="fas fa-trash"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section" id="bookings">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-alt"></i>
                        การจองจากลูกค้า
                    </h3>
                </div>

                <div class="table-aicontner">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> ชื่อลูกค้า</th>
                                <th><i class="fas fa-phone"></i> เบอร์</th>
                                <th><i class="fas fa-cog"></i> บริการ</th>
                                <th><i class="fas fa-calendar"></i> วันเวลาจอง</th>
                                <th><i class="fas fa-map-marker-alt"></i> ที่อยู่หน้างาน</th>
                                <th><i class="fas fa-receipt"></i> สลิปโอนเงิน</th>
                                <th><i class="fas fa-info-circle"></i> สถานะ</th>
                                <th><i class="fas fa-tools"></i> การจัดการ</th>
                                <th><i class="fas fa-map"></i> โลเคชั่น</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ตัวอย่างข้อมูล -->
                            <tr>
                                <td>นาย สมชาย ใจดี</td>
                                <td>081-234-5678</td>
                                <td>ซ่อมคอมพิวเตอร์</td>
                                <td>2024-02-15 14:00</td>
                                <td>123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110</td>
                                <td>
                                    <img src="https://via.placeholder.com/60x60/28a745/ffffff?text=SLIP" alt="สลิปโอนเงิน" class="payment-slip" onclick="window.open(this.src, '_blank')">
                                </td>
                                <td>
                                    <span class="status-badge status-pending">รอยืนยัน</span>
                                </td>
                                <td>
                                    <form class="form-inline" onsubmit="return confirm('ยืนยันการรับงานนี้?');">
                                        <button type="submit" class="btn btn-approve">
                                            <i class="fas fa-check"></i> รับงาน
                                        </button>
                                    </form>
                                    <form class="form-inline" onsubmit="return confirm('ยืนยันการปฏิเสธงานนี้?');">
                                        <button type="submit" class="btn btn-reject">
                                            <i class="fas fa-times"></i> ปฏิเสธ
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="https://www.google.com/maps/search/?api=1&query=13.7563,100.5018" target="_blank" class="btn btn-map">
                                        <i class="fas fa-map-marker-alt"></i> ดูแผนที่
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>นางสาว สมใส รักงาน</td>
                                <td>082-345-6789</td>
                                <td>ติดตั้งซอฟต์แวร์</td>
                                <td>2024-02-16 10:00</td>
                                <td>456 ถนนพหลโยธิน แขวงสามเสนใน เขตพญาไท กรุงเทพฯ 10400</td>
                                <td>
                                    <img src="https://via.placeholder.com/60x60/007bff/ffffff?text=SLIP" alt="สลิปโอนเงิน" class="payment-slip" onclick="window.open(this.src, '_blank')">
                                </td>
                                <td>
                                    <span class="status-badge status-accepted">รับงานแล้ว</span>
                                </td>
                                <td>
                                    <form class="form-inline" onsubmit="return confirm('ยืนยันการจบงานนี้?');">
                                        <button type="submit" class="btn btn-complete">
                                            <i class="fas fa-flag-checkered"></i> จบงาน
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="https://www.google.com/maps/search/?api=1&query=13.7849,100.5387" target="_blank" class="btn btn-map">
                                        <i class="fas fa-map-marker-alt"></i> ดูแผนที่
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>นาย วิชัย เสร็จงาม</td>
                                <td>083-456-7890</td>
                                <td>ซ่อมคอมพิวเตอร์</td>
                                <td>2024-02-14 16:30</td>
                                <td>789 ถนนรัชดาภิเษก แขวงห้วยขวาง เขตห้วยขวาง กรุงเทพฯ 10310</td>
                                <td>
                                    <img src="https://via.placeholder.com/60x60/28a745/ffffff?text=SLIP" alt="สลิปโอนเงิน" class="payment-slip" onclick="window.open(this.src, '_blank')">
                                </td>
                                <td>
                                    <span class="status-badge status-completed">งานเสร็จสิ้น</span>
                                </td>
                                <td>งานเสร็จสิ้นแล้ว</td>
                                <td>
                                    <a href="https://www.google.com/maps/search/?api=1&query=13.7651,100.5821" target="_blank" class="btn btn-map">
                                        <i class="fas fa-map-marker-alt"></i> ดูแผนที่
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="shop_income.php" class="add-btn" style="font-size: 1.2em; padding: 15px 30px;">
                    <i class="fas fa-chart-line"></i>
                    ดูรายได้
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // ปิด sidebar เมื่อคลิกนอก sidebar
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });

        // อัพเดทสถิติ (ตัวอย่าง)
        document.addEventListener('DOMContentLoaded', function() {
            // ในที่นี้คุณสามารถใช้ PHP เพื่อนับข้อมูลจริงได้
            document.getElementById('totalServices').textContent = '2';
            document.getElementById('pendingBookings').textContent = '1';
            document.getElementById('acceptedBookings').textContent = '1';
            document.getElementById('completedBookings').textContent = '1';
        });

        // Smooth scroll สำหรับเมนู
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>