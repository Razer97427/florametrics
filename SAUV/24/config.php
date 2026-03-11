<?php
// --- Configuration Base de Données ---
define('DB_NAME', 'terracoonzroot');
define('DB_USER', 'terracoonzroot');
define('DB_PASSWORD', 'Excalibur250AVI');
define('DB_HOST', 'terracoonzroot.mysql.db:3306');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('PASS_ADMIN_TOTP', 'admin');

// --- Configuration API Token ---
// Il faut generer un token fort avec cette commande: bin2hex(random_bytes(32)) sous linux Ou avec openssl sous linux: openssl rand -hex 32
define('API_TOKEN', '9c6f922d9d85dd34e51358b3eea1cbc07745eb06de113d3c4feba0108b7e31c3');

// Connexion à la base de données MySQL
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
try {
    // Crée une instance de PDO (PHP Data Objects) pour la connexion
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASSWORD);
    
    // Configure PDO pour qu'il lance des exceptions en cas d'erreur SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Si la connexion échoue, affiche un message d'erreur et arrête le script
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>