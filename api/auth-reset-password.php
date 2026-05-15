<?php
/**
 * Rawlabs - Şifre Sıfırlama API
 * Token doğrulama ve yeni şifre kaydetme.
 */
require_once __DIR__ . '/auth-helpers.php';

try {
    requirePost();

    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $token = trim($input['token'] ?? '');
    $newPassword = $input['password'] ?? '';

    if (empty($email) || empty($token) || empty($newPassword)) {
        jsonResponse(['success' => false, 'message' => 'Eksik bilgi. Lütfen tüm alanları doldurunuz.'], 400);
    }

    if (mb_strlen($newPassword) < 6) {
        jsonResponse(['success' => false, 'message' => 'Yeni şifre en az 6 karakter olmalıdır.'], 400);
    }

    $user = findUserByEmail($email);

    // Token geçerlilik kontrolü
    $tokenValid = false;
    if ($user && !empty($user['resetToken']) && !empty($user['resetTokenExpiry'])) {
        // Süre kontrolü
        $expiry = strtotime($user['resetTokenExpiry']);
        if ($expiry > time()) {
            // Token eşleşme kontrolü
            if (password_verify($token, $user['resetToken'])) {
                $tokenValid = true;
            }
        }
    }

    if (!$tokenValid) {
        jsonResponse(['success' => false, 'message' => 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş.'], 400);
    }

    // Yeni şifreyi kaydet ve token'ı temizle
    updateUser($user['id'], [
        'passwordHash' => password_hash($newPassword, PASSWORD_DEFAULT),
        'resetToken' => null,
        'resetTokenExpiry' => null
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Şifreniz başarıyla güncellendi. Giriş yapabilirsiniz.'
    ]);

} catch (Throwable $e) {
    error_log('Rawlabs Auth Reset Password Hatası: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Sunucu hatası oluştu.'], 500);
}
