<?php
session_start();
include 'config.php'; // ตรวจสอบให้แน่ใจว่าไฟล์ config.php เชื่อมต่อฐานข้อมูลได้ถูกต้อง

// --- ตรรกะการตรวจสอบสิทธิ์ที่แก้ไขแล้ว ---

// ถ้ายังไม่มี session 'admin_logged_in' และไม่ได้ส่งฟอร์มล็อกอินมา ให้เปลี่ยนเส้นทางไปหน้าล็อกอิน
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $submitted_username = $_POST['username'];
        $submitted_password = $_POST['password'];

        // 1. ดึงชื่อผู้ใช้และรหัสผ่านที่ถูกแฮชจากฐานข้อมูล
        $stmt = $conn->prepare("SELECT username, password FROM admins WHERE username = ?");
        
        // ตรวจสอบว่าการเตรียมคำสั่ง SQL สำเร็จหรือไม่
        if ($stmt === false) {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error;
            header("Location: admin_login.php");
            exit();
        }

        $stmt->bind_param("s", $submitted_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin_data = $result->fetch_assoc();
            $stored_hashed_password = $admin_data['password'];

            // 2. ใช้ password_verify() เพื่อเปรียบเทียบรหัสผ่านที่ผู้ใช้ป้อนกับรหัสผ่านที่ถูกแฮช
            if (password_verify($submitted_password, $stored_hashed_password)) {
                // รหัสผ่านถูกต้อง
                $_SESSION['admin_logged_in'] = true;
                // คุณอาจตั้งค่าตัวแปรเซสชันอื่น ๆ ที่นี่ เช่น $_SESSION['admin_id'] = $admin_data['admin_id'];
            } else {
                // รหัสผ่านไม่ถูกต้อง
                $_SESSION['error_message'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                header("Location: admin_login.php");
                exit();
            }
        } else {
            // ไม่พบชื่อผู้ใช้ในฐานข้อมูล
            $_SESSION['error_message'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            header("Location: admin_login.php");
            exit();
        }
        $stmt->close(); // ปิด statement
    } else {
        // หากยังไม่ได้ล็อกอินและไม่ได้ส่งฟอร์มล็อกอินมา ให้เปลี่ยนเส้นทางไปหน้าล็อกอิน
        header("Location: admin_login.php");
        exit();
    }
}

// --- ส่วนที่เหลือของโค้ด admin_dashboard.php ของคุณยังคงอยู่ด้านล่าง ---

// Fetch shops awaiting approval
$pending_shops = [];
// *** แก้ไข SQL query ให้ดึงร้านค้าที่ status เป็น 'pending' ***
$sql = "SELECT s.shop_id, u.name AS owner_name, u.email, u.phone, s.shop_name, s.address, s.province, s.latitude, s.longitude, s.created_at
        FROM shops s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.is_approved = 1";
$result = $conn->query($sql); // ใช้ 'pending' หรือสถานะที่เหมาะสมที่คุณตั้งไว้สำหรับร้านค้าที่รอการอนุมัติ 
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pending_shops[] = $row;
    }
}
$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าผู้ดูแลระบบ</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="http://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoDSe/GLy4+gKUprMyGAM+fTwFxJT4GzftwmaQ/" crossorigin="" />
    <style>
        .map-container {
            height: 200px; /* กำหนดความสูงสำหรับแผนที่แต่ละอัน */
            width: 100%;
            margin-top: 10px;
            border-radius: 8px;
            background-color: #e0e0e0; /* เพื่อให้เห็นว่ามีพื้นที่สำหรับแผนที่ */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top; /* เพื่อให้แผนที่ใน cell ไม่เบียด */
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h2>ยินดีต้อนรับสู่หน้าผู้ดูแลระบบ</h2>
        <p>
            <a href="admin_logout.php">ออกจากระบบ</a> |
            <a href="admin_users_manage.php">จัดการผู้ใช้งานระบบ</a> </p>

            
        <h3>ร้านค้าที่รอการอนุมัติ</h3>

        <?php if (empty($pending_shops)): ?>
            <p>ไม่มีร้านค้าที่รอการอนุมัติในขณะนี้</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อเจ้าของ</th>
                        <th>อีเมล</th>
                        <th>เบอร์โทร</th>
                        <th>ชื่อร้านค้า</th>
                        <th>ที่อยู่</th>
                        <th>จังหวัด</th>
                        <th>วันที่สมัคร</th>
                        <th>แผนที่</th>
                        <th>ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_shops as $shop): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($shop['shop_id']); ?></td>
                            <td><?php echo htmlspecialchars($shop['owner_name']); ?></td>
                            <td><?php echo htmlspecialchars($shop['email']); ?></td>
                            <td><?php echo htmlspecialchars($shop['phone']); ?></td>
                            <td><?php echo htmlspecialchars($shop['shop_name']); ?></td>
                            <td><?php echo htmlspecialchars($shop['address']); ?></td>
                            <td><?php echo htmlspecialchars($shop['province']); ?></td>
                            <td><?php echo htmlspecialchars($shop['created_at']); ?></td>
                            <td>
                                <div id="map-<?php echo htmlspecialchars($shop['shop_id']); ?>" class="map-container"></div>
                            </td>
                            <td>
                                <form action="admin_process.php" method="POST" class="action-form">
                                    <input type="hidden" name="shop_id" value="<?php echo htmlspecialchars($shop['shop_id']); ?>">
                                      <!-- ✅ ปุ่มอนุมัติ -->
    <button type="submit" name="action" value="approve" class="approve-btn">อนุมัติ</button>

    <!-- ✅ Input เหตุผลการปฏิเสธ -->
    <input type="text" name="reject_reason" placeholder="เหตุผลการปฏิเสธ" style="margin-top: 5px;" >

    <!-- ✅ ปุ่มปฏิเสธ -->
    <button type="submit" name="action" value="reject" class="reject-btn">ปฏิเสธ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script src="http://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjGwZFYgSY84FNzIO0yiQNLLRxmrhg=" crossorigin=""></script>
    <script>
        // โค้ด JavaScript สำหรับแต่ละแผนที่
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($pending_shops as $shop): ?>
                const lat_<?php echo htmlspecialchars($shop['shop_id']); ?> = <?php echo json_encode($shop['latitude']); ?>;
                const lng_<?php echo htmlspecialchars($shop['shop_id']); ?> = <?php echo json_encode($shop['longitude']); ?>;
                const shopName_<?php echo htmlspecialchars($shop['shop_id']); ?> = <?php echo json_encode(htmlspecialchars($shop['shop_name'])); ?>;
                const mapId_<?php echo htmlspecialchars($shop['shop_id']); ?> = 'map-<?php echo htmlspecialchars($shop['shop_id']); ?>';
                const mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?> = document.getElementById(mapId_<?php echo htmlspecialchars($shop['shop_id']); ?>);

                if (lat_<?php echo htmlspecialchars($shop['shop_id']); ?> && lng_<?php echo htmlspecialchars($shop['shop_id']); ?> && mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>) {
                    const map_<?php echo htmlspecialchars($shop['shop_id']); ?> = L.map(mapId_<?php echo htmlspecialchars($shop['shop_id']); ?>).setView([lat_<?php echo htmlspecialchars($shop['shop_id']); ?>, lng_<?php echo htmlspecialchars($shop['shop_id']); ?>], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map_<?php echo htmlspecialchars($shop['shop_id']); ?>);

                    L.marker([lat_<?php echo htmlspecialchars($shop['shop_id']); ?>, lng_<?php echo htmlspecialchars($shop['shop_id']); ?>]).addTo(map_<?php echo htmlspecialchars($shop['shop_id']); ?>)
                        .bindPopup(shopName_<?php echo htmlspecialchars($shop['shop_id']); ?>).openPopup();
                } else if (mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>) {
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.innerText = "ไม่มีข้อมูลตำแหน่ง";
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.style.backgroundColor = "#ffdddd"; /* แสดงสีแดงอ่อนถ้าไม่มีข้อมูล */
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.style.display = "flex";
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.style.alignItems = "center";
                    mapElement_<?php echo htmlspecialchars($shop['shop_id']); ?>.style.justifyContent = "center";
                }
            <?php endforeach; ?>
        });
    </script>
</body>
</html>