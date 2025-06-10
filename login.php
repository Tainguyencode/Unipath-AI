<?php
session_start();
   $accountFile='accounts.txt';
   $error="";
     // Kiểm tra người dùng khi ấn nút đăng nhập
     if(isset($_POST["login"])){
        $username=htmlspecialchars(trim($_POST["username"]));
        $password=htmlspecialchars(trim($_POST["password"]));

        if(empty($username) || empty($password)){
            $error= "Vui lòng nhập đầy đủ thông tin";
        }else{
            $accounts = file_exists($accountFile) ? file($accountFile, FILE_IGNORE_NEW_LINES) : [];
            $isAuthenticated=false;// xác thực
             foreach($accounts as $account){
                list($storedUser,$storedEmail,$storedPass) = explode('|', $account);
                if ($storedUser === $username && $storedPass === $password) {
                $isAuthenticated = true;
                $_SESSION['username'] = $username;
                $success = "Đăng nhập thành công! Xin chào, $username.";
                header("Location: index.php"); // trang sau khi đăng nhập thành công
                exit();
            }
        }if(!$isAuthenticated) {
            $error = "Tên đăng nhâp hoặc mật khẩu không đúng.";
        }
     }
    }
    



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập-UniPath AI</title>
    <style>
         body{
            margin: 0 auto;
            background-color: #f4f6f9;
            padding: 0;
           background-color: #e0f7e9;
        }
        .container{
            max-width: 450px;
             background: linear-gradient(to bottom, #ffffff,#a5d6a7);
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
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
    <div class="container">
        <h1>Đăng nhập</h1>
        <h3>Unipath AI chào bạn</h3>
        <div class="message">
            <p style="color: red;"><?php echo $error?></p>
        </div>
        <form action="" method="post">
            <label for="">Tên đăng nhập:</label>
            <input type="text" name="username" required>

            <label for="">Mật khẩu:</label>
            <input type="password" name="password" required>

            <button type="submit" name="login">Đăng nhập</button>
        </form>

        <div class="link">
            <p>Chưa có tài khoản <a href="Register.php">Đăng ký</a></p>
        </div>
    </div>
</body>
</html>