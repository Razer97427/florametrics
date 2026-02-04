<?php
header('Content-Type: application/json; charset=utf-8');

// Configuration de la base de données (PDO pour l'API)
define('DB_NAME', 'terracoonzroot');
define('DB_USER', 'terracoonzroot');
define('DB_PASSWORD', 'Excalibur250AVI');
define('DB_HOST', 'terracoonzroot.mysql.db');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $coderuche  = $_POST['coderuche'] ?? '';
    $nomcomplet = $_POST['nomcomplet'] ?? '';
    $status     = $_POST['status'] ?? '';
    $n_agent    = $_POST['n_agent'] ?? '';

    if (!empty($coderuche) && !empty($n_agent)) {
        // 1. Insertion/Mise à jour dans le référentiel GLOBAL 'ruches'
        // On met à jour le nom si la ruche existe déjà
        $sqlR = "INSERT INTO ruches (coderuche, nomcomplet, status) 
                 VALUES (:code, :nom, :status) 
                 ON DUPLICATE KEY UPDATE nomcomplet = :nom";
        $stmtR = $pdo->prepare($sqlR);
        $stmtR->execute([
            'code'   => $coderuche,
            'nom'    => $nomcomplet,
            'status' => $status
        ]);

        // 2. Insertion dans la table de LIAISON 'agent_ruches'
        // On utilise INSERT IGNORE pour ne pas créer de doublon si le lien existe déjà
        $sqlL = "INSERT IGNORE INTO agent_ruches (n_agent, coderuche, status) VALUES (:agent, :code, :status)";
        $stmtL = $pdo->prepare($sqlL);
        $stmtL->execute([
            'agent' => $n_agent,
            'code'  => $coderuche,
            'status' => $status
        ]);

        echo json_encode(["status" => "success", "message" => "Ruche synchronisée et liée à l'agent"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Données manquantes (n_agent ou coderuche)"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Erreur SQL : " . $e->getMessage()]);
}
?>