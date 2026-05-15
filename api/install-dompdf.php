<?php
/**
 * Rawlabs Dompdf Otomatik Kurulum Scripti (Composer Olmayan Ortamlar İçin)
 * Bu dosya, cPanel üzerinde çalıştırılarak Dompdf kütüphanesini Github üzerinden indirip 'vendor/dompdf' klasörüne kurar.
 * 
 * ÖNEMLİ: Bu dosya sunucuda sadece BİR KEZ çalıştırılmalı ve kurulum tamamlandıktan sonra 
 * GÜVENLİĞİNİZ İÇİN sunucudan derhal SİLİNMELİDİR.
 */

set_time_limit(300);

echo "<h2>Rawlabs PDF Kütüphanesi (Dompdf) Kurulumu</h2>";

if (!class_exists('ZipArchive')) {
    die("<b style='color:red'>Hata:</b> PHP ZipArchive eklentisi sunucunuzda yüklü değil. Kurulum yapılamıyor. Lütfen cPanel'den Zip eklentisini aktif edin.");
}

$vendorDir = __DIR__ . '/vendor';
$dompdfDir = $vendorDir . '/dompdf';

if (is_dir($dompdfDir)) {
    die("<b style='color:green'>Dompdf zaten kurulu!</b> (Dizin: $dompdfDir)<br>PDF sipariş formlarınız sorunsuz çalışacaktır. Bu dosyayı (install-dompdf.php) silebilirsiniz.");
}

// Dompdf son stabil sürüm ZIP URL'si (3.0.0)
$zipUrl = 'https://github.com/dompdf/dompdf/releases/download/v3.0.0/dompdf_3-0-0.zip';
$zipFile = __DIR__ . '/dompdf_temp.zip';

echo "1. Dompdf indiriliyor (Lütfen bekleyin)...<br>";
$zipContent = @file_get_contents($zipUrl);

if ($zipContent === false) {
    die("<b style='color:red'>Hata:</b> Github'dan kütüphane indirilemedi. Sunucunuzda file_get_contents URL izinleri kapalı olabilir.");
}

if (file_put_contents($zipFile, $zipContent) === false) {
    die("<b style='color:red'>Hata:</b> İndirilen ZIP dosyası api/ klasörüne yazılamadı. Klasör yazma izinlerini (CHMOD 755) kontrol edin.");
}
echo "<span style='color:green'>İndirme tamamlandı.</span><br>";

echo "2. ZIP arşivi çıkartılıyor...<br>";
$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    if (!is_dir($vendorDir)) {
        mkdir($vendorDir, 0755, true);
    }
    
    // ZIP'in içi doğrudan dompdf klasörü barındırır.
    $zip->extractTo($vendorDir);
    $zip->close();
    
    // Geçici dosyayı sil
    unlink($zipFile);

    // Dompdf içindeki autoload.php'yi proje standart klasörüne çekelim
    if (!file_exists($vendorDir . '/autoload.php')) {
        // Zip'ten çıkan autoload'u direkt vendor/autoload.php olarak yansıtalım (standart composer simülasyonu)
        $dompdfAutoload = $vendorDir . '/dompdf/autoload.inc.php';
        if (file_exists($dompdfAutoload)) {
            $autoloadStub = "<?php\n// Otomatik oluşturulan autoload (Dompdf için)\nrequire_once __DIR__ . '/dompdf/autoload.inc.php';\n";
            file_put_contents($vendorDir . '/autoload.php', $autoloadStub);
        }
    }

    echo "<h3>🎉 Kurulum Başarılı!</h3>";
    echo "<p>Dompdf başarıyla <b>api/vendor/dompdf</b> dizinine kuruldu ve autoload hazırlandı.</p>";
    echo "<p>Ödeme akışı başarıyla gerçekleştiğinde artık PDF'ler <b>api/order-pdfs/</b> içine düşecektir.</p>";
    echo "<p style='color:red'><b>GÜVENLİK UYARISI:</b> Lütfen bu dosyayı (install-dompdf.php) sunucunuzdan derhal <b>SİLİN</b>.</p>";

} else {
    echo "<b style='color:red'>Hata:</b> ZIP dosyası açılamadı.";
}
