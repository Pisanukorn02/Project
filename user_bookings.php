// user_bookings.php
<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT b.booking_id, b.booking_date, b.booking_time, b.address, b.location_lat, b.location_lng,
               b.payment_slip, b.status, b.complete_image,
               sh.shop_name,
               SUM(s.price * d.quantity) AS total_price,
               GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services
        FROM bookings b
        JOIN shops sh ON b.shop_id = sh.shop_id
        JOIN booking_details d ON b.booking_id = d.booking_id
        JOIN services s ON d.service_id = s.service_id
        WHERE b.user_id = ?
        GROUP BY b.booking_id
        ORDER BY b.created_at DESC";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

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

        .complete-img {
            max-width: 50px;
            cursor: pointer;
            border-radius: 4px;
            transition: transform 0.2s;
        }

        .complete-img:hover {
            transform: scale(1.2);
        }

        /* Lightbox */
        #lightbox {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
        }

        #lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
        }

        .btn-back {
            background: linear-gradient(135deg, #90a4ae 0%, #78909c 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(144, 164, 174, 0.4);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2><i class="fas fa-calendar-check"></i> ประวัติการจองของคุณ</h2>
        
    </div>
    
    <div class="booking-table">
       <table>
    <tr>
        <th>ร้านค้า</th>
        <th>บริการ</th>
        <th>ราคายอดรวม(บาท)</th>
        <th>วันที่/เวลา</th>
        <th>สถานที่</th>
        <th>สถานะ</th>
        <th>หน้างาน</th>
        <th>ใบเสร็จ</th>
        <th>รูปจบงาน</th>
       
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['shop_name']) ?></td>
        <td>
            <?php 
            $services = explode(',', $row['services']);
            foreach ($services as $service) {
                echo htmlspecialchars($service) . "<br>";
            }
            ?>
        </td>
 <td><?= number_format($row['total_price'], 2) ?></td>

        <td><?= $row['booking_date'] ?> <?= $row['booking_time'] ?></td>
        <td><?= htmlspecialchars($row['address']) ?></td>
        <td>
            <?php
            $status_text = '';
            switch ($row['status']) {
                case 'pending': $status_text = 'รอการตอบรับ'; break;
                case 'accepted': $status_text = 'ร้านค้ารับงานแล้ว'; break;
                case 'completed': $status_text = 'งานเสร็จสิ้น'; break;
                case 'rejected': $status_text = 'ถูกร้านค้าปฏิเสธ'; break;
                default: $status_text = htmlspecialchars($row['status']); break;
            }
            echo $status_text;
            ?>
        </td>
        <td>
            <?php if ($row['payment_slip']): 
                $slip_path = strpos($row['payment_slip'], 'uploads/') === false ? 'uploads/slips/' . $row['payment_slip'] : $row['payment_slip'];
            ?>
                <a href="<?= htmlspecialchars($slip_path) ?>" target="_blank">ดูหน้างาน</a>
            <?php else: ?> - <?php endif; ?>
        </td>
        <td>
            <?php if ($row['status'] === 'completed'): ?>
                <a href="receipt.php?booking_id=<?= $row['booking_id'] ?>" target="_blank" class="slip-link">
                    <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
                </a>
            <?php else: ?> - <?php endif; ?>
        </td>
        <td>
            <?php
            if (!empty($row['complete_image'])) {
                $img_path = strpos($row['complete_image'], 'uploads/') === false ? 'uploads/completions/' . $row['complete_image'] : $row['complete_image'];
                echo '<img src="' . htmlspecialchars($img_path) . '" class="complete-img" onclick="openImage(this.src)">';
            } else { echo "-"; }
            ?>
        </td>
       
    </tr>
    <?php endwhile; ?>
</table>

    </div>
</div>

<!-- Lightbox -->
<div id="lightbox" onclick="closeLightbox()">
    <img id="lightbox-img" src="">
</div>

<script>
function openImage(src){
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').style.display='flex';
}
function closeLightbox(){
    document.getElementById('lightbox').style.display='none';
}
</script>
 <!-- ปุ่มกลับ -->
        <div style="text-align: center;">
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> กลับไปหน้าหลัก
</body>
</html>
