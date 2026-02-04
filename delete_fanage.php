<?php
session_start();
if (!isset($_SESSION['agent'])) { header("Location: login.php"); exit; }
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $code_retour = $_POST['code_retour'] ?? '';

    if (!empty($id)) {
        $stmt = $conn->prepare("DELETE FROM fanages WHERE id_l = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
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