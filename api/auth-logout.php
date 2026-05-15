<?php
/**
 * Rawlabs - Çıkış Yap API
 */
require_once __DIR__ . '/auth-helpers.php';

try {
    requirePost();
    destroySession();
    jsonResponse(['success' => true, 'message' => 'Çıkış yapıldı.']);

} catch (Throwable $e) {
    error_log('Rawlabs Auth Logout Hatası: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Sunucu hatası oluştu.'], 500);
}
