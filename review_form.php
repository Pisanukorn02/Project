<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['booking_id'] ?? 0);

$stmt = $conn->prepare("SELECT b.*, s.shop_name FROM bookings b JOIN shops s ON b.shop_id = s.shop_id WHERE b.booking_id = ? AND b.user_id = ?");
if (!$stmt) { die("Prepare failed: " . $conn->error); }
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking || $booking['status'] !== 'completed') {
    die("ไม่สามารถรีวิวได้");
}

$check = $conn->prepare("SELECT * FROM reviews WHERE booking_id = ? AND user_id = ?");
if (!$check) { die("Prepare failed: " . $conn->error); }
$check->bind_param("ii", $booking_id, $user_id);
$check->execute();
$check_result = $check->get_result();

if ($check_result->num_rows > 0) {
    die("คุณได้รีวิวงานนี้ไปแล้ว");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $error = "คะแนนต้องอยู่ระหว่าง 1 ถึง 5";
    } elseif (strlen($comment) > 500) {
        $error = "ความยาวความคิดเห็นต้องไม่เกิน 500 ตัวอักษร";
    } else {
        $insert = $conn->prepare("INSERT INTO reviews (booking_id, user_id, shop_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        if (!$insert) { die("Prepare failed: " . $conn->error); }
        $insert->bind_param("iiiss", $booking_id, $user_id, $booking['shop_id'], $rating, $comment);
        if ($insert->execute()) {
            header("Location: customer_bookings.php");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึกรีวิว";
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>รีวิวร้านค้า</title>
    <style>
        .star-rating {
            direction: rtl; /* เรียงจากขวาไปซ้าย เพื่อให้ hover และเลือกง่าย */
            font-size: 2rem;
            unicode-bidi: bidi-override;
            display: inline-block;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            color: #ccc;
            cursor: pointer;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type="radio"]:checked ~ label {
            color: gold;
        }
    </style>
</head>
<body>

    <h2>รีวิวร้าน: <?= htmlspecialchars($booking['shop_name']) ?></h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST">
        <label>ให้คะแนน (1-5 ดาว):</label><br>
        <div class="star-rating">
            <input type="radio" id="star5" name="rating" value="5" required <?= (isset($rating) && $rating == 5) ? 'checked' : '' ?> />
            <label for="star5">&#9733;</label>

            <input type="radio" id="star4" name="rating" value="4" <?= (isset($rating) && $rating == 4) ? 'checked' : '' ?> />
            <label for="star4">&#9733;</label>

            <input type="radio" id="star3" name="rating" value="3" <?= (isset($rating) && $rating == 3) ? 'checked' : '' ?> />
            <label for="star3">&#9733;</label>

            <input type="radio" id="star2" name="rating" value="2" <?= (isset($rating) && $rating == 2) ? 'checked' : '' ?> />
            <label for="star2">&#9733;</label>

            <input type="radio" id="star1" name="rating" value="1" <?= (isset($rating) && $rating == 1) ? 'checked' : '' ?> />
            <label for="star1">&#9733;</label>
        </div>
        <br><br>

        <label>ความคิดเห็น:</label><br>
        <textarea name="comment" rows="5" cols="50" placeholder="เขียนรีวิว..." required><?= isset($comment) ? htmlspecialchars($comment) : '' ?></textarea><br><br>

        <button type="submit">ส่งรีวิว</button>
    </form>

</body>
</html>
