<?php
/**
 * Test Ödeme Başarısız Sayfası (Sadece UI)
 * Faz 3B: Statü güncelleme yetkisi payment-callback.php'ye taşınmıştır.
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

    // Güvenli dosya yolu
    $orderFilePath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $orderNumber . '.json';
    if (!file_exists($orderFilePath)) throw new Exception('Sipariş bulunamadı.');

    $orderData = json_decode(file_get_contents($orderFilePath), true);
    
    $safeOrderNumber = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');
    $safeCustomer = htmlspecialchars($orderData['customer']['fullName'] ?? 'Müşteri', ENT_QUOTES, 'UTF-8');
    $safeTotal = htmlspecialchars(number_format($orderData['summary']['grandTotal'] ?? 0, 2, ',', '.') . ' ' . ($orderData['summary']['currency'] ?? 'TRY'), ENT_QUOTES, 'UTF-8');

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
    h2 { color: #c53030; margin-bottom: 20px; }
    .details { background: #fff5f5; padding: 15px; border-radius: 8px; text-align: left; margin: 20px 0; font-size: 0.9rem; color: #742a2a; border: 1px solid #fed7d7; }
    .btn { display: inline-block; background: #e2e8f0; color: #2d3748; padding: 14px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 5px; font-size: 0.95rem; }
    .btn-primary { background: #e53e3e; color: white; }
  </style>
</head>
<body>
  <div class="failed-box">
    <div style="font-size: 4rem;">⚠️</div>
    <h2>Ödeme Başarısız</h2>
    <p style="color: #4a5568; line-height: 1.6;">Ödeme işlemi tamamlanamadı.<br>Dilerseniz sepetinize dönerek tekrar deneyebilirsiniz.</p>
    
    <div class="details">
      <div><strong>Sipariş No:</strong> <?= $safeOrderNumber ?></div>
      <div><strong>Müşteri:</strong> <?= $safeCustomer ?></div>
      <div><strong>Tutar:</strong> <?= $safeTotal ?></div>
      <div><strong>Durum:</strong> Ödeme Başarısız</div>
    </div>

    <div style="margin-top: 30px;">
      <a href="../sepet.html" class="btn btn-primary">Sepete Dön</a>
      <a href="../index.html" class="btn">Ana Sayfaya Dön</a>
    </div>
  </div>
</body>
</html>
