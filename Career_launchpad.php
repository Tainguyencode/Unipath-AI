<?php
session_start();

// Đảm bảo người dùng đã đăng nhập
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Bao gồm các file cần thiết
include "Account.php";           // Kết nối database ($conn)
include "Data_handler_study.php";  // Chứa saveStudyData, updateStudyData, fetchStudyData
include "UnipathAPI.php";            // Chứa hàm callDeepSeekChatHistory

$response = ""; // Biến để lưu phản hồi mới nhất từ AI
$display_message = ''; // Biến để hiển thị thông báo

// Lấy username và user_id của người dùng hiện tại
$current_username = $_SESSION["username"] ?? 'Khách';
$current_user_id = $_SESSION["user_id"] ?? null; // Đảm bảo user_id có sẵn

// --- Khởi tạo/Lấy dữ liệu cho form ---
// Khởi tạo các biến form mặc định là rỗng
$user_subject = "";
$user_projects = "";
$user_skills = "";
$user_experience = "";
$user_position = "";
$user_goal = "";
$question = ""; // Trường câu hỏi luôn rỗng khi trang tải

// Lấy thông báo từ session nếu có (từ lần redirect trước)
if (isset($_SESSION['message'])) {
    $display_message = $_SESSION['message'];
    unset($_SESSION['message']); // Xóa thông báo sau khi hiển thị
}

// Logic TẢI DỮ LIỆU VÀO FORM:
// Dữ liệu chỉ được tải từ SESSION để điền vào form NẾU cờ 'career_form_is_persistent' là TRUE.
// Cờ này chỉ được đặt TRUE khi AI phản hồi thành công HOẶC khi dữ liệu học tập được tải tạm thời.
if (isset($_SESSION['career_form_is_persistent']) && $_SESSION['career_form_is_persistent'] === true) {
    $user_subject = $_SESSION["career_subject"] ?? $user_subject;
    $user_projects = $_SESSION["career_projects"] ?? $user_projects;
    $user_skills = $_SESSION["career_skills"] ?? $user_skills;
    $user_experience = $_SESSION["career_experience"] ?? $user_experience;
    $user_position = $_SESSION["career_position"] ?? $user_position;
    $user_goal = $_SESSION["career_goal"] ?? $user_goal;

    // Nếu dữ liệu được tải do nút "Tải dữ liệu học tập của tôi",
    // thì ngay lập tức tắt cờ persistence để nó không giữ lại sau lần tải này.
    // Dữ liệu chỉ được giữ lại vĩnh viễn khi AI phản hồi thành công.
    if (isset($_SESSION['career_loaded_from_db_once']) && $_SESSION['career_loaded_from_db_once'] === true) {
        $_SESSION['career_form_is_persistent'] = false; // <--- TẮT PERSISTENCE SAU KHI DÙNG
        unset($_SESSION['career_loaded_from_db_once']); // Xóa cờ tạm thời
    }

} else {
    // If the flag is not set or is false, ensure all career_ session variables are cleared
    // so the form starts empty.
    unset($_SESSION['career_subject']);
    unset($_SESSION['career_projects']);
    unset($_SESSION['career_skills']);
    unset($_SESSION['career_experience']);
    unset($_SESSION['career_position']);
    unset($_SESSION['career_goal']);
    // No need to unset $_SESSION['career_form_is_persistent'] here as it's already not true.
}


// --- Xử lý yêu cầu reset chat / làm mới toàn bộ ---
if (isset($_POST['reset_chat']) || isset($_POST['reset_all'])) {
    
    // Xóa TẤT CẢ các dữ liệu form từ SESSION riêng biệt cho trang Career Launchpad
    unset($_SESSION['career_subject']);
    unset($_SESSION['career_projects']);
    unset($_SESSION['career_skills']);
    unset($_SESSION['career_experience']);
    unset($_SESSION['career_position']);
    unset($_SESSION['career_goal']);
    unset($_SESSION['career_form_is_persistent']); // Reset cờ này
    unset($_SESSION['career_loaded_from_db_once']); // Đảm bảo cờ này cũng được xóa
    
    // Xóa các biến session tạm thời trong File 2 cũ (nếu chúng còn tồn tại và không liên quan)
    unset($_SESSION["subject"]);
    unset($_SESSION["projects"]);
    unset($_SESSION["skills"]);
    unset($_SESSION["goal"]);
    unset($_SESSION["question"]);
    unset($_SESSION["chat_history_cv"]); // Xóa lịch sử chat CV

    header("Location: " . $_SERVER['PHP_SELF']); // Redirect để xóa POST data và tải lại trang sạch
    exit();
}

// --- Xử lý khi Form được gửi (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy và làm sạch dữ liệu từ POST cho tất cả các trường
    $submitted_subject = htmlspecialchars(trim($_POST["subject"] ?? ''));
    $submitted_projects = htmlspecialchars(trim($_POST["projects"] ?? ''));
    $submitted_skills = htmlspecialchars(trim($_POST["skills"] ?? ''));
    $submitted_experience = htmlspecialchars(trim($_POST["experience"] ?? ''));
    $submitted_position = htmlspecialchars(trim($_POST["position"] ?? ''));
    $submitted_goal = htmlspecialchars(trim($_POST["goal"] ?? ''));
    $submitted_question = htmlspecialchars(trim($_POST["question"] ?? ''));

    // Cập nhật các biến $user_... để form hiển thị dữ liệu đã submit ngay lập tức cho trang hiện tại
    // (Điều này quan trọng vì bạn muốn thấy dữ liệu đã nhập ngay sau khi submit, 
    // trước khi redirect và logic persistence/clear session được áp dụng)
    $user_subject = $submitted_subject;
    $user_projects = $submitted_projects;
    $user_skills = $submitted_skills;
    $user_experience = $submitted_experience;
    $user_position = $submitted_position;
    $user_goal = $submitted_goal;

    // --- Xử lý nút "Tải dữ liệu học tập của tôi" ---
    if (isset($_POST['load_study_data'])) {
        if ($current_user_id) {
            $fetched_study_data_from_db = fetchStudyData($conn, $current_user_id);
            if ($fetched_study_data_from_db) {
                // Populate SESSION variables for current display.
                $_SESSION["career_subject"] = $fetched_study_data_from_db['subject'] ?? '';
                $_SESSION["career_projects"] = $fetched_study_data_from_db['projects'] ?? '';
                $_SESSION["career_skills"] = $fetched_study_data_from_db['skills'] ?? '';
                $_SESSION["career_experience"] = $fetched_study_data_from_db['experience'] ?? ''; 
                $_SESSION["career_position"] = $fetched_study_data_from_db['position'] ?? '';     
                $_SESSION["career_goal"] = $fetched_study_data_from_db['goal'] ?? '';            
                
                // ĐẶT cờ $_SESSION['career_form_is_persistent'] = true TẠM THỜI
                // để dữ liệu này được tải vào form ở lần request tiếp theo (sau redirect).
                $_SESSION['career_form_is_persistent'] = true; // <--- ĐẶT TRUE TẠM THỜI ĐỂ HIỆN RA
                
                // Đặt thêm một cờ để biết rằng đây là từ nút "Tải dữ liệu học tập"
                $_SESSION['career_loaded_from_db_once'] = true; // <--- CỜ MỚI ĐỂ ĐÁNH DẤU

                $_SESSION['message'] = "<p style='color:blue; text-align:center;'>Dữ liệu học tập của bạn đã được tải vào form thành công!</p>";
            } else {
                $_SESSION['message'] = "<p style='color:orange; text-align:center;'>Không tìm thấy dữ liệu học tập đã lưu. Vui lòng nhập thủ công.</p>";
                $_SESSION['career_form_is_persistent'] = false; // Nếu không tìm thấy, không giữ dữ liệu
                unset($_SESSION['career_loaded_from_db_once']); // Đảm bảo cờ này cũng được xóa
            }
        } else {
            $_SESSION['message'] = "<p style='color:red; text-align:center;'>Không thể tải dữ liệu: User ID không khả dụng.</p>";
            $_SESSION['career_form_is_persistent'] = false; // Luôn đặt false nếu không thể tải
            unset($_SESSION['career_loaded_from_db_once']); // Đảm bảo cờ này cũng được xóa
        }
        
        // Redirect để xóa POST data và đảm bảo logic tải từ session được áp dụng
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } 
    // --- Xử lý nút "Tìm việc ngay" ---
    elseif (isset($_POST['find_job_now'])) {
        // --- A. Lưu/Cập nhật dữ liệu học tập vào Database (`sndata`) ---
        $study_db_operation_success = false;
        if ($current_user_id) {
            $existing_study_data = fetchStudyData($conn, $current_user_id);

            if ($existing_study_data) {
                $study_data_id = $existing_study_data['id'];
                $study_db_operation_success = updateStudyData($conn, $study_data_id, $current_user_id, 
                                                                $submitted_subject, $submitted_projects, $submitted_skills,
                                                                $submitted_experience, $submitted_position, $submitted_goal);
            } else {
                $study_db_operation_success = saveStudyData($conn, $current_user_id, 
                                                                $submitted_subject, $submitted_projects, $submitted_skills,
                                                                $submitted_experience, $submitted_position, $submitted_goal);
            }

            if ($study_db_operation_success) {
                $_SESSION['message'] = "<p style='color:green; text-align:center;'>Dữ liệu học tập của bạn đã được lưu/cập nhật thành công!</p>";
            } else {
                $_SESSION['message'] = "<p style='color:red; text-align:center;'>Có lỗi xảy ra khi lưu/cập nhật dữ liệu học tập. Vui lòng thử lại.</p>";
            }
        } else {
            $_SESSION['message'] = "<p style='color:red; text-align:center;'>Không thể lưu dữ liệu: User ID không khả dụng.</p>";
        }

        // Cập nhật các giá trị vào SESSION riêng biệt cho trang Career Launchpad
        $_SESSION["career_subject"] = $submitted_subject;
        $_SESSION["career_projects"] = $submitted_projects;
        $_SESSION["career_skills"] = $submitted_skills;
        $_SESSION["career_experience"] = $submitted_experience;
        $_SESSION["career_position"] = $submitted_position;
        $_SESSION["career_goal"] = $submitted_goal;
        
        // Mặc định cờ persistence là FALSE. Nó chỉ thành TRUE nếu AI trả lời thành công.
        $_SESSION['career_form_is_persistent'] = false; 
        unset($_SESSION['career_loaded_from_db_once']); // Đảm bảo cờ này luôn được xóa khi bấm tìm việc

        // Khởi tạo lịch sử chat nếu chưa có hoặc nếu là một cuộc trò chuyện mới
        if (!isset($_SESSION["chat_history_cv"]) || empty($_SESSION["chat_history_cv"])) {
            $_SESSION["chat_history_cv"] = [
                ["role" => "system", "content" => "Bạn là một trợ lý hỗ trợ tìm việc."]
            ];
            $_SESSION["chat_history_cv"][] = ["role" => "user", "content" => "Tôi đã học ngành: $submitted_subject, đã làm các dự án: $submitted_projects, có kỹ năng: $submitted_skills, có kinh nghiệm thực tập: $submitted_experience, vị trí mong muốn của tôi là: $submitted_position, mục tiêu của tôi là: $submitted_goal. Bạn hãy đề xuất cho tôi công việc phù hợp với bản thân, công ty phù hợp và tỉ lệ vào được các công ty đó ra sao."];
        } else {
            // Chỉ thêm câu hỏi mới nếu nó không rỗng
            if (!empty($submitted_question)) {
                $_SESSION["chat_history_cv"][] = ["role" => "user", "content" => $submitted_question];
            } 
            // Nếu không có câu hỏi mới và không phải lần đầu tiên, không làm gì.
            // Biến $response sẽ giữ giá trị rỗng hoặc giá trị cũ.
        }
        
        // Gọi API AI chỉ khi có câu hỏi mới hoặc khi khởi tạo prompt ban đầu
        // Điều kiện: (có câu hỏi mới) HOẶC (chưa có lịch sử chat CV VÀ có subject)
        if (!empty($submitted_question) || (count($_SESSION["chat_history_cv"]) == 2 && !empty($submitted_subject))) { 
            // Gọi DeepSeek R1
            $response = callDeepSeekChatHistory($_SESSION["chat_history_cv"], "deepseek/deepseek-r1:free");
            if ($response) {
                $_SESSION["chat_history_cv"][] = ["role" => "assistant", "content" => $response];
                // AI đã phản hồi thành công, ĐẶT cờ persistence là TRUE
                $_SESSION['career_form_is_persistent'] = true; // <--- Vẫn là TRUE ở đây khi AI phản hồi thành công
            } else {
                // AI không phản hồi, cờ persistence vẫn là FALSE như đã đặt mặc định.
                $_SESSION['message'] .= "<p style='color:red; text-align:center;'>AI không phản hồi. Dữ liệu form sẽ không được giữ lại.</p>";
            }
        } else if (empty($submitted_question) && count($_SESSION["chat_history_cv"]) > 2) {
             // Nếu không có câu hỏi mới và đã có lịch sử chat, thông báo cần nhập câu hỏi để tiếp tục
            $_SESSION['message'] .= "<p style='color:orange; text-align:center;'>Vui lòng nhập câu hỏi để tiếp tục trò chuyện.</p>";
        }
        
        // Redirect sau khi xử lý "Tìm việc ngay" để tránh gửi lại form khi refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Đóng kết nối database khi mọi thứ hoàn tất
if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Career Launchpad AI</title>
    <style>
        /* CSS của bạn ở đây (đã được hợp nhất và tối ưu) */
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
        }
        label{
            font-weight: bold;
            margin-top:15px;
            margin-bottom: 15px;
            display: block; /* Đảm bảo label chiếm dòng riêng */
        }
        input[type="text"]{
            width: 100%;
            padding: 15px;
            border-radius: 9px;
            border: 1px solid #ccc;
            font-size: 15px;
            margin-bottom: 15px; /* Khoảng cách dưới mỗi input */
        }
        button{
            padding: 12px 20px;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background-color:rgb(29, 150, 55);
            margin-right: 10px; /* Khoảng cách giữa các nút */
        }
        button:hover{
            transform: scale(1.05);
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        .button-group button {
            margin-right: 10px;
        }
        .button-group a {
            margin-left: 0; /* Đặt lại margin-left cho link */
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
            width: 36px;
            height: 36px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        .input_group{
            margin-bottom: 20px;
            position: relative; /* Cực kỳ quan trọng để định vị info-icon bên trong */
        }
        .info-icon{
            display: inline-block;/* thiết lập kích thước*/ 
            width: 18px;
            height: 18px;
            border-radius: 50%;/*tạo hình tròn*/
            background-color: #4caf50; /* Màu để icon hiện rõ */
            color: white;
            text-align: center;
            line-height: 18px;/* căn giữa theo chiều dọc*/ 
            font-size: 12px;
            cursor: help;
            position: absolute;/* để định vị icon*/ 
            right: 5px;          /* Vị trí icon cách lề phải */
            top: 50%;            /* Đặt icon ở giữa theo chiều dọc của khung input */
            font-weight: bold;
            transform: translateY(-50%); /* Căn giữa chính xác icon */
        }
        .info-icon::before {
            content: 'i';
        }
        .info-icon[data-tooltip]:hover::after{
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 15px;
            transform: translateX(-50%); /* THÊM THUỘC TÍNH NÀY để căn giữa hoàn hảo */
            background-color:gray; /* THÊM MÀU NỀN cho tooltip */
            white-space: nowrap; /* THÊM THUỘC TÍNH NÀY để tooltip không xuống dòng */
            font-size: 15px;
            z-index: 20; /** đảm bảo tooltip nằm trên phần tử khác */
            margin-bottom: 5px;/* khoảng cahs giữa icon và tooltip*/
        }
    </style>
</head>
<body>
    <h2>Career Launchpad AI</h2>
    <h3>Chào mừng bạn đến với Career Launchpad AI</h3>
    <p style="text-align: center;">Chào mừng, <?php echo htmlspecialchars($current_username); ?>!</p>

    <?php echo $display_message; // Hiển thị thông báo. CHÚ Ý: Biến này chứa HTML, nên KHÔNG dùng htmlspecialchars ở đây. ?>

    <form method="post">
        <div class="input_group">
            <label for="subject">Tôi đã học ngành:</label>
            <input type="text" name="subject" id="subject" placeholder="Nhập môn bạn đã học" required
                    value="<?php echo htmlspecialchars($user_subject); ?>">
            <span class="info-icon" data-tooltip="Nhập tên ngành, chuyên ngành bạn đã học."></span>
        </div>

        <div class="input_group">
            <label for="projects">Các dự án đã làm:</label>
            <input type="text" name="projects" id="projects" placeholder="Nhập dự án của bạn" required
                    value="<?php echo htmlspecialchars($user_projects); ?>">
            <span class="info-icon" data-tooltip="Liệt kê các dự án đã hoàn thành (của cá nhân, hoặc nhóm)."></span>
        </div>

        <div class="input_group">
            <label for="skills">Tôi có các kỹ năng:</label>
            <input type="text" name="skills" id="skills" placeholder="Nhập kỹ năng cứng hoặc mềm" required
                    value="<?php echo htmlspecialchars($user_skills); ?>">
            <span class="info-icon" data-tooltip="Nhập các kỹ năng chuyên môn cứng (lập trình PHP, Python,..) hoặc mềm (ví dụ: giao tiếp, làm việc nhóm)."></span>
        </div>

        <div class="input_group">
            <label for="experience">Kinh nghiệm thực tập:</label>
            <input type="text" name="experience" id="experience" placeholder="Nhập kinh nghiệm của bạn" required
                    value="<?php echo htmlspecialchars($user_experience); ?>">
            <span class="info-icon" data-tooltip="Mô tả kinh nghiệm làm việc, thực tập, hoặc các hoạt động liên quan."></span>
        </div>

        <div class="input_group">
            <label for="position">Vị trí bạn mong muốn:</label>
            <input type="text" name="position" id="position" placeholder="Nhập vị trí mong muốn" required
                    value="<?php echo htmlspecialchars($user_position); ?>">
            <span class="info-icon" data-tooltip="Vị trí muốn ứng tuyển (VD: Lập trình PHP, Lập trình Java, Kỹ sư phần mềm)."></span>
        </div>

        <div class="input_group">
            <label for="goal">Mục tiêu hướng tới:</label>
            <input type="text" name="goal" id="goal" placeholder="Nhập mục tiêu của bạn" required
                    value="<?php echo htmlspecialchars($user_goal); ?>">
            <span class="info-icon" data-tooltip="Bạn có thể nhập mục tiêu ngắn hạn hoặc dài hạn."></span>
        </div>
        
        <div class="input_group">
            <label for="question">Câu hỏi của bạn (để tiếp tục trò chuyện):</label>
            <input type="text" name="question" id="question" placeholder="Mời bạn nhập câu hỏi">
            <span class="info-icon" data-tooltip="Đặt câu hỏi cụ thể hơn để AI tư vấn về việc làm, công ty, hoặc lộ trình phát triển."></span>
        </div>

        <div class="button-group">
            <button type="submit" name="find_job_now">Tìm việc ngay</button>
            <button type="submit" name="load_study_data" formnovalidate>Tải dữ liệu học tập của tôi</button>
            <button type="submit" name="reset_chat" formnovalidate style="background-color: #f44336;">Bắt đầu cuộc trò chuyện mới</button>
            <a href="index.php">Quay lại</a>
        </div>
        <div id="loading" style="display: none; text-align:center; margin-top: 20px;">
            <div class="spinner"></div>
            <p>Đang xử lý yêu cầu của bạn...</p>
        </div>
    </form>

    <div class="chat-container">
        <h3>Lịch sử trò chuyện với AI:</h3>
        <div class="chat-history-box">
            <?php 
            if (isset($_SESSION["chat_history_cv"]) && count($_SESSION["chat_history_cv"]) > 1) {
                // Bỏ qua tin nhắn system
                foreach ($_SESSION["chat_history_cv"] as $index => $message) {
                    if ($message["role"] !== "system") {
                        $message_class = ($message["role"] === "user") ? 'user-message' : 'assistant-message';
                        $sender_name = ($message["role"] === "user") ? 'Bạn' : 'Career Launchpad AI';
                        echo "<div class='chat-message {$message_class}'>";
                        echo "<div class='message-title'>{$sender_name}:</div>";
                        echo "<p>" . nl2br(htmlspecialchars($message["content"])) . "</p>";
                        echo "</div>";
                    }
                }
            } else {
                echo "<p style='text-align: center; color: #777;'>Nhập thông tin và nhấn 'Tìm việc ngay' để bắt đầu cuộc trò chuyện.</p>";
            }
            ?>
        </div>
    </div>

    <?php if (!empty($response)): ?>
    <div id="result">
        <h3>Phản hồi mới nhất từ AI:</h3>
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
            // Đảm bảo loading ẩn đi khi trang tải lại hoàn tất (cho trường hợp refresh)
            const loading = document.getElementById("loading");
            if (loading) {
                loading.style.display = "none";
            }
        };

        // Xử lý hiển thị loading spinner và validation trước khi submit
        document.querySelector("form").addEventListener("submit", function(e) {
            const submitButton = document.activeElement;
            const loading = document.getElementById("loading");

            // Nếu nút "Tải dữ liệu học tập của tôi" hoặc "Bắt đầu cuộc trò chuyện mới" được nhấn, không cần validation form
            if (submitButton && (submitButton.name === "load_study_data" || submitButton.name === "reset_chat")) {
                // Hiển thị loading cho các thao tác này nếu cần một chút thời gian xử lý (ví dụ: tải từ DB)
                loading.style.display = "block";
                return; 
            }

            // Kiểm tra các trường bắt buộc khi nhấn "Tìm việc ngay"
            let subject = document.querySelector("input[name='subject']").value.trim();
            let projects = document.querySelector("input[name='projects']").value.trim();
            let skills = document.querySelector("input[name='skills']").value.trim();
            let experience = document.querySelector("input[name='experience']").value.trim();
            let position = document.querySelector("input[name='position']").value.trim();
            let goal = document.querySelector("input[name='goal']").value.trim();
            // Lưu ý: trường question không bắt buộc nếu chỉ là lần submit đầu tiên để AI đưa ra gợi ý ban đầu

            if (!subject || !projects || !skills || !experience || !position || !goal) {
                alert("Vui lòng nhập đầy đủ tất cả các trường thông tin bắt buộc (trừ Câu hỏi của bạn nếu đây là lần đầu tiên).");
                e.preventDefault(); // Ngăn form submit
                loading.style.display = "none"; // Ẩn loading nếu có lỗi
            } else {
                // Nếu tất cả các trường bắt buộc đều có dữ liệu, hiển thị loading
                loading.style.display = "block";
            }
        });
    </script>
</body>
</html>