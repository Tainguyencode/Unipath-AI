<?php
session_start();
   if (!isset($_SESSION["username"])){
    header ("Location:login.php");
    exit();
   }
   include "UnipathAPI.php";
   $response="";
   $model_selected=$_POST["model"]?? "deepseek/deepseek-r1:free";
        if($_SERVER["REQUEST_METHOD"]=="POST" && isset( $_POST["action"]) && $_POST["action"]== "start"){
            $_SESSION["chat_history_cv"]=[
                ["role"=>"system","content"=>"Bạn là một trợ lý hỗ trợ phỏng vấn chuyên nghiệp.
                Hãy bắt đầu buổi phỏng vấn bằng câu hỏi đầu tiên. Sau đó mỗi khi người dùng trả lời bạn cần: 
                Phân tích điểm mạnh, điểm yếu và bạn sẽ đưa ra câu trả lời tốt hơn.
                Bạn hãy hỏi tiếp câu hỏi tiếp theo liên quan đến buổi phỏng vấn.
                Luôn đảm bảo luồng câu hỏi mang tính logic phù hợp với vị trí ứng tuyển. "],
            ];
        $_SESSION["chat_history_cv"][]=["role"=> "user","content"=>"Hãy bắt đầu phỏng vấn"];

        $response= callDeepSeekChatHistory($_SESSION["chat_history_cv"],$model_selected);
        $_SESSION["chat_history_cv"][]=["role"=> "assistant","content"=> $response];
        }
        if( $_SERVER["REQUEST_METHOD"]=="POST" && isset( $_POST["action"]) && $_POST["action"]== "answer"){
            $answer=$_POST["answer"]??"";
            if(!empty($answer)&& isset($_SESSION["chat_history_cv"])){
                $_SESSION["chat_history_cv"][]=["role"=>"user","content"=> $answer];
                $_SESSION["chat_history_cv"][]=["role"=> "assistant","content"=>"Đang xử lý câu trả lời của bạn..."];
                $response=callDeepSeekChatHistory($_SESSION["chat_history_cv"],$model_selected);
        if($response){
            $_SESSION["chat_history_cv"][]=["role"=> "assistant","content"=> $response];
        } 
    }
}
   


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phỏng vấn AI</title>
     <style>
    *{
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body{
            width: 100%;
            margin: 50px auto;
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
    <h2>Phần phỏng vấn</h2>
    <h3>Chào mừng bạn đến với phần phỏng vấn</h3>
        <?php if(!isset($_SESSION["chat_history_cv"])):?>
            <form action="" method="post">
                <input type="hidden" name="action" value="start">
                <button>Bắt đầu phỏng vấn:</button>
            </form>
        <?php else:?>
            <form action="" method="post">
                <label for="answer">Câu trả lời của bạn:</label>
                <input type="text" name="answer" required placeholder="Mời bạn trả lời"><br><br>
                <input type="hidden" name="action" value="answer">
                <button type="submit">Gửi câu trả lời</button>
                <a href="index.php">Quay lại</a>
            </form>
        <?php endif;?>
    <?php if(!empty($_SESSION["chat_history_cv"])):?>
        <div id="result">
          <h3>Lịch sử phỏng vấn:</h3>
          <?php foreach($_SESSION["chat_history_cv"] as $item):?>
            <p><strong><?php echo ucfirst($item["role"]);?>:</strong><?php echo nl2br(htmlspecialchars($item["content"]));?></p>
            <?php endforeach;?>
           </div>
    <?php endif;?>
</body>
</html>