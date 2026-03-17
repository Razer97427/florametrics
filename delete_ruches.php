<?php
session_start();

// Si pas de session, retour à la connexion
if (!isset($_SESSION['agent'])) { 
    header("Location: login.php"); 
    exit; 
}

require '../config.php';

// $code = $_GET['code'] ?? '';
$agent = $_SESSION['agent'];
$code = $_POST['code'] ?? '';
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin');

// if (!empty($code)) {
//     // // 1. On supprime les fanages liés pour garder la base propre
//     // $stmt1 = $conn->prepare("DELETE FROM fanages WHERE coderuche = ?");
//     // $stmt1->bind_param("s", $code);
//     // $stmt1->execute();

//     // // 2. On supprime la ruche
//     // $stmt2 = $conn->prepare("DELETE FROM ruches WHERE coderuche = ?");
//     // $stmt2->bind_param("s", $code);
//     // $stmt2->execute();
	
// 	// $stmt = $conn->prepare("DELETE FROM agent_ruches WHERE n_agent = ? AND coderuche = ?");
//     // $stmt->bind_param("ss", $agent, $code);
//     // $stmt->execute();
// 	$stmt = $conn->prepare("UPDATE agent_ruches set status = 'N' WHERE n_agent = ? AND coderuche = ?");
//     $stmt->bind_param("ss", $agent, $code);
//     $stmt->execute();


//     $stmt2 = $conn->prepare("UPDATE ruches set status = 'N' WHERE coderuche = ?");
//     $stmt2->bind_param("s", $code);
//     $stmt2->execute();
// }

if (!empty($code)) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {

        // Si le token est absent ou incorrect, on bloque l'action

        // die("Erreur de sécurité : Jeton CSRF invalide. Veuillez rafraîchir la page.");
        $_SESSION['error'] = "Votre session a expiré ou le formulaire est invalide. Veuillez réessayer.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();

    }

    if ($isAdmin) {
        // --- CAS ADMIN : Désactivation GLOBALE ---
        
        // 1. On désactive la ruche elle-même
        $stmt1 = $conn->prepare("UPDATE ruches SET status = 'N' WHERE coderuche = ?");
        $stmt1->bind_param("s", $code);
        $stmt1->execute();

        // 2. CASCADE : On désactive TOUS les agents liés à cette ruche
        $stmt2 = $conn->prepare("UPDATE agent_ruches SET status = 'N' WHERE coderuche = ?");
        $stmt2->bind_param("s", $code);
        $stmt2->execute();
        
    } else {
        // --- CAS AGENT : Désactivation INDIVIDUELLE ---
        // L'agent ne gère plus la ruche, mais elle reste active pour les autres
        $stmt = $conn->prepare("UPDATE agent_ruches SET status = 'N' WHERE n_agent = ? AND coderuche = ?");
        $stmt->bind_param("ss", $agent, $code);
        $stmt->execute();
    }
}

// Redirection vers le nouveau dashboard (index.php)
header("Location: index.php");
exit;