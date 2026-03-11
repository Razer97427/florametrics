<?php
// On récupère le nom du fichier actuel (ex: index.php)
$page_actuelle = basename($_SERVER['PHP_SELF']); 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Florametrics - Coccinelle</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .logo a { text-decoration: none; color: inherit; }
        .logo a:hover { opacity: 0.8; }
    </style>
</head>
<body>
<header>
    <div class="brand">
        <a href="index.php" class="brand-link">
            <img src="ressources/logo.png" alt="Florametrics" class="brand-img">
        </a>
    </div>
    <nav>
        <?php if(isset($_SESSION['agent'])): ?>
            <a href="index.php" class="<?= ($page_actuelle == 'index.php') ? 'active' : '' ?>">Mes Ruches</a>
            
            <a href="download.php" class="nav-admin <?= ($page_actuelle == 'download.php') ? 'active' : '' ?>">Télécharger mon application</a>
            
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                <a href="manage_users.php" class="nav-admin <?= ($page_actuelle == 'manage_users.php') ? 'active' : '' ?>">Gérer les utilisateurs</a>
            <?php endif; ?>
            
            <a href="logout.php">Déconnexion</a>
        <?php endif; ?>
    </nav>
</header>
<div class="container">