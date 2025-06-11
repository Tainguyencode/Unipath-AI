<?php
session_start();
include 'Account.php'; // Kết nối database từ file Account.php

$error = "";
$success = "";

if (isset($_POST['register'])) { // Đã sửa tên nút từ 'resgister' thành 'register'
    $username = htmlspecialchars(trim($_POST['new_username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars(trim($_POST['new_password']));

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin";
    } else {
        // Kiểm tra username hoặc email đã tồn tại chưa
        // Sử dụng biến $conn từ Account.php
        $stmt = $conn->prepare("SELECT id FROM account WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Tên đăng nhập hoặc email đã tồn tại.";
        } else {
            // Mã hóa mật khẩu và lưu vào database
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Sử dụng biến $conn từ Account.php
            $stmt = $conn->prepare("INSERT INTO account (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashedPassword);

            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit;
            } else {
                $error = "Lỗi khi tạo tài khoản: " . $conn->error;
            }
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký-UniPath AI</title>
    <style>
        body{
            margin: 0 auto;
            background-color: #f4f6f9;
            padding: 0;
            background-color: #e0f7e9;
        }
        .main{
            max-width: 450px;
            background: linear-gradient(to bottom, #ffffff,#a5d6a7);
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        h1,h3{
            text-align: center;
            color: #2e7d32;
        }
        label{
            margin: 10px 15px;
            font-weight: bold;
        }
        button{
            margin-top: 20px;
            padding: 10px;
            background-color:rgb(30, 103, 33);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
        }
        form{
            display: flex;
            flex-direction: column;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .message{
            text-align: center;
            margin: 10px 0;
        }
        .link{
            text-align: center;
            margin-top: 15px;
        }
        .link a{
            text-decoration: none;
            color:#2e7d32;
        }
        button:hover{
            transform: scale(1.05);
            cursor: pointer;
        }
    </style>
</head>
<body>
  <div class="main">
    <h1>Đăng ký tài khoản</h1>
    <h3>Unipath AI chào bạn</h3>

    <form action="" method="post">
        <div class="message">
        <p style="color: red;"><?php echo $error?></p>
        <p style="color: green;"><?php echo $success?></p>
        </div>
        <label for="">Tên đăng nhập:</label>
        <input type="text" name="new_username" required><br>

        <label for="">Email:</label>
        <input type="email" name="email" required><br>

        <label for="">Mật khẩu:</label>
        <input type="password" name="new_password" required><br>

        <button type="submit" name="register">Đăng ký</button>
    </form>
    <div class="link">
    <p>Bạn đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
    </div>
  </div>
</body>
</html>