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

    // On récupère toutes les variables poussées par WinDev
    $login      = $_POST['login'] ?? '';
    $coderuche  = $_POST['coderuche'] ?? '';
    // On tolère les deux orthographes au cas où ton WinDev envoie 'f_fannes'
    $f_fannes   = $_POST['f_fannes'] ?? ($_POST['f_fannees'] ?? 0); 
    $f_marquees = $_POST['f_marquees'] ?? 0;
    $rang       = $_POST['rang'] ?? 1; // NOUVEAU : Récupération du rang
    $d_fanage   = $_POST['d_fanage'] ?? '';
    $status     = $_POST['status'] ?? 'A';

    if (!empty($login) && !empty($coderuche)) {
        
        // ==========================================
        // 1. VÉRIFICATION DE SÉCURITÉ
        // ==========================================
        $checkSql = "SELECT r.status as ruche_status, ar.status as agent_status 
                     FROM ruches r
                     INNER JOIN agent_ruches ar ON r.coderuche = ar.coderuche
                     WHERE r.coderuche = :code AND ar.n_agent = :login";
        
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['code' => $coderuche, 'login' => $login]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            echo json_encode(["status" => "error", "message" => "Lien ruche/agent inexistant."]);
            exit;
        }

        if ($result['ruche_status'] !== 'A' || $result['agent_status'] !== 'A') {
            echo json_encode(["status" => "error", "message" => "Synchronisation refusée : ruche ou accès désactivé."]);
            exit; 
        }

        // ==========================================
        // ÉTAPE A : RECHERCHE DE LA SESSION ACTIVE
        // ==========================================
        $stmt_last = $pdo->prepare("SELECT MAX(id_l) as last_id FROM ent_fanages WHERE coderuche = :code AND login = :login AND status = 'A'");
        $stmt_last->execute(['code' => $coderuche, 'login' => $login]);
        $row_last = $stmt_last->fetch(PDO::FETCH_ASSOC);
        $id_session = $row_last['last_id'] ?? 0;

        // ==========================================
        // ÉTAPE B : CRÉATION DE L'EN-TÊTE (Si 1er rang)
        // ==========================================
        if (!$id_session) {
            $libelle = "Rang " . $rang . " à " . $rang;
            // On utilise bien "total_f_fannees" comme sur ta capture MySQL
            $sql_ent = "INSERT INTO ent_fanages (login, coderuche, total_f_fannees, total_f_marquees, d_fanage, status, libelle, rang_depart, rang_fin) 
                        VALUES (:login, :coderuche, 0, 0, :date, 'A', :libelle, :rang_d, :rang_f)";
            $stmt_ent = $pdo->prepare($sql_ent);
            $stmt_ent->execute([
                'login'     => $login,
                'coderuche' => $coderuche,
                'date'      => $d_fanage,
                'libelle'   => $libelle,
                'rang_d'    => $rang,
                'rang_f'    => $rang
            ]);
            $id_session = $pdo->lastInsertId();
        }

        // ==========================================
        // ÉTAPE C : INSERTION DU DÉTAIL
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
            'idl'        => $id_session
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

        echo json_encode(["status" => "success", "message" => "Fanage synchronisé"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Données incomplètes"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>