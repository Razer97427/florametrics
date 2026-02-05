<?php
session_start();

// Si pas de session, retour à la connexion
if (!isset($_SESSION['agent'])) { 
    header("Location: login.php"); 
    exit; 
}

require '../config.php';

$code = $_GET['code'] ?? '';
$agent = $_SESSION['agent'];

if (!empty($code)) {
    // // 1. On supprime les fanages liés pour garder la base propre
    // $stmt1 = $conn->prepare("DELETE FROM fanages WHERE coderuche = ?");
    // $stmt1->bind_param("s", $code);
    // $stmt1->execute();

    // // 2. On supprime la ruche
    // $stmt2 = $conn->prepare("DELETE FROM ruches WHERE coderuche = ?");
    // $stmt2->bind_param("s", $code);
    // $stmt2->execute();
	
	// $stmt = $conn->prepare("DELETE FROM agent_ruches WHERE n_agent = ? AND coderuche = ?");
    // $stmt->bind_param("ss", $agent, $code);
    // $stmt->execute();
	$stmt = $conn->prepare("UPDATE agent_ruches set status = 'N' WHERE n_agent = ? AND coderuche = ?");
    $stmt->bind_param("ss", $agent, $code);
    $stmt->execute();
}

// Redirection vers le nouveau dashboard (index.php)
header("Location: index.php");
exit;