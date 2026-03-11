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



// Récupération des headers pour le token
$headers = getallheaders();
$admin_token = $headers['X-Admin-Token'] ?? "";

// 3. Tes conditions de vérification
$is_ip_ok = in_array($user_ip, $allowed_ips);
$is_token_ok = ($admin_token === "jesuisadmin");

// // 2. On vérifie si LE header spécifique contient TON token
// if (isset($headers['X-Admin-Token']) && $headers['X-Admin-Token'] === "jesuisladminleplusfort") {
//     echo "<p style='background:green; color:white; padding:10px;'>Ton token fonctionne ! Accès admin accordé.</p>";
//     // Ici, on ne fait pas de exit(), donc le reste du site s'affiche normalement
// }

// On vérifie si maintenance est à true (1)
if ($row && $row['status'] == 1) {
    // Optionnel : Autoriser ton IP pour ne pas être bloqué toi-même
    // if ($_SERVER['REMOTE_ADDR'] !== 'TON_IP_PUBLIQUE') {
    // if (in_array($user_ip, $allowed_ips)) {
    //     echo "<p style='text-align: center; color: white; font-weight: bold; background: red'>Mode maintenance actif, mais accès autorisé pour votre IP : $user_ip<br>N'oubliez pas de passer 'maintenance_site' a faux apres modifications.</p>";
    // } else {


        if ($is_ip_ok || $is_token_ok) {
        
        // On définit le message selon ce qui a été détecté
        if ($is_ip_ok && $is_token_ok) {
            $methode = "ton IP ($user_ip) ET ton Header Token";
        } elseif ($is_token_ok) {
            $methode = "ton Header Token uniquement";
        } else {
            $methode = "ton IP ($user_ip) uniquement";
        }

        echo "<p style='text-align: center; color: white; font-weight: bold; background: #a72828; margin:0; padding: 10px; font-family: sans-serif;'>
                ⚠️ Maintenance active — Accès autorisé via $methode.<br>N'oubliez pas de désactiver la maintenance en BDD.
              </p>";
              
    } else {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        // echo "<h1>Site en Maintenance</h1>";
        // echo "<p>FloraMetrics revient très vite !</p>";
        // echo <<<HTML
        // <div style="font-family: sans-serif; text-align: center; padding: 150px; background: #f4f4f4; height: 100vh;">
        // <h1 style="font-size: 50px; color: #333;">Site en Maintenance</h1>
        // <p style="font-size: 20px; color: #666;">Nous effectuons actuellement des mises à jour pour améliorer votre expérience.</p>
        // <div style="margin-top: 20px; border-top: 2px solid #007bff; width: 100px; display: inline-block;"></div>
        // <p style="margin-top: 20px; font-weight: bold;">Revenez nous voir bientôt !</p>
        // </div>
        // HTML;
        // exit(); // On arrête tout ici


        echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site en Maintenance</title>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #0d1f0d, #1a3a1a, #0f4020); /* ✅ vert foncé */
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    overflow: hidden;
}

.bg-circles {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    overflow: hidden;
    z-index: 0;
}

.bg-circles li {
    position: absolute;
    display: block;
    list-style: none;
    width: 20px;
    height: 20px;
    background: rgba(0, 200, 80, 0.15); /* ✅ cercles verts */
    border-radius: 50%;
    animation: float 25s linear infinite;
    bottom: -150px;
}

.bg-circles li:nth-child(1)  { left: 25%;  width: 80px;  height: 80px;  animation-delay: 0s;   animation-duration: 20s; }
.bg-circles li:nth-child(2)  { left: 10%;  width: 20px;  height: 20px;  animation-delay: 2s;   animation-duration: 25s; }
.bg-circles li:nth-child(3)  { left: 70%;  width: 20px;  height: 20px;  animation-delay: 4s;   animation-duration: 18s; }
.bg-circles li:nth-child(4)  { left: 40%;  width: 60px;  height: 60px;  animation-delay: 0s;   animation-duration: 22s; }
.bg-circles li:nth-child(5)  { left: 65%;  width: 20px;  height: 20px;  animation-delay: 0s;   animation-duration: 30s; }
.bg-circles li:nth-child(6)  { left: 75%;  width: 110px; height: 110px; animation-delay: 3s;   animation-duration: 20s; }
.bg-circles li:nth-child(7)  { left: 35%;  width: 150px; height: 150px; animation-delay: 7s;   animation-duration: 28s; }
.bg-circles li:nth-child(8)  { left: 50%;  width: 25px;  height: 25px;  animation-delay: 15s;  animation-duration: 35s; }
.bg-circles li:nth-child(9)  { left: 20%;  width: 15px;  height: 15px;  animation-delay: 2s;   animation-duration: 40s; }
.bg-circles li:nth-child(10) { left: 85%;  width: 150px; height: 150px; animation-delay: 0s;   animation-duration: 22s; }

@keyframes float {
    0%   { transform: translateY(0) rotate(0deg);         opacity: 1; border-radius: 50%; }
    100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; border-radius: 50%; }
}

.container {
    position: relative;
    z-index: 1;
    text-align: center;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    padding: 60px 50px;
    max-width: 600px;
    width: 90%;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
}

.gear-wrapper {
    margin-bottom: 30px;
    position: relative;
    display: inline-block;
}

.gear {
    font-size: 80px;
    display: inline-block;
    animation: spin 6s linear infinite;
    filter: drop-shadow(0 0 12px rgba(0, 200, 80, 0.6)); /* ✅ ombre verte */
}

.gear-small {
    font-size: 40px;
    display: inline-block;
    animation: spin-reverse 4s linear infinite;
    position: absolute;
    bottom: 0;
    right: -20px;
}

@keyframes spin         { from { transform: rotate(0deg); }   to { transform: rotate(360deg); } }
@keyframes spin-reverse { from { transform: rotate(0deg); }   to { transform: rotate(-360deg); } }

h1 {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 15px;
    background: linear-gradient(90deg, #00c853, #69f0ae); /* ✅ titre vert */
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.subtitle {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.65);
    line-height: 1.7;
    margin-bottom: 35px;
}

.progress-label {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 8px;
    text-align: left;
}

.progress-bar {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50px;
    height: 8px;
    overflow: hidden;
    margin-bottom: 30px;
}

.progress-fill {
    height: 100%;
    width: 0%;
    border-radius: 50px;
    background: linear-gradient(90deg, #00c853, #69f0ae); /* ✅ barre verte */
    animation: progress 4s ease-in-out forwards;
    box-shadow: 0 0 10px rgba(0, 200, 80, 0.5); /* ✅ ombre verte */
}

@keyframes progress {
    0%   { width: 0%; }
    100% { width: 57%; }
}

.divider {
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #00c853, #69f0ae); /* ✅ séparateur vert */
    border-radius: 2px;
    margin: 0 auto 30px auto;
}

.info-grid {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-bottom: 35px;
}

.info-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.info-icon {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.info-text {
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.5);
}

.btn {
    display: inline-block;
    padding: 14px 36px;
    background: linear-gradient(135deg, #00c853, #69f0ae); /* ✅ bouton vert */
    color: #fff;
    font-size: 0.95rem;
    font-weight: 600;
    border-radius: 50px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 6px 20px rgba(0, 200, 80, 0.4); /* ✅ ombre verte */
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(0, 200, 80, 0.55); /* ✅ ombre hover verte */
}

.footer {
    margin-top: 40px;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.3);
}

@media (max-width: 480px) {
    .container { padding: 40px 24px; }
    h1 { font-size: 1.6rem; }
    .gear { font-size: 60px; }
    .info-grid { gap: 18px; }
}
    </style>
</head>
<body>

    <!-- Particules de fond -->
    <ul class="bg-circles">
        <li></li><li></li><li></li><li></li><li></li>
        <li></li><li></li><li></li><li></li><li></li>
    </ul>

    <!-- Contenu principal -->
    <div class="container">

        <div class="gear-wrapper">
            <span class="gear">⚙️</span>
            <span class="gear-small">⚙️</span>
        </div>

        <h1>Site en Maintenance</h1>

        <p class="subtitle">
            Nous effectuons actuellement des mises à jour importantes<br>
            pour améliorer votre expérience. Merci de votre patience.
        </p>

        <div class="divider"></div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-icon">🔧</span>
                <span class="info-text">Mise à jour</span>
            </div>
            <div class="info-item">
                <span class="info-icon">🔒</span>
                <span class="info-text">Sécurité renforcée</span>
            </div>
            <div class="info-item">
                <span class="info-icon">⚡</span>
                <span class="info-text">Performances</span>
            </div>
            <div class="info-item">
                <span class="info-icon">🧰</span>
                <span class="info-text">Nouvelles fonctionnalitées</span>
            </div>
        </div>

        <p class="progress-label">Progression des travaux…</p>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>

        <a href="mailto:service.informatique@terracoop.re" class="btn">📩 Nous contacter</a>

        <div class="footer">
            &copy; 2026 - Florametrics — Tous droits réservés
        </div>

    </div>

</body>
</html>
HTML;
exit();
        
    // }
    }

} else {
    // echo "en mode test maintenance = 0";
}


?>