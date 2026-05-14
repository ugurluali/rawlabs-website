<?php
/**
 * Test Ödeme Başlatma Ekranı (Mockup)
 * Gerçek sanal POS bağlamından önceki ara test ekranıdır.
 */

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    die("config.php bulunamadı.");
}

$orderNumber = $_GET['order'] ?? '';

// Sipariş Numarası Formatsal Güvenlik Doğrulaması (RAW-YYYYMMDD-XXXX)
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

// Durumu payment_started yap
$orderData['status'] = 'payment_started';
file_put_contents($orderFilePath, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$total = $orderData['summary']['grandTotal'] ?? 0;
// XSS Korunması: Ekrana veri basarken htmlspecialchars kullanılır
$safeOrderNumber = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');
$safeTotal = htmlspecialchars(number_format($total, 2, ',', '.'), ENT_QUOTES, 'UTF-8');
$safeName = htmlspecialchars($orderData['customer']['fullName'] ?? 'Müşteri', ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Ödeme | Rawlabs</title>
  <style>
    body { font-family: 'Inter', sans-serif; background: #f7fafc; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .checkout-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 400px; width: 100%; text-align: center; }
    h2 { color: #2d3748; margin-bottom: 8px; }
    .warning { background: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 24px; border: 1px solid #ffeeba; }
    .details { background: #edf2f7; padding: 16px; border-radius: 8px; margin-bottom: 24px; text-align: left; font-size: 0.95rem; color: #4a5568; }
    .btn { display: block; width: 100%; padding: 14px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; text-decoration: none; margin-bottom: 12px; font-size: 1rem; text-align: center;}
    .btn-success { background: #48bb78; color: white; }
    .btn-danger { background: #f56565; color: white; }
    .btn:hover { opacity: 0.9; }
  </style>
</head>
<body>

  <div class="checkout-box">
    <h2>Sanal POS Test Ekranı</h2>
    <div class="warning">
      <strong>⚠️ Dikkat:</strong> Bu ekran gerçek bir ödeme ekranı değildir.<br>
      Banka Sanal POS entegrasyonu tamamlanana kadar sistem akışını test etmek amacıyla oluşturulmuştur.
    </div>

    <div class="details">
      <p><strong>Sipariş No:</strong> <?= $safeOrderNumber ?></p>
      <p><strong>Müşteri:</strong> <?= $safeName ?></p>
      <p style="font-size: 1.2rem; margin-top: 12px; color: #2d3748;"><strong>Ödenecek Tutar:</strong> ₺<?= $safeTotal ?></p>
    </div>

    <!-- Test Aksiyonları -->
    <a href="payment-success.php?order=<?= $safeOrderNumber ?>" class="btn btn-success">✅ Test Ödemeyi Başarılı Yap</a>
    <a href="payment-failed.php?order=<?= $safeOrderNumber ?>" class="btn btn-danger">❌ Test Ödemeyi Başarısız Yap</a>
  </div>

</body>
</html>
