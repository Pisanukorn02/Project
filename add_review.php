<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("กรุณาเข้าสู่ระบบก่อน");
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['booking_id'] ?? 0);
$shop_id = intval($_GET['shop_id'] ?? 0);

if ($booking_id <= 0 || $shop_id <= 0) {
    die("ข้อมูลไม่ถูกต้อง");
}

// ตรวจสอบว่า booking_id นี้เป็นของ user ที่ล็อกอินอยู่
$stmt = $conn->prepare("SELECT b.booking_id, s.shop_name 
                        FROM bookings b 
                        JOIN shops s ON b.shop_id = s.shop_id
                        WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("ไม่พบข้อมูลการจองนี้ หรือคุณไม่มีสิทธิ์รีวิว");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เขียนรีวิวร้าน</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>เขียนรีวิวสำหรับร้าน: <?php echo htmlspecialchars($booking['shop_name']); ?></h4>
        </div>
        <div class="card-body">
            <form action="submit_review.php" method="post">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                <input type="hidden" name="shop_id" value="<?php echo $shop_id; ?>">

                <div class="mb-3">
                    <label class="form-label">ให้คะแนน (1 - 5 ดาว)</label>
                    <select name="rating" class="form-select" required>
                        <option value="">-- เลือกคะแนน --</option>
                        <option value="1">1 - แย่มาก</option>
                        <option value="2">2 - พอใช้</option>
                        <option value="3">3 - ปานกลาง</option>
                        <option value="4">4 - ดี</option>
                        <option value="5">5 - ดีเยี่ยม</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">เขียนรีวิว</label>
                    <textarea name="comment" class="form-control" rows="4" placeholder="บอกความเห็นของคุณ..." required></textarea>
                </div>

                <button type="submit" class="btn btn-success">ส่งรีวิว</button>
                <a href="shop.php?shop_id=<?php echo $shop_id; ?>" class="btn btn-secondary">ยกเลิก</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
