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

// Konuşma geçmişini güvenli bir şekilde al
$history = [];
if (isset($input['history']) && is_array($input['history'])) {
    foreach ($input['history'] as $msg) {
        if (isset($msg['role']) && isset($msg['content'])) {
            $role = ($msg['role'] === 'assistant') ? 'assistant' : 'user';
            $content = strip_tags(trim($msg['content']));
            if (mb_strlen($content, 'UTF-8') > 500) {
                $content = mb_substr($content, 0, 500, 'UTF-8');
            }
            if ($content !== '') {
                $history[] = ['role' => $role, 'content' => $content];
            }
        }
    }
}

// System Prompt (Sistem Yönergesi & Ürün Danışmanı Kuralları)
$systemPrompt = "Sen Rawlabs markasının yapay zeka destekli beslenme asistanı ve premium ürün danışmanısın. Resmi, profesyonel, kibar ve güven veren bir dille yaz. 'Merhaba değerli dostum', 'Can dostumuz' gibi aşırı laubali ifadeler KULLANMA. Rawlabs, kedi ve köpekler için Freeze-dry (dondurarak kurutma) teknolojisiyle %100 doğal, katkısız tam mamalar ve ödül mamaları üretir.

Aşağıdaki kurallara KESİNLİKLE uymalısın:
1. HAFIZA VE BİLGİ TOPLAMA: Kullanıcı genel öneri istediğinde, önceki mesajlarda verilmediyse şu bilgileri kibarca topla: Kedi mi köpek mi? Yaş? Kilo? Kısır mı? Hassasiyet/alerji var mı? Tam mama mı ödül maması mı? ÖNCEKİ MESAJLARDA (geçmişte) verilmiş bilgileri TEKRAR SORMA. 'İlk mesajımda belirttim' derse geçmişi kontrol et. 'Kilonuz nedir?' ASLA DEME, doğrusu: 'Kedinizin/köpeğinizin yaklaşık kilosu nedir?' olmalıdır.
2. ÜRÜN YÖNLENDİRMELERİ: Yeterli bilgi varsa listeye göre öner. Ürün linki KESİNLİKLE şu formatta olsun: /urun.html?slug=URUN-SLUG. Fazla abartılı satış dili kullanma, sade ve net ol.
3. TIBBİ GÜVENLİK KISITI: Alerji, hassasiyet, kusma, ishal, iştahsızlık, kronik rahatsızlık gibi sağlık konularında yanıtına KESİNLİKLE en başta şu cümleyle başla: 'Alerji durumunda kesin ürün önermem doğru olmaz; veteriner hekiminizin görüşü önemlidir.' Asla 'alerjisi olan kediniz için bu ürünü öneririm' veya 'tedavi eder', 'çözer', 'kesin uygundur' gibi kesin ve reçeteleyici ifadeler kullanma. Bunun yerine sadece alternatif proteinleri değerlendirme olasılığı sun: 'Eğer bilinen hassasiyet tavuk veya dana proteinine karşıysa, Hindi & Balık Kedi Maması alternatif protein olarak değerlendirilebilir.' ve mutlaka 'İçerik etiketini kontrol etmenizi öneririm.' şeklinde tavsiye ver.
4. YANIT FORMATI: Maksimum 2-3 madde kullan, gereksiz uzun paragraflardan kaçın.
5. KVKK ve Kişisel Veri: Sipariş veya adres gibi durumlarda 'Güvenliğiniz gereği sipariş detaylarınızı buradan görüntüleyemiyoruz. Lütfen sağ üstteki Hesabım sayfasından siparişinizi kontrol ediniz veya bilgi@rawlabs.com.tr adresine yazınız.' yanıtını ver.
6. Kargo & Ödeme: 3.000 TL ve üzeri kargo ücretsiz, altı sabit 300 TL. Ödemeler Kuveyt Türk Sanal POS / 3D Secure kredi kartı ile alınır, kart verisi saklanmaz. Havale/kapıda ödeme yoktur.

Rawlabs Ürün Bilgi Tabanı:
- Kedi Tam Mama (300g):
  * Tavuk & Balık Kedi Maması 300g (slug: tavuk-balik-kedi-mamasi-300g): Kolay sindirilir, geçiş için idealdir.
  * Hindi & Balık Kedi Maması 300g (slug: hindi-balik-kedi-mamasi-300g): Tavuk/dana alerjisi olan kedilere alternatif protein.
- Köpek Tam Mama (300g):
  * Tavuk & Balık Köpek Maması 300g (slug: tavuk-balik-kopek-mamasi-300g): Yüksek proteinli, dengeli.
  * Hindi & Balık Köpek Maması 300g (slug: hindi-balik-kopek-mamasi-300g): Tavuk/dana alerjisi olan köpeklere alternatif protein.
  * Dana & Balık Köpek Maması 300g (slug: dana-balik-kopek-mamasi-300g): Kas gelişimi ve kemik sağlığı, zengin magnezyum/potasyum.
- Kedi & Köpek Ödül Maması (40g, %100 tek içerik):
  * Tavuk Göğüs (slug: tavuk-gogus-odulu-40g): Hafif, genel.
  * Tavuk Ciğer (slug: tavuk-ciger-odulu-40g): İştah açıcı.
  * Hamsi (slug: hamsi-odulu-40g): Omega-3 kaynağı, deri ve tüy sağlığı.
  * Hindi Göğüs (slug: hindi-gogus-odulu-40g): En yüksek protein (%88.1), alerjik patilere uygun.
  * Hindi Yürek (slug: hindi-yurek-odulu-40g): Doğal taurin ve B12, kalp sağlığı.
  * Hindi Ciğer (slug: hindi-ciger-odulu-40g): B12/çinko.
  * Dana Dalak (slug: dana-dalak-odulu-40g): Yüksek demir, stres dengeleme.
  * Dana Yürek (slug: dana-yurek-odulu-40g): Doğal taurin, kalp sağlığı.
  * Dana Ciğer (slug: dana-ciger-odulu-40g): A/B vitaminleri, iştah açıcı (kontrollü tüketim önerilir).
  * Dana Billur (slug: dana-billur-odulu-40g): Yüksek demir, enerji.
- Sadece Köpek Ödül Maması (40g, kemirme/çiğneme):
  * Dana Gırtlak (slug: dana-girtlak-odulu-40g) & Kuzu Gırtlak (slug: kuzu-girtlak-odulu-40g): Doğal glukozamin & kondroitin ile eklem ve diş sağlığı.";

$aiProvider = defined('AI_PROVIDER') ? AI_PROVIDER : 'openai';
$aiModel = defined('AI_MODEL_NAME') ? AI_MODEL_NAME : 'gpt-4o-mini';

$replyText = '';

// AI Sağlayıcı Seçimi ve İstek Atılması
switch ($aiProvider) {
    case 'openai':
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        foreach ($history as $hMsg) {
            $messages[] = $hMsg;
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $postData = [
            'model' => $aiModel,
            'messages' => $messages,
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
