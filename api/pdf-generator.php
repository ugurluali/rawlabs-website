<?php
/**
 * Rawlabs Sipariş PDF Oluşturucu
 * Dompdf kullanarak sipariş verisini şık bir PDF'ye çevirir.
 */

function generateOrderPdf($orderData, $orderNumber, $pdfStoragePath) {
    // 1. Kütüphane kontrolü (Eğer composer veya manuel kurulum yapılmışsa)
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        // Hata fırlatma ki ödeme başarılı sonucu bozulmasın, sadece hata loglansın.
        error_log("Rawlabs PDF Hatası: vendor/autoload.php bulunamadı. Lütfen Dompdf kurulumunu yapın.");
        return false;
    }

    require_once $autoloadPath;

    if (!class_exists('Dompdf\Dompdf')) {
        error_log("Rawlabs PDF Hatası: Dompdf sınıfı bulunamadı.");
        return false;
    }

    try {
        // PDF Seçeneklerini Ayarla
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Harici font veya resim yüklemek için (logo vb)
        $options->set('defaultFont', 'DejaVu Sans'); // Türkçe karakter desteği için zorunlu font

        $dompdf = new \Dompdf\Dompdf($options);

        // Tarih formatlama
        $orderDate = isset($orderData['backend_createdAt']) ? date('d.m.Y H:i', strtotime($orderData['backend_createdAt'])) : date('d.m.Y H:i');
        
        $customer = $orderData['customer'] ?? [];
        $items = $orderData['items'] ?? [];
        $summary = $orderData['summary'] ?? [];

        // Güvenli veriler (XSS ve kırılmalara karşı)
        $cName = htmlspecialchars($customer['fullName'] ?? '-', ENT_QUOTES, 'UTF-8');
        $cPhone = htmlspecialchars($customer['phone'] ?? '-', ENT_QUOTES, 'UTF-8');
        $cEmail = htmlspecialchars($customer['email'] ?? '-', ENT_QUOTES, 'UTF-8');
        $cAddress = htmlspecialchars(($customer['address'] ?? '') . ' ' . ($customer['district'] ?? '') . '/' . ($customer['city'] ?? ''), ENT_QUOTES, 'UTF-8');
        
        $total = number_format((float)($summary['grandTotal'] ?? 0), 2, ',', '.');
        $shipping = isset($summary['shippingFee']) && $summary['shippingFee'] > 0 ? '₺' . number_format($summary['shippingFee'], 2, ',', '.') : 'Ücretsiz';
        $discount = isset($summary['discount']) && $summary['discount'] > 0 ? '₺' . number_format($summary['discount'], 2, ',', '.') : '-';

        // HTML Tablo Satırları (Ürünler)
        $itemsHtml = '';
        foreach ($items as $item) {
            $name = htmlspecialchars($item['name'] ?? 'Bilinmeyen Ürün', ENT_QUOTES, 'UTF-8');
            $qty = (int)($item['quantity'] ?? 1);
            $price = number_format((float)($item['unitPrice'] ?? 0), 2, ',', '.');
            $lineTotal = number_format((float)($item['lineTotal'] ?? 0), 2, ',', '.');
            
            $itemsHtml .= "
                <tr>
                    <td style='border-bottom: 1px solid #eee; padding: 12px 8px; color: #444;'>{$name}</td>
                    <td style='border-bottom: 1px solid #eee; padding: 12px 8px; text-align: center; color: #444;'>{$qty}</td>
                    <td style='border-bottom: 1px solid #eee; padding: 12px 8px; text-align: right; color: #444;'>₺{$price}</td>
                    <td style='border-bottom: 1px solid #eee; padding: 12px 8px; text-align: right; font-weight: bold; color: #2d3748;'>₺{$lineTotal}</td>
                </tr>
            ";
        }

        // --- HTML Şablonu (Fuşya/Mor Marka Kurumsal Renkleri) ---
        // Rawlabs Primary: #9C27B0, Secondary: #2d3748
        $html = "
        <html>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
            <style>
                body { font-family: 'DejaVu Sans', sans-serif; font-size: 13px; color: #333; line-height: 1.5; }
                .container { padding: 20px; }
                .header { border-bottom: 3px solid #9C27B0; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 28px; font-weight: bold; color: #9C27B0; }
                .title { font-size: 18px; color: #2d3748; font-weight: bold; float: right; margin-top: 5px; }
                .info-box { background: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 30px; }
                .info-table { width: 100%; }
                .info-table td { padding: 5px 0; vertical-align: top; }
                .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                .items-table th { background-color: #9C27B0; color: #ffffff; padding: 12px 8px; text-align: left; font-size: 14px; }
                .items-table th.right { text-align: right; }
                .items-table th.center { text-align: center; }
                .totals-box { width: 40%; float: right; }
                .totals-table { width: 100%; border-collapse: collapse; }
                .totals-table td { padding: 8px 0; border-bottom: 1px solid #eee; }
                .totals-table td.label { font-weight: bold; color: #555; }
                .totals-table td.value { text-align: right; }
                .totals-table tr.grand-total td { font-size: 16px; font-weight: bold; color: #9C27B0; border-bottom: none; border-top: 2px solid #9C27B0; padding-top: 12px; }
                .footer-note { text-align: center; margin-top: 60px; font-size: 11px; color: #888; border-top: 1px dashed #ccc; padding-top: 15px; clear: both; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <span class='logo'>RAWLABS</span>
                    <span class='title'>SİPARİŞ FORMU</span>
                </div>

                <div class='info-box'>
                    <table class='info-table'>
                        <tr>
                            <td width='50%'>
                                <strong>Sipariş No:</strong> {$orderNumber}<br>
                                <strong>Tarih:</strong> {$orderDate}
                            </td>
                            <td width='50%'>
                                <strong>Müşteri:</strong> {$cName}<br>
                                <strong>Telefon:</strong> {$cPhone}<br>
                                <strong>E-posta:</strong> {$cEmail}<br>
                                <strong>Adres:</strong> {$cAddress}
                            </td>
                        </tr>
                    </table>
                </div>

                <table class='items-table'>
                    <thead>
                        <tr>
                            <th>Ürün Adı</th>
                            <th class='center' width='10%'>Adet</th>
                            <th class='right' width='20%'>Birim Fiyat</th>
                            <th class='right' width='20%'>Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>

                <div class='totals-box'>
                    <table class='totals-table'>
                        <tr>
                            <td class='label'>Kargo Ücreti:</td>
                            <td class='value'>{$shipping}</td>
                        </tr>
                        <tr>
                            <td class='label'>İndirim:</td>
                            <td class='value'>{$discount}</td>
                        </tr>
                        <tr class='grand-total'>
                            <td class='label'>GENEL TOPLAM:</td>
                            <td class='value'>₺{$total}</td>
                        </tr>
                    </table>
                </div>

                <div class='footer-note'>
                    Bu belge e-fatura veya e-arşiv fatura yerine geçmez. Sadece bilgi amaçlı sipariş bilgilendirme formudur.<br>
                    Bizi tercih ettiğiniz için teşekkür ederiz! | rawlabs.com.tr
                </div>
            </div>
        </body>
        </html>
        ";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        // PDF'i diske yaz (api/order-pdfs/)
        $filePath = rtrim($pdfStoragePath, '/') . '/' . $orderNumber . '.pdf';
        
        if (file_put_contents($filePath, $pdfOutput) === false) {
            error_log("Rawlabs PDF Hatası: Dosya yazılamadı -> " . $filePath);
            return false;
        }

        return true;

    } catch (Throwable $e) {
        // PDF üretim hatalarını logla, ama akışı bozma
        error_log("Rawlabs PDF Üretim Hatası: " . $e->getMessage());
        return false;
    }
}
