<?php
session_start();
if (!isset($_SESSION['agent'])) { header("Location: login.php"); exit; }
include 'include/header.php';
?>

<div class="download-page">
    <div class="download-header">
        <h1>📲 Application Florametrics</h1>
        <p>Emportez votre outil de suivi des ruches partout avec vous, même sans connexion internet.</p>
    </div>

    <div class="download-card">
        <div class="os-icon">🤖</div>
        <h2>Version Android</h2>
        <p>Version actuelle : <strong>1.1.19</strong></p>
        <p>Dernière mise à jour : <?= date('d/m/Y') ?></p>
        
        <!-- Remplacez 'app-release.apk' par le nom réel de votre fichier stocké sur le serveur -->
        <a href="florametrics.apk" class="btn btn-primary btn-large" download>
            📥 Télécharger l'APK
        </a>
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