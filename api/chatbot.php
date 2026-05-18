<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Hataları ekrana basmayı kapat, sadece logla (Güvenlik)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// JSON yanıtı döndürme yardımcı fonksiyonu
function sendResponse($status, $reply = '', $error = '') {
    echo json_encode([
        'status' => $status,
        'reply' => $reply,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Sadece POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', '', 'Geçersiz istek yöntemi.');
}

// Rate Limiting (Hız Sınırı) - 60 saniyede maksimum 5 istek
$limit = 5;
$timeWindow = 60;
$currentTime = time();

if (!isset($_SESSION['chat_rate_limit'])) {
    $_SESSION['chat_rate_limit'] = [];
}
// Zaman penceresi dışındaki kayıtları temizle
$_SESSION['chat_rate_limit'] = array_filter($_SESSION['chat_rate_limit'], function($timestamp) use ($currentTime, $timeWindow) {
    return ($currentTime - $timestamp) < $timeWindow;
});

if (count($_SESSION['chat_rate_limit']) >= $limit) {
    sendResponse('error', '', 'Çok fazla istek gönderdiniz. Lütfen biraz bekleyip tekrar deneyiniz.');
}
// İstek damgasını kaydet
$_SESSION['chat_rate_limit'][] = $currentTime;

// Config yükleme
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    // Kurulum hatası: Güvenli hata dön (Loglara yansıt)
    error_log('Chatbot Hatası: config.php bulunamadı.');
    sendResponse('error', '', 'Sistem şu an hizmet veremiyor. Lütfen daha sonra tekrar deneyin.');
}
require_once $configFile;

if (!defined('AI_API_KEY') || empty(AI_API_KEY)) {
    error_log('Chatbot Hatası: AI_API_KEY yapılandırılmamış.');
    sendResponse('error', '', 'Sistem şu an hizmet veremiyor. Lütfen daha sonra tekrar deneyin.');
}

// İstek içeriğini al ve güvenli hale getir
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['message']) || trim($input['message']) === '') {
    sendResponse('error', '', 'Lütfen bir mesaj yazın.');
}

// XSS koruması ve limit (Max 500 karakter)
$userMessage = strip_tags(trim($input['message']));
if (mb_strlen($userMessage, 'UTF-8') > 500) {
    $userMessage = mb_substr($userMessage, 0, 500, 'UTF-8');
}

// System Prompt (Sistem Yönergesi & Güvenlik Kuralları)
$systemPrompt = "Sen Rawlabs markasının yapay zeka destekli beslenme asistanı ve premium müşteri temsilcisisin. Lüks, samimi, kibar ve hayvansever bir marka dilin var. Rawlabs, kedi ve köpekler için Freeze-dry (dondurarak kurutma) teknolojisiyle %100 doğal, katkısız tam mamalar ve ödül mamaları üretir.
Aşağıdaki kurallara KESİNLİKLE uymalısın:
1. KVKK ve Kişisel Veri: Kullanıcı sipariş durumu, kargo takibi, adres, telefon veya ödeme bilgisi talep ederse HİÇBİR İŞLEM YAPMA. Kullanıcıya şu yanıtı ver: 'Güvenliğiniz gereği sipariş detaylarınızı buradan görüntüleyemiyorum. Lütfen sağ üstteki Hesabım sayfasından siparişinizi kontrol ediniz veya bilgi@rawlabs.com.tr adresine yazınız.'
2. Veteriner Kısıtı: Sen bir veteriner hekim değilsin. Hastalık teşhisi, tedavisi veya kesin sağlık iddialarında bulunamazsın. Ciddi durumlarda mutlaka 'Lütfen en kısa sürede bir veteriner hekime danışınız' şeklinde uyar.
3. Kargo: 3.000 TL ve üzeri siparişlerde kargo ücretsizdir. 3.000 TL altı siparişlerde sabit 300 TL kargo ücreti uygulanır. Teslimat süresi 1-3 iş günüdür.
4. Ödeme: Güvenli 3D Secure kredi/banka kartı kabul edilir. Havale veya kapıda ödeme seçeneğimiz yoktur.
5. İade: Açılmamış ve tekrar satılabilir ürünlerde yasal iade süreci vardır.
6. Yanıt Formatı: Yanıtların her zaman kısa, net, e-ticaret dönüşümüne yardımcı (kullanıcıyı ilgili ürüne yönlendiren) ve düz metin (veya temel HTML) olmalıdır. 
7. Sınırlandırma: Yalnızca Rawlabs, evcil hayvan beslenmesi ve kedi/köpek mamaları hakkında konuş. Diğer konularda kibarca reddet.";

$aiProvider = defined('AI_PROVIDER') ? AI_PROVIDER : 'openai';
$aiModel = defined('AI_MODEL_NAME') ? AI_MODEL_NAME : 'gpt-4o-mini';

$replyText = '';

// AI Sağlayıcı Seçimi ve İstek Atılması
switch ($aiProvider) {
    case 'openai':
        $url = 'https://api.openai.com/v1/chat/completions';
        $postData = [
            'model' => $aiModel,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'max_tokens' => 300,
            'temperature' => 0.7
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . AI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Maksimum 15 saniye bekle

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            if (isset($responseData['choices'][0]['message']['content'])) {
                $replyText = trim($responseData['choices'][0]['message']['content']);
            } else {
                error_log("Chatbot OpenAI Yanıt Parse Hatası: " . $response);
                $debugSuffix = (defined('AI_DEBUG') && AI_DEBUG) ? ' [DEBUG: response_parse_error]' : '';
                sendResponse('error', '', 'Üzgünüm, şu an bağlantı kuramıyorum. Lütfen daha sonra tekrar deneyiniz.' . $debugSuffix);
            }
        } else {
            error_log("Chatbot OpenAI API Hatası: HTTP $httpCode - " . $response . " cURL Err: " . $curlError);
            $debugSuffix = (defined('AI_DEBUG') && AI_DEBUG) ? " [DEBUG: provider_error_HTTP_$httpCode]" : '';
            sendResponse('error', '', 'Üzgünüm, asistanımız şu an hizmet veremiyor. Lütfen iletişim sayfasını kullanınız.' . $debugSuffix);
        }
        break;

    case 'gemini':
    case 'claude':
        // Gelecekte eklenebilecek sağlayıcılar için altyapı
        error_log("Chatbot Hatası: Seçili provider henüz implemente edilmedi ($aiProvider)");
        sendResponse('error', '', 'Geçici olarak sistem bakımı yapılmaktadır.');
        break;

    default:
        error_log("Chatbot Hatası: Geçersiz provider yapılandırması.");
        sendResponse('error', '', 'Sistem şu an hizmet veremiyor.');
        break;
}

// Her şey başarılıysa yanıtı dön
sendResponse('success', $replyText);
