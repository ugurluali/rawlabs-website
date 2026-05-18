<?php
/**
 * Rawlabs Sipariş Takip Paneli
 * Sadece okuma yetkisi vardır. JSON dosyalarındaki verileri listeler.
 */

// Güvenlik: HTTP Header'ları ve Cache Engelleme (Faz 6A)
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Güvenlik: Session Cookie parametrelerini sıkılaştır (Faz 6A)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Config yükle
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    die("config.php bulunamadı.");
}

$adminHash = defined('ADMIN_PANEL_PASSWORD_HASH') ? ADMIN_PANEL_PASSWORD_HASH : '';
if (empty($adminHash)) {
    die("ADMIN_PANEL_PASSWORD_HASH tanımlanmamış. Lütfen config.php dosyanızı güncelleyin.");
}

// Oturum zaman aşımı kontrolü (Faz 6A - 30 dakika idle timeout)
$timeoutDuration = 1800; // 30 dakika
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutDuration)) {
        session_unset();
        session_destroy();
        session_start();
    } else {
        $_SESSION['last_activity'] = time();
    }
}

// Çıkış işlemi
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin-orders.php");
    exit;
}

// Giriş işlemi & Brute Force Giriş Limiti (Faz 6A)
$error = '';
if (isset($_SESSION['login_lock_until']) && time() < $_SESSION['login_lock_until']) {
    $secondsLeft = $_SESSION['login_lock_until'] - time();
    $error = "Çok fazla hatalı giriş denemesi. Lütfen {$secondsLeft} saniye sonra tekrar deneyin.";
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (password_verify($_POST['password'], $adminHash)) {
            session_regenerate_id(true); // Session fixation koruması
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['last_activity'] = time();
            unset($_SESSION['login_attempts']);
            unset($_SESSION['login_lock_until']);
            header("Location: admin-orders.php");
            exit;
        } else {
            sleep(1); // Brute force sleep koruması
            
            if (!isset($_SESSION['login_attempts'])) {
                $_SESSION['login_attempts'] = 0;
            }
            $_SESSION['login_attempts']++;
            
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['login_lock_until'] = time() + 60; // 60 saniye kilitle
                $error = 'Çok fazla hatalı giriş denemesi. 60 saniye süreyle kilitlendiniz.';
            } else {
                $error = 'Hatalı şifre! (Kalan hak: ' . (5 - $_SESSION['login_attempts']) . ')';
            }
        }
    }
}

// Oturum kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Giriş Ekranı Render
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sipariş Takip Paneli - Giriş</title>
        <style>
            body { font-family: system-ui, -apple-system, sans-serif; background: #f3f4f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
            h2 { margin-top: 0; color: #1f2937; text-align: center; }
            .form-group { margin-bottom: 1rem; }
            input[type="password"] { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; }
            button { width: 100%; padding: 0.75rem; background: #2563eb; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
            button:hover { background: #1d4ed8; }
            .error { color: #dc2626; font-size: 0.875rem; margin-bottom: 1rem; text-align: center; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Admin Panel</h2>
            <?php if ($error) echo "<div class='error'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</div>"; ?>
            <form method="POST">
                <div class="form-group">
                    <input type="password" name="password" placeholder="Şifre" required autofocus>
                </div>
                <button type="submit">Giriş Yap</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// CSRF Token Oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sipariş Güncelleme İşlemi (Faz 4)
$updateMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $updateMsg = "<div style='color:#dc2626; padding:10px; background:#fee2e2; border-radius:4px; margin-bottom:15px;'>Güvenlik doğrulaması başarısız (CSRF). Lütfen sayfayı yenileyin.</div>";
    } else {
        $orderId = trim($_POST['orderId'] ?? '');
        // Güvenlik: Path traversal engelleme
        if (preg_match('/^RAW-\d{8}-\d{4}$/', $orderId)) {
            $storagePath = defined('ORDER_STORAGE_PATH') ? ORDER_STORAGE_PATH : __DIR__ . '/orders/';
            $orderFile = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $orderId . '.json';
            
            if (file_exists($orderFile)) {
                $fp = fopen($orderFile, 'c+');
                if ($fp) {
                    if (flock($fp, LOCK_EX)) {
                        $size = filesize($orderFile);
                        $content = fread($fp, $size);
                        $data = json_decode($content, true);
                        
                        if ($data) {
                            $newStatus = trim($_POST['orderStatus'] ?? '');
                            $allowedStatuses = ['new', 'preparing', 'shipped', 'completed', 'cancelled'];
                            if (!in_array($newStatus, $allowedStatuses)) $newStatus = 'new';
                            
                            // Backend Koruma: Ödemesi başarılı olmayan siparişler sadece cancelled yapılabilir
                            if (($data['paymentStatus'] ?? '') !== 'success' && $newStatus !== 'cancelled') {
                                $updateMsg = "<div style='color:#dc2626; padding:10px; background:#fee2e2; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Hata: Ödemesi başarılı olmayan siparişler sadece \"İptal\" durumuna alınabilir!</div>";
                            } else {
                                $data['orderStatus'] = $newStatus;
                                $data['updatedAt'] = date('c');
                                
                                if ($newStatus === 'shipped') {
                                    $data['cargoCompany'] = trim(strip_tags($_POST['cargoCompany'] ?? ''));
                                    $data['trackingNumber'] = trim(strip_tags($_POST['trackingNumber'] ?? ''));
                                    $data['trackingUrl'] = trim(strip_tags($_POST['trackingUrl'] ?? ''));
                                    
                                    // Kargo maili durumu kontrolü ve gönderimi (Faz 5)
                                    if (!isset($data['shippingMailStatus'])) {
                                        $data['shippingMailStatus'] = [
                                            'sent' => false,
                                            'sentAt' => null,
                                            'error' => null
                                        ];
                                    }
                                    
                                    if ($data['shippingMailStatus']['sent'] !== true) {
                                        require_once __DIR__ . '/mailer.php';
                                        $mailResult = sendShippingNotificationEmail($data);
                                        
                                        if ($mailResult === true) {
                                            $data['shippingMailStatus']['sent'] = true;
                                            $data['shippingMailStatus']['sentAt'] = date('c');
                                            $data['shippingMailStatus']['error'] = null;
                                        } else {
                                            $data['shippingMailStatus']['sent'] = false;
                                            $data['shippingMailStatus']['sentAt'] = null;
                                            $data['shippingMailStatus']['error'] = "Kargo maili gönderimi başarısız oldu";
                                        }
                                    }
                                }
                                
                                $note = trim(strip_tags($_POST['updateNote'] ?? ''));
                                
                                if (!isset($data['statusHistory'])) $data['statusHistory'] = [];
                                
                                $statusLabels = [
                                    'new' => 'Yeni',
                                    'preparing' => 'Hazırlanıyor',
                                    'shipped' => 'Kargoya Verildi',
                                    'completed' => 'Tamamlandı',
                                    'cancelled' => 'İptal'
                                ];
                                
                                $data['statusHistory'][] = [
                                    'status' => $newStatus,
                                    'label' => $statusLabels[$newStatus],
                                    'updatedAt' => date('c'),
                                    'note' => $note
                                ];
                                
                                ftruncate($fp, 0);
                                rewind($fp);
                                fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                                $updateMsg = "<div style='color:#059669; padding:10px; background:#d1fae5; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Sipariş ({$orderId}) başarıyla güncellendi.</div>";
                            }
                        }
                        flock($fp, LOCK_UN);
                    }
                    fclose($fp);
                }
            }
        }
    }
}

// --- BİZİM HESAP SENKRONİZASYONU (Faz 8C) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_bizim_hesap') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $updateMsg = "<div style='color:#dc2626; padding:10px; background:#fee2e2; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Güvenlik doğrulaması başarısız (CSRF). Lütfen sayfayı yenileyin.</div>";
    } else {
        $orderId = trim($_POST['orderId'] ?? '');
        if (preg_match('/^RAW-\d{8}-\d{4}$/', $orderId)) {
            $storagePath = defined('ORDER_STORAGE_PATH') ? ORDER_STORAGE_PATH : __DIR__ . '/orders/';
            $orderFile = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $orderId . '.json';
            
            if (file_exists($orderFile)) {
                // 1. ADIM: Siparişi kilitle, oku, 'processing' olarak işaretle (Mükerrerlik engeli)
                $fp = fopen($orderFile, 'r+');
                if ($fp) {
                    $canSync = false;
                    $orderData = [];
                    
                    if (flock($fp, LOCK_EX)) {
                        $size = filesize($orderFile);
                        if ($size > 0) {
                            $content = fread($fp, $size);
                            $orderData = json_decode($content, true);
                            
                            if ($orderData) {
                                $paymentStatus = $orderData['paymentStatus'] ?? '';
                                if (!isset($orderData['bizimHesap'])) {
                                    $orderData['bizimHesap'] = [
                                        'status' => 'none',
                                        'invoiceId' => null,
                                        'invoiceNumber' => null,
                                        'pdfUrl' => null,
                                        'syncedAt' => null,
                                        'error' => null
                                    ];
                                }
                                
                                $bhStatus = $orderData['bizimHesap']['status'] ?? 'none';
                                
                                if ($paymentStatus !== 'success') {
                                    $updateMsg = "<div style='color:#dc2626; padding:10px; background:#fee2e2; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Hata: Ödemesi başarılı olmayan siparişler Bizim Hesap'a aktarılamaz!</div>";
                                } elseif ($bhStatus === 'success') {
                                    $updateMsg = "<div style='color:#b45309; padding:10px; background:#fef3c7; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Bilgi: Bu sipariş zaten daha önce başarıyla aktarılmış.</div>";
                                } elseif ($bhStatus === 'processing') {
                                    $updateMsg = "<div style='color:#b45309; padding:10px; background:#fef3c7; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Bilgi: Bu siparişin aktarımı şu an devam ediyor. Lütfen bekleyin.</div>";
                                } else {
                                    // Aktarım izni ver ve durumunu güncelle
                                    $orderData['bizimHesap']['status'] = 'processing';
                                    $orderData['bizimHesap']['error'] = null;
                                    
                                    ftruncate($fp, 0);
                                    rewind($fp);
                                    fwrite($fp, json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                                    $canSync = true;
                                }
                            }
                        }
                        flock($fp, LOCK_UN);
                    }
                    fclose($fp);
                    
                    // 2. ADIM: cURL çağrısını helper üzerinden kilitsiz yürüt (Sunucuyu bloke etmemek için)
                    if ($canSync) {
                        require_once __DIR__ . '/bizimhesap-helper.php';
                        $syncResult = addBizimHesapInvoice($orderData);
                        
                        // 3. ADIM: Sonucu sipariş JSON'una kilitleyerek yaz
                        $fp = fopen($orderFile, 'r+');
                        if ($fp) {
                            if (flock($fp, LOCK_EX)) {
                                $size = filesize($orderFile);
                                if ($size > 0) {
                                    $content = fread($fp, $size);
                                    $latestData = json_decode($content, true);
                                    
                                    if ($latestData) {
                                        if (!isset($latestData['bizimHesap'])) {
                                            $latestData['bizimHesap'] = $orderData['bizimHesap'];
                                        }
                                        
                                        if ($syncResult['success']) {
                                            $latestData['bizimHesap']['status'] = 'success';
                                            $latestData['bizimHesap']['invoiceId'] = $syncResult['invoiceId'];
                                            $latestData['bizimHesap']['syncedAt'] = date('c');
                                            $latestData['bizimHesap']['error'] = null;
                                            
                                            $updateMsg = "<div style='color:#059669; padding:10px; background:#d1fae5; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Sipariş ({$orderId}) başarıyla Bizim Hesap'a aktarıldı. Taslak Fatura ID: {$syncResult['invoiceId']}</div>";
                                        } else {
                                            $latestData['bizimHesap']['status'] = 'failed';
                                            $latestData['bizimHesap']['syncedAt'] = date('c');
                                            $latestData['bizimHesap']['error'] = $syncResult['error'];
                                            
                                            $updateMsg = "<div style='color:#dc2626; padding:10px; background:#fee2e2; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Hata: Bizim Hesap aktarımı başarısız oldu. Hata: " . htmlspecialchars($syncResult['error'], ENT_QUOTES, 'UTF-8') . "</div>";
                                        }
                                        
                                        ftruncate($fp, 0);
                                        rewind($fp);
                                        fwrite($fp, json_encode($latestData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                                    }
                                }
                                flock($fp, LOCK_UN);
                            }
                            fclose($fp);
                        }
                    }
                }
            }
        }
    }
}

// --- RESMİ FATURA BİLGİLERİ KAYDETME (Faz 8C) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_invoice_info') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $updateMsg = "<div style='color:#dc2626; padding:10px; background:#fee2e2; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Güvenlik doğrulaması başarısız (CSRF). Lütfen sayfayı yenileyin.</div>";
    } else {
        $orderId = trim($_POST['orderId'] ?? '');
        if (preg_match('/^RAW-\d{8}-\d{4}$/', $orderId)) {
            $storagePath = defined('ORDER_STORAGE_PATH') ? ORDER_STORAGE_PATH : __DIR__ . '/orders/';
            $orderFile = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $orderId . '.json';
            
            if (file_exists($orderFile)) {
                $fp = fopen($orderFile, 'r+');
                if ($fp) {
                    if (flock($fp, LOCK_EX)) {
                        $size = filesize($orderFile);
                        if ($size > 0) {
                            $content = fread($fp, $size);
                            $data = json_decode($content, true);
                            
                            if ($data) {
                                if (!isset($data['bizimHesap'])) {
                                    $data['bizimHesap'] = [
                                        'status' => 'none',
                                        'invoiceId' => null,
                                        'invoiceNumber' => null,
                                        'pdfUrl' => null,
                                        'syncedAt' => null,
                                        'error' => null
                                    ];
                                }
                                
                                $invoiceNumber = trim(strip_tags($_POST['invoiceNumber'] ?? ''));
                                $pdfUrl = trim(strip_tags($_POST['pdfUrl'] ?? ''));
                                
                                // Basit URL Doğrulaması
                                if (!empty($pdfUrl) && !filter_var($pdfUrl, FILTER_VALIDATE_URL)) {
                                    $updateMsg = "<div style='color:#dc2626; padding:10px; background:#fee2e2; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Hata: Girdiğiniz Fatura PDF Linki geçerli bir URL formatında olmalıdır!</div>";
                                } else {
                                    $data['bizimHesap']['invoiceNumber'] = !empty($invoiceNumber) ? $invoiceNumber : null;
                                    $data['bizimHesap']['pdfUrl'] = !empty($pdfUrl) ? $pdfUrl : null;
                                    $data['bizimHesap']['invoiceInfoSavedAt'] = date('c');
                                    
                                    ftruncate($fp, 0);
                                    rewind($fp);
                                    fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                                    
                                    $updateMsg = "<div style='color:#059669; padding:10px; background:#d1fae5; border-radius:4px; margin-bottom:15px; font-weight:bold;'>Resmi Fatura Bilgileri ({$orderId}) başarıyla güncellendi.</div>";
                                }
                            }
                        }
                        flock($fp, LOCK_UN);
                    }
                    fclose($fp);
                }
            }
        }
    }
}

// Oturum açık, verileri yükle
$storagePath = defined('ORDER_STORAGE_PATH') ? ORDER_STORAGE_PATH : __DIR__ . '/orders/';
$files = glob(rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . 'RAW-*.json');

$orders = [];
if ($files) {
    foreach ($files as $file) {
        $content = @file_get_contents($file);
        if (!$content) continue;
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) continue;
        
        // Güvenlik: Kart bilgisi gibi hassas veriler json'a yazılmamış olmalı ama önlem olarak unset
        $sensitiveKeys = ['cardNumber', 'cardCvv', 'cardExpireMonth', 'cardExpireYear', 'HashData', 'MD', 'HashPassword'];
        foreach ($sensitiveKeys as $k) {
            if (isset($data[$k])) unset($data[$k]);
        }
        
        $data['_filemtime'] = filemtime($file);
        $orders[] = $data;
    }

    // Tarihe göre DESC (Yeni olan üstte)
    usort($orders, function($a, $b) {
        return $b['_filemtime'] <=> $a['_filemtime'];
    });
}

// Sipariş durumu özet sayaçları (Faz 6B - Ödeme Bekliyor Dahil)
$summaryCounts = [
    'total' => 0,
    'new' => 0,
    'preparing' => 0,
    'shipped' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'payment_pending' => 0
];

foreach ($orders as $o) {
    $summaryCounts['total']++;
    
    $isCancelled = ($o['orderStatus'] ?? '') === 'cancelled';
    
    if (($o['paymentStatus'] ?? '') !== 'success' && !$isCancelled) {
        $summaryCounts['payment_pending']++;
    } else {
        $status = $o['orderStatus'] ?? 'new';
        if (isset($summaryCounts[$status])) {
            $summaryCounts[$status]++;
        } else {
            $summaryCounts['new']++;
        }
    }
}

function esc($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function getStatusBadge($status) {
    $map = [
        'created' => ['bg' => '#e5e7eb', 'color' => '#374151', 'text' => 'Oluşturuldu'],
        'payment_started' => ['bg' => '#fef08a', 'color' => '#854d0e', 'text' => 'Ödeme Bekliyor'],
        'paid' => ['bg' => '#bbf7d0', 'color' => '#166534', 'text' => 'Ödendi'],
        'payment_failed' => ['bg' => '#fecaca', 'color' => '#991b1b', 'text' => 'Başarısız'],
        'shipped' => ['bg' => '#bfdbfe', 'color' => '#1e3a8a', 'text' => 'Kargolandı'],
        'completed' => ['bg' => '#d9f99d', 'color' => '#3f6212', 'text' => 'Tamamlandı']
    ];
    $s = $map[$status] ?? ['bg' => '#f3f4f6', 'color' => '#4b5563', 'text' => $status];
    return "<span style='background: {$s['bg']}; color: {$s['color']}; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;'>" . esc($s['text']) . "</span>";
}

function getOrderStatusBadge($status) {
    $map = [
        'new' => ['bg' => '#e2e8f0', 'color' => '#1e293b', 'text' => 'Yeni'],
        'preparing' => ['bg' => '#ffedd5', 'color' => '#9a3412', 'text' => 'Hazırlanıyor'],
        'shipped' => ['bg' => '#e0e7ff', 'color' => '#3730a3', 'text' => 'Kargoya Verildi'],
        'completed' => ['bg' => '#dcfce7', 'color' => '#166534', 'text' => 'Tamamlandı'],
        'cancelled' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'text' => 'İptal'],
        'payment_pending' => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'text' => 'Ödeme Bekliyor']
    ];
    $s = $map[$status] ?? $map['new'];
    return "<span style='background: {$s['bg']}; color: {$s['color']}; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;'>" . esc($s['text']) . "</span>";
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Takip Paneli</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color: #1f2937; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .btn { padding: 0.5rem 1rem; border-radius: 4px; border: none; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-logout { background: #ef4444; color: white; }
        .btn-view { background: #3b82f6; color: white; font-size: 0.8rem; padding: 0.4rem 0.8rem; }
        .search-box { padding: 0.5rem; width: 100%; max-width: 300px; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 1rem; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; font-weight: 600; font-size: 0.875rem; color: #4b5563; }
        tr:hover { background: #f9fafb; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 50; }
        .modal { background: white; width: 90%; max-width: 700px; border-radius: 8px; max-height: 90vh; overflow-y: auto; padding: 20px; box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
        .modal-close { cursor: pointer; font-size: 1.5rem; line-height: 1; color: #6b7280; background: none; border: none; }
        .detail-section { margin-bottom: 20px; }
        .detail-section h3 { font-size: 1.1rem; border-bottom: 1px solid #eee; padding-bottom: 5px; color: #374151; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .muted { color: #6b7280; font-size: 0.85rem; }
        
        /* Özet Sayaçları ve Gelişmiş Arama Stilleri (Faz 6A) */
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-left: 4px solid #d1d5db; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stat-card.total { border-left-color: #3b82f6; }
        .stat-card.new { border-left-color: #64748b; }
        .stat-card.preparing { border-left-color: #f97316; }
        .stat-card.shipped { border-left-color: #6366f1; }
        .stat-card.completed { border-left-color: #22c55e; }
        .stat-card.cancelled { border-left-color: #ef4444; }
        .stat-val { font-size: 1.5rem; font-weight: 800; color: #111827; line-height: 1; }
        .stat-label { font-size: 0.72rem; color: #6b7280; text-transform: uppercase; font-weight: 700; margin-top: 6px; letter-spacing: 0.05em; }
    </style>
</head>
<body>

<div class="header">
    <h1>Sipariş Takip Paneli</h1>
    <a href="?action=logout" class="btn btn-logout">Çıkış Yap</a>
</div>

<?= $updateMsg ?>

<!-- Özet Sayaçları (Faz 6B) -->
<div class="stats-container">
    <div class="stat-card total">
        <span class="stat-val"><?= (int)$summaryCounts['total'] ?></span>
        <span class="stat-label">Toplam Sipariş</span>
    </div>
    <div class="stat-card payment-pending" style="border-left-color: #9ca3af;">
        <span class="stat-val"><?= (int)$summaryCounts['payment_pending'] ?></span>
        <span class="stat-label">Ödeme Bekliyor</span>
    </div>
    <div class="stat-card new">
        <span class="stat-val"><?= (int)$summaryCounts['new'] ?></span>
        <span class="stat-label">Yeni</span>
    </div>
    <div class="stat-card preparing">
        <span class="stat-val"><?= (int)$summaryCounts['preparing'] ?></span>
        <span class="stat-label">Hazırlanıyor</span>
    </div>
    <div class="stat-card shipped">
        <span class="stat-val"><?= (int)$summaryCounts['shipped'] ?></span>
        <span class="stat-label">Kargoda</span>
    </div>
    <div class="stat-card completed">
        <span class="stat-val"><?= (int)$summaryCounts['completed'] ?></span>
        <span class="stat-label">Tamamlandı</span>
    </div>
    <div class="stat-card cancelled">
        <span class="stat-val"><?= (int)$summaryCounts['cancelled'] ?></span>
        <span class="stat-label">İptal</span>
    </div>
</div>

<!-- Filtreler ve Arama Alanı (Faz 6B) -->
<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
        <!-- Arama Kutusu -->
        <div>
            <label style="display:block; margin-bottom:5px; font-size:0.85rem; font-weight:600; color:#4b5563;">Arama</label>
            <input type="text" id="searchInput" class="search-box" style="margin-bottom:0; width:100%; max-width:none;" placeholder="No, isim, tel veya e-posta...">
        </div>
        
        <!-- Sipariş Durumu -->
        <div>
            <label style="display:block; margin-bottom:5px; font-size:0.85rem; font-weight:600; color:#4b5563;">Sipariş Durumu</label>
            <select id="statusFilter" class="search-box" style="margin-bottom:0; width:100%; max-width:none; background: white; cursor: pointer;">
                <option value="all">Tümü</option>
                <option value="payment_pending">Ödeme Bekliyor</option>
                <option value="new">Yeni</option>
                <option value="preparing">Hazırlanıyor</option>
                <option value="shipped">Kargoya Verildi</option>
                <option value="completed">Tamamlandı</option>
                <option value="cancelled">İptal</option>
            </select>
        </div>

        <!-- Ödeme Durumu -->
        <div>
            <label style="display:block; margin-bottom:5px; font-size:0.85rem; font-weight:600; color:#4b5563;">Ödeme Durumu</label>
            <select id="paymentFilter" class="search-box" style="margin-bottom:0; width:100%; max-width:none; background: white; cursor: pointer;">
                <option value="all">Tümü</option>
                <option value="success">Başarılı (success)</option>
                <option value="failed">Başarısız (failed)</option>
                <option value="unknown">Beklemede / Bilinmiyor</option>
            </select>
        </div>

        <!-- Müşteri Tipi -->
        <div>
            <label style="display:block; margin-bottom:5px; font-size:0.85rem; font-weight:600; color:#4b5563;">Müşteri Tipi</label>
            <select id="userTypeFilter" class="search-box" style="margin-bottom:0; width:100%; max-width:none; background: white; cursor: pointer;">
                <option value="all">Tümü</option>
                <option value="member">Üye</option>
                <option value="guest">Misafir</option>
            </select>
        </div>
    </div>
    
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; justify-content: space-between; border-top: 1px solid #f3f4f6; padding-top: 15px;">
        <!-- Tarih Filtreleri -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <div style="display:flex; align-items:center; gap:5px;">
                <span style="font-size:0.85rem; font-weight:600; color:#4b5563;">Başlangıç:</span>
                <input type="date" id="startDateFilter" class="search-box" style="margin-bottom:0; padding: 0.35rem 0.5rem; width:auto; height: auto;">
            </div>
            <div style="display:flex; align-items:center; gap:5px;">
                <span style="font-size:0.85rem; font-weight:600; color:#4b5563;">Bitiş:</span>
                <input type="date" id="endDateFilter" class="search-box" style="margin-bottom:0; padding: 0.35rem 0.5rem; width:auto; height: auto;">
            </div>
        </div>
        
        <!-- Butonlar -->
        <div style="display: flex; gap: 10px;">
            <button id="clearFiltersBtn" class="btn" style="background:#e5e7eb; color:#374151; font-size:0.85rem;">Filtreleri Temizle</button>
            <button id="exportCsvBtn" class="btn" style="background:#059669; color:white; font-size:0.85rem; display:flex; align-items:center; gap:5px;">
                📥 CSV Dışa Aktar
            </button>
        </div>
    </div>
</div>

<div style="overflow-x: auto;">
    <table id="ordersTable">
        <thead>
            <tr>
                <th>Sipariş No</th>
                <th>Tarih</th>
                <th>Müşteri</th>
                <th>Tutar</th>
                <th>Sipariş Durumu</th>
                <th>Ödeme</th>
                <th>Fatura (Bizim Hesap)</th>
                <th>Tip</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
            <tr><td colspan="9" style="text-align: center; padding: 2rem;">Sipariş bulunamadı.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o): 
                    $date = $o['paidAt'] ?? $o['backend_createdAt'] ?? '';
                    $dateStr = $date ? date('d.m.Y H:i', strtotime($date)) : '-';
                    $dateIso = $date ? date('Y-m-d', strtotime($date)) : '';
                    $total = number_format((float)($o['summary']['grandTotal'] ?? 0), 2, ',', '.');
                    
                    $userTypeVal = empty($o['userId']) ? 'guest' : 'member';
                    $userType = $userTypeVal === 'member' ? '<span style="color:#2563eb;font-weight:bold;">Üye</span>' : '<span class="muted">Misafir</span>';
                    
                    $payStatusVal = strtolower(trim($o['paymentStatus'] ?? ''));
                    if (empty($payStatusVal) || $payStatusVal === '-') {
                        $payStatusVal = 'unknown';
                    }
                    
                    $isCancelled = ($o['orderStatus'] ?? '') === 'cancelled';
                    $displayStatusVal = ($payStatusVal !== 'success' && !$isCancelled) ? 'payment_pending' : ($o['orderStatus'] ?? 'new');
                    
                    $customer = $o['customer'] ?? [];
                    
                    // Test Siparişi Kontrolü
                    $isTest = false;
                    foreach ($o['items'] ?? [] as $item) {
                        if (($item['slug'] ?? '') === 'test-urun-1tl' || strpos((string)($item['name'] ?? ''), 'GEÇİCİ MAIL TESTİ') !== false) {
                            $isTest = true;
                            break;
                        }
                    }
                    $testBadge = $isTest ? '<br><span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;font-size:0.65rem;font-weight:bold;">TEST SİPARİŞİ</span>' : '';
                    
                    // JSON'a encode ederek modal için hazırla (Güvenli kaçış)
                    $jsonAttr = htmlspecialchars(json_encode($o, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                ?>
                <tr data-status="<?= esc($displayStatusVal) ?>"
                    data-date="<?= esc($dateIso) ?>"
                    data-payment-status="<?= esc($payStatusVal) ?>"
                    data-user-type="<?= esc($userTypeVal) ?>"
                    data-order-id="<?= esc($o['orderId'] ?? '-') ?>"
                    data-date-str="<?= esc($dateStr) ?>"
                    data-customer-name="<?= esc($customer['fullName'] ?? '-') ?>"
                    data-customer-phone="<?= esc($customer['phone'] ?? '') ?>"
                    data-customer-email="<?= esc($customer['email'] ?? '') ?>"
                    data-total="<?= esc($total) ?>"
                    data-status-label="<?php
                        if ($payStatusVal !== 'success' && !$isCancelled) {
                            echo 'Ödeme Bekliyor';
                        } else {
                            $statusLabels = ['new' => 'Yeni', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargoya Verildi', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal'];
                            echo esc($statusLabels[$o['orderStatus'] ?? 'new'] ?? 'Yeni');
                        }
                    ?>"
                    data-payment-status-label="<?= esc($o['paymentStatus'] ?? '-') ?>"
                    data-user-type-label="<?= empty($o['userId']) ? 'Misafir' : 'Üye' ?>"
                    data-cargo-company="<?= esc($o['cargoCompany'] ?? '') ?>"
                    data-tracking-number="<?= esc($o['trackingNumber'] ?? '') ?>">
                    <td style="font-family: monospace; font-size:0.9rem;">
                        <?= esc($o['orderId'] ?? '-') ?>
                        <?= $testBadge ?>
                    </td>
                    <td style="font-size:0.9rem;"><?= esc($dateStr) ?></td>
                    <td>
                        <strong><?= esc($customer['fullName'] ?? '-') ?></strong><br>
                        <span class="muted"><?= esc($customer['phone'] ?? '') ?></span><br>
                        <span class="muted"><?= esc($customer['email'] ?? '') ?></span>
                    </td>
                    <td>₺<?= $total ?></td>
                    <td>
                        <?= getOrderStatusBadge(($payStatusVal !== 'success' && !$isCancelled) ? 'payment_pending' : ($o['orderStatus'] ?? 'new')) ?>
                        <?php
                        // Kargo mail durumunu tablonun Sipariş Durumu hücresinde göster (Faz 5)
                        if (($o['orderStatus'] ?? 'new') === 'shipped') {
                            $sStat = $o['shippingMailStatus'] ?? null;
                            if (!$sStat) {
                                echo '<br><small style="color:#d97706; font-weight:bold;">📧 Kargo Maili: Gönderilmedi</small>';
                            } elseif ($sStat['sent'] ?? false) {
                                echo '<br><small style="color:#059669; font-weight:bold;">📧 Kargo Maili: Gönderildi</small>';
                            } elseif (!empty($sStat['error'])) {
                                echo '<br><small style="color:#dc2626; font-weight:bold;">📧 Kargo Maili: Hata</small>';
                            } else {
                                echo '<br><small style="color:#d97706; font-weight:bold;">📧 Kargo Maili: Gönderilmedi</small>';
                            }
                        }
                        ?>
                    </td>
                    <td>
                        <?= esc($o['paymentStatus'] ?? '-') ?><br>
                        <?php if(!empty($o['provider'])): ?>
                            <small class="muted"><?= esc($o['provider']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $bh = $o['bizimHesap'] ?? null;
                        $bhStatus = $bh['status'] ?? 'none';
                        
                        if ($bhStatus === 'success') {
                            echo '<span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Aktarıldı</span>';
                            if (!empty($bh['invoiceId'])) {
                                echo '<br><small class="muted" style="font-family: monospace;">ID: ' . esc($bh['invoiceId']) . '</small>';
                            }
                        } elseif ($bhStatus === 'processing') {
                            echo '<span style="background: #fef08a; color: #854d0e; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">🔄 Aktarılıyor...</span>';
                        } elseif ($bhStatus === 'failed') {
                            echo '<span style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">⚠️ Hata</span>';
                            if (!empty($bh['error'])) {
                                echo '<br><small style="color: #dc2626; font-size: 0.72rem; display:block; max-width: 150px; white-space: normal; line-height:1.2;">' . esc($bh['error']) . '</small>';
                            }
                        } else {
                            echo '<span style="background: #e2e8f0; color: #475569; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Aktarılmadı</span>';
                        }
                        
                        if (!empty($bh['invoiceNumber'])) {
                            echo '<br><small style="color:#6B2C83; font-weight:bold;">No: ' . esc($bh['invoiceNumber']) . '</small>';
                        }
                        ?>
                    </td>
                    <td><?= $userType ?></td>
                    <td>
                        <button class="btn btn-view" onclick='openModal(this)' data-order='<?= $jsonAttr ?>'>Detay</button>
                        <button class="btn" style="background:#4f46e5; color:white; padding: 0.375rem 0.75rem; border-radius: 0.375rem; font-size: 0.825rem; font-weight:600; margin-left: 4px;" onclick='printDirectProforma(this)' data-order='<?= $jsonAttr ?>'>Yazdır</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="orderModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 style="margin:0;" id="m_orderId">Sipariş Detayı</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        
        <div class="grid-2">
            <div class="detail-section">
                <h3>Müşteri & Teslimat</h3>
                <p><strong>İsim:</strong> <span id="m_name"></span></p>
                <p><strong>Telefon:</strong> <span id="m_phone"></span></p>
                <p><strong>E-posta:</strong> <span id="m_email"></span></p>
                <p><strong>Adres:</strong> <span id="m_address"></span></p>
                <p><strong>İlçe/İl:</strong> <span id="m_city"></span></p>
                <p><strong>Müşteri Notu:</strong> <span id="m_note"></span></p>
            </div>
            <div class="detail-section">
                <h3>Sipariş & Ödeme Bilgisi</h3>
                <p><strong>Ödeme:</strong> <span id="m_status"></span></p>
                <p><strong>Ara Toplam:</strong> ₺<span id="m_sub"></span></p>
                <p><strong>Kargo Ücreti:</strong> ₺<span id="m_ship"></span></p>
                <p><strong>Genel Toplam:</strong> ₺<span id="m_total"></span></p>
                
                <div id="m_cargo_details" style="display:none; background:#eef2ff; padding:10px; border-radius:4px; margin-top:10px; border:1px solid #c7d2fe;">
                    <strong>Kargo Bilgileri</strong><br>
                    <small>Firma: <span id="m_cargo_comp"></span></small><br>
                    <small>Takip No: <span id="m_cargo_no"></span></small><br>
                    <small>Link: <a href="#" id="m_cargo_link" target="_blank" style="color:#4f46e5; text-decoration:underline;">Takip Et</a></small>
                </div>
                
                <div id="m_bank_details" style="display:none; background:#f3f4f6; padding:10px; border-radius:4px; margin-top:10px;">
                    <strong>Kuveyt Türk Referansları</strong><br>
                    <small>İşlem ID: <span id="m_txn"></span></small><br>
                    <small>Provizyon: <span id="m_prov"></span></small><br>
                    <small>RRN: <span id="m_rrn"></span></small><br>
                    <small>STAN: <span id="m_stan"></span></small>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h3>Ürünler</h3>
            <table style="width:100%; border:1px solid #e5e7eb; font-size:0.9rem;">
                <thead style="background:#f9fafb;">
                    <tr><th>Ürün</th><th>Adet</th><th>Birim</th><th>Toplam</th></tr>
                </thead>
                <tbody id="m_items"></tbody>
            </table>
        </div>

        <div class="detail-section">
            <h3>İşlem Geçmişi</h3>
            <div id="m_history" style="font-size:0.85rem; max-height:200px; overflow-y:auto; background:#f9fafb; padding:10px; border-radius:4px; border:1px solid #e5e7eb;">
                Henüz işlem geçmişi yok.
            </div>
        </div>

        <div class="grid-2">
            <div class="detail-section">
                <h3>Mail Durumu</h3>
                <p><strong>Admin Maili:</strong> <span id="m_mail_admin"></span></p>
                <p><strong>Müşteri Maili:</strong> <span id="m_mail_cust"></span></p>
                <p><strong>Kargo Maili:</strong> <span id="m_mail_shipping"></span></p>
                <p><strong>Mail Hatası:</strong> <span id="m_mail_error" class="muted">Yok</span></p>
            </div>
            <div class="detail-section">
                <h3>Fatura / PDF</h3>
                <div id="m_pdf_area">Bekleniyor...</div>
            </div>
        </div>
        
        <!-- Bizim Hesap Resmi Fatura İşlemleri (Faz 8C) -->
        <div class="detail-section" style="background:#fdf2f8; padding:15px; border-radius:6px; border:1px solid #fbcfe8; margin-top:20px;">
            <h3 style="color:#B12A8F; border-bottom: 1px solid #fbcfe8; font-size: 1.1rem; padding-bottom: 5px; margin-top:0;">Bizim Hesap Resmi Fatura İşlemleri</h3>
            
            <!-- Bizim Hesap Durumu ve Aktarım Butonu -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
                <div>
                    <strong>Aktarım Durumu:</strong> <span id="m_bh_status">Bekleniyor...</span>
                    <div id="m_bh_info" style="font-size:0.85rem; margin-top:4px;" class="muted"></div>
                </div>
                <div id="m_bh_action_area">
                    <!-- JavaScript ile dolacak -->
                </div>
            </div>

            <!-- Resmi Fatura Bilgileri Formu (Manuel Giriş) -->
            <div style="background:white; padding:12px; border-radius:4px; border:1px solid #fdf2f8; margin-top:10px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                <strong style="display:block; margin-bottom:10px; color:#B12A8F; font-size:0.9rem;">Resmi Fatura Bilgileri (Manuel Kayıt)</strong>
                <form id="f_invoice_info_form" method="POST" action="admin-orders.php" style="margin:0;">
                    <input type="hidden" name="action" value="save_invoice_info">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="orderId" id="f_invoice_orderId" value="">
                    
                    <div class="grid-2" style="margin-bottom:10px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color: #1F1B2E;">Fatura No</label>
                            <input type="text" name="invoiceNumber" id="f_invoiceNumber" placeholder="Örn: BHS202600000123" style="width:100%; padding:6px; border:1px solid #d1d5db; border-radius:4px; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color: #1F1B2E;">Fatura PDF Linki</label>
                            <input type="url" name="pdfUrl" id="f_pdfUrl" placeholder="https://..." style="width:100%; padding:6px; border:1px solid #d1d5db; border-radius:4px; box-sizing:border-box;">
                        </div>
                    </div>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <span id="m_invoice_saved_info" style="font-size:0.85rem; font-weight: 500;"></span>
                        <button type="submit" class="btn" style="background:#B12A8F; color:white; font-size:0.85rem; padding:6px 12px; font-weight:bold;">Fatura Bilgilerini Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="detail-section" style="background:#f9fafb; padding:15px; border-radius:6px; border:1px solid #e5e7eb; margin-top:20px;">
            <h3>Siparişi Güncelle</h3>
            <form method="POST" action="admin-orders.php">
                <input type="hidden" name="action" value="update_order">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="orderId" id="f_orderId" value="">
                
                <div id="paymentWarningBanner" style="display:none; color:#dc2626; padding:10px; background:#fee2e2; border-radius:4px; margin-bottom:15px; font-weight:bold; font-size:0.875rem; border: 1px solid #fecaca;">
                    ⚠️ DİKKAT: Bu siparişin ödemesi başarılı değildir! Ödenmemiş siparişleri kargolayamaz veya hazırlayamazsınız. Sadece İptal edebilirsiniz.
                </div>
                
                <div class="grid-2" style="margin-bottom:10px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:0.9rem; font-weight:bold;">Sipariş Durumu</label>
                        <select name="orderStatus" id="f_orderStatus" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:4px;" onchange="toggleCargoFields(this.value)">
                            <option value="new">Yeni</option>
                            <option value="preparing">Hazırlanıyor</option>
                            <option value="shipped">Kargoya Verildi</option>
                            <option value="completed">Tamamlandı</option>
                            <option value="cancelled">İptal</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:0.9rem; font-weight:bold;">İşlem Notu (Opsiyonel)</label>
                        <input type="text" name="updateNote" placeholder="Müşteriye iletilmez, iç nottur." style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:4px; box-sizing:border-box;">
                    </div>
                </div>
                
                <div id="cargoFields" style="display:none; background:#fff; padding:10px; border:1px solid #e5e7eb; border-radius:4px; margin-bottom:10px;">
                    <div class="grid-2">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-size:0.85rem;">Kargo Firması</label>
                            <input type="text" name="cargoCompany" id="f_cargoCompany" placeholder="Örn: Yurtiçi Kargo" style="width:100%; padding:6px; border:1px solid #d1d5db; border-radius:4px; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-size:0.85rem;">Takip Numarası</label>
                            <input type="text" name="trackingNumber" id="f_trackingNumber" style="width:100%; padding:6px; border:1px solid #d1d5db; border-radius:4px; box-sizing:border-box;">
                        </div>
                    </div>
                    <div style="margin-top:10px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.85rem;">Takip Linki (Opsiyonel)</label>
                        <input type="url" name="trackingUrl" id="f_trackingUrl" placeholder="https://..." style="width:100%; padding:6px; border:1px solid #d1d5db; border-radius:4px; box-sizing:border-box;">
                    </div>
                </div>
                
                <button type="submit" class="btn" style="background:#10b981; color:white; width:100%;">Durumu Güncelle</button>
            </form>
        </div>
    </div>
</div>

<script>
// Birleşik Arama, Durum, Tarih, Ödeme ve Müşteri Tipi Filtreleme Mantığı (Faz 6B)
function applyTableFilters() {
    let textFilter = document.getElementById('searchInput').value.toLowerCase();
    let statusFilter = document.getElementById('statusFilter').value;
    let paymentFilter = document.getElementById('paymentFilter').value;
    let userTypeFilter = document.getElementById('userTypeFilter').value;
    
    let startDateVal = document.getElementById('startDateFilter').value; // YYYY-MM-DD
    let endDateVal = document.getElementById('endDateFilter').value;     // YYYY-MM-DD
    
    let rows = document.querySelectorAll('#ordersTable tbody tr');
    
    rows.forEach(row => {
        if (row.cells.length < 2) return; // Boş uyarı satırını atla
        
        // 1. Metin Filtresi
        let text = row.textContent.toLowerCase();
        let matchesText = text.includes(textFilter);
        
        // 2. Sipariş Durumu Filtresi
        let rowStatus = row.getAttribute('data-status') || 'new';
        let matchesStatus = (statusFilter === 'all' || rowStatus === statusFilter);
        
        // 3. Ödeme Durumu Filtresi
        let rowPayment = row.getAttribute('data-payment-status') || 'unknown';
        let matchesPayment = (paymentFilter === 'all' || rowPayment === paymentFilter);
        
        // 4. Müşteri Tipi Filtresi
        let rowUserType = row.getAttribute('data-user-type') || 'guest';
        let matchesUserType = (userTypeFilter === 'all' || rowUserType === userTypeFilter);
        
        // 5. Tarih Aralığı Filtresi
        let rowDate = row.getAttribute('data-date') || ''; // YYYY-MM-DD
        let matchesDate = true;
        if (rowDate) {
            if (startDateVal && rowDate < startDateVal) {
                matchesDate = false;
            }
            if (endDateVal && rowDate > endDateVal) {
                matchesDate = false;
            }
        } else if (startDateVal || endDateVal) {
            matchesDate = false; // Tarihi olmayan siparişler tarih filtresi seçilmişse gizlensin
        }
        
        // Birleşik Sonuç
        if (matchesText && matchesStatus && matchesPayment && matchesUserType && matchesDate) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Event listener'ları bağla
document.getElementById('searchInput').addEventListener('keyup', applyTableFilters);
document.getElementById('statusFilter').addEventListener('change', applyTableFilters);
document.getElementById('paymentFilter').addEventListener('change', applyTableFilters);
document.getElementById('userTypeFilter').addEventListener('change', applyTableFilters);
document.getElementById('startDateFilter').addEventListener('change', applyTableFilters);
document.getElementById('endDateFilter').addEventListener('change', applyTableFilters);

// Filtreleri Temizleme Fonksiyonu (Faz 6B)
document.getElementById('clearFiltersBtn').addEventListener('click', function() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('paymentFilter').value = 'all';
    document.getElementById('userTypeFilter').value = 'all';
    document.getElementById('startDateFilter').value = '';
    document.getElementById('endDateFilter').value = '';
    applyTableFilters();
});

// CSV Dışa Aktarma Fonksiyonu (Faz 6B)
document.getElementById('exportCsvBtn').addEventListener('click', function() {
    let rows = document.querySelectorAll('#ordersTable tbody tr');
    let csvRows = [];
    
    // CSV Başlık Satırı
    let headers = [
        'Sipariş No',
        'Tarih',
        'Müşteri',
        'Telefon',
        'E-posta',
        'Tutar',
        'Sipariş Durumu',
        'Ödeme Durumu',
        'Tip',
        'Kargo Firması',
        'Takip No'
    ];
    
    function escapeCsvCell(cell) {
        if (cell === null || cell === undefined) {
            return '""';
        }
        let str = cell.toString().replace(/"/g, '""'); // Çift tırnak kaçışı
        return `"${str}"`;
    }
    
    csvRows.push(headers.map(escapeCsvCell).join(','));
    
    // Sadece görünür satırları ekle
    rows.forEach(row => {
        if (row.style.display === 'none' || row.cells.length < 2) return;
        
        let rowData = [
            row.getAttribute('data-order-id') || '',
            row.getAttribute('data-date-str') || '',
            row.getAttribute('data-customer-name') || '',
            row.getAttribute('data-customer-phone') || '',
            row.getAttribute('data-customer-email') || '',
            row.getAttribute('data-total') || '',
            row.getAttribute('data-status-label') || '',
            row.getAttribute('data-payment-status-label') || '',
            row.getAttribute('data-user-type-label') || '',
            row.getAttribute('data-cargo-company') || '',
            row.getAttribute('data-tracking-number') || ''
        ];
        
        csvRows.push(rowData.map(escapeCsvCell).join(','));
    });
    
    // UTF-8 BOM ekle (Excel Türkçe karakterleri düzgün açabilsin diye)
    let csvContent = '\uFEFF' + csvRows.join('\n');
    
    // Blob ve indirme linki oluştur
    let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    let url = URL.createObjectURL(blob);
    let link = document.createElement('a');
    
    let dateStr = new Date().toISOString().slice(0, 10);
    link.setAttribute('href', url);
    link.setAttribute('download', `rawlabs_siparisler_${dateStr}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});

// Modal Kontrolleri
function openModal(btn) {
    let rawData = btn.getAttribute('data-order');
    let order = JSON.parse(rawData);
    window.currentOrder = order; // Modal yazdirma butonu icin aktif siparisi kaydet
    let c = order.customer || {};
    let s = order.summary || {};

    document.getElementById('m_orderId').innerText = order.orderId || '-';
    
    // Test Siparişi Kontrolü
    let isTest = false;
    (order.items || []).forEach(i => {
        if (i.slug === 'test-urun-1tl' || (i.name || '').includes('GEÇİCİ MAIL TESTİ')) isTest = true;
    });
    if (isTest) {
        document.getElementById('m_orderId').innerHTML += ' <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:4px;font-size:0.75rem;font-weight:bold;vertical-align:middle;margin-left:10px;">TEST SİPARİŞİ</span>';
    }

    document.getElementById('m_name').innerText = c.fullName || '-';
    document.getElementById('m_phone').innerText = c.phone || '-';
    document.getElementById('m_email').innerText = c.email || '-';
    document.getElementById('m_address').innerText = c.address || '-';
    document.getElementById('m_city').innerText = (c.district || '') + ' / ' + (c.city || '');
    document.getElementById('m_note').innerText = c.note || '-';

    document.getElementById('m_status').innerText = order.status || '-';
    document.getElementById('m_sub').innerText = parseFloat(s.subtotal || 0).toLocaleString('tr-TR', {minimumFractionDigits:2});
    document.getElementById('m_ship').innerText = parseFloat(s.shippingFee || 0).toLocaleString('tr-TR', {minimumFractionDigits:2});
    document.getElementById('m_total').innerText = parseFloat(s.grandTotal || 0).toLocaleString('tr-TR', {minimumFractionDigits:2});

    // Banka Detayları
    let bankDiv = document.getElementById('m_bank_details');
    if (order.providerTransactionId) {
        bankDiv.style.display = 'block';
        document.getElementById('m_txn').innerText = order.providerTransactionId || '-';
        document.getElementById('m_prov').innerText = order.provisionNumber || '-';
        document.getElementById('m_rrn').innerText = order.rrn || '-';
        document.getElementById('m_stan').innerText = order.stan || '-';
    } else {
        bankDiv.style.display = 'none';
    }

    // Kargo Detayları Görüntüleme
    let cargoDiv = document.getElementById('m_cargo_details');
    if (order.orderStatus === 'shipped') {
        cargoDiv.style.display = 'block';
        document.getElementById('m_cargo_comp').innerText = order.cargoCompany || '-';
        document.getElementById('m_cargo_no').innerText = order.trackingNumber || '-';
        let link = document.getElementById('m_cargo_link');
        if (order.trackingUrl) {
            link.href = order.trackingUrl;
            link.style.display = 'inline';
        } else {
            link.style.display = 'none';
        }
    } else {
        cargoDiv.style.display = 'none';
    }

    // Güncelleme Formu Doldurma
    document.getElementById('f_orderId').value = order.orderId;
    document.getElementById('f_orderStatus').value = order.orderStatus || 'new';
    document.getElementById('f_cargoCompany').value = order.cargoCompany || '';
    document.getElementById('f_trackingNumber').value = order.trackingNumber || '';
    document.getElementById('f_trackingUrl').value = order.trackingUrl || '';
    toggleCargoFields(order.orderStatus || 'new');

    // Ödeme Durumu Uyarı ve Kısıtlama Mantığı (Faz 6B)
    let warningBanner = document.getElementById('paymentWarningBanner');
    let orderStatusSelect = document.getElementById('f_orderStatus');
    
    if (order.paymentStatus !== 'success') {
        warningBanner.style.display = 'block';
        // Ödeme başarılı değilse sadece 'cancelled' seçeneğine ve mevcut statüsüne izin ver
        for (let i = 0; i < orderStatusSelect.options.length; i++) {
            let opt = orderStatusSelect.options[i];
            if (opt.value !== 'cancelled' && opt.value !== order.orderStatus) {
                opt.disabled = true;
            } else {
                opt.disabled = false;
            }
        }
    } else {
        warningBanner.style.display = 'none';
        for (let i = 0; i < orderStatusSelect.options.length; i++) {
            orderStatusSelect.options[i].disabled = false;
        }
    }

    // Ürünler
    let itemsHtml = '';
    let items = order.items || [];
    items.forEach(i => {
        itemsHtml += `<tr>
            <td>${escapeHtml(i.name)}</td>
            <td>${parseInt(i.quantity)}</td>
            <td>₺${parseFloat(i.unitPrice||0).toLocaleString('tr-TR')}</td>
            <td>₺${parseFloat(i.lineTotal||0).toLocaleString('tr-TR')}</td>
        </tr>`;
    });
    document.getElementById('m_items').innerHTML = itemsHtml;

    // Mail Durumu
    let mStat = order.mailStatus || null;
    if (!mStat) {
        document.getElementById('m_mail_admin').innerHTML = '<span class="muted">Denenmedi</span>';
        document.getElementById('m_mail_cust').innerHTML = '<span class="muted">Denenmedi</span>';
        document.getElementById('m_mail_error').innerText = 'Yok';
    } else {
        document.getElementById('m_mail_admin').innerHTML = mStat.adminSent ? '<span style="color:green;font-weight:bold;">Gönderildi</span>' : '<span style="color:red">Gönderilmedi</span>';
        document.getElementById('m_mail_cust').innerHTML = mStat.customerSent ? '<span style="color:green;font-weight:bold;">Gönderildi</span>' : '<span style="color:red">Gönderilmedi</span>';
        document.getElementById('m_mail_error').innerText = mStat.error || 'Yok';
    }

    // Kargo Maili Durumu (Faz 5)
    let sStat = order.shippingMailStatus || null;
    if (!sStat) {
        if (order.orderStatus === 'shipped') {
            document.getElementById('m_mail_shipping').innerHTML = '<span style="color:orange;font-weight:bold;">Gönderilmedi</span>';
        } else {
            document.getElementById('m_mail_shipping').innerHTML = '<span class="muted">Gerekli Değil (Kargolanmadı)</span>';
        }
    } else {
        if (sStat.sent) {
            let sentDate = sStat.sentAt ? new Date(sStat.sentAt).toLocaleString('tr-TR') : '';
            document.getElementById('m_mail_shipping').innerHTML = `<span style="color:green;font-weight:bold;">Gönderildi</span> ${sentDate ? '<small class="muted">(' + escapeHtml(sentDate) + ')</small>' : ''}`;
        } else if (sStat.error) {
            document.getElementById('m_mail_shipping').innerHTML = `<span style="color:red;font-weight:bold;">Hata: ${escapeHtml(sStat.error)}</span>`;
        } else {
            document.getElementById('m_mail_shipping').innerHTML = '<span style="color:orange;font-weight:bold;">Gönderilmedi</span>';
        }
    }

    // Durum Geçmişi
    let historyHtml = '';
    let history = order.statusHistory || [];
    if (history.length > 0) {
        // En yeni üstte
        [...history].reverse().forEach(h => {
            let dateStr = h.updatedAt ? new Date(h.updatedAt).toLocaleString('tr-TR') : '-';
            historyHtml += `<div style="border-bottom:1px solid #eee; padding-bottom:5px; margin-bottom:5px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                    <span style="font-weight:bold; color:#374151;">${escapeHtml(h.label)}</span>
                    <span class="muted" style="font-size:0.75rem;">${escapeHtml(dateStr)}</span>
                </div>
                <div style="color:#6b7280;">${escapeHtml(h.note || 'Not girilmedi')}</div>
            </div>`;
        });
    } else {
        historyHtml = '<span class="muted">Henüz işlem geçmişi yok.</span>';
    }
    document.getElementById('m_history').innerHTML = historyHtml;

    // PDF Linki
    let pdfArea = document.getElementById('m_pdf_area');
    pdfArea.innerHTML = `<button type="button" class="btn" style="background:#4f46e5; color:white; width:100%; display:flex; align-items:center; justify-content:center; gap:8px;" onclick="printOrderProforma()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align:middle;">
            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
        </svg>
        PDF / Proforma Yazdır
    </button>`;

    // Bizim Hesap & Resmi Fatura İşlemleri (Faz 8C)
    let bh = order.bizimHesap || {};
    let bhStatus = bh.status || 'none';
    let payStatusVal = (order.paymentStatus || '').toLowerCase();
    let hasBilling = order.customer && order.customer.billing;

    // Set orderId in both forms
    document.getElementById('f_invoice_orderId').value = order.orderId || '';

    // Populate manual invoice fields
    document.getElementById('f_invoiceNumber').value = bh.invoiceNumber || '';
    document.getElementById('f_pdfUrl').value = bh.pdfUrl || '';
    
    let savedInfoEl = document.getElementById('m_invoice_saved_info');
    if (bh.invoiceInfoSavedAt) {
        let savedDate = new Date(bh.invoiceInfoSavedAt).toLocaleString('tr-TR');
        savedInfoEl.innerHTML = `<span style="color:#059669; font-weight:bold;">✔️ Kaydedildi (${escapeHtml(savedDate)})</span>`;
        if (bh.pdfUrl) {
            savedInfoEl.innerHTML += ` | <a href="${escapeHtml(bh.pdfUrl)}" target="_blank" style="color:#2563eb; font-weight:bold; text-decoration:underline;">Fatura PDF Görüntüle</a>`;
        } else {
            savedInfoEl.innerHTML += ` | <span class="muted">PDF linki girilmedi</span>`;
        }
    } else {
        savedInfoEl.innerText = '';
    }

    // Configure status & sync button
    let statusEl = document.getElementById('m_bh_status');
    let infoEl = document.getElementById('m_bh_info');
    let actionEl = document.getElementById('m_bh_action_area');

    if (payStatusVal !== 'success') {
        statusEl.innerHTML = '<span style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 9999px; font-weight: 600; font-size: 0.8rem;">Aktarılamaz</span>';
        infoEl.innerHTML = '<span style="color:#dc2626; font-weight:bold;">⚠️ Ödeme başarılı olmayan sipariş Bizim Hesap\'a aktarılamaz.</span>';
        actionEl.innerHTML = '<button class="btn" disabled style="background:#d1d5db; color:#9ca3af; cursor:not-allowed; font-size:0.85rem; padding:6px 12px; font-weight:bold;">🚀 Bizim Hesap\'a Aktar</button>';
    } else if (!hasBilling) {
        statusEl.innerHTML = '<span style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 9999px; font-weight: 600; font-size: 0.8rem;">Eksik Bilgi</span>';
        infoEl.innerHTML = '<span style="color:#dc2626; font-weight:bold;">⚠️ Fatura bilgisi eksik (Eski Sipariş). Aktarılamaz.</span>';
        actionEl.innerHTML = '<button class="btn" disabled style="background:#d1d5db; color:#9ca3af; cursor:not-allowed; font-size:0.85rem; padding:6px 12px; font-weight:bold;">🚀 Bizim Hesap\'a Aktar</button>';
    } else if (bhStatus === 'success') {
        statusEl.innerHTML = '<span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.5rem; border-radius: 9999px; font-weight: 600; font-size: 0.8rem;">Aktarıldı</span>';
        let syncDate = bh.syncedAt ? new Date(bh.syncedAt).toLocaleString('tr-TR') : '-';
        infoEl.innerHTML = `<span style="color:#059669; font-weight:bold;">✔️ Daha önce aktarıldı.</span><br><small class="muted">Bizim Hesap ID: <strong>${escapeHtml(bh.invoiceId)}</strong> | Zaman: ${escapeHtml(syncDate)}</small>`;
        actionEl.innerHTML = '<button class="btn" disabled style="background:#d1d5db; color:#9ca3af; cursor:not-allowed; font-size:0.85rem; padding:6px 12px; font-weight:bold;">🚀 Bizim Hesap\'a Aktar</button>';
    } else if (bhStatus === 'processing') {
        statusEl.innerHTML = '<span style="background: #fef08a; color: #854d0e; padding: 0.25rem 0.5rem; border-radius: 9999px; font-weight: 600; font-size: 0.8rem;">🔄 Aktarılıyor...</span>';
        infoEl.innerHTML = '<span class="muted">Sipariş şu anda Bizim Hesap API\'sine gönderiliyor...</span>';
        actionEl.innerHTML = '<button class="btn" disabled style="background:#d1d5db; color:#9ca3af; cursor:not-allowed; font-size:0.85rem; padding:6px 12px; font-weight:bold;">🚀 Aktarılıyor...</button>';
    } else if (bhStatus === 'failed') {
        statusEl.innerHTML = '<span style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 9999px; font-weight: 600; font-size: 0.8rem;">⚠️ Hata</span>';
        let syncDate = bh.syncedAt ? new Date(bh.syncedAt).toLocaleString('tr-TR') : '-';
        infoEl.innerHTML = `<span style="color:#dc2626; font-weight:bold;">Hata: ${escapeHtml(bh.error || 'Bilinmeyen API hatası')}</span><br><small class="muted">Son deneme: ${escapeHtml(syncDate)}</small>`;
        actionEl.innerHTML = `<form method="POST" action="admin-orders.php" style="margin:0;">
            <input type="hidden" name="action" value="sync_bizim_hesap">
            <input type="hidden" name="csrf_token" value="${escapeHtml(document.querySelector('input[name="csrf_token"]').value)}">
            <input type="hidden" name="orderId" value="${escapeHtml(order.orderId)}">
            <button type="submit" class="btn" style="background:#B12A8F; color:white; font-size:0.85rem; padding:6px 12px; font-weight:bold;" onclick="this.innerHTML='🔄 Aktarılıyor...'; this.disabled=true; this.form.submit();">🚀 Yeniden Aktarmayı Dene</button>
        </form>`;
    } else {
        statusEl.innerHTML = '<span style="background: #e2e8f0; color: #475569; padding: 0.25rem 0.5rem; border-radius: 9999px; font-weight: 600; font-size: 0.8rem;">Aktarılmadı</span>';
        infoEl.innerHTML = '<span class="muted">Sipariş henüz Bizim Hesap sistemine gönderilmedi.</span>';
        actionEl.innerHTML = `<form method="POST" action="admin-orders.php" style="margin:0;">
            <input type="hidden" name="action" value="sync_bizim_hesap">
            <input type="hidden" name="csrf_token" value="${escapeHtml(document.querySelector('input[name="csrf_token"]').value)}">
            <input type="hidden" name="orderId" value="${escapeHtml(order.orderId)}">
            <button type="submit" class="btn" style="background:#B12A8F; color:white; font-size:0.85rem; padding:6px 12px; font-weight:bold;" onclick="this.innerHTML='🔄 Aktarılıyor...'; this.disabled=true; this.form.submit();">🚀 Bizim Hesap'a Aktar</button>
        </form>`;
    }

    document.getElementById('orderModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('orderModal').style.display = 'none';
}

// Modal dışına tıklayınca kapatma
window.onclick = function(event) {
    let modal = document.getElementById('orderModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Kargo alanlarını göster/gizle
function toggleCargoFields(status) {
    document.getElementById('cargoFields').style.display = (status === 'shipped') ? 'block' : 'none';
}

// XSS koruması (JS tarafı)
function escapeHtml(unsafe) {
    return (unsafe || '').toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

// PDF / Proforma Yazdırma Denetimleri (Faz 7A)
window.currentOrder = null;

function printDirectProforma(btn) {
    let rawData = btn.getAttribute('data-order');
    let order = JSON.parse(rawData);
    triggerProformaPrint(order);
}

function printOrderProforma() {
    if (window.currentOrder) {
        triggerProformaPrint(window.currentOrder);
    }
}

function triggerProformaPrint(order) {
    let html = generateProformaHtml(order);
    let printWin = window.open('', '_blank');
    if (printWin) {
        printWin.document.open();
        printWin.document.write(html);
        printWin.document.close();
    } else {
        alert("Lütfen yeni sekmelerin açılmasına izin verin (Pop-up Engelleyiciyi devre dışı bırakın).");
    }
}

function generateProformaHtml(order) {
    let c = order.customer || {};
    let s = order.summary || {};
    let items = order.items || [];
    
    // Tarih alanları
    let date = order.paidAt || order.backend_createdAt || '';
    let dateStr = date ? new Date(date).toLocaleString('tr-TR') : '-';
    
    // Ödeme ve sipariş durumları
    let payStatus = (order.paymentStatus || '').toLowerCase();
    let isPaid = payStatus === 'success';
    let payStatusText = isPaid ? 'Başarılı (Kuveyt Türk)' : 'Ödeme Bekliyor / Yarım Kalan';
    
    let statusText = 'Yeni';
    if (order.orderStatus) {
        let labels = {
            'new': 'Yeni',
            'preparing': 'Hazırlanıyor',
            'shipped': 'Kargoya Verildi',
            'completed': 'Tamamlandı',
            'cancelled': 'İptal'
        };
        statusText = labels[order.orderStatus] || order.orderStatus;
    }
    
    // Ödeme başarısız ise büyük kırmızı uyarı banner'ı
    let warningBanner = '';
    if (!isPaid) {
        warningBanner = '<div class="warning-banner">⚠️ DİKKAT: BU SİPARİŞİN ÖDEMESİ TAMAMLANMAMIŞTIR. BU BELGE SEVK/FATURA YERİNE KULLANILAMAZ.</div>';
    }
    
    // Ürün tablosu satırları
    let itemsHtml = '';
    items.forEach(i => {
        let name = escapeHtml(i.name || '-');
        let qty = parseInt(i.quantity || 0);
        let unit = parseFloat(i.unitPrice || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 });
        let total = parseFloat(i.lineTotal || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 });
        itemsHtml += '<tr>' +
            '<td>' + name + '</td>' +
            '<td style="text-align: center;">' + qty + '</td>' +
            '<td style="text-align: right;">₺' + unit + '</td>' +
            '<td style="text-align: right;">₺' + total + '</td>' +
        '</tr>';
    });
    
    // Kuveyt Türk Referansları
    let bankHtml = '';
    if (order.providerTransactionId) {
        bankHtml = '<div class="section-title">Kuveyt Türk Referansları</div>' +
            '<table class="info-table">' +
                '<tr>' +
                    '<td style="width: 25%;"><strong>İşlem ID (Txn ID):</strong></td>' +
                    '<td style="width: 25%;">' + escapeHtml(order.providerTransactionId || '-') + '</td>' +
                    '<td style="width: 25%;"><strong>Provizyon No:</strong></td>' +
                    '<td style="width: 25%;">' + escapeHtml(order.provisionNumber || '-') + '</td>' +
                '</tr>' +
                '<tr>' +
                    '<td><strong>RRN:</strong></td>' +
                    '<td>' + escapeHtml(order.rrn || '-') + '</td>' +
                    '<td><strong>STAN:</strong></td>' +
                    '<td>' + escapeHtml(order.stan || '-') + '</td>' +
                '</tr>' +
            '</table>';
    }
    
    // Kargo Takip Bilgileri
    let cargoText = 'Henüz girilmedi';
    if (order.orderStatus === 'shipped') {
        cargoText = escapeHtml(order.cargoCompany || '-') + ' - Takip No: ' + escapeHtml(order.trackingNumber || '-');
        if (order.trackingUrl) {
            cargoText += ' (' + escapeHtml(order.trackingUrl) + ')';
        }
    }
    
    let html = '<!DOCTYPE html>' +
'<html lang="tr">' +
'<head>' +
    '<meta charset="UTF-8">' +
    '<title>Sipariş Proforma Bilgi Formu - ' + escapeHtml(order.orderId || '') + '</title>' +
    '<style>' +
        'body {' +
            'font-family: Arial, sans-serif;' +
            'color: #333;' +
            'margin: 0;' +
            'padding: 20px;' +
            'font-size: 12px;' +
            'line-height: 1.4;' +
        '}' +
        '.header {' +
            'display: flex;' +
            'justify-content: space-between;' +
            'align-items: center;' +
            'border-bottom: 2px solid #B12A8F;' +
            'padding-bottom: 15px;' +
            'margin-bottom: 20px;' +
        '}' +
        '.logo {' +
            'font-size: 24px;' +
            'font-weight: bold;' +
            'color: #6B2C83;' +
            'letter-spacing: 0.5px;' +
        '}' +
        '.company-info {' +
            'text-align: right;' +
            'font-size: 11px;' +
            'color: #666;' +
        '}' +
        '.title {' +
            'text-align: center;' +
            'font-size: 16px;' +
            'font-weight: bold;' +
            'margin-bottom: 20px;' +
            'color: #1F1B2E;' +
            'text-transform: uppercase;' +
            'letter-spacing: 0.5px;' +
        '}' +
        '.warning-banner {' +
            'background-color: #fee2e2;' +
            'border: 2px solid #f87171;' +
            'color: #b91c1c;' +
            'padding: 10px;' +
            'border-radius: 4px;' +
            'font-weight: bold;' +
            'text-align: center;' +
            'margin-bottom: 20px;' +
            'font-size: 11px;' +
        '}' +
        '.section-title {' +
            'font-size: 12px;' +
            'font-weight: bold;' +
            'background: #f3f4f6;' +
            'padding: 6px 10px;' +
            'margin-top: 15px;' +
            'margin-bottom: 8px;' +
            'border-left: 3px solid #B12A8F;' +
            'color: #1F1B2E;' +
            'text-transform: uppercase;' +
        '}' +
        '.info-table {' +
            'width: 100%;' +
            'border-collapse: collapse;' +
            'margin-bottom: 15px;' +
        '}' +
        '.info-table td {' +
            'padding: 5px 8px;' +
            'vertical-align: top;' +
        '}' +
        '.items-table {' +
            'width: 100%;' +
            'border-collapse: collapse;' +
            'margin-bottom: 15px;' +
        '}' +
        '.items-table th, .items-table td {' +
            'border: 1px solid #e5e7eb;' +
            'padding: 6px 10px;' +
        '}' +
        '.items-table th {' +
            'background: #f9fafb;' +
            'font-weight: bold;' +
            'text-align: left;' +
        '}' +
        '.totals-table {' +
            'width: 40%;' +
            'margin-left: auto;' +
            'border-collapse: collapse;' +
            'margin-bottom: 20px;' +
        '}' +
        '.totals-table td {' +
            'padding: 5px 8px;' +
            'border-bottom: 1px solid #eee;' +
        '}' +
        '.totals-table tr.grand-total td {' +
            'font-weight: bold;' +
            'font-size: 13px;' +
            'border-bottom: 2px double #333;' +
        '}' +
        '.footer {' +
            'margin-top: 40px;' +
            'border-top: 1px solid #ddd;' +
            'padding-top: 10px;' +
            'text-align: center;' +
            'font-size: 10px;' +
            'color: #777;' +
        '}' +
        '@media print {' +
            'body {' +
                'padding: 0;' +
            '}' +
            '.no-print {' +
                'display: none;' +
            '}' +
        '}' +
    '</style>' +
'</head>' +
'<body>' +
    warningBanner +
    '<div class="header">' +
        '<div class="logo">RAWLABS</div>' +
        '<div class="company-info">' +
            '<strong>1453 İstanbul Gıda Sanayi ve Ticaret Limited Şirketi</strong><br>' +
            'www.rawlabs.com.tr | bilgi@rawlabs.com.tr<br>' +
            'Mevlana Mah. Yunus Emre Cad. No:60 İç Kapı No: A<br>' +
            'GEBZE / KOCAELİ' +
        '</div>' +
    '</div>' +
    
    '<div class="title">Sipariş / Proforma Bilgi Formu</div>' +
    
    '<div class="section-title">Sipariş & Teslimat Bilgileri</div>' +
    '<table class="info-table">' +
        '<tr>' +
            '<td style="width: 20%;"><strong>Sipariş No:</strong></td>' +
            '<td style="width: 30%;">' + escapeHtml(order.orderId || '-') + '</td>' +
            '<td style="width: 20%;"><strong>Müşteri Adı:</strong></td>' +
            '<td style="width: 30%;"><strong>' + escapeHtml(c.fullName || '-') + '</strong></td>' +
        '</tr>' +
        '<tr>' +
            '<td><strong>Sipariş Tarihi:</strong></td>' +
            '<td>' + escapeHtml(dateStr) + '</td>' +
            '<td><strong>Telefon:</strong></td>' +
            '<td>' + escapeHtml(c.phone || '-') + '</td>' +
        '</tr>' +
        '<tr>' +
            '<td><strong>Ödeme Durumu:</strong></td>' +
            '<td>' + escapeHtml(payStatusText) + '</td>' +
            '<td><strong>E-posta:</strong></td>' +
            '<td>' + escapeHtml(c.email || '-') + '</td>' +
        '</tr>' +
        '<tr>' +
            '<td><strong>Sipariş Durumu:</strong></td>' +
            '<td>' + escapeHtml(statusText) + '</td>' +
            '<td><strong>Teslimat Adresi:</strong></td>' +
            '<td>' +
                escapeHtml(c.address || '-') + '<br>' +
                '<strong>' + escapeHtml(c.district || '-') + ' / ' + escapeHtml(c.city || '-') + '</strong>' +
            '</td>' +
        '</tr>' +
        '<tr>' +
            '<td><strong>Kargo Bilgisi:</strong></td>' +
            '<td>' + cargoText + '</td>' +
            '<td><strong>Müşteri Notu:</strong></td>' +
            '<td>' + escapeHtml(c.note || '-') + '</td>' +
        '</tr>' +
    '</table>' +
    
    '<div class="section-title">Sipariş Edilen Ürünler</div>' +
    '<table class="items-table">' +
        '<thead>' +
            '<tr>' +
                '<th style="width: 50%;">Ürün Adı</th>' +
                '<th style="width: 10%; text-align: center;">Adet</th>' +
                '<th style="width: 20%; text-align: right;">Birim Fiyat</th>' +
                '<th style="width: 20%; text-align: right;">Toplam</th>' +
            '</tr>' +
        '</thead>' +
        '<tbody>' +
            itemsHtml +
        '</tbody>' +
    '</table>' +
    
    '<table class="totals-table">' +
        '<tr>' +
            '<td>Ara Toplam:</td>' +
            '<td style="text-align: right;">₺' + parseFloat(s.subtotal || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + '</td>' +
        '</tr>' +
        '<tr>' +
            '<td>Kargo Ücreti:</td>' +
            '<td style="text-align: right;">₺' + parseFloat(s.shippingFee || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + '</td>' +
        '</tr>' +
        '<tr class="grand-total">' +
            '<td>Genel Toplam:</td>' +
            '<td style="text-align: right;">₺' + parseFloat(s.grandTotal || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + '</td>' +
        '</tr>' +
    '</table>' +
    
    bankHtml +
    
    '<div class="footer">' +
        'Bu belge e-fatura veya mali belge değildir. Sipariş/proforma bilgilendirme amacıyla oluşturulmuştur.' +
    '</div>' +
    
    '<script>' +
        'window.onload = function() {' +
            'window.print();' +
        '};' +
    '<\/script>' +
'</body>' +
'</html>';

    return html;
}
</script>
</body>
</html>
