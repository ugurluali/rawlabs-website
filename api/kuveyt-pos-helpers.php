<?php
/**
 * Kuveyt Türk Sanal POS Helper Fonksiyonları
 * Faz 3C: 3D Secure Model
 */

function kuveytHashPassword($password) {
    // PHP'de dokümandaki base64_encode(sha1($pwd, "ISO-8859-9")) mantığını uygulamak için:
    $encodedPwd = mb_convert_encoding($password, 'ISO-8859-9', 'UTF-8');
    return base64_encode(sha1($encodedPwd, true)); // true ile binary hash alınıyor
}

function kuveytHashRequest1($merchantId, $merchantOrderId, $amount, $okUrl, $failUrl, $userName, $password) {
    $hashedPassword = kuveytHashPassword($password);
    $hashString = $merchantId . $merchantOrderId . $amount . $okUrl . $failUrl . $userName . $hashedPassword;
    $encodedHashStr = mb_convert_encoding($hashString, 'ISO-8859-9', 'UTF-8');
    return base64_encode(sha1($encodedHashStr, true));
}

function kuveytHashRequest2($merchantId, $merchantOrderId, $amount, $userName, $password) {
    $hashedPassword = kuveytHashPassword($password);
    $hashString = $merchantId . $merchantOrderId . $amount . $userName . $hashedPassword;
    $encodedHashStr = mb_convert_encoding($hashString, 'ISO-8859-9', 'UTF-8');
    return base64_encode(sha1($encodedHashStr, true));
}

// Response 1 (Authentication) Hash Doğrulama
function kuveytHashResponse1($merchantOrderId, $responseCode, $orderId, $password) {
    $hashedPassword = kuveytHashPassword($password);
    $hashString = $merchantOrderId . $responseCode . $orderId . $hashedPassword;
    $encodedHashStr = mb_convert_encoding($hashString, 'ISO-8859-9', 'UTF-8');
    return base64_encode(sha1($encodedHashStr, true));
}

function kuveytVerifyResponse1Hash($merchantOrderId, $responseCode, $orderId, $receivedHash, $password) {
    if (empty($receivedHash)) return false;
    $expectedHash = kuveytHashResponse1($merchantOrderId, $responseCode, $orderId, $password);
    return hash_equals($expectedHash, $receivedHash);
}

// TODO: Response 2 (Provision) Hash doğrulaması için banka dokümantasyonunda belirtilen alanlar kontrol edilerek eklenebilir.

function kuveytXmlEscape($value) {
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function kuveytPostXml($url, $xmlString) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/xml; charset=utf-8',
        'Content-Length: ' . strlen($xmlString)
    ]);
    
    // Güvenlik: Canlı ve Test ortamı için SSL doğrulaması aktif (Zorunlu)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Kuveyt Türk Sunucu Bağlantı Hatası: " . $error);
    }
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Banka sunucusu HTTP hatası döndürdü. Kod: " . $httpCode);
    }
    
    return $response;
}

function kuveytParseXml($xmlString) {
    if (empty($xmlString)) return null;
    // Güvenlik: XXE saldırılarını engellemek için LIBXML_NONET kullanılıyor
    $xml = @simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NONET);
    if ($xml === false) return null;
    $json = json_encode($xml);
    return json_decode($json, true);
}
