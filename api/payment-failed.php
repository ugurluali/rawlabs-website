<?php
/**
 * Test Ödeme Başarısız Akışı
 */

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    die("config.php bulunamadı.");
}

$orderNumber = $_GET['order'] ?? '';

if (!preg_match('/^RAW-\d{8}-\d{4}$/', $orderNumber)) {
    die("Geçersiz sipariş numarası formatı.");
}

$orderFilePath = ORDER_STORAGE_PATH . $orderNumber . '.json';
if (!file_exists($orderFilePath)) {
    die("Sipariş dosyası bulunamadı.");
}

$orderData = json_decode(file_get_contents($orderFilePath), true);
if (!$orderData) {
    die("Sipariş dosyası bozuk.");
}

// Durumu payment_test_failed yap
$orderData['status'] = 'payment_test_failed'; // Gerçekte: 'failed' olacak
file_put_contents($orderFilePath, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$safeOrderNumber = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ödeme Başarısız | Rawlabs</title>
  <style>
    body { font-family: 'Inter', sans-serif; background: #f7fafc; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .failed-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 450px; width: 100%; text-align: center; }
    .icon { font-size: 4rem; margin-bottom: 16px; }
    h2 { color: #c53030; margin-bottom: 12px; }
    p { color: #4a5568; line-height: 1.6; margin-bottom: 24px; }
    .btn { display: inline-block; background: #e2e8f0; color: #2d3748; padding: 14px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 5px; }
    .btn-retry { background: #3182ce; color: white; }
    .btn:hover { opacity: 0.9; }
  </style>
</head>
<body>
  <div class="failed-box">
    <div class="icon">⚠️</div>
    <h2>Ödeme Başarısız Oldu</h2>
    <p>
      İşleminiz onaylanamadı (Test Red). Lütfen bilgilerinizi kontrol edip tekrar deneyin.<br>
      <strong>Sipariş Numaranız:</strong> <?= $safeOrderNumber ?>
    </p>
    <div>
      <a href="../sepet.html" class="btn">Sepete Dön</a>
      <a href="payment-start.php?order=<?= $safeOrderNumber ?>" class="btn btn-retry">Tekrar Dene</a>
    </div>
  </div>
</body>
</html>
