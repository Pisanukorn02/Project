<?php
session_start();
include 'config.php';

if (!isset($_SESSION['shop_id'])) {
    echo "ยังไม่ได้เข้าสู่ระบบ";
    exit();
}

$shop_id = $_SESSION['shop_id'];

// ดึงข้อมูลร้านจากฐานข้อมูล
$stmt = $conn->prepare("SELECT shop_name FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$shop = $result->fetch_assoc();
$stmt->close();

// เซ็ตชื่อร้านใน session ถ้ายังไม่มี
if (!isset($_SESSION['shop_name']) && $shop) {
    $_SESSION['shop_name'] = $shop['shop_name'];
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thai Sidebar Navigation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo-section {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .user-section {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #666;
            font-size: 14px;
        }

        .user-icon {
            width: 20px;
            height: 20px;
            background: #dee2e6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-section {
            flex: 1;
            padding: 10px 0;
        }

        .nav-title {
            padding: 15px 20px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin: 2px 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #495057;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .nav-link:hover {
            background: #f8f9fa;
            color: #333;
        }

        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 500;
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.7;
        }

        .nav-link.active .nav-icon {
            opacity: 1;
        }

        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 20px;
        }

        .content-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .welcome-text {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #6c757d;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="logo-section">
            <div class="logo">
                <div class="logo-icon">×</div>
                <div class="logo-text">ระบบครุภัณฑ์</div>
            </div>
        </div>

        <div class="user-section">
            <div class="user-info">
                <div class="user-icon">👤</div>
                <span>เมนูหลัก</span>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-title">จัดการข้อมูล</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="shop.board.php" class="nav-link active">
                        <div class="nav-icon">🏷️</div>
                        <span>ประเภทครุภัณฑ์</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">📦</div>
                        <span>ครุภัณฑ์</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">📋</div>
                        <span>แผนก</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">👥</div>
                        <span>ทีมย่อยบำรุง</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">👤</div>
                        <span>พนักงาน</span>
                    </a>
                </li>
            </ul>

            <div class="nav-title">รายงานและการดำเนินการ</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="report.php" class="nav-link">
                        <div class="nav-icon">📊</div>
                        <span>รายงาน</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <div class="nav-icon">📱</div>
                        <span>ส่งทาง Telegram</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <div class="content-card">
            <h1 class="welcome-text">ยินดีต้อนรับสู่ระบบจัดการครุภัณฑ์</h1>
            <p class="subtitle">เลือกเมนูจากด้านซ้ายเพื่อเริ่มจัดการข้อมูลครุภัณฑ์ของคุณ</p>
        </div>
    </main>

    <script>
        // Add interactive functionality
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Update main content based on selection
                const mainContent = document.querySelector('.content-card');
                const menuText = this.querySelector('span').textContent;
                
                mainContent.innerHTML = `
                    <h1 class="welcome-text">จัดการ${menuText}</h1>
                    <p class="subtitle">หน้าจัดการ${menuText} - กำลังพัฒนา</p>
                `;
            });
        });
    </script>
</body>
</html>