<?php
/**
 * Test Ödeme Başlatma Ekranı / Kuveyt Türk 3D Pay Gate
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

    $isKuveyt = defined('PAYMENT_PROVIDER') && PAYMENT_PROVIDER === 'kuveytturk';

    if ($isKuveyt && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/kuveyt-pos-helpers.php';
        $cardNumber = preg_replace('/\s+/', '', $_POST['cardNumber'] ?? '');
        $cardExpireMonth = str_pad($_POST['cardExpireMonth'] ?? '', 2, '0', STR_PAD_LEFT);
        $cardExpireYear = $_POST['cardExpireYear'] ?? '';
        if (strlen($cardExpireYear) === 4) {
            $cardExpireYear = substr($cardExpireYear, 2, 2);
        }
        $cardCvv = $_POST['cardCvv'] ?? '';
        $cardHolderName = $_POST['cardHolderName'] ?? '';
        $cardType = $_POST['cardType'] ?? 'Visa';

        $merchantId = KUVEYT_MERCHANT_ID;
        $customerId = KUVEYT_CUSTOMER_ID;
        $userName = KUVEYT_USERNAME;
        $password = KUVEYT_PASSWORD;

        $okUrl = KUVEYT_OK_URL . '?order=' . $orderNumber;
        $failUrl = KUVEYT_FAIL_URL . '?order=' . $orderNumber;
        
        $url = KUVEYT_MODE === 'live' ? KUVEYT_3D_PAY_URL_LIVE : KUVEYT_3D_PAY_URL_TEST;

        $amount = (string)(int)round((float)$orderData['summary']['grandTotal'] * 100);
        $merchantOrderId = $orderNumber;

        $hashData = kuveytHashRequest1($merchantId, $merchantOrderId, $amount, $okUrl, $failUrl, $userName, $password);

        $customerEmail = kuveytXmlEscape($orderData['customer']['email'] ?? 'bilgi@rawlabs.com.tr');
        
        $customerPhoneRaw = $orderData['customer']['phone'] ?? '';
        $customerPhoneDigits = preg_replace('/[^0-9]/', '', $customerPhoneRaw);
        if (strpos($customerPhoneDigits, '90') === 0 && strlen($customerPhoneDigits) > 10) {
            $customerPhoneDigits = substr($customerPhoneDigits, 2);
        }
        if (strpos($customerPhoneDigits, '0') === 0) {
            $customerPhoneDigits = substr($customerPhoneDigits, 1);
        }
        if (empty($customerPhoneDigits)) {
            $customerPhoneDigits = '5555555555';
        }
        $subscriber = kuveytXmlEscape(substr($customerPhoneDigits, 0, 15));
        
        $billAddrCity = kuveytXmlEscape($orderData['customer']['city'] ?? 'Istanbul');
        $billAddrLine = kuveytXmlEscape($orderData['customer']['address'] ?? 'Sipariş Adresi');
        $billAddrCountry = '792';
        $billAddrPostCode = '34000';
        $billAddrState = '34';

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // CardType formatı banka tarafından Case-Sensitive bekleniyor: Visa, MasterCard, Troy
        // "VISA" olarak değiştirmiyoruz!
        
        if (defined('KUVEYT_DEBUG') && KUVEYT_DEBUG === true) {
            error_log("Kuveyt Türk Debug [XML Request 1]: Amount=$amount, CurrencyCode=0949, TransactionType=Sale, InstallmentCount=0, CardType=$cardType");
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<KuveytTurkVPosMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <APIVersion>TDV2.0.0</APIVersion>
  <OkUrl>'.kuveytXmlEscape($okUrl).'</OkUrl>
  <FailUrl>'.kuveytXmlEscape($failUrl).'</FailUrl>
  <HashData>'.kuveytXmlEscape($hashData).'</HashData>
  <MerchantId>'.kuveytXmlEscape($merchantId).'</MerchantId>
  <CustomerId>'.kuveytXmlEscape($customerId).'</CustomerId>
  
  <DeviceData>
    <DeviceChannel>02</DeviceChannel>
    <ClientIP>'.kuveytXmlEscape($clientIp).'</ClientIP>
  </DeviceData>
  
  <CardHolderData>
    <BillAddrCity>'.$billAddrCity.'</BillAddrCity>
    <BillAddrCountry>'.$billAddrCountry.'</BillAddrCountry>
    <BillAddrLine1>'.$billAddrLine.'</BillAddrLine1>
    <BillAddrPostCode>'.$billAddrPostCode.'</BillAddrPostCode>
    <BillAddrState>'.$billAddrState.'</BillAddrState>
    <Email>'.$customerEmail.'</Email>
    <MobilePhone>
      <Cc>90</Cc>
      <Subscriber>'.$subscriber.'</Subscriber>
    </MobilePhone>
  </CardHolderData>
  
  <UserName>'.kuveytXmlEscape($userName).'</UserName>
  <CardNumber>'.kuveytXmlEscape($cardNumber).'</CardNumber>
  <CardExpireDateYear>'.kuveytXmlEscape($cardExpireYear).'</CardExpireDateYear>
  <CardExpireDateMonth>'.kuveytXmlEscape($cardExpireMonth).'</CardExpireDateMonth>
  <CardCVV2>'.kuveytXmlEscape($cardCvv).'</CardCVV2>
  <CardHolderName>'.kuveytXmlEscape($cardHolderName).'</CardHolderName>
  <CardType>'.kuveytXmlEscape($cardType).'</CardType>
  <TransactionType>Sale</TransactionType>
  <InstallmentCount>0</InstallmentCount>
  <Amount>'.$amount.'</Amount>
  <DisplayAmount>'.$amount.'</DisplayAmount>
  <CurrencyCode>0949</CurrencyCode>
  <MerchantOrderId>'.kuveytXmlEscape($merchantOrderId).'</MerchantOrderId>
  <TransactionSecurity>3</TransactionSecurity>
</KuveytTurkVPosMessage>';

        try {
            $htmlResponse = kuveytPostXml($url, $xml);
            echo $htmlResponse;
            exit;
        } catch (Exception $e) {
            die("Banka sunucusuna bağlanırken hata: " . $e->getMessage());
        }
    }

} catch (Throwable $e) {
    die("Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ödeme Yap | Rawlabs</title>
  <style>
    body { font-family: 'Inter', sans-serif; background: #f7fafc; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .checkout-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 450px; width: 100%; text-align: center; }
    .warning { background: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 24px; border: 1px solid #ffeeba; }
    .details { background: #edf2f7; padding: 16px; border-radius: 8px; margin-bottom: 24px; text-align: left; font-size: 0.95rem; color: #4a5568; }
    .btn { display: block; width: 100%; padding: 14px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; text-decoration: none; margin-bottom: 12px; font-size: 1rem; text-align: center;}
    .btn-success { background: #48bb78; color: white; }
    .btn-danger { background: #f56565; color: white; }
    .form-group { text-align: left; margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 0.9rem; font-weight: 600; color: #4a5568; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 1rem; box-sizing: border-box;}
    .row { display: flex; gap: 15px; }
    .col { flex: 1; }
  </style>
  <?php if (!$isKuveyt): ?>
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
  <?php endif; ?>
</head>
<body>
  <div class="checkout-box">
    <?php if ($isKuveyt): ?>
      <h2>Güvenli Ödeme Ekranı</h2>
      <div class="details">
        <p><strong>Sipariş No:</strong> <?= $safeOrderNumber ?></p>
        <p style="font-size: 1.2rem; margin-top: 12px; color: #2d3748;"><strong>Ödenecek Tutar:</strong> ₺<?= $safeTotal ?></p>
      </div>
      <form method="POST" action="">
        <div class="form-group">
          <label>Kart Sahibi Adı Soyadı</label>
          <input type="text" name="cardHolderName" class="form-control" required placeholder="Ad Soyad">
        </div>
        <div class="form-group">
          <label>Kart Numarası</label>
          <input type="text" name="cardNumber" class="form-control" required placeholder="Örn: 4000123456789010" maxlength="19">
        </div>
        <div class="row">
          <div class="col form-group">
            <label>Son Kull. Ay (AA)</label>
            <input type="text" name="cardExpireMonth" class="form-control" required placeholder="01" maxlength="2">
          </div>
          <div class="col form-group">
            <label>Son Kull. Yıl (YY)</label>
            <input type="text" name="cardExpireYear" class="form-control" required placeholder="25" maxlength="2">
          </div>
        </div>
        <div class="row">
          <div class="col form-group">
            <label>CVV</label>
            <input type="text" name="cardCvv" class="form-control" required placeholder="123" maxlength="4">
          </div>
          <div class="col form-group">
            <label>Kart Tipi</label>
            <select name="cardType" class="form-control">
              <option value="Visa">Visa</option>
              <option value="MasterCard">MasterCard</option>
              <option value="Troy">Troy</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-success" style="margin-top: 10px;">💳 3D Secure ile Öde</button>
      </form>
    <?php else: ?>
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
    <?php endif; ?>
  </div>
</body>
</html>
