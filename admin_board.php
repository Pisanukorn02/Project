<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบคลุ่งค์กิ้มใส่</title>
    <link rel="stylesheet" href="http://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoDSe/GLy4+gKUprMyGAM+fTwFxJT4GzftwmaQ/" crossorigin="" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding: 20px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            width: 30px;
            height: 30px;
            background: #ff7b47;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: white;
            font-weight: bold;
        }
        
        .logo-text {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .nav-section {
            margin-bottom: 30px;
        }
        
        .nav-title {
            color: #999;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            padding: 0 20px;
            margin-bottom: 10px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .nav-item:hover {
            background: #f8f9fa;
            color: #ff7b47;
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, #ff7b47, #ff9a6b);
            color: white;
            position: relative;
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: #ff5722;
            border-radius: 2px 0 0 2px;
        }
        
        .nav-icon {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .header {
            background: linear-gradient(135deg, #ff7b47, #ff9a6b);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }
        
        .add-btn {
            background: linear-gradient(135deg, #ff7b47, #ff9a6b);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 123, 71, 0.3);
        }
        
        /* Table */
        .table-container {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eee;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .table th {
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            padding: 18px;
            text-align: left;
            font-size: 14px;
            border-bottom: 1px solid #eee;
        }
        
        .table td {
            padding: 18px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .shop-info {
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
        }
        
        .shop-detail {
            color: #666;
            font-size: 13px;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .map-container {
            height: 120px;
            width: 150px;
            border-radius: 8px;
            background: #f0f0f0;
            margin: 5px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-direction: column;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-edit:hover {
            background: #e0a800;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .reject-reason {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
            margin: 5px 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 16px;
        }
        
        /* Icons using CSS */
        .icon-home::before { content: "🏠"; }
        .icon-shop::before { content: "🏪"; }
        .icon-users::before { content: "👥"; }
        .icon-settings::before { content: "⚙️"; }
        .icon-chart::before { content: "📊"; }
        .icon-telegram::before { content: "📱"; }
        .icon-add::before { content: "+"; }
        .icon-x::before { content: "❌"; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <div class="logo-icon">❌</div>
                <div class="logo-text">ระบบครุคลุ่งค์กิ้ม</div>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">เมนูหลัก</div>
                <a href="admin_dashboard.php" class="nav-item">
                    <span class="nav-icon icon-home"></span>
                    หน้าหลัก
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">จัดการข้อมูล</div>
                <a href="#" class="nav-item active">
                    <span class="nav-icon icon-shop"></span>
                    ประมาณกรุณ่าคิ
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon icon-users"></span>
                    ครุณ่ต
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon icon-users"></span>
                    แผนก
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon icon-users"></span>
                    ทีมดือนฝ่ายธุช
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon icon-users"></span>
                    พนักงาน
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">รายงานและการดำเนิม</div>
                <a href="#" class="nav-item">
                    <span class="nav-icon icon-chart"></span>
                    รายงาน
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon icon-telegram"></span>
                    ตึ่งค่า Telegram
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>🔧 จัดการประมาณกรุณ่าคิ</h1>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">รายการประมาณกรุณ่าคิ</h2>
                    <button class="add-btn">
                        <span class="icon-add"></span>
                        เพิ่มประมาณ
                    </button>
                </div>

                <?php
                // Mock data for demonstration - replace with your actual PHP code
                $pending_shops = [
                    [
                        'shop_id' => 1,
                        'owner_name' => 'กล้องวงจรปิด',
                        'email' => 'กล้องวงจรปิดและอุปกรณ์รักษาความปลอดภัย',
                        'phone' => '',
                        'shop_name' => '1 รายการ',
                        'address' => '',
                        'province' => '',
                        'created_at' => '',
                        'latitude' => 13.7563,
                        'longitude' => 100.5018
                    ],
                    [
                        'shop_id' => 2,
                        'owner_name' => 'คอมพิวเตอร์',
                        'email' => 'อุปกรณ์คอมพิวเตอร์และอุปกรณ์รับส่องข้าว',
                        'phone' => '',
                        'shop_name' => '3 รายการ',
                        'address' => '',
                        'province' => '',
                        'created_at' => '',
                        'latitude' => 13.7563,
                        'longitude' => 100.5018
                    ],
                    [
                        'shop_id' => 3,
                        'owner_name' => 'เครื่องมือ',
                        'email' => 'อุปกรณ์เครื่องมือและการต่อเชื่อม',
                        'phone' => '',
                        'shop_name' => '0 รายการ',
                        'address' => '',
                        'province' => '',
                        'created_at' => '',
                        'latitude' => 13.7563,
                        'longitude' => 100.5018
                    ],
                    [
                        'shop_id' => 4,
                        'owner_name' => 'เครื่องพิมพ์',
                        'email' => 'เครื่องพิมพ์และสแกนเนอร์',
                        'phone' => '',
                        'shop_name' => '1 รายการ',
                        'address' => '',
                        'province' => '',
                        'created_at' => '',
                        'latitude' => 13.7563,
                        'longitude' => 100.5018
                    ]
                ];
                ?>

                <?php if (empty($pending_shops)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🏪</div>
                        <h3>ไม่มีร้านค้าที่รอการอนุมัติในขณะนี้</h3>
                        <p>เมื่อมีร้านค้าสมัครเข้ามาใหม่ จะแสดงที่นี่</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ชื่อประเภท</th>
                                    <th>รายละเอียด</th>
                                    <th>จำนวนครุณ่าคิ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_shops as $index => $shop): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="shop-info">🔸 <?php echo htmlspecialchars($shop['owner_name']); ?></div>
                                        </td>
                                        <td>
                                            <div class="shop-detail"><?php echo htmlspecialchars($shop['email']); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php 
                                                $count = (int)filter_var($shop['shop_name'], FILTER_SANITIZE_NUMBER_INT);
                                                if ($count == 0) echo 'status-rejected';
                                                elseif ($count >= 3) echo 'status-approved'; 
                                                else echo 'status-pending';
                                            ?>">
                                                <?php echo htmlspecialchars($shop['shop_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form action="admin_process.php" method="POST" style="display: inline;">
                                                    <input type="hidden" name="shop_id" value="<?php echo htmlspecialchars($shop['shop_id']); ?>">
                                                    <button type="button" class="btn btn-edit">📝</button>
                                                    <button type="button" class="btn btn-delete">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="http://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjGwZFYgSY84FNzIO0yiQNLLRxmrhg=" crossorigin=""></script>
    <script>
        // Original map initialization code preserved
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($pending_shops as $shop): ?>
                const lat_<?php echo htmlspecialchars($shop['shop_id']); ?> = <?php echo json_encode($shop['latitude']); ?>;
                const lng_<?php echo htmlspecialchars($shop['shop_id']); ?> = <?php echo json_encode($shop['longitude']); ?>;
                const shopName_<?php echo htmlspecialchars($shop['shop_id']); ?> = <?php echo json_encode(htmlspecialchars($shop['shop_name'])); ?>;
                const mapId_<?php echo htmlspecialchars($shop['shop_id']); ?> = 'map-<?php echo htmlspecialchars($shop['shop_id']); ?>';
                const mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?> = document.getElementById(mapId_<?php echo htmlspecialchars($shop['shop_id']); ?>);

                if (lat_<?php echo htmlspecialchars($shop['shop_id']); ?> && lng_<?php echo htmlspecialchars($shop['shop_id']); ?> && mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>) {
                    const map_<?php echo htmlspecialchars($shop['shop_id']); ?> = L.map(mapId_<?php echo htmlspecialchars($shop['shop_id']); ?>).setView([lat_<?php echo htmlspecialchars($shop['shop_id']); ?>, lng_<?php echo htmlspecialchars($shop['shop_id']); ?>], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map_<?php echo htmlspecialchars($shop['shop_id']); ?>);

                    L.marker([lat_<?php echo htmlspecialchars($shop['shop_id']); ?>, lng_<?php echo htmlspecialchars($shop['shop_id']); ?>]).addTo(map_<?php echo htmlspecialchars($shop['shop_id']); ?>)
                        .bindPopup(shopName_<?php echo htmlspecialchars($shop['shop_id']); ?>).openPopup();
                } else if (mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>) {
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.innerText = "ไม่มีข้อมูลตำแหน่ง";
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.style.backgroundColor = "#ffdddd";
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.style.display = "flex";
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.style.alignItems = "center";
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.style.justifyContent = "center";
                }
            <?php endforeach; ?>
        });
    </script>
</body>
</html>