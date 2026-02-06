<?php
session_start();

if (isset($_SESSION['agent'])) {
    header("Location: index.php");
    exit();
}

require '../config.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_saisi = $_POST['login'] ?? '';
    $pass_saisi  = $_POST['password'] ?? '';

    // ÉTAPE 1 : On récupère l'utilisateur sans filtrer sur le statut pour pouvoir l'analyser
    $stmt = $conn->prepare("SELECT password, login, role, nomcomplet, status FROM florametrics WHERE login = ?");
    $stmt->bind_param("s", $login_saisi);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // ÉTAPE 2 : Vérification de l'existence et du mot de passe
    if ($user && password_verify($pass_saisi, $user['password'])) {
        
        // ÉTAPE 3 : Le mot de passe est correct, on vérifie maintenant le statut
        if ($user['status'] === 'A') {
            // Succès : Le compte est actif
            $_SESSION['agent'] = $user['login'];
            $_SESSION['role']  = $user['role'];
            $_SESSION['nom']   = $user['nomcomplet'];
            header("Location: index.php");
            exit();
        } else {
            // Erreur : Identifiants OK mais compte bloqué (status 'N' ou autre)
            $error = "Votre compte est actuellement désactivé. Veuillez contacter l'administrateur.";
        }
        
    } else {
        // Erreur : Login inexistant ou mot de passe erroné
        // On garde un message générique pour la sécurité (ne pas confirmer qu'un login existe)
        $error = "Identifiants ou mot de passe incorrects.";
    }
}

include 'include/header.php';
?>

<div class="login-wrapper">
    <div class="login-box">
        <h1 style="color: var(--primary); text-align: center; margin-bottom: 5px;">FLORAMETRICS</h1>
        <h2 style="text-align: center; font-weight: 400; color: var(--text-light); margin-bottom: 30px;">Connexion Agent</h2>
        
        <?php if($error): ?>
            <!-- On utilise une couleur différente pour le compte désactivé si vous le souhaitez -->
            <div style="background-color: #ffebee; color: #c62828; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #ffcdd2; font-size: 0.9rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Identifiant</label>
                <input type="text" name="login" required autofocus style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 5px; box-sizing: border-box;">
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Mot de passe</label>
                <input type="password" name="password" required style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 5px; box-sizing: border-box;">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; cursor: pointer; background-color: var(--primary); color: white; border: none; border-radius: 5px;">
                Se connecter
            </button>
        </form>
    </div>
</div>

<?php include 'include/footer.php'; ?>