<?php
session_start();
include('config.php');

// รับค่าการค้นหาจาก query string
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$province = isset($_GET['province']) ? trim($_GET['province']) : '';
$service_type = isset($_GET['service_type']) ? trim($_GET['service_type']) : '';

// คำสั่ง SQL เพื่อดึงบริการจากร้านที่ได้รับการอนุมัติแล้ว (is_approved = 0)
$sql = "SELECT s.*, sh.shop_name, sh.address, sh.province 
        FROM services s 
        JOIN shops sh ON s.shop_id = sh.shop_id 
        WHERE sh.is_approved = 0";

$params = [];

if (!empty($query)) {
    $sql .= " AND (sh.shop_name LIKE ? OR sh.address LIKE ?)";
    $like = "%$query%";
    $params[] = $like;
    $params[] = $like;
}

if (!empty($province)) {
    $sql .= " AND (sh.province LIKE ? OR sh.address LIKE ?)";
    $like_province = "%$province%";
    $params[] = $like_province;
    $params[] = $like_province;
}

if (!empty($service_type)) {
    $sql .= " AND s.service_type = ?";
    $params[] = $service_type;
    $types .= "s";
}


$stmt = $conn->prepare($sql);
if ($params) {
    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Project Air - หน้าแรก</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS จากธีมที่ทันสมัยทั้งหมด */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);
            color: #2c3e50;
            min-height: 100vh;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #42a5f5 0%, #1e88e5 100%);
            color: white;
            padding: 25px 0;
            text-align: center;
            box-shadow: 0 4px 20px rgba(30, 136, 229, 0.3);
            position: relative;
            z-index: 2; /* ทำให้ header อยู่ด้านบนสุด */
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50%" cy="0%" r="50%"><stop offset="0%" stop-color="white" stop-opacity="0.1"/><stop offset="100%" stop-color="white" stop-opacity="0"/></radialGradient></defs><rect width="100" height="20" fill="url(%23a)"/></svg>');
            z-index: 0;
        }

        header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        header p {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 16px;
            border-radius: 20px;
            margin-top: 15px;
            display: inline-flex; /* ใช้ flex เพื่อจัด icon กับ text */
            align-items: center;
            gap: 8px; /* ระยะห่างระหว่าง icon กับ text */
            font-weight: 500;
            backdrop-filter: blur(10px);
            z-index: 1;
            position: relative;
        }

        /* Navigation Styles */
        nav {
            background: rgba(25, 118, 210, 0.95);
            padding: 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky; /* ทำให้เมนูติดอยู่ด้านบนเมื่อเลื่อนลงมา */
            top: 0;
            z-index: 1; /* ต่ำกว่า header เล็กน้อย */
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            padding: 0 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            position: relative;
            overflow: hidden;
            display: flex; /* ใช้ flex เพื่อจัด icon กับ text */
            align-items: center;
            gap: 8px; /* ระยะห่างระหว่าง icon กับ text */
        }

        nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        nav a:hover::before {
            left: 100%;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            border-bottom-color: #ffd600;
            transform: translateY(-2px);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Search Bar */
        .search-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 8px 32px rgba(66, 165, 245, 0.15);
            backdrop-filter: blur(10px);
        }

        .search-bar {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-bar input[type="text"],
        .search-bar select {
            flex: 1;
            min-width: 250px;
            padding: 15px 20px;
            border: 2px solid #e3f2fd;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .search-bar input[type="text"]:focus,
        .search-bar select:focus {
            outline: none;
            border-color: #42a5f5;
            background: white;
            box-shadow: 0 0 0 3px rgba(66, 165, 245, 0.1);
        }

        .search-bar button {
            padding: 15px 30px;
            background: linear-gradient(135deg, #42a5f5 0%, #1e88e5 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(66, 165, 245, 0.3);
        }

        .search-bar button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 165, 245, 0.4);
        }

        /* Services Section */
        .services-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .services-header h2 {
            color: #1e88e5;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .services-header p {
            color: #546e7a;
            font-size: 1.1rem;
        }

        /* Service Grid */
        .services-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            padding-bottom: 30px; /* เพิ่ม padding ด้านล่าง */
        }

        .service-card {
            background: white;
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 8px 32px rgba(66, 165, 245, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            overflow: hidden;
            border: 1px solid rgba(66, 165, 245, 0.1);
            position: relative;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #42a5f5, #1e88e5, #0d47a1);
            transform: scaleX(0);
            transform-origin: left; /* แก้ไขให้ขีดเส้นมาจากซ้าย */
            transition: transform 0.3s ease;
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(66, 165, 245, 0.25);
        }

        .service-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 20px 20px 0 0; /* ทำให้มุมบนโค้งมน */
        }

        .service-content {
            padding: 25px;
        }

        .service-card h3 {
            color: #1e88e5;
            font-size: 1.3rem;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .service-card p {
            color: #546e7a;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 0.95rem;
            min-height: 4.5em; /* กำหนดความสูงขั้นต่ำเพื่อความสม่ำเสมอ */
            display: -webkit-box;
            -webkit-line-clamp: 3; /* จำกัดจำนวนบรรทัด */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .shop-details {
            margin-bottom: 15px;
        }

        .shop-name {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #37474f;
            font-size: 0.9rem;
        }

        .shop-name i {
            color: #42a5f5;
            width: 16px;
        }

        .shop-name strong {
            color: #1e88e5;
        }

        .price-display {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            text-align: center;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .no-services {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(66, 165, 245, 0.1);
            grid-column: 1 / -1; /* ทำให้ข้อความอยู่กึ่งกลางเมื่อไม่มีบริการ */
        }

        .no-services i {
            font-size: 4rem;
            color: #e0e0e0;
            margin-bottom: 20px;
        }

        .no-services h3 {
            color: #546e7a;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .no-services p {
            color: #90a4ae;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header h1 {
                font-size: 2rem;
            }

            .nav-container {
                flex-direction: column;
                padding: 0;
            }

            nav a {
                text-align: center;
                padding: 12px 20px;
                justify-content: center; /* จัด icon และ text ให้อยู่ตรงกลาง */
            }

            .search-bar {
                flex-direction: column;
            }

            .search-bar input[type="text"],
            .search-bar select,
            .search-bar button {
                width: 100%;
                min-width: auto;
            }

            .services-list {
                grid-template-columns: 1fr;
            }
        }
        .service-content {
    padding: 25px;
    display: flex; /* เพิ่ม display: flex */
    flex-direction: column; /* จัดเรียงองค์ประกอบในแนวตั้ง */
    /* justify-content: space-between; ถ้าอยากให้บางส่วนอยู่ด้านบนสุดและบางส่วนอยู่ด้านล่างสุด เช่น ราคา */
    flex-grow: 1; /* ทำให้เนื้อหาขยายเต็มพื้นที่หาก service-card เป็น flex item */
}

.service-card {
    /* ... โค้ดเดิม ... */
    display: flex; /* ทำให้การ์ดเป็น flex container */
    flex-direction: column; /* จัดเรียงรูปภาพและเนื้อหาในแนวตั้ง */
}
    </style>
</head>
<body>

<header>
    <h1><i class="fas fa-snowflake"></i> Project Air</h1>
    <p>แพลตฟอร์มค้นหาและจองบริการร้านแอร์</p>
    <?php if (isset($_SESSION['username'])): ?>
        <div class="user-info">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['username']) ?></div>
    <?php endif; ?>
</header>

<nav>
    <div class="nav-container">
        <a href="index.php"><i class="fas fa-home"></i> หน้าแรก</a>
        <a href="user_profile.php"><i class="fas fa-map-marker-alt"></i> ข้อมูลส่วนตัว</a>
        <a href="user_bookings.php"><i class="fas fa-calendar-check"></i> การจองของฉัน</a>
        <a href="nearby_shops.php"><i class="fas fa-map-marker-alt"></i> หาจากแผนที่</a>
        <a href="cart.php"><i class="fas fa-map-marker-alt"></i> ตะกร้าของฉัน</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'shop'): ?>
            <a href="shop_board.php"><i class="fas fa-store"></i> ร้านของฉัน</a>
        <?php endif; ?>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="register_user.html"><i class="fas fa-user-plus"></i> สมัครบัญชีผู้ใช้</a>
            <a href="register_shop.html"><i class="fas fa-store-alt"></i> สมัครร้านค้า</a>
            <a href="login.html"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
        <?php else: ?>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Search Form -->
<form method="GET" action="index.php" class="search-bar">
    <input type="text" name="query" placeholder="ค้นหาชื่อหรือที่อยู่ร้านค้า" value="<?= htmlspecialchars($query) ?>">
    
      <select name="service_type">
        <option value="">-- ประเภทบริการ --</option>
        <?php
        $service_types = ['ซ่อม', 'ล้าง', 'ติดตั้ง/เคลื่อนย้าย'];
        foreach ($service_types as $type) {
            $selected = ($service_type === $type) ? 'selected' : '';
            echo "<option value=\"$type\" $selected>$type</option>";
        }
        ?>
    </select>
    
    <select name="province">
        <option value="">-- เลือกจังหวัด --</option>
        <?php
        $provinces = [
    "กรุงเทพมหานคร", "กระบี่", "กาญจนบุรี", "กาฬสินธุ์", "กำแพงเพชร", "ขอนแก่น", "จันทบุรี", 
    "ฉะเชิงเทรา", "ชลบุรี", "ชัยนาท", "ชัยภูมิ", "ชุมพร", "เชียงราย", "เชียงใหม่", 
    "ตรัง", "ตราด", "ตาก", "นครนายก", "นครปฐม", "นครพนม", "นครราชสีมา", "นครศรีธรรมราช", 
    "นครสวรรค์", "นนทบุรี", "นราธิวาส", "น่าน", "บึงกาฬ", "บุรีรัมย์", "ปทุมธานี", 
    "ประจวบคีรีขันธ์", "ปราจีนบุรี", "ปัตตานี", "พระนครศรีอยุธยา", "พะเยา", "พังงา", 
    "พัทลุง", "พิจิตร", "พิษณุโลก", "เพชรบุรี", "เพชรบูรณ์", "แพร่", "พรรค", "ภูเก็ต", 
    "มหาสารคาม", "มุกดาหาร", "แม่ฮ่องสอน", "ยโสธร", "ยะลา", "ร้อยเอ็ด", "ระนอง", "ระยอง", 
    "ราชบุรี", "ลพบุรี", "ลำปาง", "ลำพูน", "เลย", "ศรีสะเกษ", "สกลนคร", "สงขลา", 
    "สตูล", "สมุทรปราการ", "สมุทรสงคราม", "สมุทรสาคร", "สระแก้ว", "สระบุรี", 
    "สิงห์บุรี", "สุโขทัย", "สุพรรณบุรี", "สุราษฎร์ธานี", "สุรินทร์", "หนองคาย", 
    "หนองบัวลำภู", "อ่างทอง", "อำนาจเจริญ", "อุดรธานี", "อุตรดิตถ์", "อุทัยธานี", 
    "อุบลราชธานี"
];


        foreach ($provinces as $p) {
            $selected = ($province === $p) ? 'selected' : '';
            echo "<option value=\"$p\" $selected>$p</option>";
        }
        ?>
    </select>
    <button type="submit"><i class="fas fa-search"></i> ค้นหา</button>
</form>

<!-- Services List -->
<h2 style="text-align:center; color:#1976d2;">บริการจากร้านค้าต่างๆ</h2>
<div class="services-list">
<?php
if ($result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
?>
   <a href="shop.php?shop_id=<?= $row['shop_id'] ?>&service_id=<?= $row['service_id'] ?>" class="service-card">


       <?php 
                $image_path = 'uploads/' . htmlspecialchars($row['image']);
                if (file_exists($image_path) && !empty($row['image'])): ?>
                    <img src="<?= $image_path ?>" alt="รูปบริการ <?= htmlspecialchars($row['service_name']) ?>" class="service-image">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x200?text=No+Image" alt="ไม่มีรูปภาพ" class="service-image">
                <?php endif; ?>
                <div class="service-content">
                    <h3><?= htmlspecialchars($row['service_name']) ?></h3>
                    <p><?= htmlspecialchars($row['description']) ?></p>
                    <div class="shop-details">
                        <div class="shop-name">
                            <i class="fas fa-store"></i>
                            <strong>ร้าน:</strong> <?= htmlspecialchars($row['shop_name']) ?>
                        </div>
                        <div class="shop-name">
                            <i class="fas fa-map-marker-alt"></i>
                            <strong>ที่อยู่:</strong> <?= htmlspecialchars($row['address']) ?>
                        </div>
                    </div>
                    <div class="price-display">
                        <i class="fas fa-tags"></i> ราคา: <?= number_format($row['price'], 2) ?> บาท
                    </div>
                </div>
    </a>
<?php
    endwhile;
else:
    echo "<p class='no-services'>ไม่พบบริการใดๆ</p>";
endif;

$conn->close();
?>
</div>

</body>
</html>
