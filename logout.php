<?php
session_start();
session_unset(); // ลบค่าทุกตัวใน $_SESSION
session_destroy(); // ทำลาย session

// กลับไปยังหน้า index หรือหน้า login
header("Location: index.php");
exit();
?>
