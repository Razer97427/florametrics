<?php

date_default_timezone_set('Indian/Reunion');

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


// On récupère l'état de la maintenance
$result = $conn->query("SELECT status FROM maintenance_site LIMIT 1");
$row = $result->fetch_assoc();

// 1. On charge les IPs autorisées depuis le fichier .ini
$config = parse_ini_file('authorized_ips.ini', true);
$allowed_ips = $config['maintenance']['allow_ips'] ?? [];
// 2. On récupère l'IP du visiteur actuel
$user_ip = $_SERVER['REMOTE_ADDR'];


// On vérifie si maintenance est à true (1)
if ($row && $row['status'] == 1) {
    // Optionnel : Autoriser ton IP pour ne pas être bloqué toi-même
    // if ($_SERVER['REMOTE_ADDR'] !== 'TON_IP_PUBLIQUE') {
    if (in_array($user_ip, $allowed_ips)) {
        echo "<p style='text-align: center; color: white; font-weight: bold; background: red'>Mode maintenance actif, mais accès autorisé pour votre IP : $user_ip<br>N'oubliez pas de passer 'maintenance_site' a faux apres modifications.</p>";
    } else {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        // echo "<h1>Site en Maintenance</h1>";
        // echo "<p>FloraMetrics revient très vite !</p>";
        echo <<<HTML
        <div style="font-family: sans-serif; text-align: center; padding: 150px; background: #f4f4f4; height: 100vh;">
        <h1 style="font-size: 50px; color: #333;">Site en Maintenance</h1>
        <p style="font-size: 20px; color: #666;">Nous effectuons actuellement des mises à jour pour améliorer votre expérience.</p>
        <div style="margin-top: 20px; border-top: 2px solid #007bff; width: 100px; display: inline-block;"></div>
        <p style="margin-top: 20px; font-weight: bold;">Revenez nous voir bientôt !</p>
        </div>
        HTML;
        exit(); // On arrête tout ici
        
    // }
    }

} else {
    // echo "en mode test maintenance = 0";
}


?>