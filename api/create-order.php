<?php
/**
 * Sipariş Oluşturma Endpoint'i
 * Frontend'den POST edilen JSON'u doğrular, backend sipariş numarasını üretir ve kaydeder.
 */
header('Content-Type: application/json; charset=utf-8');

try {
    // Config kontrolü
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    } else {
        throw new Exception('Sistem yapılandırma hatası: config.php bulunamadı. Lütfen cPanel üzerinden oluşturun.');
    }

    if (!defined('ORDER_STORAGE_PATH')) {
        throw new Exception('ORDER_STORAGE_PATH tanımlanmamış. config.php dosyanızı kontrol edin.');
    }

    // Güvenlik: Yalnızca POST isteklerine izin ver
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Geçersiz istek yöntemi.');
    }

    // Gelen JSON datasını oku ve boyut sınırını (10KB) kontrol et
    $inputData = file_get_contents('php://input');
    if (strlen($inputData) > 10240) {
        throw new Exception('İstek boyutu çok büyük.');
    }

    $data = json_decode($inputData, true);

    if (!$data || !is_array($data)) {
        throw new Exception('Geçersiz JSON verisi.');
    }

    // --- Doğrulama Adımları ---
    $missingFields = [];
    
    // Müşteri verisi root'ta veya customer objesinde olabilir
    $customer = isset($data['customer']) && is_array($data['customer']) ? $data['customer'] : $data;
    
    $fullName = $customer['fullName'] ?? $customer['name'] ?? $data['fullName'] ?? $data['name'] ?? '';
    $phone = $customer['phone'] ?? $data['phone'] ?? '';
    $email = $customer['email'] ?? $data['email'] ?? '';
    $city = $customer['city'] ?? $data['city'] ?? '';
    $district = $customer['district'] ?? $data['district'] ?? '';
    $address = $customer['address'] ?? $data['address'] ?? '';
    $note = $customer['note'] ?? $data['note'] ?? '';

    if (empty(trim((string)$fullName))) $missingFields[] = 'Ad Soyad';
    if (empty(trim((string)$phone))) $missingFields[] = 'Telefon';
    if (empty(trim((string)$email))) $missingFields[] = 'E-posta';
    if (empty(trim((string)$city))) $missingFields[] = 'İl';
    if (empty(trim((string)$district))) $missingFields[] = 'İlçe';
    if (empty(trim((string)$address))) $missingFields[] = 'Açık Adres';

    if (!empty($missingFields)) {
        throw new Exception('Eksik teslimat bilgileri: ' . implode(', ', $missingFields));
    }

    // E-posta Doğrulaması (filter_var)
    if (!filter_var(trim((string)$email), FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Geçersiz e-posta adresi.');
    }

    // Sepet Doğrulaması
    $items = $data['items'] ?? [];
    if (empty($items) || !is_array($items)) {
        throw new Exception('Sepetiniz boş.');
    }

    /** 
     * TODO: TUTAR GÜVENLİĞİ
     * Gerçek ödeme entegrasyonu öncesi toplam tutar backend tarafında ürün fiyat listesi (veritabanı/dosya) üzerinden yeniden hesaplanmalıdır.
     * Şimdilik frontend'den gelen tutarı kabul ediyoruz.
     */
    $summary = isset($data['summary']) && is_array($data['summary']) ? $data['summary'] : [];
    $totals = isset($data['totals']) && is_array($data['totals']) ? $data['totals'] : [];
    
    $grandTotal = $summary['grandTotal'] ?? $totals['grandTotal'] ?? $data['total'] ?? null;
    
    if ($grandTotal === null || !is_numeric($grandTotal)) {
        throw new Exception('Sipariş toplam tutarı doğrulanamadı.');
    }

    // --- Sipariş No Üretimi (Backend tarafında güvenli) ---
    // Format: RAW-YYYYMMDD-XXXX
    $orderNumber = 'RAW-' . date('Ymd') . '-' . mt_rand(1000, 9999);

    // Sipariş Payload'ını Güncelle (kaydedilecek veri)
    $orderDataToSave = [
        'orderId' => $orderNumber,
        'status' => 'created', // created, payment_started, paid_test_success, payment_test_failed, paid, failed, cancelled
        'backend_createdAt' => date('c'),
        'customer' => [
            'fullName' => trim((string)$fullName),
            'phone' => trim((string)$phone),
            'email' => trim((string)$email),
            'city' => trim((string)$city),
            'district' => trim((string)$district),
            'address' => trim((string)$address),
            'note' => trim((string)$note)
        ],
        'items' => $items,
        'summary' => [
            'grandTotal' => (float)$grandTotal,
            // Opsiyonel diğer alanları da ekleyebilirsiniz
            'subtotal' => $summary['subtotal'] ?? $totals['subtotal'] ?? null,
            'discount' => $summary['discount'] ?? $totals['discount'] ?? null,
            'shippingFee' => $summary['shippingFee'] ?? $totals['shippingFee'] ?? null,
            'currency' => $summary['currency'] ?? $totals['currency'] ?? 'TRY'
        ]
    ];

    // Dizin var mı kontrol et, yoksa hata at
    if (!is_dir(ORDER_STORAGE_PATH)) {
        throw new Exception('Sipariş kayıt dizini bulunamadı: ' . ORDER_STORAGE_PATH);
    }

    // JSON Kaydetme
    $orderFilePath = ORDER_STORAGE_PATH . $orderNumber . '.json';
    if (@file_put_contents($orderFilePath, json_encode($orderDataToSave, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Sipariş dosyası oluşturulamadı. (Yazma izni eksik olabilir: ' . ORDER_STORAGE_PATH . ')');
    }

    // Başarılı Yanıt Döndür
    echo json_encode([
        'success' => true,
        'orderNumber' => $orderNumber,
        'paymentUrl' => (defined('SITE_URL') ? SITE_URL : '') . '/api/payment-start.php?order=' . $orderNumber,
        'message' => 'Sipariş başarıyla taslak olarak oluşturuldu.'
    ]);

} catch (Exception $e) {
    // PHP fatal error ve Exception yakalama
    http_response_code(400); // Bad Request veya uygun hata kodu
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    // PHP 7+ Error (Fatal hataları yakalar)
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sistem Hatası: ' . $e->getMessage()
    ]);
}

