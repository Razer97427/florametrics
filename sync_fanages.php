<?php
header('Content-Type: application/json; charset=utf-8');

// Utilise les mêmes identifiants que dans ton check_login.php
define('DB_NAME', 'terracoonzroot');
define('DB_USER', 'terracoonzroot');
define('DB_PASSWORD', 'Excalibur250AVI');
define('DB_HOST', 'terracoonzroot.mysql.db');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // On récupère toutes les colonnes de ton interface WinDev
    $login      = $_POST['login'] ?? '';
    $coderuche  = $_POST['coderuche'] ?? '';
    $f_fannes   = $_POST['f_fannes'] ?? 0;
    $f_marquees = $_POST['f_marquees'] ?? 0;
    $d_fanage   = $_POST['d_fanage'] ?? '';
    $status     = $_POST['status'] ?? '';

    if (!empty($login) && !empty($coderuche)) {
        $sql = "INSERT INTO fanages (login, coderuche, f_fannes, f_marquees, d_fanage, status) 
                VALUES (:login, :coderuche, :fannes, :marquees, :date, :status)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'login'      => $login,
            'coderuche'  => $coderuche,
            'fannes'     => $f_fannes,
            'marquees'   => $f_marquees,
            'date'       => $d_fanage,
            'status'     => $status
        ]);

        echo json_encode(["status" => "success", "message" => "Fanage synchronisé"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Données incomplètes"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>