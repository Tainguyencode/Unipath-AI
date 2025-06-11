<?php
session_start();
if(!isset($_SESSION["username"])) {
    header("Location:login.php");
    exit();
}
include "UnipathAPI.php";
$response = "";
$model_selected =$_POST["model"] ?? "deepseek/deepseek-chat-v3-0324:free";
// kiểm tra form khi đc gửi
//lấy câu hỏi ngiuoiwf dùng nhập vào
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $question=$_POST["question"]??"";
//
    if(!empty($question)){
        if(!isset($_SESSION["chat_history_hd"])){
            $_SESSION["chat_history_hd"]=[
                ["role"=>"system","content"=>"Bạn là trợ lý AI hỗ trợ câu hỏi của người dùng"],
            ];
        }
        // thêm câu hỏi mới từ người dùng vào lịch sử với vai trò user
        $_SESSION["chat_history_hd"][]=["role"=> "user","content"=>$question];
        
        $response=callDeepSeekChatHistory($_SESSION["chat_history_hd"],$model_selected);
        // nếu có phản hồi từ AI phản hồi đó sẽ đc thêm vào chat_history_hd để duy trì cuộc trò chuyện
        if($response){
            $_SESSION["chat_history_hd"][]=["role"=> "assistant","content"=>$response];
        }
}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hỏi đáp</title>
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
        select,input[type="text"]{
            width: 100%;
            padding: 15px;
            border-radius: 9px;
            border: 1px solid #ccc;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        select:hover, input[type="text"]:hover{
            box-shadow: 0 0 10px rgba(46, 125, 50, 0.4);
            border-color: #2e7d32;
            background-color: #f1fff4;
        }
        select:focus, input[type="text"]:focus{
                outline: none;
                box-shadow: 0 0 12px rgba(46, 125, 50, 0.6);
                border-color: #2e7d32 ;
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
         .spinner {
            border: 4px solid rgba(0, 0, 0, .1);
            border-left-color: #2e7d32; /* Màu của phần quay */
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
       }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <h2>Phần hỏi đáp</h2>
    <h3>Chào mừng bạn đến với phần hỏi đáp</h3>
    <form action="" method="post">

        <label for="">Chọn mô hình AI mà bạn muốn trò chuyện:</label>
        <select name="model" id="model" title="Chọn mô hình AI để tương tác">
            <option value="deepseek/deepseek-chat-v3-0324:free" <?php echo ($model_selected == 'deepseek/deepseek-chat-v3-0324:free') ? 'selected' : ''; ?>>Deepseek chat</option>
            <option value="deepseek/deepseek-r1:free" <?php echo ($model_selected == 'deepseek/deepseek-r1:free') ? 'selected' : ''; ?>>Deepseek r1</option>
            <option value="google/gemini-2.0-flash-exp:free" <?php echo ($model_selected == 'google/gemini-2.0-flash-exp:free') ? 'selected' : ''; ?>>Gemini-2.0-flash</option>
        </select><br><br>

        <label for="">Câu hỏi của bạn:</label>
        <input type="text" name="question" placeholder="Mời bạn nhập câu hỏi" title="Bạn có thể hỏi bất cứ điều gì mà bạn muốn AI trả lời"><br><br>

        <button type="submit">Gửi câu hỏi</button>
         <a href="index.php">Quay lại</a>
         <div id="loading" style="display: none; text-align:center; margin-top: 20px;">
    <div class="spinner"></div>
       <p>Đang trả lời câu hỏi của bạn.....</p>
    </div>
    </form>
    <?php if (!empty($_SESSION["chat_history_hd"])): ?>
    <div id="result">
        <h3>Kết quả:</h3>
        <?php foreach($_SESSION["chat_history_hd"] as  $thutu):?>
        <p><strong><?php echo ucfirst($thutu["role"]);?>:</strong><?php echo nl2br(htmlspecialchars($response)); ?></p>
        <?php endforeach;?>
    </div>
    <?php endif; ?>
    <script>
       // kiểm tra dữ liệu trước khi submit
         document.querySelector("form").addEventListener("submit",function(e){
            const submitButton=document.activeElement;
            const loading=document.getElementById("loading");
        
            let question=document.querySelector("input[name='question']").value.trim();
            

            if(!question){
                alert("Vui lòng nhập nhập câu hỏi trước khi gửi");
                e.preventDefault();// ngăn form submit
                loading.style.display="none";
            }else{
                  // Nếu tất cả các trường đều có dữ liệu, hiển thị loading
                   loading.style.display = "block";
            }
         });
         // Đảm bảo loading ẩn đi khi trang tải lại hoàn tất 
         window.addEventListener('load',()=>{
            const loading=document.getElementById("loading");
            if(loading){
                loading.style.display="none";
            }
         });

    </script>
</body>
</html>