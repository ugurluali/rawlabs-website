<?php
/**
 * E-posta Gönderme Modülü (Faz 1 & 2)
 * PHPMailer ile SMTP üzerinden Admin ve Müşteri sipariş bildirimlerini gönderir.
 */

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Merkezi Mail Gönderim Fonksiyonu
 * @param array $orderData Sipariş verisi
 * @return array Mail gönderim sonuç statüleri (mailStatus)
 */
function sendOrderSuccessEmails($orderData) {
    $mailStatus = [
        'adminSent' => $orderData['mailStatus']['adminSent'] ?? false,
        'customerSent' => $orderData['mailStatus']['customerSent'] ?? false,
        'lastAttemptAt' => date('c'),
        'error' => null
    ];

    $hasError = false;

    // 1. Admin Maili Gönderimi
    if (!$mailStatus['adminSent']) {
        try {
            $adminResult = sendOrderSuccessAdminMail($orderData);
            if ($adminResult === true) {
                $mailStatus['adminSent'] = true;
            } else {
                $hasError = true;
            }
        } catch (\Throwable $e) {
            $hasError = true;
            error_log("Rawlabs sendOrderSuccessEmails Admin Mail Beklenmedik Hata (Sipariş: {$orderData['orderId']}): " . $e->getMessage());
        }
    }

    // 2. Müşteri Maili Gönderimi
    if (!$mailStatus['customerSent']) {
        try {
            $customerResult = sendOrderSuccessCustomerMail($orderData);
            if ($customerResult === true) {
                $mailStatus['customerSent'] = true;
            } else {
                $hasError = true;
            }
        } catch (\Throwable $e) {
            $hasError = true;
            error_log("Rawlabs sendOrderSuccessEmails Müşteri Mail Beklenmedik Hata (Sipariş: {$orderData['orderId']}): " . $e->getMessage());
        }
    }

    // Eğer herhangi bir adımda hata oluştuysa genel hata mesajı set edilir
    if ($hasError) {
        $mailStatus['error'] = "Mail gönderimi başarısız oldu";
    }

    return $mailStatus;
}

/**
 * Özel PHPMailer objesi oluşturur.
 */
function createMailer() {
    $mail = new PHPMailer(true);
    
    // Config'de tanımlı değilse fallback değerler kullan (Önlem)
    $host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
    $port = defined('SMTP_PORT') ? SMTP_PORT : 25;
    $user = defined('SMTP_USER') ? SMTP_USER : '';
    $pass = defined('SMTP_PASS') ? SMTP_PASS : '';
    $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@rawlabs.com.tr';
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Rawlabs';

    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = !empty($user);
    $mail->Username   = $user;
    $mail->Password   = $pass;
    if ($port == 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($port == 587) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->Port       = $port;
    $mail->CharSet    = 'UTF-8';
    
    // Timeout makul seviyede tutulur (Kuveyt Türk callback süresi için optimize edilmiştir)
    $mail->Timeout    = 15;

    $mail->setFrom($fromEmail, $fromName);
    return $mail;
}

/**
 * Admin'e (Satıcı) sipariş bildirim maili gönderir.
 */
function sendOrderSuccessAdminMail($orderData) {
    try {
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'info@rawlabs.com.tr');
        
        $mail = createMailer();
        $mail->addAddress($adminEmail);
        $mail->Subject = 'Yeni Sipariş: ' . $orderData['orderId'];

        // Sipariş kalemi formatlama
        $itemsHtml = '';
        foreach ($orderData['items'] as $item) {
            $safeName = htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $safeQty = (int)($item['quantity'] ?? 0);
            $safeTotal = number_format((float)($item['lineTotal'] ?? 0), 2, ',', '.');
            $itemsHtml .= "<li>{$safeQty}x {$safeName} (₺{$safeTotal})</li>";
        }

        $customer = $orderData['customer'] ?? [];
        $summary = $orderData['summary'] ?? [];

        $html = "<h2>Yeni Sipariş Alındı!</h2>";
        $html .= "<p><strong>Sipariş No:</strong> {$orderData['orderId']}</p>";
        $html .= "<p><strong>Tarih:</strong> {$orderData['paidAt']}</p>";
        
        $html .= "<h3>Müşteri Bilgileri</h3>";
        $html .= "<p><strong>Ad Soyad:</strong> " . htmlspecialchars($customer['fullName'] ?? '') . "</p>";
        $html .= "<p><strong>Telefon:</strong> " . htmlspecialchars($customer['phone'] ?? '') . "</p>";
        $html .= "<p><strong>E-posta:</strong> " . htmlspecialchars($customer['email'] ?? '') . "</p>";
        $html .= "<p><strong>Adres:</strong> " . htmlspecialchars($customer['address'] ?? '') . " " . htmlspecialchars($customer['district'] ?? '') . "/" . htmlspecialchars($customer['city'] ?? '') . "</p>";
        
        $html .= "<h3>Ürünler</h3>";
        $html .= "<ul>$itemsHtml</ul>";
        
        $html .= "<h3>Ödeme Detayları</h3>";
        $html .= "<p><strong>Toplam Tutar:</strong> ₺" . number_format($summary['grandTotal'] ?? 0, 2, ',', '.') . "</p>";
        $html .= "<p><strong>Ödeme Durumu:</strong> Başarılı ({$orderData['provider']})</p>";
        
        // Sadece başarılı işlem referanslarını logluyoruz (KVKK/PCI-DSS güvenli veriler)
        if (!empty($orderData['providerTransactionId'])) {
            $html .= "<p><strong>İşlem Referansı:</strong> {$orderData['providerTransactionId']}</p>";
            $html .= "<p><strong>Provizyon No:</strong> " . ($orderData['provisionNumber'] ?? '-') . "</p>";
            $html .= "<p><strong>RRN:</strong> " . ($orderData['rrn'] ?? '-') . "</p>";
            $html .= "<p><strong>STAN:</strong> " . ($orderData['stan'] ?? '-') . "</p>";
        }

        $mail->isHTML(true);
        $mail->Body = $html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMsg = isset($mail) && $mail instanceof PHPMailer ? $mail->ErrorInfo : '';
        error_log("Rawlabs SMTP Admin Mail Hatası (Sipariş: {$orderData['orderId']}): " . $e->getMessage() . ($errorMsg ? " | PHPMailer Hata: " . $errorMsg : ""));
        return "Mail gönderimi başarısız oldu";
    } catch (\Throwable $e) {
        $errorMsg = isset($mail) && $mail instanceof PHPMailer ? $mail->ErrorInfo : '';
        error_log("Rawlabs SMTP Admin Mail Hatası (Sipariş: {$orderData['orderId']}): " . $e->getMessage() . ($errorMsg ? " | PHPMailer Hata: " . $errorMsg : ""));
        return "Mail gönderimi başarısız oldu";
    }
}

/**
 * Müşteriye onay maili gönderir.
 */
function sendOrderSuccessCustomerMail($orderData) {
    try {
        $customerEmail = $orderData['customer']['email'] ?? '';
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return "Geçersiz veya boş müşteri e-postası.";
        }

        $mail = createMailer();
        $mail->addAddress($customerEmail, $orderData['customer']['fullName'] ?? 'Müşteri');
        $mail->Subject = 'Siparişiniz Başarıyla Alındı - ' . $orderData['orderId'];

        $itemsHtml = '';
        foreach ($orderData['items'] as $item) {
            $safeName = htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $safeQty = (int)($item['quantity'] ?? 0);
            $safeTotal = number_format((float)($item['lineTotal'] ?? 0), 2, ',', '.');
            $itemsHtml .= "<li>{$safeQty}x {$safeName} - ₺{$safeTotal}</li>";
        }

        $summary = $orderData['summary'] ?? [];
        $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rawlabs.com.tr';

        $html = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>";
        $html .= "<h2 style='color: #2c3e50;'>Teşekkürler! Siparişinizi Aldık.</h2>";
        $html .= "<p>Sayın <strong>" . htmlspecialchars($orderData['customer']['fullName'] ?? 'Müşterimiz') . "</strong>,</p>";
        $html .= "<p><strong>{$orderData['orderId']}</strong> numaralı siparişinizin ödemesi başarıyla gerçekleşti. Siparişiniz hazırlanmaya başlanmıştır.</p>";
        
        $html .= "<div style='background-color: #f9f9f9; padding: 15px; border-radius: 6px; margin: 20px 0;'>";
        $html .= "<h3 style='margin-top: 0; color: #34495e;'>Sipariş Özeti</h3>";
        $html .= "<ul style='padding-left: 20px;'>$itemsHtml</ul>";
        $html .= "<hr style='border: 0; border-top: 1px solid #ddd;'>";
        $html .= "<p style='font-size: 16px;'><strong>Toplam Tutar: ₺" . number_format($summary['grandTotal'] ?? 0, 2, ',', '.') . "</strong></p>";
        $html .= "</div>";

        $html .= "<h3 style='color: #34495e;'>Teslimat Adresi</h3>";
        $html .= "<p>" . htmlspecialchars($orderData['customer']['address'] ?? '') . "<br>";
        $html .= htmlspecialchars($orderData['customer']['district'] ?? '') . " / " . htmlspecialchars($orderData['customer']['city'] ?? '') . "</p>";
        
        $html .= "<p style='margin-top: 30px; color: #7f8c8d; font-size: 14px;'>Bizi tercih ettiğiniz için teşekkür ederiz.<br><strong>Rawlabs Ekibi</strong><br><a href='{$siteUrl}' style='color: #3498db; text-decoration: none;'>{$siteUrl}</a></p>";
        $html .= "</div>";

        $mail->isHTML(true);
        $mail->Body = $html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMsg = isset($mail) && $mail instanceof PHPMailer ? $mail->ErrorInfo : '';
        error_log("Rawlabs SMTP Müşteri Mail Hatası (Sipariş: {$orderData['orderId']}): " . $e->getMessage() . ($errorMsg ? " | PHPMailer Hata: " . $errorMsg : ""));
        return "Mail gönderimi başarısız oldu";
    } catch (\Throwable $e) {
        $errorMsg = isset($mail) && $mail instanceof PHPMailer ? $mail->ErrorInfo : '';
        error_log("Rawlabs SMTP Müşteri Mail Hatası (Sipariş: {$orderData['orderId']}): " . $e->getMessage() . ($errorMsg ? " | PHPMailer Hata: " . $errorMsg : ""));
        return "Mail gönderimi başarısız oldu";
    }
}

/**
 * Müşteriye kargo takip/bilgilendirme maili gönderir.
 */
function sendShippingNotificationEmail($orderData) {
    try {
        $customerEmail = $orderData['customer']['email'] ?? '';
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return "Geçersiz veya boş müşteri e-postası.";
        }

        $mail = createMailer();
        $mail->addAddress($customerEmail, $orderData['customer']['fullName'] ?? 'Müşteri');
        
        $orderId = $orderData['orderId'] ?? 'RAW-';
        $mail->Subject = 'Rawlabs Siparişiniz Kargoya Verildi - ' . $orderId;

        $customerName = htmlspecialchars($orderData['customer']['fullName'] ?? 'Müşterimiz', ENT_QUOTES, 'UTF-8');
        $cargoCompany = htmlspecialchars($orderData['cargoCompany'] ?? '', ENT_QUOTES, 'UTF-8');
        $trackingNumber = htmlspecialchars($orderData['trackingNumber'] ?? '', ENT_QUOTES, 'UTF-8');
        $trackingUrl = htmlspecialchars($orderData['trackingUrl'] ?? '', ENT_QUOTES, 'UTF-8');
        $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rawlabs.com.tr';

        $html = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>";
        $html .= "<h2 style='color: #2c3e50;'>Harika Haber! Siparişiniz Kargoya Verildi.</h2>";
        $html .= "<p>Sayın <strong>" . $customerName . "</strong>,</p>";
        $html .= "<p><strong>" . htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') . "</strong> numaralı siparişiniz kargo firmasına teslim edilmiştir.</p>";
        
        $html .= "<div style='background-color: #f9f9f9; padding: 15px; border-radius: 6px; margin: 20px 0;'>";
        $html .= "<h3 style='margin-top: 0; color: #34495e;'>Kargo Bilgileri</h3>";
        $html .= "<p><strong>Kargo Firması:</strong> " . $cargoCompany . "</p>";
        $html .= "<p><strong>Takip Numarası:</strong> " . $trackingNumber . "</p>";

        if (!empty($trackingUrl) && filter_var($trackingUrl, FILTER_VALIDATE_URL)) {
            $html .= "<p style='margin-top: 15px;'>";
            $html .= "<a href='" . $trackingUrl . "' target='_blank' style='display: inline-block; background-color: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Kargo Takip Sayfası</a>";
            $html .= "</p>";
        }
        $html .= "</div>";

        $html .= "<h3 style='color: #34495e;'>Rawlabs Destek</h3>";
        $html .= "<p>Siparişinizle ilgili herhangi bir sorunuz olması durumunda bizimle <a href='mailto:bilgi@rawlabs.com.tr' style='color: #3498db; text-decoration: none;'>bilgi@rawlabs.com.tr</a> adresinden veya <a href='tel:+905324206635' style='color: #3498db; text-decoration: none;'>0532 420 66 35</a> numaralı telefonumuzdan iletişime geçebilirsiniz.</p>";
        
        $html .= "<p style='margin-top: 30px; color: #7f8c8d; font-size: 14px;'>Bizi tercih ettiğiniz için teşekkür ederiz.<br><strong>Rawlabs Ekibi</strong><br><a href='{$siteUrl}' style='color: #3498db; text-decoration: none;'>{$siteUrl}</a></p>";
        $html .= "</div>";

        $mail->isHTML(true);
        $mail->Body = $html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMsg = isset($mail) && $mail instanceof PHPMailer ? $mail->ErrorInfo : '';
        error_log("Rawlabs SMTP Kargo Bildirim Mail Hatası (Sipariş: {$orderData['orderId']}): " . $e->getMessage() . ($errorMsg ? " | PHPMailer Hata: " . $errorMsg : ""));
        return "Kargo maili gönderimi başarısız oldu";
    } catch (\Throwable $e) {
        $errorMsg = isset($mail) && $mail instanceof PHPMailer ? $mail->ErrorInfo : '';
        error_log("Rawlabs SMTP Kargo Bildirim Mail Hatası (Sipariş: {$orderData['orderId']}): " . $e->getMessage() . ($errorMsg ? " | PHPMailer Hata: " . $errorMsg : ""));
        return "Kargo maili gönderimi başarısız oldu";
    }
}
