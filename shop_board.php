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
    <title>ร้านค้าของฉัน</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body { font-family: sans-serif; background: #f0f0f0; padding: 20px; }
        h2 { color: #1976d2; }
        .section { background: #fff; padding: 20px; margin-bottom: 30px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; vertical-align: middle; }
        img { max-width: 100px; height: auto; display: block; margin: 0 auto; } /* ปรับให้รูปภาพดูดีขึ้น */
        .btn { padding: 6px 10px; border: none; border-radius: 5px; cursor: pointer; margin-right: 5px; text-decoration:none; color:#fff; }
        .btn-approve { background: #28a745; }
        .btn-reject { background: #dc3545; }
        .btn-edit { background: #007bff; }
        .btn-delete { background: #e53935; }
        .btn-map { background: #ffc107; color: #333; } /* สไตล์สำหรับปุ่มแผนที่ */
        .btn-map:hover { background: #e0a800; }

        .add-service-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background: #1976d2;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
        }
        .add-service-btn:hover {
            background: #155a9c;
        }
        .btn-complete {
            background: #28a745;
            color: white;
            padding: 6px 10px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn-complete:hover {
            background: #218838;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%,rgb(21, 23, 158) 100%);
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
            background: linear-gradient(135deg, #667eea 0%,rgb(43, 67, 173) 100%);
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
            background: linear-gradient(135deg, #667eea 0%,rgb(55, 47, 172) 100%);
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
            background: linear-gradient(135deg, #667eea 0%,rgb(33, 42, 160) 100%);
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
            background: linear-gradient(135deg, #667eea 0%,rgb(70, 59, 211) 100%);
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
        
        /* **เพิ่ม CSS สำหรับเมนูกรองสถานะใหม่** */
        .status-filter-menu {
            margin-top: 20px;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .status-filter-menu a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 20px;
            transition: all 0.3s ease;
            margin: 0 5px;
        }
        .status-filter-menu a:hover {
            background-color: #e3f2fd;
        }
        .status-filter-menu a.active {
            background-color: #1976d2;
            color: #fff;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(25, 118, 210, 0.3);
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> ร้านค้าของฉัน</h1>
            <?php if (isset($_SESSION['shop_name'])): ?>
                <p style="text-align: center; color: #666; margin-top: 10px;">ยินดีต้อนรับ, ร้าน <?= htmlspecialchars($_SESSION['shop_name']) ?></p>
            <?php endif; ?>
        </div>

        <?php
        // ดึง shop_id ของร้านนี้จาก session
        $user_id = $_SESSION['user_id'] ?? 0;

        // ตรวจสอบว่าเป็นร้านหรือไม่
        if ($_SESSION['role'] !== 'shop') {
            header("Location: login.html");
            exit;
        }

        // ดึง shop_id
        $stmt = $conn->prepare("SELECT shop_id FROM shops WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $shop = $result->fetch_assoc();
        $shop_id = $shop['shop_id'] ?? 0;

        // ดึงข้อมูลจำนวนบริการ
        $totalServices = 0;
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM services WHERE shop_id = ?");
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalServices = $row['total'];
        }

        // รอการยืนยัน (สถานะ booking = pending)
        $pendingBookings = 0;
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE shop_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $pendingBookings = $row['total'];
        }

        // งานที่รับแล้ว (สถานะ booking = accepted)
        $acceptedBookings = 0;
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE shop_id = ? AND status = 'accepted'");
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $acceptedBookings = $row['total'];
        }

        // งานที่เสร็จสิ้น (สถานะ booking = completed)
        $completedBookings = 0;
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE shop_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $completedBookings = $row['total'];
        }
        ?>

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
    </div>
    <a href="add_service.php" class="add-service-btn">เพิ่มบริการใหม่</a>

    <div class="section">
        <h3>บริการของร้าน</h3>
        <?php
        $sql = "SELECT * FROM services WHERE shop_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<table>
                    <tr>
                        <th>ชื่อบริการ</th>
                        <th>รายละเอียด</th>
                        <th>ราคา</th>
                        <th>ภาพ</th>
                        <th>จัดการ</th>
                    </tr>";

            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['service_name']) . "</td>
                        <td>" . htmlspecialchars($row['description']) . "</td>
                        <td>" . htmlspecialchars(number_format($row['price'], 2)) . " บาท</td>
                        <td>";
                // ตรวจสอบว่ามีภาพหรือไม่ก่อนแสดง
                if (!empty($row['image']) && file_exists('uploads/' . $row['image'])) {
                    echo "<img src='uploads/" . htmlspecialchars($row['image']) . "' alt='ภาพบริการ'>";
                } else {
                    echo "ไม่มีภาพ";
                }
                echo "</td>
                        <td>
                            <a href='edit_service.php?service_id=" . $row['service_id'] . "' class='btn btn-edit'>แก้ไข</a>
                            <a href='delete_service.php?service_id=" . $row['service_id'] . "' class='btn btn-delete' onclick='return confirm(\"ยืนยันการลบบริการนี้?\");'>ลบ</a>
                        </td>
                    </tr>";
            }

            echo "</table>";
        } else {
            echo "<p>ยังไม่มีบริการที่เพิ่มไว้</p>";
        }

        $stmt->close();
        ?>
    </div>

    <?php
    // สมมุติว่าเชื่อมต่อฐานข้อมูล $conn แล้ว และ $shop_id มีค่า

    // รับค่าจาก URL เพื่อกรองสถานะ
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

    // ตรวจสอบสถานะที่รับมาให้อยู่ในรายการที่อนุญาต (enum ของ status)
    $allowed_status = ['pending', 'accepted', 'rejected', 'completed'];
    if (!in_array($status_filter, $allowed_status)) {
        $status_filter = 'pending'; // กำหนดค่าเริ่มต้นถ้าค่าที่ส่งมาไม่ถูกต้อง
    }

    // ดึงข้อมูลจองตามสถานะและร้านค้า
    $sql = "SELECT b.*, u.name AS customer_name, u.phone AS customer_phone, s.service_name , s.service_type
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN services s ON b.service_id = s.service_id
        WHERE b.shop_id = ? AND b.status = ?
        ORDER BY b.booking_date ASC, b.booking_time ASC";


    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $shop_id, $status_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <div class="status-filter-menu">
        <a href="?status=pending" class="<?= $status_filter === 'pending' ? 'active' : '' ?>">รอดำเนินการ (Pending)</a>
        <a href="?status=accepted" class="<?= $status_filter === 'accepted' ? 'active' : '' ?>">รับงานแล้ว (Accepted)</a>
        <a href="?status=rejected" class="<?= $status_filter === 'rejected' ? 'active' : '' ?>">ปฏิเสธ (Rejected)</a>
        <a href="?status=completed" class="<?= $status_filter === 'completed' ? 'active' : '' ?>">เสร็จสิ้น (Completed)</a>
    </div>

    <?php
    // แสดงตารางตามผลลัพธ์ที่ได้
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<thead>
                <tr>
                    <th>ชื่อลูกค้า</th>
                    <th>เบอร์</th>
                    <th>บริการ</th>
                    <th>วันเวลาจอง</th>
                    <th>ที่อยู่หน้างาน</th>
                    <th>รูปหน้างาน</th>
                    <th>สถานะ</th>
                    <th>การจัดการ</th>
                    <th>โลเคชั่น</th>
                </tr>
            </thead><tbody>";

        while ($row = $result->fetch_assoc()) {
            // ... แสดงข้อมูลเหมือนเดิม ...
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['customer_phone']) . "</td>";
            echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['booking_date']) . " " . htmlspecialchars($row['booking_time']) . "</td>";
            echo "<td>" . htmlspecialchars($row['address']) . "</td>";

            echo "<td>";
            if (!empty($row['payment_slip'])) {
                $slip_path = $row['payment_slip'];
                if (strpos($slip_path, 'uploads/') === false) {
                    $slip_path = 'uploads/slips/' . $slip_path;
                }
                echo "<a href='" . htmlspecialchars($slip_path) . "' target='_blank'>ดูรูป</a>";
            } else {
                echo "-";
            }
            echo "</td>";
            
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";

            // การจัดการ ปรับแสดงตามสถานะเหมือนเดิม
            echo "<td>";
            if ($row['status'] === 'pending') {
                // ปุ่มรับงาน, ปฏิเสธ
                echo "<form method='POST' action='booking_action.php' style='display:inline-block; margin-right:5px;' onsubmit='return confirm(\"ยืนยันการรับงานนี้?\");'>";
                echo "<input type='hidden' name='booking_id' value='" . htmlspecialchars($row['booking_id']) . "'>";
                echo "<button type='submit' class='btn btn-approve' name='action' value='approve'>รับงาน</button>";
                echo "</form>";

                echo "<form method='POST' action='booking_action.php' style='display:inline-block;' onsubmit='return confirm(\"ยืนยันการปฏิเสธงานนี้?\");'>";
                echo "<input type='hidden' name='booking_id' value='" . htmlspecialchars($row['booking_id']) . "'>";
                echo "<button type='submit' class='btn btn-reject' name='action' value='reject'>ปฏิเสธ</button>";
                echo "</form>";
            } elseif ($row['status'] === 'accepted') {
                // ปุ่มจบงาน
                echo "<form method='POST' action='booking_action.php' onsubmit='return confirm(\"ยืนยันการจบงานนี้?\");'>";
                echo "<input type='hidden' name='booking_id' value='" . htmlspecialchars($row['booking_id']) . "'>";
                echo "<button type='submit' class='btn btn-complete' name='action' value='complete'>จบงาน</button>";
                echo "</form>";
            } elseif ($row['status'] === 'rejected') {
                echo "ปฏิเสธงานแล้ว";
            } elseif ($row['status'] === 'completed') {
                echo "งานเสร็จสิ้นแล้ว";
            }
            echo "</td>";

            echo "<td>";
            if (!empty($row['location_lat']) && !empty($row['location_lng']) && is_numeric($row['location_lat']) && is_numeric($row['location_lng'])) {
                $maps_link = "https://www.google.com/maps/search/?api=1&query=" . $row['location_lat'] . "," . $row['location_lng'];
                echo "<a href='" . htmlspecialchars($maps_link) . "' target='_blank' class='btn btn-map'>ดูแผนที่</a>";
            } else {
                echo "-";
            }
            echo "</td>";

            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p>ยังไม่มีการจองจากลูกค้าในสถานะนี้</p>";
    }

    $stmt->close();
    $conn->close();

    ?>
</div>
<a href="shop_income.php" class="add-service-btn">รายได้</a>
</body>
</html>