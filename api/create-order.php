<?php
/**
 * Sipariş Oluşturma Endpoint'i
 * Hem define() hem return array config yapısını destekler.
 */
header('Content-Type: application/json; charset=utf-8');

try {
    // Config kontrolü
    if (file_exists(__DIR__ . '/config.php')) {
        $config = require_once __DIR__ . '/config.php';
    } else {
        throw new Exception('Sistem yapılandırma hatası: config.php bulunamadı.');
    }

    // Config değerlerini hem define hem array yapısından oku
    $storagePath = defined('ORDER_STORAGE_PATH') ? ORDER_STORAGE_PATH : (isset($config['ORDER_STORAGE_PATH']) ? $config['ORDER_STORAGE_PATH'] : null);
    $siteUrl = defined('SITE_URL') ? SITE_URL : (isset($config['SITE_URL']) ? $config['SITE_URL'] : '');

    if (!$storagePath) {
        throw new Exception('ORDER_STORAGE_PATH tanımlanmamış.');
    }

    // Güvenlik: Yalnızca POST isteklerine izin ver
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Geçersiz istek yöntemi.');
    }

    // Gelen JSON datasını oku
    $inputData = file_get_contents('php://input');
    $data = json_decode($inputData, true);

    if (!$data || !is_array($data)) {
        throw new Exception('Geçersiz JSON verisi.');
    }

    // --- Doğrulama Adımları ---
    // Müşteri verisi root'ta veya customer objesinde olabilir
    $customer = isset($data['customer']) && is_array($data['customer']) ? $data['customer'] : $data;
    
    $fullName = $customer['fullName'] ?? $customer['name'] ?? $data['fullName'] ?? $data['name'] ?? '';
    $phone = $customer['phone'] ?? $data['phone'] ?? '';
    $email = $customer['email'] ?? $data['email'] ?? '';
    $city = $customer['city'] ?? $data['city'] ?? '';
    $district = $customer['district'] ?? $data['district'] ?? '';
    $address = $customer['address'] ?? $data['address'] ?? '';
    $note = $customer['note'] ?? $data['note'] ?? '';

    if (empty(trim((string)$fullName)) || empty(trim((string)$phone)) || empty(trim((string)$email))) {
        throw new Exception('Eksik müşteri bilgileri.');
    }

    // Sepet Doğrulaması
    $items = $data['items'] ?? [];
    if (empty($items)) {
        throw new Exception('Sepetiniz boş.');
    }

    $summary = isset($data['summary']) && is_array($data['summary']) ? $data['summary'] : [];
    $totals = isset($data['totals']) && is_array($data['totals']) ? $data['totals'] : [];
    $grandTotal = $summary['grandTotal'] ?? $totals['grandTotal'] ?? $data['total'] ?? null;
    
    if ($grandTotal === null) {
        throw new Exception('Sipariş tutarı doğrulanamadı.');
    }

    // --- Sipariş No Üretimi ---
    $orderNumber = 'RAW-' . date('Ymd') . '-' . mt_rand(1000, 9999);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userId = $_SESSION['user_id'] ?? null;

    $orderDataToSave = [
        'orderId' => $orderNumber,
        'userId' => $userId,
        'status' => 'created',
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
            'currency' => $summary['currency'] ?? $totals['currency'] ?? 'TRY'
        ]
    ];

    if (!is_dir($storagePath)) {
        throw new Exception('Sipariş kayıt dizini bulunamadı.');
    }

    $orderFilePath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $orderNumber . '.json';
    if (@file_put_contents($orderFilePath, json_encode($orderDataToSave, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Sipariş dosyası yazılamadı.');
    }

    // --- Kullanıcı Sipariş İndeksini Güncelle ---
    if ($userId) {
        $userOrdersPath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . 'user-orders.json';
        $fp = @fopen($userOrdersPath, 'c+');
        if ($fp) {
            flock($fp, LOCK_EX);
            fseek($fp, 0, SEEK_END);
            $size = ftell($fp);
            $userOrders = [];
            if ($size > 0) {
                rewind($fp);
                $content = fread($fp, $size);
                $userOrders = json_decode($content, true) ?: [];
            }
            if (!isset($userOrders[$userId])) {
                $userOrders[$userId] = [];
            }
            if (!in_array($orderNumber, $userOrders[$userId])) {
                $userOrders[$userId][] = $orderNumber;
            }
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($userOrders, JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    echo json_encode([
        'success' => true,
        'orderNumber' => $orderNumber,
        'paymentUrl' => $siteUrl . '/api/payment-start.php?order=' . $orderNumber
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
