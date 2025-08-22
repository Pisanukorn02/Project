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