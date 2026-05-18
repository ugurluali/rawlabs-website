<?php
/**
 * Rawlabs - Bizim Hesap Entegrasyon Helper Modülü (Faz 8B)
 * Bu dosya doğrudan çağrılarak çalıştırılamaz. Sadece addBizimHesapInvoice fonksiyonunu tanımlar.
 */

if (basename($_SERVER['SCRIPT_FILENAME']) === 'bizimhesap-helper.php') {
    http_response_code(403);
    exit('Doğrudan erişim engellenmiştir.');
}

/**
 * Sipariş verisini Bizim Hesap Satış Faturası Taslağı olarak aktarır.
 * 
 * @param array $orderData Sipariş JSON verisi
 * @return array Aktarım sonuç özeti
 */
function addBizimHesapInvoice(array $orderData): array {
    try {
        // 1. Config Yükleme ve Güvenlik Kontrolleri
        $configPath = dirname(__DIR__) . '/api/config.php';
        if (!file_exists($configPath)) {
            $configPath = dirname(__FILE__) . '/config.php'; // Fallback
        }

        $config = [];
        if (file_exists($configPath)) {
            $config = include $configPath;
        }

        $firmId = defined('BIZIMHESAP_FIRM_ID') ? BIZIMHESAP_FIRM_ID : (isset($config['BIZIMHESAP_FIRM_ID']) ? $config['BIZIMHESAP_FIRM_ID'] : null);
        $token = defined('BIZIMHESAP_TOKEN') ? BIZIMHESAP_TOKEN : (isset($config['BIZIMHESAP_TOKEN']) ? $config['BIZIMHESAP_TOKEN'] : null);

        if (empty($firmId)) {
            error_log("Rawlabs Bizim Hesap Error: BIZIMHESAP_FIRM_ID tanımlı değil.");
            return [
                'success' => false,
                'error' => 'Sistem yapılandırma hatası: API kimlik bilgileri (FIRM ID) eksik.'
            ];
        }

        // 2. Temel Alan Kontrolleri
        if (empty($orderData['orderId']) || empty($orderData['customer']) || empty($orderData['items'])) {
            return [
                'success' => false,
                'error' => 'Geçersiz sipariş verisi: Gerekli alanlar eksik.'
            ];
        }

        $customer = $orderData['customer'];
        $billing = isset($customer['billing']) && is_array($customer['billing']) ? $customer['billing'] : [];

        // 3. Fatura Tarihi ve Vadesi Belirleme
        // paidAt varsa veya backend_createdAt
        $invoiceDateRaw = $orderData['paidAt'] ?? $orderData['backend_createdAt'] ?? date('c');
        $invoiceDate = date('Y-m-d H:i:s', strtotime($invoiceDateRaw));
        $dueDate = $invoiceDate; // E-ticaret anında tahsilat olduğundan vade tarihi aynıdır.

        // 4. Müşteri Fatura Bilgilerini Hazırlama
        $billingType = $billing['type'] ?? 'individual';
        $title = '';
        $taxNo = '';
        $taxOffice = '';

        if ($billingType === 'corporate') {
            $title = $billing['companyTitle'] ?? '';
            $taxNo = $billing['vkn'] ?? '';
            $taxOffice = $billing['taxOffice'] ?? '';
        } else {
            $title = $billing['fullName'] ?? $customer['fullName'] ?? '';
            $taxNo = $billing['tckn'] ?? '11111111111';
            $taxOffice = '';
        }

        // Müşteri İletişim & Adres Fallback'leri
        $address = $billing['address'] ?? $customer['address'] ?? '';
        $district = $billing['district'] ?? $customer['district'] ?? '';
        $city = $billing['city'] ?? $customer['city'] ?? '';
        $email = $billing['email'] ?? $customer['email'] ?? '';
        $phone = $billing['phone'] ?? $customer['phone'] ?? '';

        if (empty($title) || empty($address)) {
            return [
                'success' => false,
                'error' => 'Fatura kesmek için müşteri unvanı/adı ve fatura adresi zorunludur.'
            ];
        }

        // 5. Ürün Kalemlerini Oluşturma
        $details = [];
        $grossTotal = 0;

        foreach ($orderData['items'] as $item) {
            $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
            if ($qty <= 0) $qty = 1;

            $lineTotal = isset($item['lineTotal']) ? (float)$item['lineTotal'] : 0.0;
            
            // Yuvarlama ve KDV Ayrıştırma Matematik Kuralları (%10 KDV Mama Ürünleri)
            $total = round($lineTotal, 2);
            $net = round($total / 1.10, 2);
            $tax = round($total - $net, 2);
            
            // Birim fiyatı hassas kuruş hesabı için 4 hane olarak saklanır
            $unitPrice = round($net / $qty, 4);

            $details[] = [
                'ProductId' => $item['slug'] ?? 'urun',
                'ProductName' => $item['name'] ?? 'Rawlabs Ürün',
                'TaxRate' => 10.0,
                'Quantity' => (float)$qty,
                'UnitPrice' => $unitPrice,
                'Discount' => '0.00',
                'Net' => $net,
                'Tax' => $tax,
                'Total' => $total
            ];

            $grossTotal += $total;
        }

        // 6. Kargo Hizmet Bedelini Kalem Olarak Ekleme (%20 KDV)
        $shippingFee = isset($orderData['summary']['shippingFee']) ? (float)$orderData['summary']['shippingFee'] : 0.0;
        if ($shippingFee > 0.01) {
            $shippingTotal = round($shippingFee, 2);
            $shippingNet = round($shippingTotal / 1.20, 2);
            $shippingTax = round($shippingTotal - $shippingNet, 2);

            $details[] = [
                'ProductId' => 'kargo',
                'ProductName' => 'Kargo Ücreti',
                'TaxRate' => 20.0,
                'Quantity' => 1.0,
                'UnitPrice' => $shippingNet,
                'Discount' => '0.00',
                'Net' => $shippingNet,
                'Tax' => $shippingTax,
                'Total' => $shippingTotal
            ];

            $grossTotal += $shippingTotal;
        }

        // 7. API Payload Yapılandırması
        $payload = [
            'FirmID' => (string)$firmId,
            'firmId' => (string)$firmId,
            'InvoiceType' => '3', // 3: Satış Faturası
            'InvoiceDate' => $invoiceDate,
            'DueDate' => $dueDate,
            'Note' => 'Rawlabs Siparişi: ' . $orderData['orderId'],
            'customers' => [
                'CustomerId' => $orderData['orderId'],
                'Title' => $title,
                'Address' => $address,
                'TaxOffice' => $taxOffice,
                'TaxNo' => $taxNo,
                'Email' => $email,
                'Phone' => $phone
            ],
            'Details' => $details,
            'Amounts' => [
                'Currency' => 'TL',
                'Gross' => round($grossTotal, 2)
            ]
        ];

        // 8. cURL İle Güvenli POST İsteği
        $ch = curl_init('https://bizimhesap.com/api/b2b/addinvoice');
        if ($ch === false) {
            throw new Exception('cURL oturumu başlatılamadı.');
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Key: ' . $firmId
        ];
        if (!empty($token)) {
            $headers[] = 'Token: ' . $token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // 9. API Yanıt İşleme ve Maskeleme
        if ($response === false) {
            error_log("Rawlabs Bizim Hesap Error: cURL Hatası - " . $curlError);
            return [
                'success' => false,
                'error' => 'Bizim Hesap API sunucusuna bağlanılamadı.'
            ];
        }

        if ($httpStatus !== 200) {
            error_log("Rawlabs Bizim Hesap Error: HTTP Durum Kodu $httpStatus. Yanıt: " . strip_tags($response));
            return [
                'success' => false,
                'error' => 'Bizim Hesap API sunucusu hata döndürdü.'
            ];
        }

        $resData = json_decode($response, true);
        
        // Yanıtın başarım durumunu kontrol et
        $isSuccess = false;
        $invoiceId = null;

        if (is_array($resData)) {
            // Bizim Hesap genellikle durum kodunu veya eklenen belge id'sini döner
            if (isset($resData['success']) && $resData['success'] == true) {
                $isSuccess = true;
            } elseif (isset($resData['status']) && ($resData['status'] == 'success' || $resData['status'] == '1' || $resData['status'] == 1)) {
                $isSuccess = true;
            } elseif (isset($resData['invoiceId']) || isset($resData['InvoiceId']) || isset($resData['id'])) {
                // Eğer ID döndüyse başarılı kabul edebiliriz
                $isSuccess = true;
            }

            // ID'yi yakala
            $invoiceId = $resData['invoiceId'] ?? $resData['InvoiceId'] ?? $resData['id'] ?? $resData['ID'] ?? null;
            if (!$invoiceId && isset($resData['result']) && is_array($resData['result'])) {
                $invoiceId = $resData['result']['invoiceId'] ?? $resData['result']['id'] ?? null;
            }
        }

        if ($isSuccess) {
            return [
                'success' => true,
                'invoiceId' => $invoiceId ? (string)$invoiceId : 'TASLAK',
                'raw' => 'Sipariş Bizim Hesap\'a başarıyla aktarıldı.'
            ];
        } else {
            error_log("Rawlabs Bizim Hesap API Hata Yanıtı: " . json_encode($resData, JSON_UNESCAPED_UNICODE));
            return [
                'success' => false,
                'error' => 'Bizim Hesap faturayı oluşturamadı. API hata döndü.'
            ];
        }

    } catch (Throwable $e) {
        error_log("Rawlabs Bizim Hesap İstisna Hatası: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        return [
            'success' => false,
            'error' => 'Bizim Hesap aktarımında teknik bir istisna oluştu.'
        ];
    }
}
