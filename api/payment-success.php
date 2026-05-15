<?php
/**
 * Test Ödeme Başarılı Sayfası (Sadece UI)
 * DİKKAT: Faz 3B ile statü güncellemesi ve PDF üretimi payment-callback.php'ye taşınmıştır.
 */

try {
    $orderNumber = $_GET['order'] ?? '';
    if (!preg_match('/^RAW-\d{8}-\d{4}$/', $orderNumber)) throw new Exception('Geçersiz sipariş.');

    $safeOrderNumber = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');

} catch (Throwable $e) {
    die("Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sipariş Başarılı | Rawlabs</title>
  <style>
    body { font-family: 'Inter', sans-serif; background: #f7fafc; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .success-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 450px; width: 100%; text-align: center; }
    h2 { color: #276749; }
    .btn { display: inline-block; background: #48bb78; color: white; padding: 14px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 20px; }
  </style>
  <script>
    if (typeof localStorage !== 'undefined') { localStorage.removeItem('rawlabs_cart'); }
  </script>
</head>
<body>
  <div class="success-box">
    <div style="font-size: 4rem;">🎉</div>
    <h2>Siparişiniz Başarıyla Alındı!</h2>
    <p>Ödeme işleminiz alındı ve kontrol ediliyor.<br><strong>Sipariş No:</strong> <?= $safeOrderNumber ?></p>
    <a href="../index.html" class="btn">Ana Sayfaya Dön</a>
  </div>
</body>
</html>
