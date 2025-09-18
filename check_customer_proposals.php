<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['shop_id']) || $_SESSION['role'] !== 'shop') {
    echo json_encode(['new_proposals'=>[]]);
    exit();
}

$shop_id = $_SESSION['shop_id'];

// ดึง booking ที่มีข้อเสนอใหม่จากลูกค้าและสถานะเป็น accepted หรือ pending
$sql = "SELECT booking_id, booking_date, booking_time, proposed_date, proposed_time,
            GROUP_CONCAT(CONCAT(d.service_id, ':', s.service_name) SEPARATOR ', ') AS services,
            status
        FROM bookings b
        JOIN booking_details d ON b.booking_id=d.booking_id
        JOIN services s ON d.service_id=s.service_id
        WHERE b.shop_id=? 
          AND b.proposal_status='pending' 
          AND b.status IN ('accepted','pending')
        GROUP BY b.booking_id
        ORDER BY b.proposed_date ASC, b.proposed_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();

$proposals = [];
while($row = $result->fetch_assoc()){
    $proposals[] = $row;
}
$stmt->close();

echo json_encode(['new_proposals'=>$proposals]);
