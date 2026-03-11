<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Configuration
define('DB_NAME', 'terracoonzroot');
define('DB_USER', 'terracoonzroot');
define('DB_PASSWORD', 'Excalibur250AVI');
define('DB_HOST', 'terracoonzroot.mysql.db');

// function log_access($login) {
//     $file = __DIR__ . '/access_log.txt';
//     $date = date('Y-m-d H:i:s');
//     $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'; // Récupère le type de navigateur/appareil
//     $user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
//     // Le format de la ligne : Date | IP | Méthode | Navigateur
//     // On ajoute le LOGIN pour savoir qui se connecte
//     $log_entry = "[$date] LOGIN: $login | IP: $user_ip | Navigateur: $agent" . PHP_EOL;
    
//     // Écrit dans le fichier (FILE_APPEND permet de ne pas effacer le contenu précédent)
//     $result = file_put_contents($file, $log_entry, FILE_APPEND);

//     if ($result === false) {
//         // SI CA ECHOUE : On arrête tout et on affiche pourquoi
//         $error = error_get_last();
//         echo json_encode([
//             "status" => "debug_error",
//             "message" => "L'ecriture a echoue",
//             "raison" => $error['message'] ?? 'Permission denied ou chemin incorrect',
//             "chemin_tente" => $file,
//             "dossier_ecritable" => is_writable(__DIR__) ? "OUI" : "NON"
//         ]);
//         exit;
//     }
// }

try {
    // Connexion PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Données reçues
    $login = $_REQUEST['login'] ?? '';
    $password = $_REQUEST['password'] ?? '';
    $d_connexion = $_REQUEST['d_connexion'] ?? '';

    // Vérifier champs
    if (empty($login) || empty($password)) {
        echo json_encode([
            "status" => "error",
            "message" => "Champs manquants.",
            "code" => "300"
        ]);
        exit;
    }

    // Récupération de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT password, nomcomplet, status 
        FROM florametrics 
        WHERE login = :login
    ");
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1 — Vérifier si le login existe
    if (!$user) {
        echo json_encode([
            "status" => "error",
            "message" => "Login incorrect.",
            "code" => "301"
        ]);
        exit;
    }

    // 2 — Vérifier si le compte est actif
    if ($user['status'] !== 'A') {
        echo json_encode([
            "status" => "error",
            "message" => "Compte désactivé.",
            "code" => "302",
            "etat" => $user['status']
        ]);
        exit;
    }

    // 3 — Vérifier le mot de passe
    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Mot de passe incorrect.",
            "code" => "301",
            "etat" => $user['status']
        ]);
        exit;
    }

    // 4 — Mot de passe correct → mise à jour date connexion
    if (!empty($d_connexion)) {
        $upd = $pdo->prepare("UPDATE florametrics SET d_connexion = :d WHERE login = :login");
        $upd->execute(['d' => $d_connexion, 'login' => $login]);
    } else {
        $upd = $pdo->prepare("UPDATE florametrics SET d_connexion = NOW() WHERE login = :login");
        $upd->execute(['login' => $login]);
    }

    // SUCCESS
    echo json_encode([
        "status" => "success",
        "message" => "Connexion réussie",
        "login" => $login,
        "nomcomplet" => $user['nomcomplet'],
        "etat" => $user['status'],
        "code" => "200"
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Erreur serveur : " . $e->getMessage(),
        "code" => "500"
    ]);
}
?>