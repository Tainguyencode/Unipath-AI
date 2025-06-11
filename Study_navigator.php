<?php
session_start(); // Bắt đầu hoặc tiếp tục phiên làm việc

// Đảm bảo người dùng đã đăng nhập và có user_id trong session
if (!isset($_SESSION["user_id"])) { // Sử dụng user_id để quản lý dữ liệu DB
    header("Location: login.php"); // Chuyển hướng về trang đăng nhập nếu chưa đăng nhập
    exit();
}

// Bao gồm các file cần thiết
include "Account.php";           // File chứa kết nối database ($conn)
include "Data_handler_study.php"; // File chứa các hàm saveStudyData, updateStudyData, fetchStudyData
include "UnipathAPI.php";         // File chứa hàm callDeepSeekChatHistory để tương tác với AI

$response = ""; // Biến để lưu phản hồi mới nhất từ AI
$display_message = ''; // Biến để hiển thị thông báo cho người dùng

// Lấy username và user_id của người dùng hiện tại từ session
$current_username = $_SESSION["username"] ?? 'Khách';
$current_user_id = $_SESSION["user_id"];

// --- Khởi tạo/Lấy dữ liệu cho form ---
// Ưu tiên lấy dữ liệu từ SESSION để duy trì trạng thái hiện tại của form
// trong cùng một phiên đăng nhập.
// Sử dụng các biến session riêng biệt cho trang Study Navigator
$user_subject = $_SESSION["study_subject"] ?? "";
$user_projects = $_SESSION["study_projects"] ?? "";
$user_skills = $_SESSION["study_skills"] ?? "";
$user_goal = $_SESSION["study_goal"] ?? ""; // Thêm biến cho mục tiêu
$question = ""; // Trường câu hỏi luôn rỗng khi trang tải

// Lấy thông báo từ session nếu có (từ lần redirect trước)
if (isset($_SESSION['message'])) {
    $display_message = $_SESSION['message'];
    unset($_SESSION['message']); // Xóa thông báo sau khi đã hiển thị
}

// --- Xử lý yêu cầu reset toàn bộ ---
if (isset($_POST['reset_all'])) {
    unset($_SESSION['chat_history']); // Xóa toàn bộ lịch sử trò chuyện AI trong session
    // Xóa các dữ liệu form từ session để làm trống các trường input khi reset
    unset($_SESSION['study_subject']);
    unset($_SESSION['study_projects']);
    unset($_SESSION['study_skills']);
    unset($_SESSION['study_goal']);
    unset($_SESSION['message']); // Xóa thông báo cũ

    $_SESSION['message'] = "<p style='color:green; text-align:center;'>Đã làm mới tất cả dữ liệu. Bắt đầu cuộc trò chuyện mới!</p>";
    header("Location: " . $_SERVER['PHP_SELF']); // Redirect để xóa POST data và tải lại trang sạch
    exit();
}

// --- Xử lý khi Form được gửi (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy và làm sạch dữ liệu từ POST cho các trường học tập và câu hỏi
    $submitted_subject = htmlspecialchars(trim($_POST["subject"] ?? ''));
    $submitted_projects = htmlspecialchars(trim($_POST["projects"] ?? ''));
    $submitted_skills = htmlspecialchars(trim($_POST["skills"] ?? ''));
    $submitted_goal = htmlspecialchars(trim($_POST["goal"] ?? '')); // Lấy dữ liệu mục tiêu
    $submitted_question = htmlspecialchars(trim($_POST["question"] ?? ''));

    // CẬP NHẬT CÁC BIẾN SESSION riêng biệt cho trang Study Navigator
    $_SESSION["study_subject"] = $submitted_subject;
    $_SESSION["study_projects"] = $submitted_projects;
    $_SESSION["study_skills"] = $submitted_skills;
    $_SESSION["study_goal"] = $submitted_goal; // Cập nhật session cho mục tiêu

    // Cập nhật các biến $user_... để form hiển thị dữ liệu đã submit ngay lập tức
    $user_subject = $submitted_subject;
    $user_projects = $submitted_projects;
    $user_skills = $submitted_skills;
    $user_goal = $submitted_goal;

    // Đây là hành động chính khi nhấn "Gợi ý lộ trình học"
    if (isset($_POST['suggest_roadmap'])) {
        // Kiểm tra xem các trường quan trọng có rỗng không
        if (empty($submitted_subject) || empty($submitted_projects) || empty($submitted_skills) || empty($submitted_goal)) {
            $_SESSION['message'] = "<p style='color:red; text-align:center;'>Vui lòng nhập đầy đủ các thông tin môn học, dự án, kỹ năng và mục tiêu để nhận lộ trình học tập.</p>";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        // --- A. Lưu/Cập nhật dữ liệu học tập vào Database (`sndata`) ---
        $study_db_operation_success = false;
        $existing_study_data = fetchStudyData($conn, $current_user_id);

        if ($existing_study_data) {
            $study_data_id = $existing_study_data['id'];
            // Cập nhật dữ liệu học tập nếu đã tồn tại
            // Cần cập nhật hàm updateStudyData để hỗ trợ cột 'goal' nếu bạn thêm vào DB
            $study_db_operation_success = updateStudyData($conn, $study_data_id, $current_user_id, $submitted_subject, $submitted_projects, $submitted_skills);
        } else {
            // Lưu dữ liệu học tập mới nếu chưa tồn tại
            // Cần cập nhật hàm saveStudyData để hỗ trợ cột 'goal' nếu bạn thêm vào DB
            $study_db_operation_success = saveStudyData($conn, $current_user_id, $submitted_subject, $submitted_projects, $submitted_skills);
        }

        // Đặt thông báo hiển thị cho người dùng về trạng thái lưu dữ liệu
        if ($study_db_operation_success) {
            $_SESSION['message'] = "<p style='color:green; text-align:center;'>Dữ liệu học tập của bạn đã được lưu/cập nhật thành công!</p>";
        } else {
            $_SESSION['message'] = "<p style='color:red; text-align:center;'>Có lỗi xảy ra khi lưu/cập nhật dữ liệu học tập. Vui lòng thử lại.</p>";
        }

        // --- B. Quản lý Chat History và Gọi API AI ---
        // Khởi tạo lịch sử chat nếu chưa có hoặc nếu là một cuộc trò chuyện mới
        // Hoặc nếu câu hỏi mới không phải là tiếp nối của prompt ban đầu
        if (!isset($_SESSION["chat_history"]) || empty($_SESSION["chat_history"]) || (!empty($submitted_question) && strpos($_SESSION["chat_history"][count($_SESSION["chat_history"]) - 1]['content'], "Tôi đang học các môn:") === false && empty($_SESSION['initial_roadmap_generated']))) {
            $_SESSION["chat_history"] = [
                // Thêm tin nhắn hệ thống để định hướng vai trò của AI
                ["role" => "system", "content" => "Bạn là một trợ lý học tập giúp lên một lộ trình học chi tiết."]
            ];
            // Thêm prompt ban đầu của người dùng dựa trên thông tin đã submit
            $_SESSION["chat_history"][] = [
                "role" => "user",
                "content" => "Tôi đang học các môn: $submitted_subject. Tôi đã làm các dự án: $submitted_projects. Kỹ năng của tôi gồm: $submitted_skills. Mục tiêu của tôi là $submitted_goal. Tôi muốn trở thành một chuyên gia trong lĩnh vực trên. Hãy giúp tôi xây dựng một lộ trình học tập chi tiết trong 3 tháng, bao gồm từng kỹ năng cần học, thứ tự học, và nguồn tài liệu phù hợp (link hoặc tên khóa học nếu có)."
            ];
            $_SESSION['initial_roadmap_generated'] = true; // Đánh dấu đã tạo lộ trình ban đầu
        }

        // Nếu người dùng gửi câu hỏi mới, thêm vào lịch sử
        if (!empty($submitted_question)) {
            $_SESSION["chat_history"][] = ["role" => "user", "content" => $submitted_question];
        }

        // Gọi API AI chỉ khi có câu hỏi mới hoặc khi khởi tạo prompt ban đầu
        if (!empty($submitted_question) || (count($_SESSION["chat_history"]) == 2 && isset($_SESSION['initial_roadmap_generated']))) {
            $response = callDeepSeekChatHistory($_SESSION["chat_history"], "deepseek/deepseek-chat-v3-0324:free");
            if ($response) {
                // Thêm phản hồi của AI vào lịch sử trò chuyện
                $_SESSION["chat_history"][] = ["role" => "assistant", "content" => $response];
            } else {
                $_SESSION['message'] .= "<p style='color:red; text-align:center;'>Có lỗi xảy ra khi gọi AI. Vui lòng thử lại.</p>";
            }
        } else {
            if (empty($submitted_question)) { // Chỉ hiển thị nếu không có câu hỏi mới
                $_SESSION['message'] .= "<p style='color:orange; text-align:center;'>Đã lưu thông tin của bạn. Vui lòng đặt câu hỏi để nhận gợi ý lộ trình.</p>";
            }
        }

        // Sau khi xử lý POST, redirect để tránh gửi lại dữ liệu form khi refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Đóng kết nối database khi mọi thứ hoàn tất
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Study Navigator AI</title>
    <style>
        /* CSS của bạn ở đây (không thay đổi) */
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
            font-family: Arial, sans-serif;
        }
        form{
            background:#ffffff ;
            padding: 25px;
            border-radius: 12px;
            max-width: 800px;
            margin: 10px auto;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background: linear-gradient(to bottom, #ffffff,#a5d6a7);
            animation: fadeIn 0.6s ease-in-out; /* Thêm animation */
        }
        label{
            font-weight: bold;
            margin-top:15px;
            margin-bottom: 15px;
            display: block; /* Để mỗi label trên một dòng riêng */
        }
        input[type="text"], select{ /* Áp dụng style cho cả select */
            width: 100%;
            padding: 15px;
            border-radius: 9px;
            border: 1px solid #ccc;
            font-size: 15px;
            margin-bottom: 15px; /* Khoảng cách dưới mỗi input */
        }
        input[type="text"]:hover, select:hover{ /* Thêm hover cho select */
            background-color: #f1f8e9;
            transition:background-color 0.3s ease ;
        }
        @keyframes fadeIn{ /* Keyframe cho animation */
            from{opacity:0; transform: translateY(30px);}
            to{opacity: 1; transform: translateY(0);}
        }
        button{
            padding: 12px 20px;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background-color:rgb(29, 150, 55);
            margin-right: 10px;
        }
        button:hover{
            transform: scale(1.05);
        }
        .button-group {
            display: flex; /* Dùng flexbox để sắp xếp các nút */
            justify-content: space-between; /* Đặt khoảng cách giữa các nút */
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap; /* Cho phép các nút xuống dòng trên màn hình nhỏ */
            gap: 10px; /* Khoảng cách giữa các nút */
        }
        .button-group button, .button-group a {
            margin-right: 0; /* Bỏ margin-right mặc định */
        }
        .button-group a {
            text-decoration: none;
            color:#007bff;
            font-size: 18px;
            white-space: nowrap; /* Ngăn link bị xuống dòng */
        }
        .button-group a:hover{
            text-decoration: underline;
        }
        .chat-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 25px;
            background: #fdfdfd;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .chat-history-box {
            max-height: 450px; /* Max height for chat scroll */
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .chat-message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 10px;
            line-height: 1.5;
            word-wrap: break-word; /* Ensure long words wrap */
        }
        .user-message {
            background-color: #dcf8c6; /* Light green for user */
            text-align: right;
            margin-left: 15%; /* Indent from left */
        }
        .assistant-message {
            background-color: #e6e6e6; /* Light gray for assistant */
            text-align: left;
            margin-right: 15%; /* Indent from right */
        }
        .message-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        #result { /* For the initial generated roadmap/response */
            max-width: 700px;
            margin: 40px auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
            border-left: 5px solid #2e7d32;
        }
        #result h3{
            color: #FA8072;
            text-align: left;
            margin-bottom: 10px;
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
            margin-right: 10px;
        }
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <h2>Study Navigator AI</h2>
    <h3>Chào mừng bạn đến với Study Navigator AI</h3>
    <p style="text-align: center;">Chào mừng, <?php echo htmlspecialchars($current_username); ?>!</p>

    <?php echo $display_message; // Hiển thị thông báo ?>

    <form method="post">
        <label for="subject">Tôi đang học các môn:</label>
        <input type="text" name="subject" id="subject" placeholder="Nhập môn bạn đã học"
                value="<?php echo htmlspecialchars($user_subject); ?>"><br>

        <label for="projects">Các dự án đã làm:</label>
        <input type="text" name="projects" id="projects" placeholder="Nhập dự án của bạn"
                value="<?php echo htmlspecialchars($user_projects); ?>"><br>

        <label for="skills">Tôi có các kỹ năng:</label>
        <input type="text" name="skills" id="skills" placeholder="Nhập kỹ năng cứng hoặc mềm"
                value="<?php echo htmlspecialchars($user_skills); ?>"><br>

        <label for="goal">Mục tiêu học tập:</label>
        <select name="goal" id="goal">
            <option value="">Chọn mục tiêu học tập của bạn:</option>
            <option value="Trở thành chuyên gia" <?php if($user_goal=="Trở thành chuyên gia") echo "selected";?>>Trở thành chuyên gia</option>
            <option value="Nâng cao kiến thức" <?php if($user_goal=="Nâng cao kiến thức") echo "selected";?>>Nâng cao kiến thức</option>
            <option value="Chuẩn bị phỏng vấn" <?php if($user_goal=="Chuẩn bị phỏng vấn") echo "selected";?>>Chuẩn bị phỏng vấn</option>
            <option value="Thi chứng chỉ" <?php if($user_goal=="Thi chứng chỉ") echo "selected";?>>Thi chứng chỉ</option>
            <option value="Làm việc thực tế" <?php if($user_goal=="Làm việc thực tế") echo "selected";?>>Làm việc thực tế</option>
        </select><br>

        <label for="question">Câu hỏi của bạn (để tiếp tục trò chuyện):</label>
        <input type="text" name="question" id="question" placeholder="Mời bạn nhập câu hỏi" value=""><br>

        <div class="button-group">
            <button type="submit" name="suggest_roadmap">Gợi ý lộ trình học</button>
            <button type="submit" name="reset_all" formnovalidate style="background-color: #b71c1c;">Làm mới</button>
            <a href="index.php">Quay lại</a>
        </div>
        <div id="loading" style="display: none; text-align:center; margin-top: 20px;">
            <div class="spinner"></div>
            <p>Đang tạo lộ trình cho bạn.....</p>
        </div>
    </form>

    <div class="chat-container">
        <h3>Lịch sử trò chuyện với AI:</h3>
        <div class="chat-history-box">
            <?php
            if (isset($_SESSION["chat_history"]) && count($_SESSION["chat_history"]) > 1) {
                // Bỏ qua tin nhắn system (tin nhắn đầu tiên trong lịch sử chat)
                foreach ($_SESSION["chat_history"] as $index => $message) {
                    if ($message["role"] !== "system") {
                        $message_class = ($message["role"] === "user") ? 'user-message' : 'assistant-message';
                        $sender_name = ($message["role"] === "user") ? 'Bạn' : 'Study Navigator AI';
                        echo "<div class='chat-message {$message_class}'>";
                        echo "<div class='message-title'>{$sender_name}:</div>";
                        echo "<p>" . nl2br(htmlspecialchars($message["content"])) . "</p>";
                        echo "</div>";
                    }
                }
            } else {
                echo "<p style='text-align: center; color: #777;'>Nhập thông tin và nhấn 'Gợi ý lộ trình học' để bắt đầu cuộc trò chuyện.</p>";
            }
            ?>
        </div>
    </div>

    <?php
    // Chỉ hiển thị phản hồi mới nhất nếu nó không rỗng và không phải là một phần của lịch sử chat (đã hiển thị ở trên)
    // Biến $response được sử dụng để hiển thị các tin nhắn lỗi hoặc thông báo tạm thời từ quá trình xử lý PHP
    if (!empty($response) && (isset($_SESSION["chat_history"]) && end($_SESSION["chat_history"])["content"] !== $response)): ?>
    <div id="result">
        <h3>Phản hồi mới nhất từ AI (nếu có lỗi hoặc tin nhắn tạm thời):</h3>
        <p><?php echo nl2br(htmlspecialchars($response)); ?></p>
    </div>
    <?php endif; ?>

    <script>
        // Tự động cuộn xuống cuối lịch sử chat khi trang tải xong
        window.onload = function() {
            var chatHistoryBox = document.querySelector('.chat-history-box');
            if (chatHistoryBox) {
                chatHistoryBox.scrollTop = chatHistoryBox.scrollHeight;
            }
            // Đảm bảo loading ẩn đi khi trang tải lại hoàn tất (cho trường hợp quay lại trang bằng nút back)
            const loading = document.getElementById("loading");
            if (loading) {
                loading.style.display = "none";
            }
        };

        // Tooltip khi hover qua input
        document.querySelectorAll("input[type='text'], select").forEach(element => { // Áp dụng cho cả select
            element.addEventListener("mouseover", () => {
                element.style.borderColor = "#2e7d32";
                let tooltipText = "";
                if (element.name === "subject") {
                    tooltipText = "Nhập tên môn học hoặc khóa học bạn đã/đang học";
                } else if (element.name === "projects") {
                    tooltipText = "Nhập các dự án bạn đã làm (nếu có), ví dụ như: website bán quần áo,..";
                } else if (element.name === "skills") {
                    tooltipText = "Nhập các kỹ năng bạn có như Teamwork, giao tiếp, lập trình php,..";
                } else if (element.name === "question") {
                    tooltipText = "Đặt câu hỏi để có lộ trình phù hợp, ví dụ: Tôi nên học gì tiếp theo?,..";
                } else if (element.name === "goal") {
                    tooltipText = "Chọn mục tiêu để AI đưa ra lộ trình phù hợp";
                }
                element.title = tooltipText;
            });
            element.addEventListener("mouseout", () => { // Thêm event mouseout để bỏ màu border
                element.style.borderColor = "#ccc";
            });
        });

        // Kiểm tra dữ liệu và hiển thị loading spinner trước khi submit
        document.querySelector("form").addEventListener("submit", function(e) {
            const submitButton = document.activeElement;
            const loading = document.getElementById("loading");

            // Nếu nút "Làm mới" được nhấn, cho phép form submit ngay lập tức
            if (submitButton && submitButton.name === "reset_all") {
                return;
            }

            // Kiểm tra các trường bắt buộc khi nhấn "Gợi ý lộ trình học"
            let subject = document.getElementById("subject").value.trim();
            let projects = document.getElementById("projects").value.trim();
            let skills = document.getElementById("skills").value.trim();
            let goal = document.getElementById("goal").value.trim();
            let question = document.getElementById("question").value.trim(); // question chỉ cần nếu là câu hỏi tiếp theo

            // Điều kiện kiểm tra:
            // Nếu là lần đầu submit (chưa có chat_history hoặc reset), các trường chính phải có.
            // Nếu đã có chat_history và chỉ gửi câu hỏi mới, chỉ cần question.
            const hasChatHistory = <?php echo isset($_SESSION["chat_history"]) && count($_SESSION["chat_history"]) > 1 ? 'true' : 'false'; ?>;

            let isValid = true;
            let errorMessage = "Vui lòng nhập đầy đủ các trường thông tin:\n";

            if (!subject) {
                isValid = false;
                errorMessage += "- Môn học đã/đang học\n";
            }
            if (!projects) {
                isValid = false;
                errorMessage += "- Dự án đã làm\n";
            }
            if (!skills) {
                isValid = false;
                errorMessage += "- Kỹ năng\n";
            }
            if (!goal) {
                isValid = false;
                errorMessage += "- Mục tiêu học tập\n";
            }

            // Logic kiểm tra câu hỏi đầu tiên vs các câu hỏi tiếp theo
            if (!hasChatHistory && !question) {
                // Nếu chưa có lịch sử chat (lần đầu tạo lộ trình) thì câu hỏi cũng bắt buộc
                isValid = false;
                errorMessage += "- Câu hỏi của bạn (để bắt đầu trò chuyện)\n";
            } else if (hasChatHistory && !question && submitButton && submitButton.name === "suggest_roadmap") {
                // Nếu đã có lịch sử chat và nhấn "Gợi ý lộ trình học" mà không nhập câu hỏi,
                // thì không có gì để gửi đến AI, không hiển thị loading
                // và đưa ra thông báo phù hợp.
                isValid = false;
                errorMessage = "Vui lòng nhập câu hỏi để tiếp tục trò chuyện.";
            }

            if (!isValid) {
                alert(errorMessage);
                e.preventDefault(); // Ngăn form submit
                loading.style.display = "none";
            } else {
                // Nếu tất cả các trường đều có dữ liệu, hiển thị loading
                loading.style.display = "block";
            }
        });
    </script>
</body>
</html>