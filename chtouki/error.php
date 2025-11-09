<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Échouée</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .error-icon {
            width: 80px;
            height: 80px;
            background: #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .error-icon i {
            font-size: 40px;
            color: white;
        }
        .title {
            color: #dc3545;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .message {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .reference {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            font-family: monospace;
            color: #333;
        }
        .btn-retry {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
            margin-right: 10px;
        }
        .btn-retry:hover {
            background: #0056b3;
        }
        .btn-home {
            display: inline-block;
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn-home:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-times"></i>
        </div>
        <div class="title">Transaction Échouée</div>
        <div class="message">
            Désolé, votre demande de remboursement n'a pas pu être traitée.<br>
            Veuillez réessayer ou contacter notre service client.
        </div>
        <?php if (isset($_SESSION['transaction_id'])): ?>
        <div class="reference">
            Référence: <?php echo htmlspecialchars($_SESSION['transaction_id']); ?>
        </div>
        <?php endif; ?>
        <div>
            <a href="index.php" class="btn-retry">
                <i class="fas fa-redo"></i> Réessayer
            </a>
            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i> Accueil
            </a>
        </div>
    </div>
</body>
</html> 