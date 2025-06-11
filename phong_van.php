<?php
session_start();
   if (!isset($_SESSION["username"])){
    header ("Location:login.php");
    exit();
   }
   include "UnipathAPI.php";
   $response="";
   $model_selected=$_POST["model"]?? "deepseek/deepseek-r1:free";
        if($_SERVER["REQUEST_METHOD"]=="POST" && isset( $_POST["action"]) && $_POST["action"]== "stop"){
            unset($_SESSION["chat_history_cv"]);
            header ("Location:".$_SERVER['PHP_SELF']);// làm mới trang
            exit();
        }
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
            transition: all 0.3s ease;
        }
          input[type="text"]:hover{
            background-color: #f1fff4;
            box-shadow:0 0 10px rgb(46, 125, 50, 0.2);
            border-color: #2e7d32;
          }
        button{
            margin: 0 30px;
            padding: 12px 20px;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background-color:rgb(29, 150, 55);
            transition: transform 0.2s ease, background-color 0.3s ease;

        }
        button:hover{
           transform: scale(1.1);
           opacity: 0.9;
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
                <input type="text" name="answer" placeholder="Mời bạn trả lời"
                        title="Mời bạn nhập câu trả lời rồi AI sẽ hỏi câu tiếp theo"><br><br>
                <input type="hidden" name="action" value="answer">
                <button type="submit" title="Bạn xác nhận gửi câu trả lời">Gửi câu trả lời</button>
                <button type="submit" name="action" value="stop" title="Bạn xác nhận kết thúc phỏng vấn" style="background-color:rgb(227, 101, 98);">Dừng phỏng vấn</button>
                <a href="index.php">Quay lại</a>
                <div id="loading" style="display: none; text-align:center; margin-top: 20px;">
                    <div class="spinner"></div>
                    <p>Đang tối ưu câu trả lời của bạn.....</p>
                </div>
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
    <script>
        // kiểm tra dữ liệu trước khi submit
         document.querySelector("form").addEventListener("submit",function(e){
            const submitButton = document.querySelector("button[name='action'][type='submit']:focus");
            const loading=document.getElementById("loading");
         // lấy nút submit đã đc ấn
         let pressedButton=e.submitter;
         // mếu là nút dừng phỏng vấn bỏ qua
            if(pressedButton && pressedButton.name==="action" && pressedButton.value==="stop"){
                return;
            }
            
            let answer=answerInput.value.trim();

            if(!answer){
                alert("Vui lòng nhập câu trả lời trước khi gửi");
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