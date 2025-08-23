// user_bookings.php
<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT b.*, s.service_name, sh.shop_name FROM bookings b JOIN services s ON b.service_id = s.service_id JOIN shops sh ON b.shop_id = sh.shop_id WHERE b.user_id = $user_id ORDER BY b.created_at DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการจองของฉัน</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Kanit', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 0 10px;
        }

        .header h2 {
            font-size: 1.8rem;
            font-weight: 500;
            color: #2c3e50;
            margin: 0;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #5a67d8;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 400;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .back-button:hover {
            background: #f8fafc;
            transform: translateX(-5px);
        }

        .back-button i {
            font-size: 1rem;
        }

        .booking-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #5a67d8;
            color: white;
            padding: 12px 15px;
            font-weight: 500;
            font-size: 1rem;
            text-align: left;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f4f8;
            color: #4a5568;
            font-size: 0.95rem;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending { background: #ebf5ff; color: #1a56db; }
        .status-accepted { background: #f0fff4; color: #046c4e; }
        .status-completed { background: #f0f9ff; color: #0c4a6e; }
        .status-rejected { background: #fff5f5; color: #c81e1e; }

        .slip-link {
            color: #5a67d8;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .slip-link:hover {
            background: #f0f4f8;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2><i class="fas fa-calendar-check"></i> ประวัติการจองของคุณ</h2>
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            กลับหน้าหลัก
        </a>
    </div>
    
    <div class="booking-table">
        <table>
    <tr>
        <th>ร้านค้า</th>
        <th>บริการ</th>
        <th>วันที่/เวลา</th>
        <th>สถานที่</th>
        <th>สถานะ</th>
        <th>สลิป</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['shop_name']) ?></td>
        <td><?= htmlspecialchars($row['service_name']) ?></td>
        <td><?= $row['booking_date'] ?> <?= $row['booking_time'] ?></td>
        <td><?= htmlspecialchars($row['address']) ?></td>
        <td>
    <?php
    $status_text = '';
    switch ($row['status']) {
        case 'pending':
            $status_text = 'รอการตอบรับจากร้านค้า';
            break;
        case 'accepted':
            $status_text = 'ร้านค้ารับงานแล้ว';
            break;
        case 'completed':
            $status_text = 'งานเสร็จสิ้น';
            break;
        case 'rejected':
            $status_text = 'ถูกร้านค้าปฏิเสธ';
            break;
        default:
            $status_text = htmlspecialchars($row['status']); // หรือ "ไม่ทราบสถานะ"
            break;
    }
    echo $status_text;
    ?>
</td>

        <td>
    <?php if ($row['payment_slip']): ?>
        <?php 
            $slip_path = $row['payment_slip'];
            // ถ้าไม่มีคำว่า "uploads/" แปะ prefix ให้
            if (strpos($slip_path, 'uploads/') === false) {
                $slip_path = 'uploads/slips/' . $slip_path;
            }
        ?>
        <a href="<?= htmlspecialchars($slip_path) ?>" target="_blank">ดูสลิป</a>
    <?php else: ?>
        -
    <?php endif; ?>
</td>

    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
