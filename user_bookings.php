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
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f8fb;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #1976d2;
            color: white;
        }
    </style>
</head>
<body>
<h2>ประวัติการจองของคุณ</h2>
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
