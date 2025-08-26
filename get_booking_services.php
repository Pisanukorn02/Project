<?php
include 'config.php';

$booking_id = intval($_GET['booking_id'] ?? 0);

$sql = "SELECT s.service_name, d.quantity, s.price 
        FROM booking_details d
        JOIN services s ON d.service_id = s.service_id
        WHERE d.booking_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' width='100%' style='border-collapse: collapse;'>
            <tr>
                <th>บริการ</th>
                <th>จำนวน</th>
                <th>ราคา/หน่วย</th>
                <th>รวม</th>
            </tr>";
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['quantity'] * $row['price'];
        $total += $subtotal;
        echo "<tr>
                <td>".htmlspecialchars($row['service_name'])."</td>
                <td>".$row['quantity']."</td>
                <td>".number_format($row['price'],2)."</td>
                <td>".number_format($subtotal,2)."</td>
              </tr>";
    }
    echo "<tr>
            <td colspan='3'><strong>รวมทั้งหมด</strong></td>
            <td><strong>".number_format($total,2)."</strong></td>
          </tr>";
    echo "</table>";
} else {
    echo "ไม่พบรายการบริการ";
}
