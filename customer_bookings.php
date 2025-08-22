<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT b.*, s.service_name, s.price, sh.shop_name FROM bookings b 
        JOIN services s ON b.service_id = s.service_id 
        JOIN shops sh ON b.shop_id = sh.shop_id
        WHERE b.user_id = ? ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>การจองของฉัน</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        .btn-review {
            background: #ffc107; color: #000; padding: 5px 10px;
            border-radius: 5px; text-decoration: none;
        }
        .btn-review:hover { background: #e0a800; }
    </style>
</head>
<body>
    <h2>การจองของฉัน</h2>

    <table>
        <thead>
            <tr>
                <th>บริการ</th>
                <th>ร้านค้า</th>
                <th>วันที่จอง</th>
                <th>เวลา</th>
                <th>สถานะ</th>
                <th>ราคา</th>
                <th>รีวิว</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                    <td><?= htmlspecialchars($row['shop_name']) ?></td>
                    <td><?= htmlspecialchars($row['booking_date']) ?></td>
                    <td><?= htmlspecialchars($row['booking_time']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= number_format($row['price'], 2) ?> บาท</td>
                    <td>
                        <?php
                        if ($row['status'] === 'completed') {
                            // ตรวจสอบว่ามีรีวิวแล้วหรือยัง
                            $check = $conn->prepare("SELECT * FROM reviews WHERE booking_id = ?");
                            $check->bind_param("i", $row['booking_id']);
                            $check->execute();
                            $review_result = $check->get_result();

                            if ($review_result->num_rows === 0) {
                                echo "<a href='review_form.php?booking_id=" . $row['booking_id'] . "' class='btn-review'>รีวิว</a>";
                            } else {
                                echo "รีวิวแล้ว";
                            }
                            $check->close();
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
