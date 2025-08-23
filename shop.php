<?php
session_start();
include 'config.php';

if (!isset($_GET['shop_id'])) {
    die("ไม่พบร้านค้า");
}
$shop_id = intval($_GET['shop_id']);

// ดึงข้อมูลร้าน
$shop_sql = "SELECT * FROM shops WHERE shop_id = ?";
$shop_stmt = $conn->prepare($shop_sql);
$shop_stmt->bind_param("i", $shop_id);
$shop_stmt->execute();
$shop_result = $shop_stmt->get_result();
$shop = $shop_result->fetch_assoc();

if (!$shop) {
    die("ไม่พบร้านค้านี้");
}

// ดึงบริการของร้านนี้
$shop_id = intval($_GET['shop_id']);
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if ($service_id > 0) {
    // ถ้ามีการส่ง service_id มา → ดึงเฉพาะบริการนั้น
    $service_sql = "SELECT * FROM services WHERE shop_id = ? AND service_id = ? AND is_approved = 1";
    $service_stmt = $conn->prepare($service_sql);
    $service_stmt->bind_param("ii", $shop_id, $service_id);
} else {
    // ถ้าไม่มี service_id → ดึงบริการทั้งหมดของร้าน
    $service_sql = "SELECT * FROM services WHERE shop_id = ? AND is_approved = 1";
    $service_stmt = $conn->prepare($service_sql);
    $service_stmt->bind_param("i", $shop_id);
}

$service_stmt->execute();
$services = $service_stmt->get_result();


// ดึงรีวิวร้าน
$stmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, u.name FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();

// สำหรับฟอร์มรีวิว: ตรวจสอบ booking ของ user ที่จบแล้วกับร้านนี้ (ถ้า login แล้ว)
$can_review = false;
$pending_booking_id = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check_booking_stmt = $conn->prepare("SELECT b.booking_id FROM bookings b LEFT JOIN reviews r ON b.booking_id = r.booking_id WHERE b.user_id = ? AND b.shop_id = ? AND b.status = 'completed' AND r.review_id IS NULL LIMIT 1");
    $check_booking_stmt->bind_param("ii", $user_id, $shop_id);
    $check_booking_stmt->execute();
    $check_booking_result = $check_booking_stmt->get_result();
    if ($row = $check_booking_result->fetch_assoc()) {
        $can_review = true;
        $pending_booking_id = $row['booking_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลร้าน: <?= htmlspecialchars($shop['shop_name']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9ff 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: #2c3e50;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #42a5f5 0%, #1e88e5 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 4px 20px rgba(66, 165, 245, 0.3);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .shop-info {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border: 1px solid #e3f2fd;
        }

        .shop-info h2 {
            color: #1565c0;
            font-size: 2em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9ff;
            border-radius: 10px;
            border-left: 4px solid #42a5f5;
        }

        .info-item i {
            color: #42a5f5;
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border: 1px solid #e3f2fd;
        }

        .section h3 {
            color: #1565c0;
            font-size: 1.8em;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .service-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid #e3f2fd;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(66, 165, 245, 0.2);
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #42a5f5, #1e88e5);
        }

        .service-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 2px solid #e3f2fd;
        }

        .service-card h4 {
            color: #1565c0;
            font-size: 1.4em;
            margin-bottom: 15px;
        }

        .service-card p {
            margin-bottom: 15px;
            color: #546e7a;
        }

        .price {
            font-size: 1.3em;
            color: #d32f2f;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .btn-book {
            background: linear-gradient(135deg, #42a5f5 0%, #1e88e5 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(66, 165, 245, 0.4);
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 165, 245, 0.6);
            color: white;
            text-decoration: none;
        }

        .review-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid #e3f2fd;
            position: relative;
        }

        .review-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #42a5f5, #1e88e5);
            border-radius: 15px 15px 0 0;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .reviewer-name {
            font-weight: bold;
            color: #1565c0;
            font-size: 1.1em;
        }

        .rating {
            color: #ff9800;
            font-size: 1.2em;
        }

        .review-date {
            color: #90a4ae;
            font-size: 0.9em;
        }

        .review-comment {
            color: #546e7a;
            line-height: 1.6;
            margin-top: 10px;
        }

        .review-form {
            background: linear-gradient(135deg, #e8f5e8 0%, #f1f8ff 100%);
            border-radius: 20px;
            padding: 30px;
            border: 2px solid #c8e6c9;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1565c0;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #42a5f5;
            box-shadow: 0 0 0 3px rgba(66, 165, 245, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.6);
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

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(144, 164, 174, 0.6);
            color: white;
            text-decoration: none;
        }

        .no-reviews {
            text-align: center;
            color: #90a4ae;
            font-style: italic;
            padding: 40px;
            background: #f8f9ff;
            border-radius: 15px;
            border: 2px dashed #e3f2fd;
        }

        .review-notice {
            background: #fff3e0;
            color: #f57c00;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #ffcc02;
            text-align: center;
            font-weight: bold;
        }

        .stars-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stars-input input[type="number"] {
            width: 80px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
            
            .review-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        .btn-cart {
    background-color: #ffc107;
    color: #000;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
}
.btn-cart:hover {
    background-color: #e0a800;
}

/* แทนที่ CSS ของ .shop-image เดิมด้วยโค้ดนี้ */

.shop-info {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    border: 1px solid #e3f2fd;
    position: relative; /* เพิ่มเพื่อให้จัดตำแหน่งได้ */
}

.shop-image {
    position: absolute;    /* ให้รูปลอยอยู่ในตำแหน่งที่กำหนด */
    top: 30px;            /* ระยะห่างจากด้านบน */
    right: 30px;          /* ระยะห่างจากด้านขวา */
    width: 120px;         /* ขนาดรูป */
    height: 120px;        /* ความสูงเท่ากับความกว้าง */
    overflow: hidden;
    border-radius: 15px;  /* เปลี่ยนจาก 50% เป็น 15px เพื่อให้เป็นสี่เหลี่ยมมุมโค้ง */
    border: 3px solid #42a5f5;  /* เปลี่ยนสีขอบให้เข้ากับธีม */
    box-shadow: 0 4px 15px rgba(66, 165, 245, 0.3);
    z-index: 1;           /* ให้รูปอยู่ข้างหน้า */
}

.shop-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;    /* ปรับให้รูปเต็มกรอบโดยไม่เบี้ยว */
    display: block;
}

/* ปรับให้เนื้อหาไม่ทับกับรูป */
.shop-info h2 {
    color: #1565c0;
    font-size: 2em;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-right: 140px;  /* เว้นที่ว่างสำหรับรูป */
}

.shop-info .info-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9ff;
    border-radius: 10px;
    border-left: 4px solid #42a5f5;
    margin-right: 140px;  /* เว้นที่ว่างสำหรับรูป */
}

/* สำหรับหน้าจอมือถือ */
@media (max-width: 768px) {
    .shop-image {
        position: static;     /* ยกเลิกการลอย */
        margin: 0 auto 20px auto;  /* จัดกึ่งกลาง */
        width: 100px;
        height: 100px;
    }
    
    .shop-info h2,
    .shop-info .info-item {
        margin-right: 0;      /* ยกเลิกการเว้นที่ว่าง */
    }
}

    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-store"></i> ข้อมูลร้าน</h1>
        </div>
    </div>

    <div class="container">
        <!-- ข้อมูลร้าน -->
        <div class="shop-info">
            <!-- รูปภาพร้าน -->
        <?php if (!empty($shop['shop_image'])): ?>
            <div class="shop-image">
                <img src="uploads/shop_images/<?= htmlspecialchars($shop['shop_image']) ?>" alt="<?= htmlspecialchars($shop['shop_name']) ?>">
            </div>
        <?php endif; ?>
        
            <h2><i class="fas fa-store-alt"></i> <?= htmlspecialchars($shop['shop_name']) ?></h2>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span><strong>ที่อยู่:</strong> <?= htmlspecialchars($shop['address']) ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-map-pin"></i>
                <span><strong>จังหวัด:</strong> <?= htmlspecialchars($shop['province']) ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <span><strong>เบอร์โทร:</strong> <?= htmlspecialchars($shop['phone']) ?></span>
            </div>
        </div>

        <!-- บริการ -->
        <div class="section">
            <h3><i class="fas fa-concierge-bell"></i> บริการที่ร้านนี้ให้บริการ</h3>
            <div class="services-grid">
                <?php while($row = $services->fetch_assoc()): ?>
                <div class="service-card">
                    <img src="uploads/<?= htmlspecialchars($row['image']); ?>" alt="<?= htmlspecialchars($row['service_name']) ?>">
                    <h4><?= htmlspecialchars($row['service_name']) ?></h4>
                    <p><?= htmlspecialchars($row['description']) ?></p>
                    <div class="price">
                        <i class="fas fa-tag"></i> ราคา: <?= number_format($row['price']) ?> บาท
                    </div>
                    <a href="booking_form.php?service_id=<?= $row['service_id']; ?>" class="btn-book">
                        <i class="fas fa-calendar-check"></i> จองบริการนี้
                    </a>
                    <a href="add_to_cart.php?service_id=<?= $row['service_id']; ?>" class="btn-cart">
    <i class="fas fa-shopping-cart"></i> เพิ่มลงตะกร้า
</a>




                    
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- รีวิว -->
        <div class="section">
            <h3><i class="fas fa-star"></i> รีวิวจากลูกค้า</h3>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-name">
                                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($row['name']) ?>
                            </div>
                            <div class="rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $row['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                (<?= $row['rating'] ?>/5)
                            </div>
                        </div>
                        <div class="review-date">
                            <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                        </div>
                        <div class="review-comment">
                            <?= nl2br(htmlspecialchars($row['comment'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-reviews">
                    <i class="fas fa-comment-slash" style="font-size: 3em; margin-bottom: 20px; color: #e3f2fd;"></i>
                    <p>ยังไม่มีรีวิวสำหรับร้านนี้</p>
                    <p style="margin-top: 10px; color: #90a4ae;">เป็นคนแรกที่รีวิวร้านนี้!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ฟอร์มรีวิว -->
        <?php if ($can_review): ?>
            <div class="section">
                <h3><i class="fas fa-edit"></i> เขียนรีวิว</h3>
                <form action="submit_review.php" method="POST" class="review-form">
                    <input type="hidden" name="booking_id" value="<?= $pending_booking_id ?>">
                    
                    <div class="form-group">
                        <label for="rating"><i class="fas fa-star"></i> คะแนน (1-5 ดาว):</label>
                        <div class="stars-input">
                            <input type="number" name="rating" id="rating" min="1" max="5" required>
                            <span style="color: #ff9800;">⭐⭐⭐⭐⭐</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment"><i class="fas fa-comment"></i> ความคิดเห็น:</label>
                        <textarea name="comment" id="comment" rows="4" placeholder="แบ่งปันประสบการณ์ของคุณกับร้านนี้..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> ส่งรีวิว
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="section">
                <div class="review-notice">
                    <i class="fas fa-info-circle"></i> 
                    คุณสามารถเขียนรีวิวได้หลังจากใช้บริการของร้านนี้เรียบร้อยแล้ว
                </div>
            </div>
        <?php endif; ?>

        <!-- ปุ่มกลับ -->
        <div style="text-align: center;">
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> กลับไปหน้าหลัก