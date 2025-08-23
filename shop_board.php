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
    <link rel="stylesheet" href="shop_board.css"> <!-- CSS ของ shop_board -->
    <script src="https://kit.fontawesome.com/yourcode.js" crossorigin="anonymous"></script>

    
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
    // อัพโหลดหลักฐานงานเสร็จก่อนจบงาน
    echo "<form method='POST' action='booking_action.php' enctype='multipart/form-data' onsubmit='return confirm(\"อัพโหลดรูปหลักฐานและยืนยันการจบงาน?\");' style='display:inline-block;'>";
    echo "<input type='hidden' name='booking_id' value='" . htmlspecialchars($row['booking_id']) . "'>";
    echo "<input type='file' name='completion_proof' accept='image/*' required style='margin-bottom:5px; display:block;'>";
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
<a href="report.php" class="add-service-btn">รายได้</a>
</body>
</html>

