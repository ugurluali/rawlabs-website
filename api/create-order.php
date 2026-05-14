<?php
/**
 * Sipariş Oluşturma Endpoint'i
 * Frontend'den POST edilen JSON'u doğrular, backend sipariş numarasını üretir ve kaydeder.
 */
header('Content-Type: application/json; charset=utf-8');

// Config kontrolü
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // Config yoksa hata dön
    echo json_encode(['success' => false, 'message' => 'Sistem yapılandırma hatası: config.php bulunamadı. Lütfen cPanel üzerinden oluşturun.']);
    exit;
}

// Güvenlik: Yalnızca POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

// Gelen JSON datasını oku ve boyut sınırını (10KB) kontrol et
$inputData = file_get_contents('php://input');
if (strlen($inputData) > 10240) {
    echo json_encode(['success' => false, 'message' => 'İstek boyutu çok büyük.']);
    exit;
}

$data = json_decode($inputData, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz JSON verisi.']);
    exit;
}

// --- Doğrulama Adımları ---
$missingFields = [];
$customer = $data['customer'] ?? [];

if (empty(trim($customer['fullName'] ?? ''))) $missingFields[] = 'Ad Soyad';
if (empty(trim($customer['phone'] ?? ''))) $missingFields[] = 'Telefon';
if (empty(trim($customer['email'] ?? ''))) $missingFields[] = 'E-posta';
if (empty(trim($customer['city'] ?? ''))) $missingFields[] = 'İl';
if (empty(trim($customer['district'] ?? ''))) $missingFields[] = 'İlçe';
if (empty(trim($customer['address'] ?? ''))) $missingFields[] = 'Açık Adres';

if (!empty($missingFields)) {
    echo json_encode(['success' => false, 'message' => 'Eksik teslimat bilgileri: ' . implode(', ', $missingFields)]);
    exit;
}

// E-posta Doğrulaması (filter_var)
if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz e-posta adresi.']);
    exit;
}

// Sepet Doğrulaması
$items = $data['items'] ?? [];
if (empty($items) || !is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Sepetiniz boş.']);
    exit;
}

/** 
 * TODO: TUTAR GÜVENLİĞİ
 * Gerçek ödeme entegrasyonu öncesi toplam tutar backend tarafında ürün fiyat listesi (veritabanı/dosya) üzerinden yeniden hesaplanmalıdır.
 * Şimdilik frontend'den gelen tutarı kabul ediyoruz.
 */
$summary = $data['summary'] ?? [];
if (!isset($summary['grandTotal']) || !is_numeric($summary['grandTotal'])) {
    echo json_encode(['success' => false, 'message' => 'Sipariş toplam tutarı doğrulanamadı.']);
    exit;
}

// --- Sipariş No Üretimi (Backend tarafında güvenli) ---
// Format: RAW-YYYYMMDD-XXXX
$orderNumber = 'RAW-' . date('Ymd') . '-' . mt_rand(1000, 9999);

// Sipariş Payload'ını Güncelle
$data['orderId'] = $orderNumber;
$data['status'] = 'created'; // created, payment_started, paid_test_success, payment_test_failed, paid, failed, cancelled
$data['backend_createdAt'] = date('c');

// JSON Kaydetme
$orderFilePath = ORDER_STORAGE_PATH . $orderNumber . '.json';
if (file_put_contents($orderFilePath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
    echo json_encode(['success' => false, 'message' => 'Sipariş dosyası oluşturulamadı (Dosya/Klasör izinlerini kontrol edin).']);
    exit;
}

// Başarılı Yanıt Döndür
// Gerçek banka entegrasyonu olmadığından şimdilik payment-start.php'ye yönlendiriyoruz.
echo json_encode([
    'success' => true,
    'orderId' => $orderNumber,
    'paymentUrl' => 'api/payment-start.php?order=' . $orderNumber,
    'message' => 'Sipariş başarıyla taslak olarak oluşturuldu.'
]);
