<?php
/**
 * Rawlabs - Siparişlerim Endpoint'i
 * Sadece giriş yapmış kullanıcının kendi siparişlerini döndürür.
 */
require_once __DIR__ . '/auth-helpers.php';
$config = [];
if (file_exists(__DIR__ . '/config.php')) {
    $config = require_once __DIR__ . '/config.php';
}
$storagePath = defined('ORDER_STORAGE_PATH') ? ORDER_STORAGE_PATH : (isset($config['ORDER_STORAGE_PATH']) ? $config['ORDER_STORAGE_PATH'] : __DIR__ . '/orders/');

try {
    // Sadece GET veya POST
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Geçersiz istek.'], 405);
    }

    $user = getLoggedInUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.'], 401);
    }

    $userId = $user['id'];
    $userOrdersPath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . 'user-orders.json';
    
    $orderIds = [];
    if (file_exists($userOrdersPath)) {
        $fp = @fopen($userOrdersPath, 'r');
        if ($fp) {
            flock($fp, LOCK_SH);
            $content = stream_get_contents($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            
            $allOrders = json_decode($content, true);
            if (is_array($allOrders) && isset($allOrders[$userId])) {
                $orderIds = $allOrders[$userId];
            }
        }
    }

    $myOrders = [];
    foreach ($orderIds as $orderId) {
        $orderFilePath = rtrim($storagePath, '/\\') . DIRECTORY_SEPARATOR . $orderId . '.json';
        if (file_exists($orderFilePath)) {
            $content = @file_get_contents($orderFilePath);
            if ($content) {
                $orderData = json_decode($content, true);
                // Double check: userId eşleşiyor mu?
                if (isset($orderData['userId']) && $orderData['userId'] === $userId) {
                    
                    // Güvenli özet çıkar (hassas müşteri bilgilerini filtrele)
                    $summary = [
                        'orderId' => $orderData['orderId'],
                        'createdAt' => $orderData['backend_createdAt'] ?? '',
                        'status' => $orderData['status'] ?? 'unknown',
                        'grandTotal' => $orderData['summary']['grandTotal'] ?? 0,
                        'currency' => $orderData['summary']['currency'] ?? 'TRY',
                        'itemCount' => count($orderData['items'] ?? []),
                    ];

                    // Ürün özetini de ekleyelim (İlk ürün + n ürün daha)
                    $items = $orderData['items'] ?? [];
                    $itemSummaryStr = '';
                    if (!empty($items)) {
                        $firstItem = $items[0]['name'] ?? 'Ürün';
                        if (count($items) > 1) {
                            $itemSummaryStr = $firstItem . ' ve ' . (count($items) - 1) . ' ürün daha';
                        } else {
                            $itemSummaryStr = $firstItem;
                        }
                    }
                    $summary['itemSummary'] = $itemSummaryStr;

                    $myOrders[] = $summary;
                }
            }
        }
    }

    // Tarihe göre yeniden eskiye sırala
    usort($myOrders, function($a, $b) {
        return strtotime($b['createdAt']) - strtotime($a['createdAt']);
    });

    jsonResponse(['success' => true, 'orders' => $myOrders]);

} catch (Throwable $e) {
    error_log("Rawlabs Sipariş Çekme Hatası: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Siparişler getirilirken bir hata oluştu.'], 500);
}
