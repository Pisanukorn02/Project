<?php
session_start();
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

// รายได้แยกตามวัน
$sql_daily = "SELECT DATE(b.created_at) AS date, SUM(s.price) AS total FROM bookings b JOIN services s ON b.service_id = s.service_id WHERE $where_clause AND b.status = 'completed' GROUP BY DATE(b.created_at) ORDER BY date DESC";
$stmt = $conn->prepare($sql_daily);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$daily_result = $stmt->get_result();

// รายได้แยกตามเดือน
$sql_monthly = "SELECT DATE_FORMAT(b.created_at, '%Y-%m') AS month, SUM(s.price) AS total FROM bookings b JOIN services s ON b.service_id = s.service_id WHERE $where_clause AND b.status = 'completed' GROUP BY month ORDER BY month DESC";
$stmt = $conn->prepare($sql_monthly);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$monthly_result = $stmt->get_result();

// งานที่จบแล้ว
$sql_jobs = "SELECT b.booking_id, s.service_name, b.created_at, s.price FROM bookings b JOIN services s ON b.service_id = s.service_id WHERE $where_clause AND b.status = 'completed' ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql_jobs);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$jobs_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานรายได้</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        h2, h3 { color: #1976d2; }
        form { margin-bottom: 20px; }
        .chart-container { width: 100%; max-width: 700px; margin: auto; }
    </style>
</head>
<body>

    <h2>รายงานรายได้</h2>

    <form method="GET">
        <label>วันที่เริ่มต้น: <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>"></label>
        <label>ถึงวันที่: <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>"></label>
        <button type="submit">กรอง</button>
    </form>

    <h3>ยอดรอรับ (Accepted): <?= number_format($accepted_total ?? 0, 2) ?> บาท</h3>
    <h3>ยอดจบงาน (Completed): <?= number_format($completed_total ?? 0, 2) ?> บาท</h3>

    <h3>จำนวนงาน</h3>
    <ul>
        <li>งานที่รับแล้ว: <?= $status_counts['accepted'] ?></li>
        <li>งานที่ปฏิเสธ: <?= $status_counts['rejected'] ?></li>
        <li>งานที่จบแล้ว: <?= $status_counts['completed'] ?></li>
    </ul>

    <div class="chart-container">
        <canvas id="statusChart"></canvas>
    </div>

    <h3>รายได้แยกตามเดือน</h3>
    <table>
        <tr><th>เดือน</th><th>รายได้ (บาท)</th></tr>
        <?php while ($row = $monthly_result->fetch_assoc()): ?>
            <tr><td><?= $row['month'] ?></td><td><?= number_format($row['total'], 2) ?></td></tr>
        <?php endwhile; ?>
    </table>

    <h3>รายได้แยกตามวัน</h3>
    <table>
        <tr><th>วันที่</th><th>รายได้ (บาท)</th></tr>
        <?php while ($row = $daily_result->fetch_assoc()): ?>
            <tr><td><?= $row['date'] ?></td><td><?= number_format($row['total'], 2) ?></td></tr>
        <?php endwhile; ?>
    </table>

    <h3>รายการงานที่สร้างรายได้</h3>
    <table>
        <tr><th>รหัสจอง</th><th>บริการ</th><th>วันที่</th><th>ราคา</th></tr>
        <?php while ($row = $jobs_result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['booking_id'] ?></td>
                <td><?= htmlspecialchars($row['service_name']) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td><?= number_format($row['price'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <script>
        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['รับงานแล้ว', 'ปฏิเสธ', 'จบงาน'],
                datasets: [{
                    label: 'จำนวนงาน',
                    data: [
                        <?= $status_counts['accepted'] ?>,
                        <?= $status_counts['rejected'] ?>,
                        <?= $status_counts['completed'] ?>
                    ],
                    backgroundColor: ['#28a745', '#dc3545', '#007bff'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

</body>
</html>
