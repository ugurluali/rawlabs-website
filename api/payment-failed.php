<?php
/**
 * Test Ödeme Başarısız Sayfası
 */

try {
    if (file_exists(__DIR__ . '/config.php')) {
        $config = require_once __DIR__ . '/config.php';
    } else {
        throw new Exception('config.php bulunamadı.');
    }

    $storagePath = defined('ORDER_STORAGE_PATH') ? ORDER_STORAGE_PATH : (isset($config['ORDER_STORAGE_PATH']) ? $config['ORDER_STORAGE_PATH'] : null);
    if (!$storagePath) throw new Exception('ORDER_STORAGE_PATH tanımlanmamış.');

    $orderNumber = $_GET['order'] ?? '';
    if (!preg_match('/^RAW-\d{8}-\d{4}$/', $orderNumber)) throw new Exception('Geçersiz sipariş.');

    $orderFilePath = $storagePath . $orderNumber . '.json';
    if (!file_exists($orderFilePath)) throw new Exception('Sipariş bulunamadı.');

    $orderData = json_decode(file_get_contents($orderFilePath), true);
    $orderData['status'] = 'payment_test_failed';
    file_put_contents($orderFilePath, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $safeOrderNumber = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');

} catch (Throwable $e) {
    die("Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Ödeme Başarısız | Rawlabs</title>
  <style>
    body { font-family: 'Inter', sans-serif; background: #f7fafc; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .failed-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 450px; width: 100%; text-align: center; }
    h2 { color: #c53030; }
    .btn { display: inline-block; background: #e2e8f0; color: #2d3748; padding: 14px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 5px; }
    .btn-primary { background: #3182ce; color: white; }
  </style>
</head>
<body>
  <div class="failed-box">
    <div style="font-size: 4rem;">⚠️</div>
    <h2>Ödeme Başarısız Oldu</h2>
    <p>İşleminiz test akışında reddedildi.<br><strong>Sipariş No:</strong> <?= $safeOrderNumber ?></p>
    <div>
      <a href="../sepet.html" class="btn btn-primary">Sepete Dön</a>
      <a href="../magaza.html" class="btn">Mağazaya Dön</a>
    </div>
  </div>
</body>
</html>
