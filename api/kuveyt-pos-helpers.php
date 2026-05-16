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
    $hashData = base64_encode(sha1($encodedHashStr, true));

    if (defined('KUVEYT_DEBUG') && KUVEYT_DEBUG === true) {
        $okDomain = parse_url($okUrl, PHP_URL_HOST) ?? 'bilinmiyor';
        $failDomain = parse_url($failUrl, PHP_URL_HOST) ?? 'bilinmiyor';
        $pwdLen = strlen($password);
        $hPwdLen = strlen($hashedPassword);
        
        error_log("Kuveyt Türk Debug [Request 1 Hash]: MerchantId=$merchantId, MerchantOrderId=$merchantOrderId, Amount=$amount, OkDomain=$okDomain, FailDomain=$failDomain, UserName=$userName, PasswordLength=$pwdLen, HashedPwdLength=$hPwdLen");
        error_log("Kuveyt Türk Debug [Request 1 HashString]: ToplamUzunluk=" . strlen($hashString) . ", AlanSirasi: MerchantId+MerchantOrderId+Amount+OkUrl+FailUrl+UserName+HashedPassword");
    }

    return $hashData;
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

function kuveytHashResponse1FromHashPassword($merchantOrderId, $responseCode, $orderId, $hashPassword) {
    $hashString = $merchantOrderId . $responseCode . $orderId . $hashPassword;
    $encodedHashStr = mb_convert_encoding($hashString, 'ISO-8859-9', 'UTF-8');
    return base64_encode(sha1($encodedHashStr, true));
}

function kuveytVerifyResponse1Hash($merchantOrderId, $responseCode, $orderId, $receivedHash, $password, $bankHashPassword = null) {
    if (empty($receivedHash)) return false;
    
    if (!empty($bankHashPassword)) {
        if (defined('KUVEYT_DEBUG') && KUVEYT_DEBUG === true) {
            error_log("Kuveyt Türk Debug: Hash doğrulaması bankanın VPosMessage.HashPassword değeri kullanılarak yapılıyor.");
        }
        $expectedHash = kuveytHashResponse1FromHashPassword($merchantOrderId, $responseCode, $orderId, $bankHashPassword);
    } else {
        if (defined('KUVEYT_DEBUG') && KUVEYT_DEBUG === true) {
            error_log("Kuveyt Türk Debug: Hash doğrulaması yerel KUVEYT_PASSWORD hash'i kullanılarak yapılıyor (Fallback).");
        }
        $expectedHash = kuveytHashResponse1($merchantOrderId, $responseCode, $orderId, $password);
    }
    
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
        error_log("Kuveyt Türk Sunucu Bağlantı Hatası: " . $error);
        throw new Exception("Kuveyt Türk Sunucu Bağlantı Hatası: " . $error);
    }
    
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("Banka sunucusu HTTP hatası döndürdü. Kod: " . $httpCode);
        throw new Exception("Banka sunucusu HTTP hatası döndürdü. Kod: " . $httpCode);
    }
    
    return $response;
}

function kuveytSafeDebugStringAnalysis($value, $label = 'String Analizi') {
    if (!defined('KUVEYT_DEBUG') || KUVEYT_DEBUG !== true) return;

    $isEmpty = empty($value) ? 'Evet' : 'Hayır';
    $len = strlen($value ?? '');
    $trimLen = strlen(trim($value ?? ''));
    
    $first20 = substr(trim($value ?? ''), 0, 20);
    $type = 'Bilinmiyor';
    if (strpos($first20, '<') === 0) $type = 'XML';
    elseif (stripos($first20, '%3c') !== false) $type = 'URL Encoded XML';
    elseif (strpos($first20, '&lt;') !== false) $type = 'HTML Entity XML';
    elseif (preg_match('/^[a-zA-Z0-9+\/]+={0,2}$/', $first20) && $len > 50) $type = 'Base64-like';

    $hasVPos = strpos($value ?? '', '<VPosMessage') !== false ? 'Evet' : 'Hayır';
    $hasRespCode = strpos($value ?? '', '<ResponseCode') !== false ? 'Evet' : 'Hayır';
    $hasLtEntity = strpos($value ?? '', '&lt;') !== false ? 'Evet' : 'Hayır';
    $hasUrlLt = stripos($value ?? '', '%3c') !== false ? 'Evet' : 'Hayır';
    $hasAuthRespStr = strpos($value ?? '', 'AuthenticationResponse') !== false ? 'Evet' : 'Hayır';

    error_log("Kuveyt Türk Debug [$label]: Boş=$isEmpty, Uzunluk=$len, TrimUzunluk=$trimLen, Tür=$type, VPosVar=$hasVPos, RespCodeVar=$hasRespCode, LtEntityVar=$hasLtEntity, UrlLtVar=$hasUrlLt, AuthRespStrVar=$hasAuthRespStr");
}

function kuveytNormalizeAuthenticationResponse($value) {
    if (empty($value)) return '';
    $value = trim($value);
    
    // "AuthenticationResponse=" gibi bir prefix varsa temizle
    if (strpos($value, 'AuthenticationResponse=') === 0) {
        $value = substr($value, strlen('AuthenticationResponse='));
    }
    
    // BOM temizliği
    $value = preg_replace('/^[\xef\xbb\xbf]+/', '', $value);
    
    // HTML entity decode
    if (strpos($value, '&lt;') !== false || strpos($value, '&gt;') !== false) {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
    
    // URL decode (Çift decode'dan kaçınmak için şarta bağlı)
    if (strpos($value, '<') === false && stripos($value, '%3c') !== false) {
        $value = rawurldecode($value);
    }
    
    // Hala XML gibi başlamıyorsa son bir entity decode dene
    if (strpos($value, '<') === false) {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
    
    return $value;
}

function kuveytParseXml($xmlString) {
    if (empty($xmlString)) return null;
    
    $xmlString = trim($xmlString);
    
    // Kontrol karakterlerini temizle (tab, newline hariç)
    $xmlString = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $xmlString);
    
    $hasDeclaration = false;
    // XML declaration'ı her yerden temizle (Sadece başta olmayabilir)
    if (preg_match('/<\?xml[^>]+>/i', $xmlString)) {
        $xmlString = preg_replace('/<\?xml[^>]+>/i', '', $xmlString);
        $xmlString = trim($xmlString);
        $hasDeclaration = true;
    }

    // 1. Güvenli Yapısal Analiz
    $invalidAmpersandCount = preg_match_all('/&(?!amp;|lt;|gt;|quot;|apos;|#\d+;|#x[0-9a-fA-F]+;)/', $xmlString);
    $openingTagCount = preg_match_all('/<[a-zA-Z0-9_:-]+[^>]*>/', $xmlString);
    $closingTagCount = preg_match_all('/<\/[a-zA-Z0-9_:-]+>/', $xmlString);
    
    $firstTagName = 'Bulunamadı';
    if (preg_match('/<([a-zA-Z0-9_:-]+)/', $xmlString, $matches)) {
        $firstTagName = $matches[1];
    }

    if (defined('KUVEYT_DEBUG') && KUVEYT_DEBUG === true) {
        $hasResponseCode = strpos($xmlString, '<ResponseCode') !== false ? 'Evet' : 'Hayır';
        $hasResponseMessage = strpos($xmlString, '<ResponseMessage') !== false ? 'Evet' : 'Hayır';
        $hasHashData = strpos($xmlString, '<HashData') !== false ? 'Evet' : 'Hayır';
        $hasMD = strpos($xmlString, '<MD') !== false ? 'Evet' : 'Hayır';
        $hasVPosMessage = strpos($xmlString, '<VPosMessage') !== false ? 'Evet' : 'Hayır';

        error_log("Kuveyt Türk Debug [XML Yapı Analizi]: IlkTag=$firstTagName, AcilisTag=$openingTagCount, KapanisTag=$closingTagCount, GecersizAmpersand=$invalidAmpersandCount, RespCode=$hasResponseCode, RespMsg=$hasResponseMessage, HashData=$hasHashData, MD=$hasMD, VPos=$hasVPosMessage");
    }

    // 2. Invalid Ampersand Düzeltmesi
    $invalidAmpersandFixed = 'Hayır';
    $fixedAmpersandCount = 0;
    if ($invalidAmpersandCount > 0) {
        $xmlString = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;|#\d+;|#x[0-9a-fA-F]+;)/', '&amp;', $xmlString, -1, $fixedAmpersandCount);
        $invalidAmpersandFixed = 'Evet';
    }
    
    if (defined('KUVEYT_DEBUG') && KUVEYT_DEBUG === true) {
        error_log("Kuveyt Türk Debug [Ampersand Düzeltme]: Uygulandı=$invalidAmpersandFixed, Adet=$fixedAmpersandCount");
    }
    
    libxml_use_internal_errors(true);
    // Güvenlik: XXE saldırılarını engellemek için LIBXML_NONET kullanılıyor
    $xml = @simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NONET);
    
    $usedWrapper = false;
    $errorCount1 = 0;
    
    if ($xml === false) {
        $errors = libxml_get_errors();
        $errorCount1 = count($errors);
        if ($errorCount1 > 0) {
            $err = $errors[0];
            error_log(sprintf("Kuveyt Türk Hata: libxml Hata 1 [Code: %d, Level: %d, Line: %d, Col: %d]", $err->code, $err->level, $err->line, $err->column));
        }
        libxml_clear_errors();
        
        $wrappedXml = '<KuveytTurkResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . "\n" . $xmlString . "\n" . '</KuveytTurkResponse>';
        
        $xml = @simplexml_load_string($wrappedXml, 'SimpleXMLElement', LIBXML_NONET);
        $usedWrapper = true;
        
        if ($xml === false) {
            $errors2 = libxml_get_errors();
            if (count($errors2) > 0) {
                $err2 = $errors2[0];
                error_log(sprintf("Kuveyt Türk Hata: libxml Hata 2 [Code: %d, Level: %d, Line: %d, Col: %d]", $err2->code, $err2->level, $err2->line, $err2->column));
            }
            error_log("Kuveyt Türk Hata: XML Parse (Wrapper ile de) başarısız. Libxml hata sayısı: " . count($errors2));
            libxml_clear_errors();
            
            // 3. Fallback Parse: Regex Extractor
            $response = [];
            $response['_root'] = $firstTagName;
            
            $fieldsToExtract = ['ResponseCode', 'ResponseMessage', 'MerchantOrderId', 'OrderId', 'TransactionSecurity', 'BusinessKey', 'Stan', 'RRN', 'HashData', 'MD'];
            foreach ($fieldsToExtract as $field) {
                if (preg_match("/<$field>(.*?)<\/$field>/is", $xmlString, $valMatches)) {
                    $response[$field] = trim($valMatches[1]);
                }
            }
            
            $safeLogStr = "";
            foreach (['ResponseCode', 'ResponseMessage'] as $f) {
                $safeLogStr .= "$f=" . ($response[$f] ?? 'Yok') . ", ";
            }
            $safeLogStr .= "HashData=" . (isset($response['HashData']) ? 'Var' : 'Yok') . ", ";
            $safeLogStr .= "MD=" . (isset($response['MD']) ? 'Var' : 'Yok');
            
            error_log("Kuveyt Türk Hata [Regex Fallback Parser]: " . rtrim($safeLogStr, ', '));
            return $response;
        }
    }
    
    if (defined('KUVEYT_DEBUG') && KUVEYT_DEBUG === true) {
        error_log("Kuveyt Türk Debug: XML Parse başarılı. WrapperDendi=$usedWrapper, DeclarationTemizlendi=$hasDeclaration, IlkHataSayisi=$errorCount1");
    }
    
    $json = json_encode($xml);
    return json_decode($json, true);
}
