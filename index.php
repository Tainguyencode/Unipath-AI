<?php
session_start();
    if(!isset($_SESSION["username"])){
        header("Location:login.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniPath AI-Giao diện</title>
    <style>
        *{
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body{
            margin: 0 auto;
            padding: 0;
            background: linear-gradient(to bottom, #ffffff,#a5d6a7);
            animation: backgroundShift 20s ease infinite alternate;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        @keyframes backgroundShift {
            0%{ background-position: 0% 50%;}
            50%{ background-position: 100% 50%;}
            100%{ background-position: 100% 50%;}
        }
        header{
            background-color:rgb(58, 157, 63);
            color:white ;
            padding: 20px;
            text-align: center;
            font-size: 25px;
            animation: backgroundShift 10s ease infinite; /* Giữ animation này nếu bạn muốn header cũng thay đổi màu */
        }
        @keyframes fadeInDown{
            from{opacity: 0; transform: translateY(-20px);}
            to{opacity: 1;transform: translateY(0);}
        }
        nav{
            background-color:rgb(64, 178, 70);
        }
        .menu{
            display: flex;
            padding: 14px 18px;
            justify-content: space-between;

        }
        .menu li{
            list-style: none;
        }
        .menu li a{
            color: white;
            font-size: 22px;
            text-decoration: none;
            margin: 0 20px;
            display: inline-block;
        }
        .menu li a:hover{
            background-color: rgb(44, 138, 50);
            color:black;
            border-radius: 6px;
            text-decoration: underline;
            transform: scale(1.1);
        }
        .container{
            text-align: center;
            padding: 25px;
        }
        @keyframes floatBox{
            0%{ transform: translateY(0px);}
            50%{ transform: translateY(-10px);}
            100%{ transform: translateY(0px);}
        }
        .content{
            background-color: white;
            padding: 30px;
            margin: 20px auto;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(18, 17, 17, 0.1);
            max-width: 700px;
            transition: transform 0.3s, box-shadow 0.3s;
            animation: floatBox 4s ease-in-out infinite;
        }
        .content:hover{
            cursor: pointer;
            transform: scale(1.1);
            box-shadow: 0 12px 24px rgba(0,0,0,0.3);
        }
        h2{
            font-size: 28px;
            margin: 10px 0;
        }
        p{
            font-size: 15px;
            margin-bottom: 15px;
        }
        h3{
            font-size: 22px;
            margin: 5px 0;
        }
        .logo{
            background-color:#f5f5f5;
            text-align: center;
            padding: 15px;
        }
        @keyframes pulse{
            0%{ transform: scale(1);}
            50%{ transform: scale(1.08);}
            100%{ transform: scale(1);}
        }
        .btn{
            border-radius: 8px;
            color: #ffffff;
            background-color: rgb(64, 178, 70);
            padding: 10px 19px;
            margin-top: 8px;
            text-decoration: none;
            font-size: 18px;
            background: linear-gradient(to right, #43a047, #66bb6a);
            display: inline-block;
            transition: all 0.3s ease-in-out;
            animation: pulse 2.5s infinite;
        }
        .btn:hover{
            cursor: pointer;
            text-decoration: underline;
            transform: scale(1.1) rotate(-1deg);
            background: linear-gradient(to right, #2e7d32, #66bb6a);
            box-shadow: 0 4px 12px rgb(0,0,0,0.2);
        }
        .logo img{
            transition: transform 0.5s;
        }
        .logo img:hover{
            transform: rotate(5deg) scale(1.05);
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>
<body>
    <header>UniPath AI - Ứng dụng AI vào học tập và tìm kiếm
        việc làm phù hợp với sinh viên
    </header>
    <div class="logo">
        <img src="1.png" alt="" width="13%" height="8%">
    </div>
    <nav>
        <ul class="menu">
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="StudyNavigator.php">Study Navigator AI</a></li>
            <li><a href="Career_launchpad.php">Career Launchapad AI</a></li>
            <li><a href="hoi_dap.php">Hỏi đáp</a></li>
            <li><a href="phong_van.php">Phỏng vấn</a></li>
            <li><a href="logout.php">Đăng xuất</a></li>
        </ul>
    </nav>
    <div class="container">
        <div class="content" data-aos="zoom-in"> <h2>Chào mừng bạn đến với UniPath AI</h2>
            <p>Cùng khám phá các tính năng thông minh hỗ trợ học tập và tìm kiếm việc làm cho sinh viên ngay thôi nào!</p>
        </div>
        <div class="content" data-aos="zoom-in"> <h2>Phân hệ 1</h2>
            <h3>Study Navigator</h3>
            <p>Cá nhân hóa lộ trình học tập dựa trên điểm mạnh, sở thích, môn học và mục tiêu của bạn</p>
            <a href="Study_navigator.php" class="btn">Bắt đầu học</a>
        </div>
        <div class="content" data-aos="zoom-in"> <h2>Phân hệ 2</h2>
            <h3>Career Launchapad AI</h3>
            <p>Tìm hiểu việc làm phù hợp và định hướng các kỹ năng cần thiết để phát triển nghề nghiệp mơ ước.</p>
            <a href="Career_launchpad.php" class="btn">Tìm việc ngay</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>