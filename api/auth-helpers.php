<?php
/**
 * Rawlabs Auth Yardımcı Fonksiyonları
 * Tüm auth endpoint'leri bu dosyayı kullanır.
 * MySQL'e geçişte sadece bu dosya değiştirilecektir.
 */

// Session başlat (henüz başlamadıysa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kullanıcı deposu dosya yolunu döndürür
 */
function getUserStoragePath() {
    if (defined('USER_STORAGE_PATH')) {
        return USER_STORAGE_PATH;
    }
    return __DIR__ . '/users/users.json';
}

/**
 * Tüm kullanıcıları oku (flock ile güvenli)
 */
function readUsers() {
    $path = getUserStoragePath();
    
    if (!file_exists($path)) {
        // İlk çalışmada otomatik oluştur
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    
    $fp = fopen($path, 'r');
    if (!$fp) return [];
    
    flock($fp, LOCK_SH);
    $content = fread($fp, filesize($path) ?: 1);
    flock($fp, LOCK_UN);
    fclose($fp);
    
    $users = json_decode($content, true);
    return is_array($users) ? $users : [];
}

/**
 * Tüm kullanıcıları yaz (flock ile güvenli)
 */
function writeUsers($users) {
    $path = getUserStoragePath();
    
    $fp = fopen($path, 'c');
    if (!$fp) return false;
    
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    
    return true;
}

/**
 * E-posta ile kullanıcı bul (null döner bulamazsa)
 */
function findUserByEmail($email) {
    $users = readUsers();
    $email = mb_strtolower(trim($email), 'UTF-8');
    
    foreach ($users as $user) {
        if (mb_strtolower($user['email'] ?? '', 'UTF-8') === $email) {
            return $user;
        }
    }
    return null;
}

/**
 * ID ile kullanıcı bul
 */
function findUserById($id) {
    $users = readUsers();
    foreach ($users as $user) {
        if (($user['id'] ?? '') === $id) {
            return $user;
        }
    }
    return null;
}

/**
 * Kullanıcıyı güncelle (ID'ye göre)
 */
function updateUser($id, $data) {
    $users = readUsers();
    foreach ($users as &$user) {
        if (($user['id'] ?? '') === $id) {
            $user = array_merge($user, $data);
            return writeUsers($users);
        }
    }
    return false;
}

/**
 * Benzersiz kullanıcı ID'si üret
 */
function generateUserId() {
    return 'usr_' . bin2hex(random_bytes(8));
}

/**
 * Güvenli token üret (şifre sıfırlama vb.)
 */
function generateSecureToken() {
    return bin2hex(random_bytes(32));
}

/**
 * JSON response gönder
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Sadece POST isteklerine izin ver
 */
function requirePost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Geçersiz istek yöntemi.'], 405);
    }
}

/**
 * Session'dan giriş yapan kullanıcıyı al
 */
function getLoggedInUser() {
    if (!empty($_SESSION['user_id'])) {
        return findUserById($_SESSION['user_id']);
    }
    return null;
}

/**
 * Oturum oluştur
 */
function createSession($user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['login_time'] = time();
}

/**
 * Oturum yok et
 */
function destroySession() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
