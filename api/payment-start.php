<?php
/**
 * Test Ödeme Başlatma Ekranı
 * Hata durumunda JSON veya temiz hata mesajı döner.
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
    if (!preg_match('/^RAW-\d{8}-\d{4}$/', $orderNumber)) {
        throw new Exception('Geçersiz sipariş numarası.');
    }

    $orderFilePath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $orderNumber . '.json';
    if (!file_exists($orderFilePath)) {
        throw new Exception('Sipariş dosyası bulunamadı.');
    }

    $orderData = json_decode(file_get_contents($orderFilePath), true);
    if (!$orderData) throw new Exception('Sipariş dosyası bozuk.');

    // Durumu güncelle
    $orderData['status'] = 'payment_started';
    file_put_contents($orderFilePath, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $safeOrderNumber = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');
    $safeTotal = htmlspecialchars(number_format($orderData['summary']['grandTotal'], 2, ',', '.'), ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($orderData['customer']['fullName'] ?? 'Müşteri', ENT_QUOTES, 'UTF-8');

} catch (Throwable $e) {
    die("Hata: " . $e->getMessage());
}
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
    .warning { background: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 24px; border: 1px solid #ffeeba; }
    .details { background: #edf2f7; padding: 16px; border-radius: 8px; margin-bottom: 24px; text-align: left; font-size: 0.95rem; color: #4a5568; }
    .btn { display: block; width: 100%; padding: 14px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; text-decoration: none; margin-bottom: 12px; font-size: 1rem; text-align: center;}
    .btn-success { background: #48bb78; color: white; }
    .btn-danger { background: #f56565; color: white; }
  </style>
  <script>
    async function simulateCallback(status, redirectUrl) {
      const orderId = "<?= $safeOrderNumber ?>";
      const btn = event.target;
      const originalText = btn.innerHTML;
      btn.innerHTML = "İşleniyor...";
      btn.disabled = true;

      try {
        const res = await fetch('payment-callback.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            orderId: orderId,
            status: status,
            callbackToken: 'TEST_RAWLABS_CALLBACK_TOKEN'
          })
        });
        const data = await res.json();
        if (data.success) {
          window.location.href = redirectUrl;
        } else {
          alert('Callback başarısız: ' + data.message);
          btn.innerHTML = originalText;
          btn.disabled = false;
        }
      } catch (err) {
        alert('Sunucuya ulaşılamadı.');
        btn.innerHTML = originalText;
        btn.disabled = false;
      }
    }
  </script>
</head>
<body>
  <div class="checkout-box">
    <h2>Sanal POS Test Ekranı</h2>
    <div class="warning">
      Bu ekran banka sanal POS entegrasyonu gelene kadar test amaçlıdır. Faz 3B gereği Webhook / Callback simülasyonu ile çalışır.
    </div>
    <div class="details">
      <p><strong>Sipariş No:</strong> <?= $safeOrderNumber ?></p>
      <p><strong>Müşteri:</strong> <?= $safeName ?></p>
      <p style="font-size: 1.2rem; margin-top: 12px; color: #2d3748;"><strong>Tutar:</strong> ₺<?= $safeTotal ?></p>
    </div>
    <button onclick="simulateCallback('success', 'payment-success.php?order=<?= $safeOrderNumber ?>')" class="btn btn-success">✅ Test Ödemeyi Başarılı Yap</button>
    <button onclick="simulateCallback('failed', 'payment-failed.php?order=<?= $safeOrderNumber ?>')" class="btn btn-danger">❌ Test Ödemeyi Başarısız Yap</button>
  </div>
</body>
</html>
