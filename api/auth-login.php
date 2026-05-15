<?php
/**
 * Rawlabs - Giriş Yap API
 */
require_once __DIR__ . '/auth-helpers.php';

try {
    requirePost();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        jsonResponse(['success' => false, 'message' => 'Geçersiz veri formatı.'], 400);
    }

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'E-posta ve şifre gereklidir.'], 400);
    }

    // Kullanıcıyı bul
    $user = findUserByEmail($email);

    // Güvenlik: Kullanıcı var/yok bilgisini sızdırma
    if (!$user || !password_verify($password, $user['passwordHash'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'E-posta veya şifre hatalı.'], 401);
    }

    // Session oluştur
    createSession($user);

    jsonResponse([
        'success' => true,
        'message' => 'Giriş başarılı.',
        'user' => [
            'id' => $user['id'],
            'fullName' => $user['fullName'],
            'email' => $user['email']
        ]
    ]);

} catch (Throwable $e) {
    error_log('Rawlabs Auth Login Hatası: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Sunucu hatası oluştu.'], 500);
}
