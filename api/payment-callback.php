<?php
/**
 * Ödeme Callback (Webhook) Endpoint'i
 * Faz 3B: POS sağlayıcı bağımsız güvenli callback simülasyonu.
 */

header('Content-Type: application/json; charset=utf-8');

try {
    if (file_exists(__DIR__ . '/config.php')) {
        $config = require_once __DIR__ . '/config.php';
    } else {
        throw new Exception('config.php bulunamadı.');
    }

    $storagePath = defined('ORDER_STORAGE_PATH') ? ORDER_STORAGE_PATH : (isset($config['ORDER_STORAGE_PATH']) ? $config['ORDER_STORAGE_PATH'] : null);
    if (!$storagePath) throw new Exception('ORDER_STORAGE_PATH tanımlanmamış.');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Geçersiz istek yöntemi.');
    }

    // JSON veya form-data alabilir
    $inputData = file_get_contents('php://input');
    $data = json_decode($inputData, true);

    if (!$data) {
        $data = $_POST;
    }

    $orderNumber = $data['orderId'] ?? '';
    $status = $data['status'] ?? '';
    $callbackToken = $data['callbackToken'] ?? '';
    $transactionId = $data['transactionId'] ?? 'TEST-TXN-' . time();

    if (!preg_match('/^RAW-\d{8}-\d{4}$/', $orderNumber)) {
        throw new Exception('Geçersiz sipariş numarası.');
    }

    // Güvenlik: Mock token kontrolü (Gerçek POS'ta burada imza doğrulaması yapılır)
    if ($callbackToken !== 'TEST_RAWLABS_CALLBACK_TOKEN') {
        http_response_code(403);
        throw new Exception('Callback imza doğrulaması başarısız.');
    }

    $orderFilePath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $orderNumber . '.json';
    
    if (!file_exists($orderFilePath)) {
        throw new Exception('Sipariş bulunamadı.');
    }

    // Flock ile okuma ve yazma (Race condition önlemek için)
    $fp = fopen($orderFilePath, 'r+');
    if (!$fp) {
        throw new Exception('Sipariş dosyasına ulaşılamadı.');
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new Exception('Sipariş kilitlenemedi.');
    }

    $filesize = filesize($orderFilePath);
    if ($filesize === 0) {
        flock($fp, LOCK_UN);
        fclose($fp);
        throw new Exception('Sipariş boş veya bulunamadı.');
    }

    $orderJson = fread($fp, $filesize);
    $orderData = json_decode($orderJson, true);

    if (!$orderData) {
        flock($fp, LOCK_UN);
        fclose($fp);
        throw new Exception('Sipariş verisi bozuk.');
    }

    // Idempotency: Zaten paid ise işlem yapma
    if ($orderData['status'] === 'paid' || $orderData['status'] === 'paid_test_success') {
        flock($fp, LOCK_UN);
        fclose($fp);
        echo json_encode(['success' => true, 'message' => 'Sipariş zaten ödenmiş.']);
        exit;
    }

    // Statü güncelleme
    if ($status === 'success') {
        $orderData['status'] = 'paid';
        $orderData['paymentStatus'] = 'success';
        $orderData['paidAt'] = date('c');
        $orderData['provider'] = 'test_mock';
        $orderData['providerTransactionId'] = $transactionId;

        // PDF üretimi (Ödeme onaylandığı için burada tetiklenir)
        $pdfStoragePath = __DIR__ . '/order-pdfs/';
        if (!is_dir($pdfStoragePath)) {
            @mkdir($pdfStoragePath, 0755, true);
        }
        $pdfGenFile = __DIR__ . '/pdf-generator.php';
        if (file_exists($pdfGenFile)) {
            require_once $pdfGenFile;
            // PDF hatalarını yakala ki callback patlamasın
            try {
                generateOrderPdf($orderData, $orderNumber, $pdfStoragePath);
            } catch (Exception $e) {
                error_log("PDF Üretim Hatası (Sipariş: $orderNumber): " . $e->getMessage());
            }
        }

    } elseif ($status === 'failed') {
        $orderData['status'] = 'payment_failed';
        $orderData['paymentStatus'] = 'failed';
    } else {
        flock($fp, LOCK_UN);
        fclose($fp);
        throw new Exception('Bilinmeyen callback statüsü.');
    }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    echo json_encode(['success' => true, 'message' => "Sipariş güncellendi: {$orderData['status']}"]);

} catch (Throwable $e) {
    if (http_response_code() === 200) {
        http_response_code(400);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
