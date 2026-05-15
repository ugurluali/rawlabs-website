<?php
/**
 * Rawlabs - Oturum Kontrol API
 */
require_once __DIR__ . '/auth-helpers.php';

try {
    $user = getLoggedInUser();

    if ($user) {
        jsonResponse([
            'loggedIn' => true,
            'user' => [
                'id' => $user['id'],
                'fullName' => $user['fullName'],
                'email' => $user['email']
            ]
        ]);
    } else {
        jsonResponse(['loggedIn' => false]);
    }

} catch (Throwable $e) {
    error_log('Rawlabs Auth Me Hatası: ' . $e->getMessage());
    jsonResponse(['loggedIn' => false]);
}
