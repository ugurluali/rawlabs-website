<?php
/**
 * Rawlabs - Şifremi Unuttum API
 * Token üretir ve error_log'a yazar. Mail entegrasyonu sonraki fazda.
 */
require_once __DIR__ . '/auth-helpers.php';

try {
    requirePost();

    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Geçerli bir e-posta adresi giriniz.'], 400);
    }

    // Güvenlik: Kullanıcı var/yok bilgisini ASLA sızdırma
    $genericMessage = 'Eğer bu e-posta adresi sistemimizde kayıtlıysa, şifre sıfırlama bağlantısı gönderilecektir.';

    $user = findUserByEmail($email);

    if ($user) {
        // Token üret (1 saat geçerli)
        $token = generateSecureToken();
        $expiry = date('c', time() + 3600);

        // Kullanıcıyı güncelle
        updateUser($user['id'], [
            'resetToken' => password_hash($token, PASSWORD_DEFAULT),
            'resetTokenExpiry' => $expiry
        ]);

        // Config'den site URL'sini al
        $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rawlabs.com.tr';
        $resetLink = $siteUrl . '/hesabim.html?action=reset&token=' . $token . '&email=' . urlencode($user['email']);

        // Test modda: sadece sunucu loguna yaz
        error_log('Rawlabs Şifre Sıfırlama [' . $user['email'] . ']: ' . $resetLink);

        // NOT: Canlı modda burada PHPMailer ile mail gönderilecek
    }

    // Her durumda aynı mesajı döndür
    jsonResponse([
        'success' => true,
        'message' => $genericMessage
    ]);

} catch (Throwable $e) {
    error_log('Rawlabs Auth Forgot Password Hatası: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Sunucu hatası oluştu.'], 500);
}
