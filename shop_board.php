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

<?php

include 'config.php';

if (!isset($_SESSION['shop_id']) || $_SESSION['role'] !== 'shop') {
    header("Location: login.html");
    exit();
}

$shop_id = $_SESSION['shop_id'];
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where_clause = "b.shop_id = ?";
$params = [$shop_id];
$types = "i";

if (!empty($start_date) && !empty($end_date)) {
    $where_clause .= " AND DATE(b.created_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

// รายได้รอรับ
$sql_accepted = "SELECT SUM(s.price) AS total FROM bookings b JOIN services s ON b.service_id = s.service_id WHERE $where_clause AND b.status = 'accepted'";
$stmt = $conn->prepare($sql_accepted);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($accepted_total);
$stmt->fetch();
$stmt->close();

// รายได้จบงาน
$sql_completed = "SELECT SUM(s.price) AS total FROM bookings b JOIN services s ON b.service_id = s.service_id WHERE $where_clause AND b.status = 'completed'";
$stmt = $conn->prepare($sql_completed);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($completed_total);
$stmt->fetch();
$stmt->close();

// นับจำนวนสถานะงาน
$status_counts = ['accepted' => 0, 'rejected' => 0, 'completed' => 0];
$sql_status = "SELECT b.status, COUNT(*) as total FROM bookings b WHERE $where_clause GROUP BY b.status";
$stmt = $conn->prepare($sql_status);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['total'];
}
$stmt->close();

// รายได้รายวัน
$daily_result = $conn->query("SELECT DATE(booking_date) as date, SUM(price) as total, COUNT(*) as jobs
FROM bookings
JOIN services ON bookings.service_id = services.service_id WHERE status='completed'
AND booking_date BETWEEN '$start_date' AND '$end_date'
GROUP BY date
ORDER BY date ASC");

// รายได้รายเดือน
$monthly_result = $conn->query("SELECT DATE_FORMAT(booking_date,'%M %Y') as month, 
                                      SUM(price) as total
                               FROM bookings
                               JOIN services ON bookings.service_id = services.service_id
                               WHERE status='completed'
                               AND booking_date BETWEEN '$start_date' AND '$end_date'
                               GROUP BY month
                               ORDER BY booking_date ASC");

// งานที่จบแล้ว
$sql_jobs = "SELECT b.booking_id, s.service_name, b.created_at, s.price FROM bookings b JOIN services s ON b.service_id = s.service_id WHERE $where_clause AND b.status = 'completed' ORDER BY b.created_at ASC";
$stmt = $conn->prepare($sql_jobs);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$jobs_result = $stmt->get_result();



// เก็บข้อมูลสำหรับตารางและกราฟ
$months = [];
$incomes = [];
while ($row = $result->fetch_assoc()) {
    $months[] = $row['month'];
    $incomes[] = $row['total_income'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานรายได้ - Project Air</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .filter-section {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 25px 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group label {
            font-weight: 500;
            color: #333;
            white-space: nowrap;
        }

        .filter-group input[type="date"] {
            padding: 10px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Kanit', sans-serif;
            transition: all 0.3s ease;
        }

        .filter-group input[type="date"]:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }

        .btn-filter {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            font-family: 'Kanit', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.6);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }

        .stat-card.pending::before { background: linear-gradient(90deg, #ffc107, #ff8f00); }
        .stat-card.completed::before { background: linear-gradient(90deg, #28a745, #20c997); }
        .stat-card.accepted::before { background: linear-gradient(90deg, #17a2b8, #6f42c1); }
        .stat-card.rejected::before { background: linear-gradient(90deg, #dc3545, #fd7e14); }

        .stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 15px;
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.pending .stat-icon { background: linear-gradient(135deg, #ffc107, #ff8f00); }
        .stat-card.completed .stat-icon { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-card.accepted .stat-icon { background: linear-gradient(135deg, #17a2b8, #6f42c1); }
        .stat-card.rejected .stat-icon { background: linear-gradient(135deg, #dc3545, #fd7e14); }

        .stat-title {
            font-size: 1rem;
            color: #666;
            margin-bottom: 5px;
            font-weight: 400;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #888;
        }

        .chart-section, .table-section {
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

        .chart-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            position: relative;
            height: 400px;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #333;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f3f4;
            color: #555;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .amount {
            font-weight: 600;
            color: #28a745;
        }

        .job-id {
            font-family: 'Monaco', monospace;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-weight: 500;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                justify-content: space-between;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
        }

        .trend-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 15px;
            font-weight: 500;
        }

        .trend-up {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .trend-down {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
    </style>
</head>
<body>

<div class="header">
    <h1><i class="fas fa-chart-line"></i> รายงานรายได้</h1>
    <p class="subtitle">ติดตามและวิเคราะห์รายได้ของร้านค้า</p>
</div>

<div class="container">
    <!-- Filter Section -->
   <form method="GET" id="filterForm">
    <div class="filter-section">
        <div class="filter-group">
            <label><i class="fas fa-calendar-alt"></i> วันที่เริ่มต้น:</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start_date ?? '') ?>">
        </div>
        <div class="filter-group">
            <label><i class="fas fa-calendar-alt"></i> ถึงวันที่:</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end_date ?? '') ?>">
        </div>
        <button type="submit" class="btn-filter">
            <i class="fas fa-filter"></i> กรองข้อมูล
        </button>
    </div>
</form>

    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">

     <div class="stat-card accepted">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-title">งานที่เสร็จแล้ว</div>
            <div class="stat-value"><?= $status_counts['completed'] ?></div>
            
        </div>
        
        <div class="stat-card completed">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-title">รายได้</div>
            <div class="stat-value"><?= number_format($completed_total ?? 0, 2) ?></div>
            <div class="stat-label">
                <span class="trend-indicator trend-up">
                </span>
            </div>
        </div>

        <div class="stat-card accepted">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-title">งานที่รับแล้ว</div>
            <div class="stat-value"><?= $status_counts['accepted'] ?></div>
            
        </div>

        <div class="stat-card pending">
            <div class="stat-icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-title">รายได้รอรับ</div>
            <div class="stat-value"><?= number_format($accepted_total ?? 0, 2) ?></div>
            <div class="stat-label">
                <span class="trend-indicator trend-up">
                </span>
            </div>
        </div>

        

        

       

        <div class="stat-card rejected">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-title">งานที่ปฏิเสธ</div>
            <div class="stat-value"><?= $status_counts['rejected']?></div>
            
        </div>
    </div>

    

    <!-- Charts Section -->
    <div class="charts-grid">
        <div class="chart-section">
            <div class="section-header">
                <i class="fas fa-chart-pie"></i>
                
                
                <span>สถานะงาน</span>
            </div>
            <div class="section-content">
                <div class="chart-container">
        <canvas id="statusChart"></canvas>
    </div>
            </div>
        </div>

        

   <!-- Monthly Revenue Table -->
<div class="table-section">
    <div class="section-header">
        <i class="fas fa-calendar-alt"></i>
        <span>รายได้แยกตามเดือน</span>
    </div>
    <div class="section-content">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> เดือน-ปี</th>
                        <th><i class="fas fa-money-bill-wave"></i> รายได้ (บาท)</th>
                        <th><i class="fas fa-chart-line"></i> เปรียบเทียบ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $previous_total = null;
                    while ($row = $monthly_result->fetch_assoc()):
                        $trend_class = '';
                        $trend_icon = '';
                        $trend_text = '';

                        if ($previous_total !== null) {
                            $percent = ($row['total'] - $previous_total) / $previous_total * 100;
                            $percent_rounded = round(abs($percent), 1); // ปัดเป็น 1 จุดทศนิยม

                            if ($percent > 0) {
                                $trend_class = 'trend-up';
                                $trend_icon = '<i class="fas fa-arrow-up"></i>';
                                $trend_text = "+{$percent_rounded}%";
                            } elseif ($percent < 0) {
                                $trend_class = 'trend-down';
                                $trend_icon = '<i class="fas fa-arrow-down"></i>';
                                $trend_text = "-{$percent_rounded}%";
                            } else {
                                $trend_class = 'trend-neutral';
                                $trend_text = "0%";
                            }
                        }

                        $previous_total = $row['total'];
                    ?>
                    <tr>
                        <td><?= $row['month'] ?></td>
                        <td class="amount"><?= number_format($row['total'],2) ?></td>
                        <td>
                            <?php if ($previous_total !== null): ?>
                                <span class="trend-indicator <?= $trend_class ?>">
                                    <?= $trend_icon ?> <?= $trend_text ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* ตัวอย่างสไตล์ trend */
.trend-up { color: #28a745; font-weight: bold; }
.trend-down { color: #dc3545; font-weight: bold; }
.trend-neutral { color: #6c757d; font-weight: bold; }
.trend-indicator i { margin-right: 4px; }
</style>


<!-- Daily Revenue Table -->
<div class="table-section">
    <div class="section-header">
        <i class="fas fa-calendar-day"></i>
        <span>รายได้แยกตามวัน</span>
    </div>
    <div class="section-content">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar-day"></i> วันที่</th>
                        <th><i class="fas fa-money-bill-wave"></i> รายได้ (บาท)</th>
                        <th><i class="fas fa-tasks"></i> จำนวนงาน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $daily_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('m/d/Y', strtotime($row['date'])) ?></td>
                        <td class="amount"><?= number_format($row['total'],2) ?></td>
                        <td><?= $row['jobs'] ?> งาน</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

   <!-- Jobs Revenue Table -->
<div class="table-section">
    <div class="section-header">
        <i class="fas fa-list-alt"></i>
        <span>รายการงานที่สร้างรายได้</span>
    </div>
    <div class="section-content">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> รหัสจอง</th>
                        <th><i class="fas fa-cogs"></i> บริการ</th>
                        <th><i class="fas fa-calendar-alt"></i> วันที่</th>
                        <th><i class="fas fa-money-bill"></i> ราคา</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $jobs_result->fetch_assoc()): ?>
                    <tr>
                        <td><span class="job-id">#B<?= $row['booking_id'] ?></span></td>
                        <td><?= htmlspecialchars($row['service_name']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                        <td class="amount"><?= number_format($row['price'],2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    // Status Pie Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['งานที่เสร็จสิ้น', 'งานที่รับแล้ว', 'งานที่ปฏิเสธ'],
            datasets: [{
                data: [<?= $status_counts['completed'] ?>, <?= $status_counts['accepted'] ?>, <?= $status_counts['rejected'] ?>],
                backgroundColor: [
                    '#28a745',
                    '#17a2b8', 
                    '#dc3545'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            family: 'Kanit',
                            size: 14
                        }
                    }
                }
            }
        }
    });

    

    // Filter function
    function filterData() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (startDate && endDate) {
            // In real implementation, this would make AJAX call to filter data
            console.log('Filtering from', startDate, 'to', endDate);
            alert('กรองข้อมูลจาก ' + startDate + ' ถึง ' + endDate);
        } else {
            alert('กรุณาเลือกวันที่เริ่มต้นและสิ้นสุด');
        }
    }

    // Add loading animation to cards
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.stat-card, .chart-section, .table-section');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
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