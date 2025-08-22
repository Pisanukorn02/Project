<?php
// search.php
include 'config.php';

$query_text = isset($_GET['query']) ? trim($_GET['query']) : ''; // ใช้ชื่อตัวแปรที่ชัดเจนขึ้น
$province_name = isset($_GET['province']) ? trim($_GET['province']) : ''; // ใช้ชื่อตัวแปรที่ชัดเจนขึ้น

// สร้าง SQL query พื้นฐาน
// เปลี่ยนเป็น sh.is_approved = 0 เพื่อแสดงร้านที่อนุมัติแล้ว (สอดคล้องกับ admin_process.php)
$sql = "SELECT s.*, sh.shop_name, sh.address, sh.province 
        FROM services s 
        JOIN shops sh ON s.shop_id = sh.shop_id 
        WHERE sh.is_approved = 0"; // ใช้ sh.is_approved เพื่อตรวจสอบสถานะร้านค้า

$params = []; // อาร์เรย์สำหรับเก็บค่าที่จะผูก
$types = "";   // สตริงสำหรับเก็บชนิดข้อมูลของพารามิเตอร์

// เพิ่มเงื่อนไขการค้นหาตามชื่อร้านหรือที่อยู่
if (!empty($query_text)) {
    $sql .= " AND (sh.address LIKE ? OR sh.shop_name LIKE ?)";
    $params[] = "%" . $query_text . "%";
    $params[] = "%" . $query_text . "%";
    $types .= "ss"; // สอง string
}

// เพิ่มเงื่อนไขการค้นหาตามจังหวัด
if (!empty($province_name)) {
    $sql .= " AND sh.province = ?";
    $params[] = $province_name;
    $types .= "s"; // หนึ่ง string
}

// เตรียมคำสั่ง SQL
$stmt = $conn->prepare($sql);

// ตรวจสอบข้อผิดพลาดในการเตรียมคำสั่ง
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// ผูกค่าพารามิเตอร์แบบไดนามิก
if (!empty($params)) {
    // สร้างอาร์เรย์สำหรับ call_user_func_array
    // ตัวแรกคือ string types, ที่เหลือคือ references ของ params
    $bind_args = [$types];
    foreach ($params as $key => &$value) { // ใช้ & เพื่อส่งเป็น reference
        $bind_args[] = &$value;
    }
    // เรียก bind_param แบบไดนามิก
    call_user_func_array([$stmt, 'bind_param'], $bind_args);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ผลการค้นหา</title>
    <link href="https://fonts.googleapis.com/css?family=Kanit:400,700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            background: #f4f8fb;
            color: #222;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #1976d2;
            margin-bottom: 30px;
        }
        .services-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 25px;
            padding: 0 20px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .service-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 20px;
            border: 1px solid #ddd;
            transition: transform 0.2s, border-color 0.2s, color 0.2s;
            color: #333;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .service-card:hover {
            transform: translateY(-5px) scale(1.02);
            border-color: #1976d2;
            color: #1976d2;
            cursor: pointer;
        }
        .service-card a {
            text-decoration: none;
            color: inherit;
        }
        .service-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .service-card h3 {
            margin: 0 0 10px 0;
            color: #1976d2;
            font-size: 1.3em;
        }
        .service-card p {
            margin-bottom: 10px;
            font-size: 0.95em;
            line-height: 1.5;
            flex-grow: 1;
        }
        .shop-info {
            font-size: 0.95em;
            color: #555;
            margin-bottom: 4px;
        }
        .shop-info strong {
            color: #1976d2;
        }
        .price-display {
            font-size: 1.1em;
            font-weight: bold;
            color: #28a745;
            margin-top: 10px;
        }
        .no-results {
            text-align: center;
            padding: 50px;
            font-size: 1.2em;
            color: #666;
            width: 100%; /* ให้ข้อความอยู่ตรงกลางใน grid */
            grid-column: 1 / -1; /* ครอบคลุมทุกคอลัมน์ใน grid */
        }
    </style>
</head>
<body>
    <h2>ผลการค้นหาบริการ</h2>
    <div class="services-list">
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <a href="shop.php?shop_id=<?= htmlspecialchars($row['shop_id']); ?>" class="service-card">
                <img src="uploads/<?= htmlspecialchars($row['image']); ?>" alt="รูปบริการ">
                <h3><?= htmlspecialchars($row['service_title']); ?></h3>
                <p><?= htmlspecialchars($row['description']); ?></p>
                <div class="shop-info"><strong>ร้าน:</strong> <?= htmlspecialchars($row['shop_name']); ?></div>
                <div class="shop-info"><strong>ที่อยู่:</strong> <?= htmlspecialchars($row['address']); ?></div>
                <div class="shop-info"><strong>จังหวัด:</strong> <?= htmlspecialchars($row['province']); ?></div>
                <div class="price-display"><strong>ราคา:</strong> <?= htmlspecialchars($row['price']); ?> บาท</div>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-results">ไม่พบบริการที่ตรงกับเกณฑ์การค้นหาของคุณ</p>
    <?php endif; ?>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>