<?php
session_start();
if(!isset($_SESSION["username"])) {
    header("Location:login.php");
    exit();
}
include "UnipathAPI.php";
$response = "";
 if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(!empty($_POST["subject"]))$_SESSION["subject"] = $_POST["subject"];
    if(!empty($_POST["projects"]))$_SESSION["projects"] = $_POST["projects"];
    if(!empty($_POST["skills"]))$_SESSION["skills"] = $_POST["skills"];
    if(!empty($_POST["experience"]))$_SESSION["experience"] = $_POST["experience"];
    if(!empty($_POST["position"]))$_SESSION["position"] = $_POST["position"];
    if(!empty($_POST["goal"]))$_SESSION["goal"] = $_POST["goal"];
    // Lấy dữ liệu từ session
    $subject = $_SESSION["subject"] ?? "";
    $projects = $_SESSION["projects"] ?? "";
    $skills = $_SESSION["skills"] ?? "";
    $experience = $_SESSION["experience"] ?? "";
    $position = $_SESSION["position"] ??"";
    $goal= $_SESSION["goal"] ??"";
    $question= $_SESSION["question"] ??"";

    if(!isset($_SESSION["chat_history_cv"])){
        $_SESSION["chat_history_cv"] = [
            ["role"=>"system","content"=>"Bạn là một trợ lý hỗ trợ tìm việc"]
        ];
        $_SESSION["chat_history_cv"][]=["role"=> "user","content"=> "Tôi đã học ngành : $subject, đã làm các dự án: $projects, có kỹ năng: $skills, có kinh nghiêm thực tập:$experience, vị trí mong muốn của tôi là:$position,
     mục tiêu của tôi là:$goal.Bạn hãy đề xuất cho tôi công việc phù hợp với bản thân, công ty phù hợp và tỉ lệ vào được các công ty đó ra sao."];
    }
   $_SESSION["chat_history_cv"][]=["role"=> "user","content"=> $question];
    // Gọi DeepSeek R1
    $response= callDeepSeekChatHistory($_SESSION["chat_history_cv"], "deepseek/deepseek-r1:free");
    if($response){
        $_SESSION["chat_history_cv"][]=["role"=> "assistant","content"=> $response];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Career Launchpad AI</title>
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
            color:#FA8072;
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
    <h2>Career Launchpad AI</h2>
    <h3>Chào mừng bạn đến với Career Launchpad AI</h3>
    <form method="post">

        <label for="">Tôi đã học ngành:</label>
        <input type="text" name="subject" placeholder="Nhập môn bạn đã học" required
               value="<?php echo htmlspecialchars($_SESSION["subject"] ??'');?>"><br><br>

         <label for="">Các dự án đã làm:</label>
        <input type="text" name="projects" placeholder="Nhập dự án của bạn" required
               value="<?php echo htmlspecialchars($_SESSION["projects"] ??'');?>"><br><br>

         <label for="">Tôi có các kỹ năng:</label>
        <input type="text" name="skills" placeholder="Nhập kỹ năng cứng hoặc mềm" required
               value="<?php echo htmlspecialchars($_SESSION["skills"] ??'');?>"><br><br>

         <label for="">Kinh nghiệm thực tâp:</label>
        <input type="text" name="experience" placeholder="Nhập kinh nghiệm của bạn" required
               value="<?php echo htmlspecialchars($_SESSION["experience"] ??'');?>"><br><br>

         <label for="">Vị trí bạn mong muốn:</label>
        <input type="text" name="position" placeholder="Nhập vị trí mong muốn" required
               value="<?php echo htmlspecialchars($_SESSION["position"] ??'');?>"><br><br>

         <label for="">Mục tiêu hướng tới:</label>
        <input type="text" name="goal" placeholder="Nhập mục tiêu của bạn" required\
               value="<?php echo htmlspecialchars($_SESSION["goal"] ??'');?>"><br><br>
        
        <label for="">Câu hỏi của bạn:</label>
        <input type="text" name="question" required placeholder="Mời bạn nhập câu hỏi"><br><br>
        <button type="submit">Tìm việc ngay</button>
         <a href="index.php">Quay lại</a>
    </form>
    <?php if (!empty($response)): ?>
    <div id="result">
        <h3>Kết quả:</h3>
        <p><?php echo nl2br(htmlspecialchars($response)); ?></p>
    </div>
    <?php endif; ?>
</body>
</html>
