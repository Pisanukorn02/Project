<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['new_proposals'=>[]]);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT p.proposal_id, p.booking_id, p.proposed_date, p.proposed_time
        FROM booking_proposals p
        JOIN bookings b ON p.booking_id = b.booking_id
        WHERE b.user_id = ? AND p.status='pending'
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$proposals = [];
while($row = $result->fetch_assoc()){
    $proposals[] = $row;
}

echo json_encode(['new_proposals'=>$proposals]);
?>
