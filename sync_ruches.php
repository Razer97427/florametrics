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
    $nc_agent   = $_POST['nc_agent'] ?? '';
    $d_creation = $_POST['d_creation'] ?? '';

    if (!empty($coderuche) && !empty($n_agent)) {
		
		
		// ÉTAPE 0 : LA VÉRIFICATION (VOTRE AJOUT)
		
		 $checkAgent = $pdo->prepare("SELECT COUNT(*) FROM florametrics WHERE login = :agent");
		 $n_agent = trim($n_agent); // Pour nettoyer les espaces sur le login de l'agent
		 $checkAgent->execute(['agent' => $n_agent]);
    
		 if ($checkAgent->fetchColumn() == 0) {
			echo json_encode(["status" => "error", "message" => "Échec de synchronisation : l'agent $n_agent n'existe pas."]);
			exit; 
			}
		
		
        // 1. Insertion/Mise à jour dans le référentiel GLOBAL 'ruches'
        // On met à jour le nom si la ruche existe déjà
        $sqlR = "INSERT IGNORE INTO ruches (coderuche, nomcomplet, status, n_agent, d_creation) 
                 VALUES (:code, :nom, :status, :nc_agent, :d_creation)";
                 //ON DUPLICATE KEY UPDATE nomcomplet = :nom";
        $stmtR = $pdo->prepare($sqlR);
        $stmtR->execute([
            'code'   => $coderuche,
            'nom'    => $nomcomplet,
            'status' => $status,
            'nc_agent' => $nc_agent,
            'd_creation' => $d_creation
        ]);

        // 2. Insertion dans la table de LIAISON 'agent_ruches'
        // On utilise INSERT IGNORE pour ne pas créer de doublon si le lien existe déjà
        $sqlL = "INSERT INTO agent_ruches (n_agent, coderuche, status) VALUES (:agent, :code, :status) ON DUPLICATE KEY UPDATE status = :status";

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