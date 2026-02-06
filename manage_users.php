<?php
// --- DEBUG ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../config.php';

// SÉCURITÉ : Strictement réservé aux Admins
if (!isset($_SESSION['agent']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$message = "";
$error = "";

// --- 1. LOGIQUE DE SUPPRESSION (Basée sur le login) ---
if (isset($_GET['delete_login'])) {
    $login_to_delete = $_GET['delete_login'];
    
    if ($login_to_delete === $_SESSION['agent']) {
        $error = "Sécurité : Vous ne pouvez pas supprimer votre propre compte admin.";
    } else {
        // $del = $conn->prepare("DELETE FROM florametrics WHERE login = ?");
        $del = $conn->prepare("UPDATE florametrics set status = 'N' WHERE login = ?");
        $del->bind_param("s", $login_to_delete);
        if ($del->execute()) {
            $message = "Utilisateur supprimé avec succès.";
        }
    }
}

// --- 2. LOGIQUE D'AJOUT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $new_login = $_POST['login'];
    $new_nom = $_POST['nomcomplet']; // On utilise le nom de colonne correct
    $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $new_role = $_POST['role'];

    // Vérifier si le login existe déjà
    $test = $conn->prepare("SELECT login FROM florametrics WHERE login = ?");
    $test->bind_param("s", $new_login);
    $test->execute();
    if ($test->get_result()->num_rows > 0) {
        $error = "Erreur : Cet identifiant est déjà utilisé.";
    } else {
        $ins = $conn->prepare("INSERT INTO florametrics (login, password, nomcomplet, role) VALUES (?, ?, ?, ?)");
        $ins->bind_param("ssss", $new_login, $new_pass, $new_nom, $new_role);
        if ($ins->execute()) {
            $message = "Nouvel utilisateur créé !";
        } else {
            $error = "Erreur lors de la création : " . $conn->error;
        }
    }
}

// --- 3. RÉCUPÉRATION DE LA LISTE ---
$result = $conn->query("SELECT * FROM florametrics ORDER BY login ASC");

include 'include/header.php';
?>

<div class="container">
    <h2>🛠️ Gestion des Utilisateurs</h2>

    <?php if($message): ?> <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;"><?= $message ?></div> <?php endif; ?>
    <?php if($error): ?> <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;"><?= $error ?></div> <?php endif; ?>

    <div class="add-user-section" style="background: #fdfdfd; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0;">Ajouter un nouvel agent</h3>
        <form method="POST" class="add-user-form" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="text" name="login" placeholder="Identifiant (Login)" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="text" name="nomcomplet" placeholder="Nom Complet" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="password" name="password" placeholder="Mot de passe" required style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <select name="role" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="Agent">Agent</option>
                <option value="Admin">Admin</option>
            </select>
            <button type="submit" name="add_user" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Créer l'utilisateur</button>
        </form>
    </div>

<div class="table-wrapper">
    <table class="user-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                <th style="padding: 12px; text-align: left;">LOGIN</th>
                <th style="padding: 12px; text-align: left;">STATUS</th>
                <th style="padding: 12px; text-align: left;">NOM</th>
                <th style="padding: 12px; text-align: left;">RÔLE</th>
                <th style="padding: 12px; text-align: left;">DERNIÈRE CONNEXION</th>
                <th style="padding: 12px; text-align: left;">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px;"><strong><?= htmlspecialchars($row['login'] ?? '') ?></strong></td>
                <td style="padding: 12px;"><strong><?= htmlspecialchars($row['status'] ?? '') ?></strong></td>
                <td style="padding: 12px;"><?= htmlspecialchars($row['nomcomplet'] ?? 'N/A') ?></td>
                <td style="padding: 12px;">
                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.8em; background: <?= ($row['role'] == 'Admin') ? '#ffcccc' : '#e2f0ff' ?>;">
                        <?= htmlspecialchars($row['role'] ?? 'Agent') ?>
                    </span>
                </td>
                <td style="padding: 12px; color: #666; font-size: 0.9em;"><?= $row['d_connexion'] ?: 'Jamais' ?></td>
                <td style="padding: 12px;">
                    <a href="edit_user.php?login=<?= urlencode($row['login']) ?>" style="text-decoration: none; color: #007bff; margin-right: 15px;">Modifier</a>
                    
                    <?php if($row['login'] !== $_SESSION['agent']): ?>
                        <a href="manage_users.php?delete_login=<?= urlencode($row['login']) ?>" 
                           onclick="return confirm('Voulez-vous vraiment supprimer <?= $row['login'] ?> ?')" 
                           style="text-decoration: none; color: #dc3545;">Supprimer</a>
                    <?php else: ?>
                        <span style="color: #bbb; font-style: italic;">(Moi)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
                    </div>
</div>

<?php include 'include/footer.php'; ?>