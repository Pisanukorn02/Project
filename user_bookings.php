<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT b.booking_id, b.booking_date, b.booking_time, b.address, b.location_lat, b.location_lng,
               b.site_photos, b.status, b.complete_image,
               sh.shop_name,
               SUM(s.price * d.quantity) AS total_price,
               GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services,
               b.proposed_date, b.proposed_time, b.proposal_status
        FROM bookings b
        JOIN shops sh ON b.shop_id = sh.shop_id
        JOIN booking_details d ON b.booking_id = d.booking_id
        JOIN services s ON d.service_id = s.service_id
        WHERE b.user_id = ?
        GROUP BY b.booking_id
        ORDER BY b.booking_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รายการจองของฉัน</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {font-family:'Kanit',sans-serif; background:#f0f4f8; min-height:100vh; padding:30px 20px;}
.container {max-width:1200px; margin:0 auto;}
.header {display:flex; align-items:center; justify-content:space-between; margin-bottom:30px; padding:0 10px;}
.header h2 {font-size:1.8rem; font-weight:500; color:#2c3e50;}
.booking-table {background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 6px rgba(0,0,0,0.05);}
table {width:100%; border-collapse:collapse;}
th {background:#5a67d8; color:white; padding:12px 15px; font-weight:500; font-size:1rem; text-align:left;}
td {padding:12px 15px; border-bottom:1px solid #f0f4f8; color:#4a5568; font-size:0.95rem; vertical-align:top;}
tr:last-child td {border-bottom:none;}
tr:hover td {background-color:#f8fafc;}
.complete-img {max-width:50px; cursor:pointer; border-radius:4px; transition: transform 0.2s;}
.complete-img:hover {transform:scale(1.2);}
#lightbox {display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); justify-content:center; align-items:center;}
#lightbox img {max-width:90%; max-height:90%; border-radius:8px;}
.btn-back {background:linear-gradient(135deg,#90a4ae 0%,#78909c 100%); color:white; padding:15px 30px; border-radius:25px; text-decoration:none; display:inline-flex; align-items:center; gap:10px; font-weight:bold; transition:all 0.3s ease; margin-top:30px; box-shadow:0 4px 15px rgba(144,164,174,0.4);}
.btn-primary {background:#3498db;color:white;padding:6px 12px;border:none;border-radius:5px;cursor:pointer;}
.btn-primary:hover {background:#2980b9;}
.btn-warning {background:#f39c12;color:white;padding:6px 12px;border:none;border-radius:5px;cursor:pointer;}
.btn-warning:hover {background:#e67e22;}
</style>
</head>
<body>

<!-- Modal ข้อเสนอเวลาใหม่ -->
<div id="proposalModal" style="display:none; position:fixed; z-index:10000; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center;">
    <div style="background:white; padding:20px; border-radius:10px; max-width:400px; text-align:center; position:relative;">
        <span onclick="closeProposalModal()" style="position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px;">&times;</span>
        <div id="proposalDetail"></div>
        <form id="proposalForm" method="POST">
            <input type="hidden" name="booking_id" id="proposalBookingId">
            <button type="button" onclick="respondProposal('accept')" style="padding:10px 20px; margin:5px; background:#28a745; color:white; border:none; border-radius:5px;">ยอมรับ</button>
            <button type="button" onclick="respondProposal('reject')" style="padding:10px 20px; margin:5px; background:#c0392b; color:white; border:none; border-radius:5px;">ปฏิเสธ</button>
        </form>
    </div>
</div>

<div class="container">
<div class="header">
<h2><i class="fas fa-calendar-check"></i> ประวัติการจองของคุณ</h2>
</div>

<div class="booking-table">
<table>
<tr>
<th>ร้านค้า</th>
<th>บริการ</th>
<th>ราคายอดรวม(บาท)</th>
<th>วันที่/เวลา</th>
<th>สถานที่</th>
<th>สถานะ</th>
<th>หน้างาน</th>
<th>ใบเสร็จ</th>
<th>รูปจบงาน</th>
<th>ตอบรับ</th>
<th>ขอเปลี่ยนเวลา</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['shop_name']) ?></td>
<td><?php foreach(explode(',', $row['services']) as $s) echo htmlspecialchars($s)."<br>"; ?></td>
<td><?= number_format($row['total_price'],2) ?></td>
<td><?= $row['booking_date']." ".$row['booking_time'] ?></td>
<td><?= htmlspecialchars($row['address']) ?></td>
<td>
<?php
switch($row['status']){
    case 'pending': echo 'รอการตอบรับ'; break;
    case 'accepted': echo 'ร้านค้ารับงานแล้ว'; break;
    case 'completed': echo 'งานเสร็จสิ้น'; break;
    case 'rejected': echo 'ถูกร้านค้าปฏิเสธ'; break;
    default: echo htmlspecialchars($row['status']); break;
}
?>
</td>
<td>
<?php if($row['site_photos']): ?>
<a href="<?= htmlspecialchars($row['site_photos']) ?>" target="_blank">ดูหน้างาน</a>
<?php else: ?> - <?php endif; ?>
</td>
<td>
<?php if($row['status']==='completed'): ?>
<a href="receipt.php?booking_id=<?= $row['booking_id'] ?>" target="_blank">พิมพ์ใบเสร็จ</a>
<?php else: ?> - <?php endif; ?>
</td>
<td>
<?php if (!empty($row['complete_image'])) { 
    $img_path = strpos($row['complete_image'], 'uploads/') === false 
                ? 'uploads/completions/' . $row['complete_image'] 
                : $row['complete_image']; 
    echo '<img src="' . htmlspecialchars($img_path) . '" class="complete-img" onclick="openImage(this.src)">'; 
} else { echo "-"; } ?>
</td>
<td>
<?php
if(!empty($row['proposed_date']) && $row['proposal_status']==='pending'){
    ?>
    <button type="button" class="btn-warning" style="margin-top:5px;"
        onclick="openProposalModal(
            <?= $row['booking_id'] ?>,
            '<?= $row['proposed_date'] ?>',
            '<?= $row['proposed_time'] ?>',
            '<?= addslashes($row['services']) ?>',
            '<?= $row['booking_date'] ?>',
            '<?= $row['booking_time'] ?>'
        )">
        ดูข้อเสนอเวลาใหม่
    </button>
    <?php
} else { echo "-"; }
?>
</td>
<td>
<?php
if($row['status'] === 'pending' || $row['status'] === 'accepted'){
?>
<form method="POST" action="customer_propose_time.php" style="margin-top:5px;">
    <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
    <label>วันที่ใหม่:</label>
    <input type="date" name="proposed_date" required min="<?= date('Y-m-d') ?>">
    <label>เวลาที่ใหม่:</label>
    <input type="time" name="proposed_time" required>
    <button type="submit" class="btn-primary">ขอเปลี่ยนเวลา</button>
</form>
<?php
} elseif(!empty($row['proposed_date']) && $row['proposal_status']==='pending'){
    echo "-"; // ถ้ามีข้อเสนอร้านค้าแล้วไม่แสดงฟอร์ม
} else {
    echo "-";
}
?>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> กลับไปหน้าหลัก</a>
</div>

<!-- Lightbox -->
<div id="lightbox" onclick="closeLightbox()">
<img id="lightbox-img" src="">
</div>

<script>
function openImage(src){ 
    document.getElementById('lightbox-img').src=src; 
    document.getElementById('lightbox').style.display='flex'; 
}
function closeLightbox(){ document.getElementById('lightbox').style.display='none'; }

function openProposalModal(booking_id, new_date, new_time, service_name, old_date, old_time) {
    document.getElementById('proposalBookingId').value = booking_id;
    let detail = `
        <strong>ร้านค้าได้ปรับเวลาการให้บริการใหม่</strong><br>
        <span style="color:#1976d2;">บริการ:</span> ${service_name}<br>
        <span style="color:#1976d2;">วัน-เวลาเดิม:</span> ${old_date} ${old_time}<br>
        <span style="color:#e67e22;">เปลี่ยนเป็น:</span> ${new_date} ${new_time}<br><br>
        คุณต้องการยอมรับเวลาที่ร้านค้าเสนอหรือไม่?
    `;
    document.getElementById('proposalDetail').innerHTML = detail;
    document.getElementById('proposalModal').style.display = 'flex';
}
function closeProposalModal(){ document.getElementById('proposalModal').style.display='none'; }

function respondProposal(action){
    let booking_id = document.getElementById('proposalBookingId').value;
    fetch('respond_proposal.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'booking_id='+booking_id+'&action='+action
    }).then(res => res.text())
    .then(data=>{
        alert(data);
        closeProposalModal();
        location.reload();
    }).catch(err=>console.error(err));
}
</script>
</body>
</html>
