<?php
function callDeepSeekChatHistory($chat_history, $model ) {
    $api_key = "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";
    $url = "https://openrouter.ai/api/v1/chat/completions";
    
    // cấu trúc gửi tới AI 
    $data = [
        "model" => $model,
        "messages" => $chat_history,
        "temperature" => 0.7,
        "stream"=> false
    ];
    // thiết lập headers để xác thực API key và định dạng Json
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key"
    ];
   //khởi tạo cấu hình CURL để gửi post request tới API của deepseek
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);
    // thực hiện gửi và nhận kết quả
    $result = curl_exec($ch);

    curl_close($ch);
    //Phân tích phản hồi và nếu hợp lệ trả về câu trả lời nếu ko báo lỗi
    $response = json_decode($result, true);
 if (is_array($response) && isset($response["choices"][0]["message"]["content"])) {
    return $response["choices"][0]["message"]["content"];
}
   return "Không thể kết nối tới AI hoặc lỗi";

}
?>
