<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าบริการ</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: #1e88e5;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            font-size: 16px;
        }
        .back-button:hover {
            background-color: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .back-button i {
            margin-right: 8px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .cart-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 20px;
        }
        .item-details {
            flex-grow: 1;
        }
        .item-name {
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }
        .item-price {
            color: #2196F3;
            font-weight: 500;
            font-size: 16px;
        }
        .remove-button {
            background-color: #ff5252;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .remove-button:hover {
            background-color: #ff1744;
        }
        .checkout-button {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 30px auto 0;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .checkout-button:hover {
            background-color: #45a049;
        }
        .empty-cart {
            text-align: center;
            color: #666;
            padding: 30px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            กลับหน้าหลัก
        </a>
        <h2>ตะกร้าบริการของคุณ</h2>

        <?php if (count($cart) > 0): ?>
    <ul class="cart-list">
    <?php foreach ($cart as $index => $item): ?>
        <li class="cart-item">
            <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="ภาพบริการ">
            <div class="item-details">
                <div class="item-name"><?= htmlspecialchars($item['service_name']) ?></div>
                <div class="item-price"><?= number_format($item['price']) ?> บาท</div>
            </div>
            <form action="cart_action.php" method="POST">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="index" value="<?= $index ?>">
                <button type="submit" class="remove-button" onclick="return confirm('คุณต้องการลบบริการนี้ออกจากตะกร้าหรือไม่?')">
                    ลบ
                </button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
    <form action="booking_form.php" method="GET">
        <input type="hidden" name="from_cart" value="1">
        <button type="submit" class="checkout-button">จองทั้งหมด</button>
    </form>

<?php else: ?>
    <div class="empty-cart">ยังไม่มีบริการในตะกร้า</div>
<?php endif; ?>
    </div>
</body>
</html>
