<?php
session_start();
session_destroy();
// On redirige vers la page de login après déconnexion
header("Location: login.php");
exit;
?>