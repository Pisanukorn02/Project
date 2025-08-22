<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
?>

<h2>ตะกร้าบริการของคุณ</h2>

<?php if (count($cart) > 0): ?>
    <ul>
    <?php foreach ($cart as $index => $item): ?>
        <li>
            <img src="uploads/<?= htmlspecialchars($item['image']) ?>" width="80" alt="ภาพบริการ">
            <?= htmlspecialchars($item['service_name']) ?> - <?= number_format($item['price']) ?> บาท

            <!-- ฟอร์มลบรายการ -->
            <form action="cart_action.php" method="POST" style="display:inline;">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="index" value="<?= $index ?>">
                <button type="submit" onclick="return confirm('คุณต้องการลบบริการนี้ออกจากตะกร้าหรือไม่?')">ลบ</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
    <form action="booking_form.php" method="GET">
        <input type="hidden" name="from_cart" value="1">
        <button type="submit">จองทั้งหมด</button>
    </form>

<?php else: ?>
    <p>ยังไม่มีบริการในตะกร้า</p>
<?php endif; ?>
