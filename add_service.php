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
    $service_type = $_POST['service_type'];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มบริการใหม่</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f4f7f6;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"], 
        textarea, 
        input[type="number"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, 
        textarea:focus, 
        input[type="number"]:focus,
        select:focus {
            border-color: #007bff;
            outline: none;
        }

        input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px 0;
        }

        .btn-submit {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #218838;
        }

        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            color: #d8000c;
            background-color: #ffb3b3;
            border: 1px solid #d8000c;
        }
        
        .link-back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .link-back:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-plus-circle"></i> เพิ่มบริการใหม่</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form action="add_service.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="service_name">ชื่อบริการ</label>
                <input type="text" id="service_name" name="service_name" required>
            </div>

            <div class="form-group">
                <label for="service_type">ประเภทบริการ</label>
                <select id="service_type" name="service_type" required>
                    <option value="">-- เลือกประเภทบริการ --</option>
                    <option value="ซ่อม">ซ่อม</option>
                    <option value="ล้าง">ล้าง</option>
                    <option value="ติดตั้ง/เคลื่อนย้าย">ติดตั้ง/เคลื่อนย้าย</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">รายละเอียด</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="price">ราคา (บาท)</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="image">รูปภาพบริการ</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>

            <button type="submit" class="btn-submit">เพิ่มบริการ</button>
        </form>
    </div>
    
    <a href="shop_board.php" class="link-back">
        <i class="fas fa-arrow-left"></i> กลับไปหน้าร้านของฉัน
    </a>
</body>
</html>