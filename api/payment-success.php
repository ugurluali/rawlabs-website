<?php
/**
 * Test Ödeme Başarılı Akışı
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

// Durumu paid_test_success yap
$orderData['status'] = 'paid_test_success'; // Gerçekte: 'paid' olacak
file_put_contents($orderFilePath, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$safeOrderNumber = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');

/**
 * TODO: E-POSTA VE PDF ENTEGRASYONU
 * Başarılı ödeme sonrasında burada mailer.php kullanılarak:
 * 1. Müşteriye fatura / bilgilendirme maili atılacak.
 * 2. Rawlabs yönetimine sipariş onay / PDF dökümü iletilecek.
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sipariş Başarılı | Rawlabs</title>
  <style>
    body { font-family: 'Inter', sans-serif; background: #f7fafc; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .success-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 450px; width: 100%; text-align: center; }
    .icon { font-size: 4rem; margin-bottom: 16px; }
    h2 { color: #276749; margin-bottom: 12px; }
    p { color: #4a5568; line-height: 1.6; margin-bottom: 24px; }
    .btn { display: inline-block; background: #48bb78; color: white; padding: 14px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; }
    .btn:hover { opacity: 0.9; }
    .note { margin-top: 24px; font-size: 0.85rem; color: #a0aec0; border-top: 1px solid #e2e8f0; padding-top: 16px; }
  </style>
  <script>
    // Başarılı sipariş sonrası frontend sepetini sıfırlama (opsiyonel ancak önerilir)
    if (typeof localStorage !== 'undefined') {
        localStorage.removeItem('rawlabs_cart');
    }
  </script>
</head>
<body>
  <div class="success-box">
    <div class="icon">🎉</div>
    <h2>Siparişiniz Başarıyla Alındı!</h2>
    <p>
      Ödemeniz test akışında başarılı olarak işaretlendi.<br>
      <strong>Sipariş Numaranız:</strong> <?= $safeOrderNumber ?>
    </p>
    <a href="../index.html" class="btn">Ana Sayfaya Dön</a>
    <div class="note">
      Sistem notu: E-posta bildirimi ve PDF dökümü entegrasyonu ilerleyen aşamada bu adıma eklenecektir.
    </div>
  </div>
</body>
</html>
