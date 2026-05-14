<?php
/**
 * Banka Sanal POS Webhook / Callback Endpoint'i
 * Bankadan dönen 3D Secure veya direkt ödeme onayı yanıtlarını yakalamak için iskelet.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek.");
}

// TODO: Banka API entegrasyonu sağlandığında aşağıdaki işlemler buraya eklenecektir:
// 1. $_POST üzerinden gelen Hash / Signature doğrulaması
// 2. İşlem başarılı ise orderId üzerinden JSON'u bulup status='paid' yapmak
// 3. İşlem başarısız ise status='failed' yapmak
// 4. Başarılı ise mailer.php'yi çağırıp müşteri & admin'e e-posta göndermek
// 5. Uygun bir şekilde payment-success.php veya payment-failed.php'ye yönlendirme (3D dönüş tipine göre)

echo "Callback Endpoint Hazır.";
