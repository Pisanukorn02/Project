<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop') {
    header("Location: login.html");
    exit();
}

$shop_id = $_SESSION['shop_id'];

if (!isset($_GET['service_id'])) {
    echo "ไม่พบบริการนี้";
    exit();
}
$service_id = intval($_GET['service_id']);

// โหลดข้อมูลบริการ (ตอนโหลดหน้า)
$stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ? AND shop_id = ?");
$stmt->bind_param("ii", $service_id, $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$service = $result->fetch_assoc();
$stmt->close();

if (!$service) {
    echo "ไม่พบบริการนี้หรือคุณไม่มีสิทธิ์แก้ไข";
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $service_type = $_POST['service_type'] ?? '';
    $newImage = $service['image'];  // ตั้งค่ารูปเดิมไว้ก่อน

    // เช็คอัพโหลดรูปภาพใหม่ไหม
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $message = "ไฟล์รูปภาพต้องเป็น jpg, jpeg, png หรือ gif เท่านั้น";
        } else {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $newFileName = uniqid('service_', true) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // ลบรูปเก่า ถ้ามี
                if ($service['image'] && file_exists($uploadDir . $service['image'])) {
                    unlink($uploadDir . $service['image']);
                }
                $newImage = $newFileName;
            } else {
                $message = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
            }
        }
    }

    if (empty($message)) {
        // อัปเดตข้อมูลบริการ
        $stmt = $conn->prepare("UPDATE services SET service_name=?, service_type=?, description=?, price=?, image=?, is_approved=0 WHERE service_id=? AND shop_id=?");
        $stmt->bind_param("sssdsii", $service_name, $service_type, $description, $price, $newImage, $service_id, $shop_id);
        if ($stmt->execute()) {
            $message = "อัปเดตบริการเรียบร้อยแล้ว รอการอนุมัติใหม่";

            // อัปเดต $service สำหรับแสดงในฟอร์มหลัง submit
            $service['service_name'] = $service_name;
            $service['service_type'] = $service_type;
            $service['description'] = $description;
            $service['price'] = $price;
            $service['image'] = $newImage;
            $service['is_approved'] = 0;
        } else {
            $message = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>แก้ไขบริการ</title>
    <style>
        /* ใส่ CSS ตามต้องการ */
    </style>
</head>
<body>
    <h2 style="text-align:center;">แก้ไขบริการ</h2>
    <?php if ($message): ?>
        <div style="color: red; text-align:center;"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form action="edit_service.php?service_id=<?= $service_id ?>" method="POST" enctype="multipart/form-data">
        <label>ชื่อบริการ</label>
        <input type="text" name="service_name" value="<?= htmlspecialchars($service['service_name']) ?>" required>

        <label>ประเภทบริการ</label>
        <select name="service_type" required>
            <option value="">-- เลือกประเภทบริการ --</option>
            <option value="ซ่อม" <?= $service['service_type'] === 'ซ่อม' ? 'selected' : '' ?>>ซ่อม</option>
            <option value="ล้าง" <?= $service['service_type'] === 'ล้าง' ? 'selected' : '' ?>>ล้าง</option>
            <option value="ติดตั้ง/เคลื่อนย้าย" <?= $service['service_type'] === 'ติดตั้ง/เคลื่อนย้าย' ? 'selected' : '' ?>>ติดตั้ง/เคลื่อนย้าย</option>
        </select>

        <label>รายละเอียด</label>
        <textarea name="description" rows="4" required><?= htmlspecialchars($service['description']) ?></textarea>

        <label>ราคา (บาท)</label>
        <input type="number" name="price" min="0" step="0.01" value="<?= htmlspecialchars($service['price']) ?>" required>

        <label>รูปภาพบริการ (ถ้าต้องการเปลี่ยน)</label><br>
        <?php if (!empty($service['image']) && file_exists('uploads/' . $service['image'])): ?>
            <img src="uploads/<?= htmlspecialchars($service['image']) ?>" alt="รูปบริการ" style="max-width: 200px; display: block; margin-bottom: 10px;">
        <?php else: ?>
            <p>ไม่มีภาพ</p>
        <?php endif; ?>
        <input type="file" name="image" accept="image/*">

        <input type="submit" value="บันทึกการแก้ไข">
    </form>
    <div style="text-align:center;">
        <a href="shop_board.php">กลับไปหน้าร้านของฉัน</a>
    </div>
</body>
</html>
