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
        
        // --- Matematiksel Tutarlılık Hesaplaması ---
        $discountVal = (float)($summary['discount'] ?? 0);
        $shippingVal = (float)($summary['shippingFee'] ?? 0);
        $grandTotalVal = (float)($summary['grandTotal'] ?? 0);
        
        // Ara toplamı belirle (İndirim öncesi tutar)
        // Eğer JSON'da subtotal varsa onu kullan, yoksa grandTotal + discount - shipping formülüyle bul
        $subtotal = $summary['subtotal'] ?? $summary['subtotalAmount'] ?? ($grandTotalVal + $discountVal - $shippingVal);

        // Ürün tablosu için oran hesaplama (Eğer ürün birim fiyatları indirimliyse orijinale çevir)
        $currentItemsTotal = 0;
        foreach ($items as $item) {
            $currentItemsTotal += (float)($item['lineTotal'] ?? 0);
        }

        // Eğer ürünlerin toplamı ara toplamdan farklıysa (yani ürünler indirimli gelmişse) oran uygula
        $ratio = ($currentItemsTotal > 0 && abs($currentItemsTotal - $subtotal) > 0.01) ? ($subtotal / $currentItemsTotal) : 1;

        $fSubtotal = number_format($subtotal, 2, ',', '.');
        $fDiscount = $discountVal > 0 ? '-₺' . number_format($discountVal, 2, ',', '.') : '-';
        $fShipping = $shippingVal > 0 ? '₺' . number_format($shippingVal, 2, ',', '.') : 'Ücretsiz';
        $fGrandTotal = number_format($grandTotalVal, 2, ',', '.');

        // HTML Tablo Satırları (Ürünler - Orijinal/İndirimsiz Fiyatlar)
        $itemsHtml = '';
        foreach ($items as $item) {
            $name = htmlspecialchars($item['name'] ?? 'Bilinmeyen Ürün', ENT_QUOTES, 'UTF-8');
            $qty = (int)($item['quantity'] ?? 1);
            
            // Orijinal birim fiyat ve satır toplamını oranla hesapla
            $origLineTotal = (float)($item['lineTotal'] ?? 0) * $ratio;
            $origUnitPrice = $qty > 0 ? ($origLineTotal / $qty) : 0;

            $price = number_format($origUnitPrice, 2, ',', '.');
            $lineTotal = number_format($origLineTotal, 2, ',', '.');
            
            $itemsHtml .= "
                <tr>
                    <td style='border-bottom: 1px solid #eee; padding: 10px 8px; color: #444;'>{$name}</td>
                    <td style='border-bottom: 1px solid #eee; padding: 10px 8px; text-align: center; color: #444;'>{$qty}</td>
                    <td style='border-bottom: 1px solid #eee; padding: 10px 8px; text-align: right; color: #444;'>₺{$price}</td>
                    <td style='border-bottom: 1px solid #eee; padding: 10px 8px; text-align: right; font-weight: bold; color: #2d3748;'>₺{$lineTotal}</td>
                </tr>
            ";
        }

        // --- HTML Şablonu (Tek Sayfa Düzeni İçin Optimize Edildi) ---
        $html = "
        <html>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
            <style>
                @page { margin: 30px; }
                body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.4; margin: 0; }
                .container { padding: 0; }
                .header { border-bottom: 2px solid #9C27B0; padding-bottom: 15px; margin-bottom: 20px; }
                .logo { font-size: 24px; font-weight: bold; color: #9C27B0; }
                .title { font-size: 16px; color: #2d3748; font-weight: bold; float: right; margin-top: 5px; }
                .info-box { background: #f9f9f9; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
                .info-table { width: 100%; }
                .info-table td { padding: 3px 0; vertical-align: top; }
                .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .items-table th { background-color: #9C27B0; color: #ffffff; padding: 10px 8px; text-align: left; font-size: 13px; }
                .items-table th.right { text-align: right; }
                .items-table th.center { text-align: center; }
                .totals-box { width: 35%; float: right; margin-top: 10px; }
                .totals-table { width: 100%; border-collapse: collapse; }
                .totals-table td { padding: 6px 0; border-bottom: 1px solid #eee; }
                .totals-table td.label { font-weight: bold; color: #555; }
                .totals-table td.value { text-align: right; }
                .totals-table tr.grand-total td { font-size: 14px; font-weight: bold; color: #9C27B0; border-bottom: none; border-top: 2px solid #9C27B0; padding-top: 10px; }
                .footer-note { text-align: center; margin-top: 40px; font-size: 10px; color: #888; border-top: 1px dashed #ccc; padding-top: 10px; clear: both; position: relative; bottom: 0; }
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
                            <td width='45%'>
                                <strong>Sipariş No:</strong> {$orderNumber}<br>
                                <strong>Tarih:</strong> {$orderDate}
                            </td>
                            <td width='55%'>
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
                            <td class='label'>Ara Toplam:</td>
                            <td class='value'>₺{$fSubtotal}</td>
                        </tr>
                        <tr>
                            <td class='label'>İndirim:</td>
                            <td class='value'>{$fDiscount}</td>
                        </tr>
                        <tr>
                            <td class='label'>Kargo Ücreti:</td>
                            <td class='value'>{$fShipping}</td>
                        </tr>
                        <tr class='grand-total'>
                            <td class='label'>GENEL TOPLAM:</td>
                            <td class='value'>₺{$fGrandTotal}</td>
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
