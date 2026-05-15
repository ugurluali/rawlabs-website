<?php
/**
 * Config Skeleton
 * ÖNEMLİ: Gerçek config.php dosyası bu örnek baz alınarak sadece sunucuda (cPanel) oluşturulmalıdır.
 * Bu dosya repoya eklenen güvenli örnek şablondur. Gerçek config.php repo içinde asla bulunmamalıdır!
 */

// Sistem ayarları
define('SITE_URL', 'https://rawlabs.com.tr');
define('ORDER_STORAGE_PATH', __DIR__ . '/orders/');

// E-posta SMTP ayarları (TODO)
define('SMTP_HOST', 'mail.rawlabs.com.tr');
define('SMTP_PORT', 465);
define('SMTP_USER', 'bilgi@rawlabs.com.tr');
define('SMTP_PASS', 'SMTP_PASSWORD_HERE');
define('SMTP_FROM_EMAIL', 'bilgi@rawlabs.com.tr');
define('SMTP_FROM_NAME', 'Rawlabs');

// Ödeme Ayarları
define('PAYMENT_PROVIDER', 'kuveytturk'); // 'mock' veya 'kuveytturk'
define('KUVEYT_MODE', 'test'); // 'test' veya 'live'
define('KUVEYT_MERCHANT_ID', '756009'); // Mağaza No
define('KUVEYT_CUSTOMER_ID', '95922770'); // Müşteri No
define('KUVEYT_USERNAME', 'VP755237'); // API Kullanıcı Adı
define('KUVEYT_PASSWORD', 'BURAYA_GERCEK_SIFRE_GELECEK'); // API Şifresi (Sunucudaki config.php'de doldurun!)

// Kuveyt Türk Uç Noktaları (Endpoints)
define('KUVEYT_3D_PAY_URL_TEST', 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home/ThreeDModelPayGate');
define('KUVEYT_3D_PROVISION_URL_TEST', 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home/ThreeDModelProvisionGate');
define('KUVEYT_3D_PAY_URL_LIVE', 'https://sanalpos.kuveytturk.com.tr/ServiceGateWay/Home/ThreeDModelPayGate');
define('KUVEYT_3D_PROVISION_URL_LIVE', 'https://sanalpos.kuveytturk.com.tr/ServiceGateWay/Home/ThreeDModelProvisionGate');

// Callback ve Sonuç Sayfaları
define('KUVEYT_OK_URL', SITE_URL . '/api/payment-callback.php');
define('KUVEYT_FAIL_URL', SITE_URL . '/api/payment-callback.php');
define('KUVEYT_SUCCESS_PAGE', SITE_URL . '/api/payment-success.php');
define('KUVEYT_FAIL_PAGE', SITE_URL . '/api/payment-failed.php');

// Kullanıcı Auth Ayarları
define('USER_STORAGE_PATH', __DIR__ . '/users/users.json');
