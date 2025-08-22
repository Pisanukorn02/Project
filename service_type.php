<?php
session_start();
include 'config.php';

// รับค่าประเภทบริการจาก query string เช่น ?type=ล้าง
$service_type = isset($_GET['type']) ? trim($_GET['type']) : '';

if (empty($service_type)) {
    echo "ไม่พบประเภทบริการที่ต้องการ";
    exit;
}

// ค้นหาบริการจากร้านค้าที่อนุมัติแล้ว
$stmt = $conn->prepare("SELECT s.*, sh.shop_name, sh.address 
                        FROM services s
                        JOIN shops sh ON s.shop_id = sh.shop_id
                        WHERE s.service_type = ? AND sh.is_approved = 0");
$stmt->bind_param("s", $service_type);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บริการประเภท <?= htmlspecialchars($service_type) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        header {
            background: #1976d2;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .services-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            padding: 30px;
            max-width: 1200px;
            margin: auto;
        }
        .service-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-decoration: none;
            color: black;
            transition: 0.3s;
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
        .service-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }
        .service-card h3 {
            margin: 10px 0;
            color: #1976d2;
        }
        .service-card .price {
            font-weight: bold;
            color: green;
        }
    </style>
</head>
<body>

<header>
    <h1>บริการประเภท: <?= htmlspecialchars($service_type) ?></h1>
</header>

<div class="services-list">
<?php
if ($result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
?>
    <a href="shop.php?shop_id=<?= $row['shop_id'] ?>" class="service-card">
        <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="รูปบริการ">
        <h3><?= htmlspecialchars($row['service_name']) ?></h3>
        <p><?= htmlspecialchars($row['description']) ?></p>
        <div class="price">ราคา: <?= htmlspecialchars($row['price']) ?> บาท</div>
        <div>ร้าน: <?= htmlspecialchars($row['shop_name']) ?></div>
        <div>ที่อยู่: <?= htmlspecialchars($row['address']) ?></div>
    </a>
<?php
    endwhile;
else:
    echo "<p style='text-align:center;'>ไม่พบบริการในประเภทนี้</p>";
endif;
$conn->close();
?>
</div>

</body>
</html>
