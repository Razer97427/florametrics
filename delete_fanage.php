<?php
session_start();
if (!isset($_SESSION['agent'])) { header("Location: login.php"); exit; }
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $code_retour = $_POST['code_retour'] ?? '';

    if (!empty($id)) {
        // $stmt = $conn->prepare("DELETE FROM fanages WHERE id_l = ?");
        // $stmt->bind_param("i", $id);
        // $stmt->execute();

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {

        // Si le token est absent ou incorrect, on bloque l'action

        // die("Erreur de sécurité : Jeton CSRF invalide. Veuillez rafraîchir la page.");
        $_SESSION['error'] = "Votre session a expiré ou le formulaire est invalide. Veuillez réessayer.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();

    }

        $stmt = $conn->prepare("UPDATE ent_fanages set status = 'N' WHERE id_l = ? AND coderuche = ?");
        $stmt->bind_param("is", $id,$code_retour);
        $stmt->execute();

        $stmt2 = $conn->prepare("UPDATE det_fanages set status = 'N' WHERE id_l = ? AND coderuche = ?");
        $stmt2->bind_param("is", $id,$code_retour);
        $stmt2->execute();


    }

    if (!empty($code_retour)) {
        header("Location: fanages.php?code=" . urlencode($code_retour));
    } else {
        header("Location: index.php");
    }
    exit;
} else {
    die("Action non autorisée.");
}