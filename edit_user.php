<?php
// --- DEBUG ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../config.php';

if (!isset($_SESSION['agent']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$message = "";
$error = "";

if (!isset($_GET['login'])) {
    header("Location: manage_users.php");
    exit();
}

$login_to_edit = $_GET['login'];

$stmt = $conn->prepare("SELECT * FROM florametrics WHERE login = ?");
$stmt->bind_param("s", $login_to_edit);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) { die("Utilisateur non trouvé."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $new_nom = $_POST['nomcomplet'];
    $new_role = $_POST['role'];
    $new_pass = $_POST['password'];

    try {
        if (!empty($new_pass)) {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE florametrics SET nomcomplet = ?, role = ?, password = ? WHERE login = ?");
            $upd->bind_param("ssss", $new_nom, $new_role, $hashed_pass, $login_to_edit);
        } else {
            $upd = $conn->prepare("UPDATE florametrics SET nomcomplet = ?, role = ? WHERE login = ?");
            $upd->bind_param("sss", $new_nom, $new_role, $login_to_edit);
        }

        if ($upd->execute()) {
            $message = "Utilisateur mis à jour avec succès !";
            $user['nomcomplet'] = $new_nom;
            $user['role'] = $new_role;
			
			// Si l'utilisateur qui modifie est CELUI qui est connecté, on met à jour sa session
		if ($login_to_edit === $_SESSION['agent']) {
			$_SESSION['nom_affichage'] = $new_nom;
			$_SESSION['role'] = $new_role;
			}
        }
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

include 'include/header.php';
?>

<div class="container">
    <div class="actions-bar">
        <h2>Modifier : <?= htmlspecialchars($login_to_edit) ?></h2>
        <a href="manage_users.php" class="btn" style="background: gray;">Retour</a>
    </div>

    <?php if($message): ?>
        <div style="background: var(--success-bg); color: var(--primary); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--primary);">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <div class="login-box" style="margin: 0 auto; width: 100%; max-width: 500px; box-shadow: none; border: 1px solid var(--border);">
        <form method="POST">
            <div class="form-group">
                <label>Identifiant (Login)</label>
                <input type="text" value="<?= htmlspecialchars($user['login']) ?>" disabled style="background: #eee; cursor: not-allowed;">
            </div>

            <div class="form-group">
                <label>Nom Complet</label>
                <input type="text" name="nomcomplet" value="<?= htmlspecialchars($user['nomcomplet'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Rôle</label>
                <select name="role" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                    <option value="Agent" <?= ($user['role'] === 'Agent') ? 'selected' : '' ?>>Agent</option>
                    <option value="Admin" <?= ($user['role'] === 'Admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label>Nouveau mot de passe (optionnel)</label>
                <input type="password" name="password" placeholder="Laisser vide pour ne pas changer">
            </div>

            <button type="submit" name="update_user" class="btn btn-primary btn-full">
                Enregistrer les modifications
            </button>
        </form>
    </div>
</div>

<?php include 'include/footer.php'; ?>