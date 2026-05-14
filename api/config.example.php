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

// Banka Sanal POS ayarları (Örnek)
define('POS_PROVIDER', 'EXAMPLE_BANK');
define('POS_MERCHANT_ID', 'MERCHANT_ID_HERE');
define('POS_TERMINAL_ID', 'TERMINAL_ID_HERE');
define('POS_STORE_KEY', 'STORE_KEY_HERE');
define('POS_API_URL', 'https://api.examplebank.com/pos');
define('POS_SUCCESS_URL', SITE_URL . '/api/payment-callback.php?status=success');
define('POS_FAIL_URL', SITE_URL . '/api/payment-callback.php?status=fail');
