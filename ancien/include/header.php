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
    <div class="logo"><a href="index.php">🐞 FLORAMETRICS</a></div>
    <nav>
        <?php if(isset($_SESSION['agent'])): ?>
            <a href="index.php">Mes Ruches</a>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                <a href="manage_users.php" class="nav-admin">Gérer les utilisateurs</a>
            <?php endif; ?>
            <a href="logout.php">Déconnexion</a>
        <?php endif; ?>
    </nav>
</header>
<div class="container">