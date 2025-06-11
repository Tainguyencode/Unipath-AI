<?php
$host = 'localhost';
$db = 'unipathai';       // tên database bạn đã tạo
$user = 'root';       // tài khoản MySQL (XAMPP/Laragon thường là 'root')
$pass = '';           // mật khẩu MySQL (thường để trống nếu dùng XAMPP)

$conn = new mysqli($host, $user, $pass, $db);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
