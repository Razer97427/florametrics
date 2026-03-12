<?php
// Activation du debug pour voir l'erreur exacte
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['agent'])) { header("Location: index.php"); exit; }

// Vérifiez bien le chemin de config.php (si c'est 'config.php' ou '../config.php')
require '../config.php'; 

$error = "";
$agent_actuel = $_SESSION['agent'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ---> NOUVEAU : Vérification du token CSRF <---

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {

        // Si le token est absent ou incorrect, on bloque l'action

        // die("Erreur de sécurité : Jeton CSRF invalide. Veuillez rafraîchir la page.");
        $_SESSION['error'] = "Votre session a expiré ou le formulaire est invalide. Veuillez réessayer.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();

    }

    $code = trim($_POST['coderuche']);
    $nom = trim($_POST['nomcomplet']);
    
    if (!empty($code) && !empty($nom)) {
        // 1. S'assurer que la ruche existe dans le référentiel global 'ruches'
        $check = $conn->prepare("SELECT coderuche FROM ruches WHERE coderuche = ?");
        if (!$check) { die("Erreur SQL (Table ruches manquante ?) : " . $conn->error); }
        
        $check->bind_param("s", $code);
        $check->execute();
        
        if ($check->get_result()->num_rows === 0) {
            // Création dans la table globale si inexistante
            $ins_global = $conn->prepare("INSERT INTO ruches (coderuche, nomcomplet, status) VALUES (?, ?, 'A')");
            $ins_global->bind_param("ss", $code, $nom);
            $ins_global->execute();
        }

        // 2. Création du lien dans 'agent_ruches'
        // C'est souvent ici que ça plante si la table 'agent_ruches' n'existe pas !
        $stmt_link = $conn->prepare("INSERT IGNORE INTO agent_ruches (n_agent, coderuche, status) VALUES (?, ?, 'A')");
        
        if (!$stmt_link) {
            // Ce message vous dira si la table agent_ruches est manquante
            die("Erreur SQL : La table 'agent_ruches' est probablement manquante ou mal nommée. Erreur : " . $conn->error);
        }

        $stmt_link->bind_param("ss", $agent_actuel, $code);
        
        if ($stmt_link->execute()) {
            // On redirige bien vers index.php
            header("Location: index.php");
            exit;
        } else {
            $error = "Erreur lors de la liaison à votre compte : " . $stmt_link->error;
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

include 'include/header.php';
?>

<?php if (isset($_SESSION['error'])): ?>
    <div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <strong>Oups !</strong> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="login-box" style="max-width: 100%;">
        <a href="index.php" style="text-decoration: none; color: #666; font-size: 0.9rem;">← Retour</a>

    <h2 style="margin-top: 15px;">Lier une ruche</h2>
    
    <?php if($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <div class="form-group">
            <label>Code Barre / ID</label>
            <input type="text" name="coderuche" required placeholder="Scanner ou saisir le code">
        </div>
        <div class="form-group">
            <label>Nom de la ruche</label>
            <input type="text" name="nomcomplet" required placeholder="Nom pour votre liste">
        </div>
        <button type="submit" class="btn btn-primary btn-full">Lier à mon compte</button>
    </form>
</div>

<?php include 'include/footer.php'; ?>