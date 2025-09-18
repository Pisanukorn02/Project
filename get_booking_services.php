<?php
session_start();
include 'config.php';

$booking_id = intval($_GET['booking_id']);

// ดึงข้อมูล booking + customer
$stmt = $conn->prepare("SELECT b.booking_id, b.booking_date, b.booking_time, b.status, b.extra_fee, u.name AS customer_name, u.phone AS customer_phone
                        FROM bookings b
                        JOIN users u ON b.user_id = u.user_id
                        WHERE b.booking_id = ? AND b.shop_id = ?");
$stmt->bind_param("ii", $booking_id, $_SESSION['shop_id']);
$stmt->execute();
$booking_result = $stmt->get_result();
$booking = $booking_result->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo "ไม่พบข้อมูลการจอง";
    exit;
}

// ดึงรายการบริการ
$stmt = $conn->prepare("SELECT s.service_name, d.quantity, d.price
                        FROM booking_details d
                        JOIN services s ON d.service_id = s.service_id
                        WHERE d.booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$services_result = $stmt->get_result();
$stmt->close();

// สร้าง HTML
$html = "<p><strong>การจอง #{$booking['booking_id']} ({$booking['status']})</strong></p>";
$html .= "<p>ลูกค้า: {$booking['customer_name']} ({$booking['customer_phone']})<br>";
$html .= "วันที่จอง: {$booking['booking_date']} {$booking['booking_time']}</p>";

$html .= "<table border='1' cellpadding='5' cellspacing='0' style='width:100%; text-align:center;'>";
$html .= "<tr><th>บริการ</th><th>จำนวน</th><th>ราคาต่อหน่วย</th><th>รวม</th></tr>";

$total = 0;
while($s = $services_result->fetch_assoc()){
    $sum = $s['quantity'] * $s['price'];
    $total += $sum;
    $html .= "<tr>
                <td>{$s['service_name']}</td>
                <td>{$s['quantity']}</td>
                <td>".number_format($s['price'],2)."</td>
                <td>".number_format($sum,2)."</td>
              </tr>";
}

// เพิ่ม extra_fee เป็นรายการสุดท้าย
if(!empty($booking['extra_fee']) && $booking['extra_fee'] > 0){
    $total += $booking['extra_fee'];
    $html .= "<tr>
                <td>ค่าบริการพิเศษ / ค่าส่ง</td>
                <td>1</td>
                <td>".number_format($booking['extra_fee'],2)."</td>
                <td>".number_format($booking['extra_fee'],2)."</td>
              </tr>";
}

$html .= "<tr>
            <td colspan='3'><strong>รวมทั้งหมด</strong></td>
            <td><strong>".number_format($total,2)." บาท</strong></td>
          </tr>";
$html .= "</table>";

echo $html;
