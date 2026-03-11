<?php
header('Content-Type: application/json; charset=utf-8');

// Identifiants de connexion (inchangés)
define('DB_NAME', 'terracoonzroot');
define('DB_USER', 'terracoonzroot');
define('DB_PASSWORD', 'Excalibur250AVI');
define('DB_HOST', 'terracoonzroot.mysql.db');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // On récupère toutes les variables poussées par WinDev
    $login          = $_POST['login'] ?? '';
    $coderuche      = $_POST['coderuche'] ?? '';
    $f_fannes       = $_POST['f_fannes'] ?? ($_POST['f_fannees'] ?? 0); 
    $f_marquees     = $_POST['f_marquees'] ?? 0;
    $rang           = $_POST['rang'] ?? 1;
    $d_fanage       = $_POST['d_fanage'] ?? '';
    $status         = $_POST['status'] ?? 'A';
    
    // NOUVELLE VARIABLE : ID local de WinDev
    $id_l_windev    = $_POST['id_l_windev'] ?? 0; 

    if (!empty($login) && !empty($coderuche) && !empty($id_l_windev)) {
        
        // ==========================================
        // VÉRIFICATION AGENT / RUCHE (CONSERVÉE)
        // ==========================================
        $checkSql = "SELECT r.status as ruche_status, ar.status as agent_status 
                     FROM ruches r
                     INNER JOIN agent_ruches ar ON r.coderuche = ar.coderuche
                     WHERE r.coderuche = :code AND ar.n_agent = :login";
        
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['code' => $coderuche, 'login' => $login]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            echo json_encode(["status" => "error", "message" => "Liaison agent/ruche inexistante"]);
            exit;
        }

        if ($result['ruche_status'] !== 'A' || $result['agent_status'] !== 'A') {
            echo json_encode(["status" => "error", "message" => "Accès désactivé pour cette ruche ou cet agent"]);
            exit;
        }

        // ==========================================
        // ÉTAPE A : RECHERCHE DE L'ID SERVEUR VIA ID WINDEV
        // ==========================================
        // On vérifie si un en-tête existe déjà pour cet id_l local précis
        $stmt_last = $pdo->prepare("SELECT id_l FROM ent_fanages WHERE id_l_windev = :id_wd AND login = :login AND coderuche = :code");
        $stmt_last->execute([
            'id_wd' => $id_l_windev,
            'login' => $login,
            'code'  => $coderuche
        ]);
        $id_session = $stmt_last->fetchColumn();

        // ==========================================
        // ÉTAPE B : SI NOUVEL ID WINDEV -> INSERT ENTETE
        // ==========================================
        if (!$id_session) {
            $libelle = "Session " . $id_l_windev; // Libellé temporaire
            $sql_ent = "INSERT INTO ent_fanages (login, coderuche, total_f_fannees, total_f_marquees, d_fanage, status, libelle, rang_depart, rang_fin, id_l_windev) 
                        VALUES (:login, :coderuche, 0, 0, :date, 'A', :libelle, :rang, :rang, :id_wd)";
            
            $stmt_ent = $pdo->prepare($sql_ent);
            $stmt_ent->execute([
                'login'     => $login,
                'coderuche' => $coderuche,
                'date'      => $d_fanage,
                'libelle'   => $libelle,
                'rang'      => $rang,
                'id_wd'     => $id_l_windev
            ]);
            $id_session = $pdo->lastInsertId();
        }

        // ==========================================
        // ÉTAPE C : INSERTION DU DÉTAIL (Liaison via ID MariaDB)
        // ==========================================
        $sql_det = "INSERT INTO det_fanages (login, coderuche, rang, f_fannees, f_marquees, d_fanage, status, id_l) 
                    VALUES (:login, :coderuche, :rang, :fannes, :marquees, :date, :status, :idl)";
        
        $stmt_det = $pdo->prepare($sql_det);
        $stmt_det->execute([
            'login'      => $login,
            'coderuche'  => $coderuche,
            'rang'       => $rang,
            'fannes'     => $f_fannes,
            'marquees'   => $f_marquees,
            'date'       => $d_fanage,
            'status'     => $status,
            'idl'        => $id_session // On utilise l'ID récupéré ou créé à l'étape A/B
        ]);

        // ==========================================
        // ÉTAPE D : MISE À JOUR DYNAMIQUE (Totaux et Rangs)
        // ==========================================
        $sql_upd = "UPDATE ent_fanages SET 
                    total_f_fannees = (SELECT SUM(f_fannees) FROM det_fanages WHERE id_l = :id AND status = 'A'),
                    total_f_marquees = (SELECT SUM(f_marquees) FROM det_fanages WHERE id_l = :id AND status = 'A'),
                    rang_depart = (SELECT MIN(rang) FROM det_fanages WHERE id_l = :id AND status = 'A'),
                    rang_fin = (SELECT MAX(rang) FROM det_fanages WHERE id_l = :id AND status = 'A')
                    WHERE id_l = :id";
        
        $stmt_upd = $pdo->prepare($sql_upd);
        $stmt_upd->execute(['id' => $id_session]);

        // Mise à jour finale du libellé "Rang X à Y"
        $sql_lib = "UPDATE ent_fanages SET libelle = CONCAT('Rang ', rang_depart, ' à ', rang_fin) WHERE id_l = :id";
        $stmt_lib = $pdo->prepare($sql_lib);
        $stmt_lib->execute(['id' => $id_session]);

        echo json_encode([
            "status" => "success", 
            "message" => "Synchronisation réussie", 
            "id_l_serveur" => $id_session
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Données incomplètes (login, ruche ou id_l manquant)"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Erreur DB : " . $e->getMessage()]);
}
?>