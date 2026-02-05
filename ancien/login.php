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

    $stmt = $conn->prepare("SELECT password, login, role, nomcomplet FROM florametrics WHERE login = ?");
    $stmt->bind_param("s", $login_saisi);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($pass_saisi, $user['password'])) {
        $_SESSION['agent'] = $user['login'];
        $_SESSION['role']  = $user['role'];
        $_SESSION['nom']   = $user['nomcomplet'];
        header("Location: index.php");
        exit();
    } else {
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
            <div class="status-inactive" style="padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Identifiant</label>
                <input type="text" name="login" required autofocus style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 5px; box-sizing: border-box;">
            </div>
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Mot de passe</label>
                <input type="password" name="password" required style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 5px; box-sizing: border-box;">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">Se connecter</button>
        </form>
    </div>
</div>

<?php include 'include/footer.php'; ?>