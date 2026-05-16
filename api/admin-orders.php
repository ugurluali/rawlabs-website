<?php
/**
 * Rawlabs Sipariş Takip Paneli
 * Sadece okuma yetkisi vardır. JSON dosyalarındaki verileri listeler.
 */

session_start();

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

// Çıkış işlemi
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin-orders.php");
    exit;
}

// Giriş işlemi
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (password_verify($_POST['password'], $adminHash)) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin-orders.php");
        exit;
    } else {
        sleep(1); // Brute force koruması
        $error = 'Hatalı şifre!';
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
                            
                            $data['orderStatus'] = $newStatus;
                            $data['updatedAt'] = date('c');
                            
                            if ($newStatus === 'shipped') {
                                $data['cargoCompany'] = trim(strip_tags($_POST['cargoCompany'] ?? ''));
                                $data['trackingNumber'] = trim(strip_tags($_POST['trackingNumber'] ?? ''));
                                $data['trackingUrl'] = trim(strip_tags($_POST['trackingUrl'] ?? ''));
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
        'cancelled' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'text' => 'İptal']
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
    </style>
</head>
<body>

<div class="header">
    <h1>Sipariş Takip Paneli</h1>
    <a href="?action=logout" class="btn btn-logout">Çıkış Yap</a>
</div>

<?= $updateMsg ?>

<input type="text" id="searchInput" class="search-box" placeholder="Sipariş no, isim veya e-posta ara...">

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
                <th>Tip</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
            <tr><td colspan="8" style="text-align: center; padding: 2rem;">Sipariş bulunamadı.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o): 
                    $date = $o['paidAt'] ?? $o['backend_createdAt'] ?? '';
                    $dateStr = $date ? date('d.m.Y H:i', strtotime($date)) : '-';
                    $total = number_format((float)($o['summary']['grandTotal'] ?? 0), 2, ',', '.');
                    $userType = empty($o['userId']) ? '<span class="muted">Misafir</span>' : '<span style="color:#2563eb;font-weight:bold;">Üye</span>';
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
                <tr>
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
                    <td><?= getOrderStatusBadge($o['orderStatus'] ?? 'new') ?></td>
                    <td>
                        <?= esc($o['paymentStatus'] ?? '-') ?><br>
                        <?php if(!empty($o['provider'])): ?>
                            <small class="muted"><?= esc($o['provider']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= $userType ?></td>
                    <td><button class="btn btn-view" onclick='openModal(this)' data-order='<?= $jsonAttr ?>'>Detay</button></td>
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
                <p><strong>Mail Hatası:</strong> <span id="m_mail_error" class="muted">Yok</span></p>
            </div>
            <div class="detail-section">
                <h3>Fatura / PDF</h3>
                <div id="m_pdf_area">Bekleniyor...</div>
            </div>
        </div>
        
        <div class="detail-section" style="background:#f9fafb; padding:15px; border-radius:6px; border:1px solid #e5e7eb; margin-top:20px;">
            <h3>Siparişi Güncelle</h3>
            <form method="POST" action="admin-orders.php">
                <input type="hidden" name="action" value="update_order">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="orderId" id="f_orderId" value="">
                
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
// Arama Filtresi
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#ordersTable tbody tr');
    
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Modal Kontrolleri
function openModal(btn) {
    let rawData = btn.getAttribute('data-order');
    let order = JSON.parse(rawData);
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
    pdfArea.innerHTML = '<span class="muted">PDF/Fatura entegrasyonu sonraki fazda aktif edilecek.</span>';

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
</script>
</body>
</html>
