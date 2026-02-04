<?php
header('Content-Type: application/json; charset=utf-8');

// Configuration de la base de données
define('DB_NAME', 'terracoonzroot');
define('DB_USER', 'terracoonzroot');
define('DB_PASSWORD', 'Excalibur250AVI');
define('DB_HOST', 'terracoonzroot.mysql.db');

try {
    // 1. Connexion à la base de données avec PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    
    // Activation des exceptions pour les erreurs SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Récupération des données (POST ou GET via $_REQUEST)
    $login = $_REQUEST['login'] ?? '';
    $password = $_REQUEST['password'] ?? '';
    $d_connexion = $_REQUEST['d_connexion'] ?? ''; // La date envoyée par WinDev

    // 3. Vérification des champs obligatoires
    if (!empty($login) && !empty($password)) {
        
        // Préparation de la requête pour trouver l'utilisateur
        $stmt = $pdo->prepare("SELECT password FROM florametrics WHERE login = :login");
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. Vérification du mot de passe
        if ($user && password_verify($password, $user['password'])) {
            
            // 5. Mise à jour de la date de dernière connexion dans la base
            // Note : On utilise NOW() si d_connexion est vide, sinon on prend la valeur de WinDev
            if (!empty($d_connexion)) {
                $update = $pdo->prepare("UPDATE florametrics SET d_connexion = :d_connexion WHERE login = :login");
                $update->execute([
                    'd_connexion' => $d_connexion,
                    'login' => $login
                ]);
            } else {
                $update = $pdo->prepare("UPDATE florametrics SET d_connexion = NOW() WHERE login = :login");
                $update->execute(['login' => $login]);
            }

            // Réponse de succès
            echo json_encode([
                "status" => "success", 
                "message" => "Connexion réussie",
                "login" => $login,
				"code" => "200"
            ]);
            
        } else {
            // Identifiants incorrects
            echo json_encode([
                "status" => "error", 
                "message" => "Identifiants incorrects",
				"code" => "301"
            ]);
        }
    } else {
        // Champs vides
        echo json_encode([
            "status" => "error", 
            "message" => "Champs manquants (login ou password)",
			"code" => "300"
        ]);
    }

} catch (PDOException $e) {
    // Erreur technique (Base de données)
    echo json_encode([
        "status" => "error", 
        "message" => "Erreur de connexion à la base : " . $e->getMessage()
    ]);
}
?>