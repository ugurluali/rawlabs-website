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

    $isKuveyt = defined('PAYMENT_PROVIDER') && PAYMENT_PROVIDER === 'kuveytturk';

    // --- KUVEYT TÜRK 3D SECURE (REQUEST 2 / PROVISION) AKIŞI ---
    if ($isKuveyt && isset($_POST['AuthenticationResponse'])) {
        require_once __DIR__ . '/kuveyt-pos-helpers.php';
        
        $authResponse = $_POST['AuthenticationResponse'] ?? '';
        
        // Ham veri güvenli analizi
        kuveytSafeDebugStringAnalysis($authResponse, 'Ham AuthenticationResponse');
        
        $xmlString = kuveytNormalizeAuthenticationResponse($authResponse);
        
        // Normalize sonrası veri güvenli analizi
        kuveytSafeDebugStringAnalysis($xmlString, 'Normalize Sonrası XML');

        $response = kuveytParseXml($xmlString);

        if (!$response) {
            error_log("Kuveyt Türk Debug: simplexml parse başarısız.");
            // Parse başarısızsa kullanıcıyı beyaz ekranda bırakma
            $merchantOrderId = $_GET['order'] ?? '';
            
            // Eğer URL'de order yoksa, ham yanıtın içinden MerchantOrderId bulmaya çalış
            if (empty($merchantOrderId) && preg_match('/<MerchantOrderId>([^<]+)<\/MerchantOrderId>/i', $xmlString, $matches)) {
                $merchantOrderId = trim($matches[1]);
            }
            
            if (preg_match('/^RAW-\d{8}-\d{4}$/', $merchantOrderId)) {
                $orderFilePath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $merchantOrderId . '.json';
                if (file_exists($orderFilePath)) {
                    $fp = fopen($orderFilePath, 'r+');
                    if ($fp && flock($fp, LOCK_EX)) {
                        $filesize = filesize($orderFilePath);
                        if ($filesize > 0) {
                            $orderData = json_decode(fread($fp, $filesize), true);
                            if (isset($orderData['status']) && $orderData['status'] !== 'paid') {
                                $orderData['status'] = 'payment_failed';
                                $orderData['paymentStatus'] = 'failed';
                                $orderData['failReason'] = 'Banka yanıtı okunamadı (Parse Hatası)';
                                ftruncate($fp, 0);
                                rewind($fp);
                                fwrite($fp, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                                fflush($fp);
                            }
                        }
                        flock($fp, LOCK_UN);
                        fclose($fp);
                    }
                }
                header("Location: payment-failed.php?order=" . urlencode($merchantOrderId));
                exit;
            }
            
            die('Geçersiz banka yanıtı. İşlem doğrulanamadı.');
        }

        error_log("Kuveyt Türk Debug: simplexml parse başarılı.");

        $responseCode = $response['ResponseCode'] ?? '';
        $responseMessage = $response['ResponseMessage'] ?? '';
        $merchantOrderId = $response['MerchantOrderId'] ?? ($response['VPosMessage']['MerchantOrderId'] ?? '');
        $md = $response['MD'] ?? '';
        $orderIdFromBank = $response['OrderId'] ?? ($response['VPosMessage']['OrderId'] ?? '');
        $hashDataFromBank = $response['HashData'] ?? '';
        $bankHashPassword = $response['VPosMessage']['HashPassword'] ?? null;

        // 2. Base64 '+' karakter normalizasyonu
        if (strpos($hashDataFromBank, ' ') !== false) {
            error_log("Kuveyt Türk Debug: HashData içinde boşluk var, '+' ile değiştiriliyor.");
        }
        $hashDataFromBank = str_replace(' ', '+', trim($hashDataFromBank));
        
        if ($bankHashPassword !== null) {
            $bankHashPassword = str_replace(' ', '+', trim($bankHashPassword));
            error_log("Kuveyt Türk Debug: Bank HashPassword bulundu ve normalize edildi.");
        }
        
        $md = str_replace(' ', '+', trim($md));

        // 3. Güvenli Debug Logları
        error_log("Kuveyt Türk Debug: MerchantOrderId: " . ($merchantOrderId ? 'Var' : 'Yok') . ", MD: " . ($md ? 'Var' : 'Yok') . ", HashData: " . ($hashDataFromBank ? 'Var' : 'Yok') . ", ResponseCode: " . $responseCode);

        if (!preg_match('/^RAW-\d{8}-\d{4}$/', $merchantOrderId)) {
            die('Geçersiz sipariş formatı.');
        }

        // Debug için genel log
        if (!empty($bankHashPassword)) {
            error_log("Response1 hash source: bank HashPassword");
        } else {
            error_log("Response1 hash source: local password hash fallback");
        }

        $orderFilePath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $merchantOrderId . '.json';
        if (!file_exists($orderFilePath)) die('Sipariş bulunamadı.');

        $fp = fopen($orderFilePath, 'r+');
        if (!$fp || !flock($fp, LOCK_EX)) die('Siparişe erişilemiyor.');

        $filesize = filesize($orderFilePath);
        $orderData = json_decode(fread($fp, $filesize), true);

        // Idempotency: Zaten paid ise işlem yapma, başarıya yönlendir
        if ($orderData['status'] === 'paid' || $orderData['status'] === 'paid_test_success') {
            flock($fp, LOCK_UN);
            fclose($fp);
            header("Location: payment-success.php?order=" . urlencode($merchantOrderId));
            exit;
        }

        // HASH KONTROLÜ ÖNCESİ EKSİK VERİ KONTROLÜ (Transaction Response Contract)
        if (empty($hashDataFromBank) && empty($md) && empty($bankHashPassword)) {
            error_log("Kuveyt Türk Debug: Hash doğrulaması atlandı, HashData/MD yok; banka transaction response contract döndü.");
            
            $orderData['status'] = 'payment_failed';
            $orderData['paymentStatus'] = 'failed';
            $orderData['failReason'] = 'Banka işlem yanıtı başarısız';
            $orderData['bankResponseCode'] = $responseCode;
            $orderData['bankResponseMessage'] = $responseMessage;

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            header("Location: payment-failed.php?order=" . urlencode($merchantOrderId));
            exit;
        }

        // Güvenlik: Bankadan gelen yanıtın gerçekten bankadan geldiğini doğrula (Response 1 Hash kontrolü)
        if (!kuveytVerifyResponse1Hash($merchantOrderId, $responseCode, $orderIdFromBank, $hashDataFromBank, KUVEYT_PASSWORD, $bankHashPassword)) {
            error_log("Kuveyt Türk Güvenlik Uyarısı: Sipariş $merchantOrderId için banka imza doğrulaması (Hash) başarısız oldu.");
            
            // Kullanıcıyı çirkin bir hata sayfasında bırakmamak için payment_failed yap ve yönlendir
            $orderData['status'] = 'payment_failed';
            $orderData['paymentStatus'] = 'failed';
            $orderData['failReason'] = 'Banka yanıt doğrulaması başarısız';

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            header("Location: payment-failed.php?order=" . urlencode($merchantOrderId));
            exit;
        }

        if ($responseCode === '00') {
            // 3D Doğrulama Başarılı -> Şimdi ProvisionGate (Request 2)
            $amount = round((float)$orderData['summary']['grandTotal'] * 100);
            $merchantId = KUVEYT_MERCHANT_ID;
            $customerId = KUVEYT_CUSTOMER_ID;
            $userName = KUVEYT_USERNAME;
            $password = KUVEYT_PASSWORD;

            $hashData = kuveytHashRequest2($merchantId, $merchantOrderId, $amount, $userName, $password);

            $provXml = '<?xml version="1.0" encoding="UTF-8"?>
<KuveytTurkVPosMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <APIVersion>TDV2.0.0</APIVersion>
  <HashData>'.kuveytXmlEscape($hashData).'</HashData>
  <MerchantId>'.kuveytXmlEscape($merchantId).'</MerchantId>
  <CustomerId>'.kuveytXmlEscape($customerId).'</CustomerId>
  <UserName>'.kuveytXmlEscape($userName).'</UserName>
  <TransactionType>Sale</TransactionType>
  <InstallmentCount>0</InstallmentCount>
  <Amount>'.$amount.'</Amount>
  <MerchantOrderId>'.kuveytXmlEscape($merchantOrderId).'</MerchantOrderId>
  <TransactionSecurity>3</TransactionSecurity>
  <KuveytTurkVPosAdditionalData>
    <AdditionalData>
      <Key>MD</Key>
      <Data>'.kuveytXmlEscape($md).'</Data>
    </AdditionalData>
  </KuveytTurkVPosAdditionalData>
</KuveytTurkVPosMessage>';

            $provUrl = KUVEYT_MODE === 'live' ? KUVEYT_3D_PROVISION_URL_LIVE : KUVEYT_3D_PROVISION_URL_TEST;

            try {
                $provResponseXml = kuveytPostXml($provUrl, $provXml);
                $provResult = kuveytParseXml($provResponseXml);

                if (isset($provResult['ResponseCode']) && $provResult['ResponseCode'] === '00') {
                    // Provision başarılı, sipariş onaylandı
                    $orderData['status'] = 'paid';
                    $orderData['paymentStatus'] = 'success';
                    $orderData['paidAt'] = date('c');
                    $orderData['provider'] = 'kuveytturk';
                    $orderData['providerTransactionId'] = $orderIdFromBank;
                    $orderData['provisionNumber'] = $provResult['ProvisionNumber'] ?? '';
                    $orderData['rrn'] = $provResult['RRN'] ?? '';
                    $orderData['stan'] = $provResult['Stan'] ?? '';
                    $orderData['businessKey'] = $provResult['BusinessKey'] ?? '';

                    // PDF Üretimi
                    $pdfStoragePath = __DIR__ . '/order-pdfs/';
                    if (!is_dir($pdfStoragePath)) @mkdir($pdfStoragePath, 0755, true);
                    $pdfGenFile = __DIR__ . '/pdf-generator.php';
                    if (file_exists($pdfGenFile)) {
                        require_once $pdfGenFile;
                        try { generateOrderPdf($orderData, $merchantOrderId, $pdfStoragePath); } catch (Exception $e) {}
                    }

                    ftruncate($fp, 0);
                    rewind($fp);
                    fwrite($fp, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    fflush($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);

                    header("Location: payment-success.php?order=" . urlencode($merchantOrderId));
                    exit;
                } else {
                    $orderData['status'] = 'payment_failed';
                    $orderData['paymentStatus'] = 'failed';
                    $orderData['failReason'] = $provResult['ResponseMessage'] ?? 'Provizyon reddedildi.';
                }
            } catch (Exception $e) {
                $orderData['status'] = 'payment_failed';
                $orderData['paymentStatus'] = 'failed';
                $orderData['failReason'] = 'Banka bağlantı hatası: ' . $e->getMessage();
            }
        } else {
            // 3D Doğrulama Başarısız
            $orderData['status'] = 'payment_failed';
            $orderData['paymentStatus'] = 'failed';
            $orderData['failReason'] = $responseMessage;
        }

        // Başarısız durumda kaydet ve yönlendir
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        header("Location: payment-failed.php?order=" . urlencode($merchantOrderId));
        exit;
    }

    // --- MOCK TEST AKIŞI (Faz 3B'den kalan) ---
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
