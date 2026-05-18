<?php
/**
 * Config Skeleton
 * ÖNEMLİ: Gerçek config.php dosyası bu örnek baz alınarak sadece sunucuda (cPanel) oluşturulmalıdır.
 * Bu dosya repoya eklenen güvenli örnek şablondur. Gerçek config.php repo içinde asla bulunmamalıdır!
 */

// Sistem ayarları
define('SITE_URL', 'https://rawlabs.com.tr');
define('ORDER_STORAGE_PATH', __DIR__ . '/orders/');

// E-posta SMTP ayarları ve Bildirimler
define('SMTP_HOST', 'mail.rawlabs.com.tr');
define('SMTP_PORT', 465);
define('SMTP_USER', 'bilgi@rawlabs.com.tr');
define('SMTP_PASS', 'SMTP_PASSWORD_HERE');
define('SMTP_FROM_EMAIL', 'bilgi@rawlabs.com.tr');
define('SMTP_FROM_NAME', 'Rawlabs');
define('ADMIN_EMAIL', 'bilgi@rawlabs.com.tr'); // Sipariş bildirimlerinin gideceği e-posta

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

// Admin Panel Güvenliği
// Şifre: admin123 (Bu hash örnektir. password_hash('şifreniz', PASSWORD_DEFAULT) ile üretin)
define('ADMIN_PANEL_PASSWORD_HASH', '$2y$10$w85.gS2w0Rj3.W./VjJtK.bI17zH0pL92Z2qG56bQ/iYI3l6yBvF.');

// Bizim Hesap Entegrasyon Ayarları (Faz 8)
define('BIZIMHESAP_FIRM_ID', 'YOUR_BIZIMHESAP_FIRM_ID');
// Token bazı servislerde gerekebilir. Addinvoice için panelinizde Token yoksa boş bırakabilirsiniz.
define('BIZIMHESAP_TOKEN', '');


