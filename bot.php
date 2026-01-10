<?php

$API_KEY = '8122167214:AAHyYd8JjPNs3AhZTCqoqxCIq5xC1QVlxX0'; // توكن تليجرام
$GEMINI_KEY = getenv('AIngJJb-9FRsCJ0nsOREZc');
 // مفتاح Gemini API

// 2. دالة إرسال الرسائل لتليجرام
function bot($method, $datas=[]){
    global $API_KEY;
    $url = "https://api.telegram.org/bot".$API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    return json_decode($res);
}

// 3. دالة الاتصال بذكاء Gemini الاصطناعي
function askGemini($message) {
    global $GEMINI_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=".$GEMINI_KEY;
    $data = [
        "contents" => [
            ["parts" => [["text" => $message]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // لتجنب مشاكل الأمان في السيرفر
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $exec = curl_exec($ch);
    $response = json_decode($exec, true);
    curl_close($ch);

    // التحقق من وجود النص في الرد المستلم من جوجل
    if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        return $response['candidates'][0]['content']['parts'][0]['text'];
    } else {
        return "الرد الخام من جوجل هو: " . $exec;

    }
}

// 4. استقبال التحديثات من تليجرام
$update = json_decode(file_get_contents('php://input'));

if(isset($update->message)){
    $message = $update->message;
    $text = $message->text;
    $chat_id = $message->chat->id;
    $name = $message->from->first_name;

    // حالة الأمر الترحيبي /start
    if($text == "/start"){
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "أهلاً بك يا $name! أنا بوتك الذكي المطور من قبل سمبدي الكمالي و المتصل بـ Gemini. 🤖\nأرسل لي أي سؤال وسأحاول الإجابة عليه.",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "قناتنا 📢", 'url' => 'https://t.me/dev_osamh']],
                    [['text' => "المطور 👨‍💻", 'url' => 'https://t.me/dev_osamh']]
                ]
            ])
        ]);
    } 
    // الرد على بقية الرسائل باستخدام الذكاء الاصطناعي
    else {
        // إظهار حالة "جاري الكتابة" ليعرف المستخدم أن البوت يفكر ✍️
        bot('sendChatAction', [
            'chat_id' => $chat_id,
            'action' => 'typing'
        ]);

        $ai_response = askGemini($text);

        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $ai_response
        ]);
    }
}








