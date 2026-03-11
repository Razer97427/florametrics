<?php
session_start();
if (!isset($_SESSION['agent'])) { header("Location: login.php"); exit; }

// 2. Logique de téléchargement sécurisé (Proxy PHP)
// Si l'URL contient ?action=get_apk, on sert le fichier
if (isset($_GET['action']) && $_GET['action'] === 'get_apk') {
    
    // Chemin vers le fichier SÉCURISÉ (idéalement hors du dossier public 'www' ou 'public_html')
    // Exemple : si votre site est dans /var/www/html, mettez l'APK dans /var/www/files
    $file_path = 'florametrics.apk'; 
    
    // Si vous ne pouvez pas sortir du dossier public, utilisez un nom de dossier complexe 
    // et protégez-le avec un .htaccess ("Deny from all")
    // $file_path = 'dossier_secret_x9s8/florametrics.apk';

    if (file_exists($file_path)) {
        // On vide les tampons de sortie pour ne pas corrompre le fichier binaire
        if (ob_get_level()) ob_end_clean();
        
        // On force le navigateur à télécharger le fichier
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.android.package-archive');
        header('Content-Disposition: attachment; filename="florametrics_v1.1.22.apk"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        
        // On lit le fichier et on l'envoie au navigateur
        readfile($file_path);
        exit;
    } else {
        die("Erreur : Le fichier d'installation est introuvable sur le serveur.");
    }
}

include 'include/header.php';
?>

<div class="download-page">
    <div class="download-header">
        <h1>📲 Application Florametrics</h1>
        <p>Emportez votre outil de suivi des ruches partout avec vous, même sans connexion internet.</p>
    </div>

    <div class="download-card">
        <!-- <div class="os-icon">🤖</div> -->
        <div class="os-icon"><img src="ressources/android.svg" alt="Android" /></div>
        <h2>Version Android</h2>
        <p>Version actuelle : <strong>1.1.22</strong></p>
        <p>Dernière mise à jour : <?= date('d/m/Y') ?></p>
        
        <!-- Remplacez 'votreapp.apk' par le nom réel de votre fichier stocké sur le serveur -->
        <!-- <a href="florametrics.apk" class="btn btn-primary btn-large" download> -->
            <a href="download.php?action=get_apk" class="btn btn-primary btn-large">
            📥 Télécharger l'APK
        </a>
    </div>

    <div class="download-card">
        <!-- <div class="os-icon">🤖</div> -->
        <div class="os-icon"aria-label="iOS">
            <img src="ressources/ios.svg" alt="iOS" />
        </div>

        <h2>Version iOS</h2>
        <a class="btn btn-disabled" aria-disabled="true" tabindex="-1">
        Aucune version iOS disponible actuellement...</a>
        <!-- <p>Version actuelle : <strong>1.1.20</strong></p>
        <p>Dernière mise à jour : <?= date('d/m/Y') ?></p> -->
        
        <!-- Remplacez 'votreapp.apk' par le nom réel de votre fichier stocké sur le serveur -->
        <!-- <a href="florametrics.apk" class="btn btn-primary btn-large" download>
            📥 Télécharger l'APK
        </a> -->
    </div>

    <div class="instructions">
        <h3>Comment installer l'application ?</h3>
        <ol>
            <li>Cliquez sur le bouton <strong>Télécharger</strong> ci-dessus.</li>
            <li>Si votre téléphone affiche un avertissement, allez dans les <strong>Paramètres</strong>.</li>
            <li>Autorisez l'installation depuis <strong>cette source</strong> (ou "Sources inconnues").</li>
            <li>Ouvrez le fichier téléchargé et cliquez sur <strong>Installer</strong>.</li>
        </ol>
        <div class="alert-info">
            ℹ️ L'application n'est pas sur le Play Store ? C'est normal, il s'agit d'une application professionnelle interne.
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>