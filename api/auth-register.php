<?php
/**
 * Rawlabs - Hesap Oluşturma API
 */
require_once __DIR__ . '/auth-helpers.php';

try {
    requirePost();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        jsonResponse(['success' => false, 'message' => 'Geçersiz veri formatı.'], 400);
    }

    $fullName = trim($input['fullName'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    // Validasyon
    if (empty($fullName) || mb_strlen($fullName) < 2) {
        jsonResponse(['success' => false, 'message' => 'Ad Soyad en az 2 karakter olmalıdır.'], 400);
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Geçerli bir e-posta adresi giriniz.'], 400);
    }

    if (empty($password) || mb_strlen($password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Şifre en az 6 karakter olmalıdır.'], 400);
    }

    // Duplicate kontrolü
    $existing = findUserByEmail($email);
    if ($existing) {
        jsonResponse(['success' => false, 'message' => 'Bu e-posta adresi zaten kayıtlıdır.'], 409);
    }

    // Yeni kullanıcı oluştur
    $userId = generateUserId();
    $newUser = [
        'id' => $userId,
        'fullName' => $fullName,
        'email' => mb_strtolower($email, 'UTF-8'),
        'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
        'createdAt' => date('c'),
        'resetToken' => null,
        'resetTokenExpiry' => null
    ];

    $users = readUsers();
    $users[] = $newUser;

    if (!writeUsers($users)) {
        jsonResponse(['success' => false, 'message' => 'Hesap oluşturulurken bir hata oluştu.'], 500);
    }

    // Otomatik giriş yap
    createSession($newUser);

    jsonResponse([
        'success' => true,
        'message' => 'Hesabınız başarıyla oluşturuldu.',
        'user' => [
            'id' => $newUser['id'],
            'fullName' => $newUser['fullName'],
            'email' => $newUser['email']
        ]
    ]);

} catch (Throwable $e) {
    error_log('Rawlabs Auth Register Hatası: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Sunucu hatası oluştu.'], 500);
}
