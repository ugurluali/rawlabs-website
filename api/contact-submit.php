<?php
/**
 * Rawlabs İletişim Formu Backend Endpoint'i
 * CSRF önlemi, Honeypot, Rate Limiting ve Güvenli Validasyon barındırır.
 */
header('Content-Type: application/json; charset=utf-8');

try {
    // 1. İstek Yöntemi Kontrolü
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz istek yöntemi. Yalnızca POST isteklerine izin verilir.'
        ]);
        exit;
    }

    // 2. Sistem Yapılandırmalarını Yükle
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    } else {
        throw new Exception('Sistem yapılandırma hatası: config.php bulunamadı.');
    }

    require_once __DIR__ . '/mailer.php';

    // 3. Rate Limit Kontrolü (Session Tabanlı - 60 Saniye)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $currentTime = time();
    $rateLimitWindow = 60; // saniye cinsinden bekleme süresi

    if (isset($_SESSION['last_contact_time'])) {
        $timeDiff = $currentTime - $_SESSION['last_contact_time'];
        if ($timeDiff < $rateLimitWindow) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Lütfen tekrar denemeden önce 60 saniye bekleyin.'
            ]);
            exit;
        }
    }

    // 4. Girdi Kaynağı Çözümleme (JSON ve URL-Encoded Desteği)
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if (stripos($contentType, 'application/json') !== false) {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);
    } else {
        $input = $_POST;
    }

    if (!is_array($input)) {
        $input = [];
    }

    // 5. Bot Koruması (Honeypot) Kontrolü
    $websiteHoneypot = isset($input['website']) ? trim(strip_tags((string)$input['website'])) : '';
    if (!empty($websiteHoneypot)) {
        // Bot tespit edilirse, botu şüphelendirmemek için sessizce 200 OK (başarı) dönüyoruz.
        // E-posta gönderimi tetiklenmez.
        echo json_encode([
            'success' => true,
            'message' => 'Mesajınız başarıyla iletildi.'
        ]);
        exit;
    }

    // 6. Veri Sanitizasyonu (Girdi Temizliği)
    $name = isset($input['name']) ? trim(strip_tags((string)$input['name'])) : '';
    $email = isset($input['email']) ? trim(strip_tags((string)$input['email'])) : '';
    $phone = isset($input['phone']) ? trim(strip_tags((string)$input['phone'])) : '';
    $subject = isset($input['subject']) ? trim(strip_tags((string)$input['subject'])) : '';
    $message = isset($input['message']) ? trim(strip_tags((string)$input['message'])) : '';

    // 7. Sıkı Backend Validasyonları (Doğrulamalar)

    // Ad Soyad Kontrolü (2-100 Karakter)
    if (empty($name) || mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Lütfen geçerli bir ad soyad girin (En az 2, en fazla 100 karakter).'
        ]);
        exit;
    }

    // E-posta Kontrolü (Geçerli format)
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Lütfen geçerli bir e-posta adresi girin.'
        ]);
        exit;
    }

    // Telefon Numarası Kontrolü (7-20 Karakter, Rakam, Boşluk ve +, -, (, ) işaretleri)
    if (empty($phone) || strlen($phone) < 7 || strlen($phone) > 20 || !preg_match('/^[0-9\s\+\-\(\)]+$/', $phone)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Lütfen geçerli bir telefon numarası girin (7-20 karakter aralığında).'
        ]);
        exit;
    }

    // Konu Kontrolü (2-80 Karakter)
    if (empty($subject) || mb_strlen($subject, 'UTF-8') < 2 || mb_strlen($subject, 'UTF-8') > 80) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Lütfen geçerli bir konu başlığı seçin veya girin.'
        ]);
        exit;
    }

    // Mesaj Kontrolü (10-3000 Karakter)
    if (empty($message) || mb_strlen($message, 'UTF-8') < 10 || mb_strlen($message, 'UTF-8') > 3000) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Lütfen geçerli bir mesaj yazın (En az 10, en fazla 3000 karakter).'
        ]);
        exit;
    }

    // 8. Gönderim Verisini Hazırla
    $contactData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject,
        'message' => $message,
        'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? 'Belirtilmedi'
    ];

    // 9. Mail Gönderimini Başlat
    $mailResult = sendContactFormEmail($contactData);

    if ($mailResult === true) {
        // Son başarılı gönderim zamanını Session'a kaydet (Rate limiting için)
        $_SESSION['last_contact_time'] = time();

        echo json_encode([
            'success' => true,
            'message' => 'Mesajınız başarıyla iletildi.'
        ]);
    } else {
        // Hata durumunu PHP hata günlüğüne yaz
        error_log("Rawlabs İletişim Formu Mail Gönderim Başarısızlığı: " . (is_string($mailResult) ? $mailResult : 'Bilinmeyen hata'));
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Mesajınız iletilirken teknik bir hata oluştu. Lütfen daha sonra tekrar deneyin.'
        ]);
    }

} catch (Throwable $e) {
    // Beklenmeyen sunucu hatalarını günlüğe yaz
    error_log("Rawlabs İletişim Formu Beklenmeyen Hata: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Mesajınız iletilirken teknik bir hata oluştu. Lütfen daha sonra tekrar deneyin.'
    ]);
}
