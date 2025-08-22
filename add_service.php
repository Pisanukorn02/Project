<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shop') {
    header("Location: login.html");
    exit();
}

$shop_id = $_SESSION['shop_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $service_type = $_POST['service_type']; // ⬅️ เพิ่มตรงนี้

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $message = "ไฟล์รูปภาพต้องเป็น jpg, jpeg, png หรือ gif เท่านั้น";
        } else {
            $newFileName = uniqid('service_', true) . '.' . $fileExtension;
            $uploadDir = 'uploads/';
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // ✅ เพิ่ม service_type ลง SQL ด้วย
                $stmt = $conn->prepare("INSERT INTO services (shop_id, service_name, description, service_type, price, image, created_at, is_approved)
                                        VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)");
                $stmt->bind_param("isssds", $shop_id, $service_name, $description, $service_type, $price, $newFileName);
                if ($stmt->execute()) {
    header("Location: shop_board.php");
    exit();
} else {
    $message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error;
    unlink($destPath);
}

                $stmt->close();
            } else {
                $message = "เกิดข้อผิดพลาดในการอัพโหลดไฟล์";
            }
        }
    } else {
        $message = "กรุณาเลือกไฟล์รูปภาพสำหรับบริการ";
    }
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
     <title>เพิ่มบริการใหม่</title>
    <style>
        body { font-family: sans-serif; background: #f9f9f9; padding: 20px; }
        form { max-width: 400px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
        input[type=text], textarea, input[type=number] {
            width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc;
        }
        input[type=submit] {
            background: #1976d2; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer;
        }
        input[type=submit]:hover {
            background: #145a9c;
        }
        .message {
            margin: 10px 0; padding: 10px; background: #f0f0f0; border-radius: 5px; color: #333;
        }
        a { display: inline-block; margin-top: 15px; color: #1976d2; text-decoration: none; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">เพิ่มบริการใหม่</h2>
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form action="add_service.php" method="POST" enctype="multipart/form-data">
        <label>ชื่อบริการ</label>
        <input type="text" name="service_name" required>

        <label>ประเภทบริการ</label>
            <select name="service_type" required>
        <option value="">-- เลือกประเภทบริการ --</option>
        <option value="ซ่อม">ซ่อม</option>
        <option value="ล้าง">ล้าง</option>
        <option value="ติดตั้ง/เคลื่อนย้าย">ติดตั้ง/เคลื่อนย้าย</option>
             </select><br>

        <label>รายละเอียด</label>
        <textarea name="description" rows="4" required></textarea>

        <label>ราคา (บาท)</label>
        <input type="number" name="price" min="0" step="0.01" required>

        <label>รูปภาพบริการ</label>
        <input type="file" name="image" accept="image/*" required>

        <input type="submit" value="เพิ่มบริการ">

    </form>

    <div style="text-align:center;">
        <a href="shop_board.php">กลับไปหน้าร้านของฉัน</a>
    </div>
    </form>
</body>
</html>
