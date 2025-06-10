<?php
session_start();
if(!isset($_SESSION["username"])) {
    header("Location:login.php");
    exit();
}
    include "UnipathAPI.php"; 
    $response = "";// hiển thị kết quả trae vè của AI
    if ($_SERVER["REQUEST_METHOD"] == "POST") {//kiểm tra người dùng  đã gửi form bằng post ko nếu có thì xử lý
    // giữ lại các câu input có giá trị lưu vào session cho câu hỏi tiếp theo
    if(!empty($_POST["subject"])) $_SESSION["subject"]= $_POST["subject"];
    if(!empty($_POST["projects"])) $_SESSION["projects"]= $_POST["projects"];
    if(!empty($_POST["skills"])) $_SESSION["skills"]= $_POST["skills"];
    //lấy  lại thông tin từ sesion( nếu có )để dung khi tạo prompt howacj hiển thị lại giao diện
    
    $subject = $_SESSION["subject"]??"";
    $projects = $_SESSION["projects"]??"";
    $skills = $_SESSION["skills"]??"";
    $question=$_SESSION["question"]??"";
    //khởi tạo lịch sử chat
    if(!isset($_SESSION["chat_history"])) {
        $_SESSION["chat_history"]=[
            // cho AI đóng vai trò là 1 trợ lý
            ["role"=>"system","content"=>"Bạn là một trợ lý học tập giúp lên một lộ trình học chi tiết"]
        ];
        // thêm vào lịch sử để AI hiểu đc ục tiêu học tập 
        $_SESSION["chat_history"][]=["role"=>"user","content"=>"Tôi đang học các môn: $subject. Tôi đã làm các dự án: $projects. Kỹ năng của tôi gồm: $skills. 
            Tôi muốn trở thành một chuyên gia trong lĩnh vực trên. 
            Hãy giúp tôi xây dựng một lộ trình học tập chi tiết trong 3 tháng,
            bao gồm từng kỹ năng cần học,
            thứ tự học, và nguồn tài liệu phù hợp (link hoặc tên khóa học nếu có)."];
    }
    // thêm câu hỏi mới của người dùng vào lịch sử
    $_SESSION["chat_history"][]=["role"=> "user","content"=> $question];
    
    // Gọi API đến DeepSeek (placeholder)
   $response = callDeepSeekChatHistory($_SESSION["chat_history"], "deepseek/deepseek-chat-v3-0324:free");
   // thêm phản hồi lại lịch sử
   if($response){
    $_SESSION["chat_history"][]=["role"=> "assistant","content"=> $response];
   }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Study Navigator AI</title>
    <style>
         *{
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body{
            width: 100%;
            margin: 0 auto;
            padding: 0;
            background-color: #e0f7e9;
        }
        form{
            background:#ffffff ;
            padding: 25px;
            border-radius: 12px;
            max-width: 800px;
            margin: 10px auto;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background: linear-gradient(to bottom, #ffffff,#a5d6a7);
        }
        label{
            font-weight: bold;
            margin-top:15px;
            margin-bottom: 15px;
        }
        input[type="text"]{
            width: 100%;
            padding: 15px;
            border-radius: 9px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        button{
            padding: 12px 20px;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
             background-color:rgb(29, 150, 55);
        }
        button:hover{
           transform: scale(1.1);
        }
        a{
            margin-left:20px ;
            text-decoration: none;
            color:#007bff;
            font-size: 18px;
        }
        a:hover{
            text-decoration: underline;
        }
        #result{
            max-width: 700px;
            margin: 40px auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        
        }
        #result h3{
            color: #FA8072;
        }
        #result p{
             white-space: pre-wrap;
             line-height: 1.6;
        }
        h2, h3{
            text-align: center;
           color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="main">
        <h2>Study Navigator AI</h2>
        <h3>Chào mừng bạn đến với Study Navigator AI</h3>
    <form method="post">
        <!-- giữ lại giá trị người dùng đã nhập khi AI tre lời sẽ ko bị mất đi -->
        <label>Môn học đã/đang học:</label><br>
        <input type="text" name="subject" required placeholder="Tên môn hoặc khóa học"
               value="<?php echo htmlspecialchars($_SESSION["subject"]??'');?>"><br><br>
        
        <label>Dự án đã làm:</label><br>
        <input type="text" name="projects" required placeholder="Các dự án đã từng làm"
               value="<?php echo htmlspecialchars($_SESSION["projects"]??'');?>"><br><br>
        
        <label>Kỹ năng:</label><br>
        <input type="text" name="skills" required placeholder="kỹ năng cứng hoặc mềm"
               value="<?php echo htmlspecialchars($_SESSION["skills"]??'');?>"><br><br>
        <label for="">Câu hỏi của bạn:</label>
        <input type="text" name="question" required placeholder="Mời bạn nhập câu hỏi"><br><br>
        
        <button type="submit">Gợi ý lộ trình học</button>
        <a href="index.php">Quay lại</a>
    </form>
    <?php if (!empty($response)): ?>
    <div id="result">
        <h3>Kết quả:</h3>
        <p><?php echo nl2br(htmlspecialchars($response)); ?></p>
    </div>
    <?php endif; ?>
    </div>
</body>
</html>
