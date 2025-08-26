<?php
session_start();
include 'config.php';

// ตรวจสอบ booking_id
if (!isset($_GET['booking_id'])) {
    die("ไม่พบหมายเลขการจอง");
}

$booking_id = intval($_GET['booking_id']);

// ดึงข้อมูลการจอง + ข้อมูลลูกค้า + ชื่อร้าน
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_date, u.name AS customer_name, u.email, u.phone, u.address AS customer_address,
           sh.shop_name
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN shops sh ON b.shop_id = sh.shop_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die("ไม่พบข้อมูลการจอง");
}

// ดึงรายละเอียดบริการจาก booking_details
$stmt = $conn->prepare("
    SELECT s.service_name, s.price, d.quantity
    FROM booking_details d
    JOIN services s ON d.service_id = s.service_id
    WHERE d.booking_id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$details = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จการจอง</title>
    <style>
        body { font-family: Tahoma, sans-serif; margin: 20px; background: #f5f5f5; }
        .receipt-container { max-width: 800px; margin: auto; border: 1px solid #333; padding: 20px; background: white; border-radius: 10px; }
        h2, h3 { text-align: center; margin: 5px 0; }
        .info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #333; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .btn-print { 
            display: block; 
            margin: 20px auto; 
            padding: 10px 20px; 
            font-size: 16px; 
            background: #007bff; 
            color: #fff; 
            border: none; 
            cursor: pointer; 
            border-radius: 5px; 
        }
        .btn-print:hover { background: #0056b3; }
        @media print {
            .btn-print { display: none; }
        }
    </style>
</head>
<body>
<div class="receipt-container">
    <h2>ใบเสร็จการจองบริการร้านแอร์</h2>
    <h3>Air Service Booking System</h3>
    <hr>
    <div class="info">
        <p><strong>ร้านค้า:</strong> <?= htmlspecialchars($booking['shop_name']) ?></p>
        <p><strong>หมายเลขการจอง:</strong> <?= $booking['booking_id'] ?></p>
        <p><strong>วันที่จอง:</strong> <?= $booking['booking_date'] ?></p>
        <p><strong>ชื่อลูกค้า:</strong> <?= htmlspecialchars($booking['customer_name']) ?></p>
        <p><strong>เบอร์โทร:</strong> <?= htmlspecialchars($booking['phone']) ?></p>
        <p><strong>ที่อยู่:</strong> <?= htmlspecialchars($booking['customer_address']) ?></p>
    </div>

    <table>
        <tr>
            <th>บริการ</th>
            <th>จำนวน</th>
            <th>ราคา/หน่วย (บาท)</th>
            <th>รวม (บาท)</th>
        </tr>
        <?php
        $total = 0;
        while ($row = $details->fetch_assoc()) {
            $line_total = $row['quantity'] * $row['price'];
            $total += $line_total;
            echo "<tr>
                    <td>".htmlspecialchars($row['service_name'])."</td>
                    <td>{$row['quantity']}</td>
                    <td>".number_format($row['price'],2)."</td>
                    <td>".number_format($line_total,2)."</td>
                  </tr>";
        }
        ?>
        <tr>
            <td colspan="3"><strong>รวมทั้งหมด</strong></td>
            <td><strong><?= number_format($total,2) ?></strong></td>
        </tr>
    </table>

    <p style="text-align: center; margin-top: 30px;">
        ขอบคุณที่ใช้บริการร้านแอร์ของเรา
    </p>
</div>

<button class="btn-print" onclick="window.print()">🖨 พิมพ์ใบเสร็จ</button>
</body>
</html>
