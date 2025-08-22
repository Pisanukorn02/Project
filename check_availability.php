<?php
include 'config.php';

header('Content-Type: application/json');

$shop_id = intval($_POST['shop_id'] ?? 0);
$date = $_POST['booking_date'] ?? '';
$time = $_POST['booking_time'] ?? '';

if (!$shop_id || !$date || !$time) {
    echo json_encode(['available' => false, 'error'=>'ข้อมูลไม่ครบ']);
    exit();
}

// แปลงเวลาเป็น HH:mm:ss ถ้าจำเป็น
if (strlen($time) === 5) $time .= ':00';

$stmt = $conn->prepare("
    SELECT COUNT(*) as cnt 
    FROM bookings 
    WHERE shop_id=? AND booking_date=? AND booking_time=? AND status='รอการตอบรับจากร้านค้า'
");
$stmt->bind_param("iss", $shop_id, $date, $time);

if (!$stmt->execute()) {
    echo json_encode(['available' => false, 'error'=>$stmt->error]);
    exit();
}

$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode(['available' => ($res['cnt'] == 0)]);
