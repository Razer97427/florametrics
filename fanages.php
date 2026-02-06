<?php
session_start();
if (!isset($_SESSION['agent'])) { header("Location: login.php"); exit; }
require '../config.php';

$code = $_GET['code'] ?? '';
$view_id = $_GET['view_id'] ?? null;

// 1. Infos de la ruche
$stmt = $conn->prepare("SELECT * FROM ruches WHERE coderuche = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$ruche = $stmt->get_result()->fetch_assoc();

if (!$ruche) { header("Location: index.php"); exit; }

// 2. Traitement de l'Ajout Manuel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_add'])) {
    $fannes = (int)$_POST['f_fannes'];
    $marquees = (int)$_POST['f_marquees'];
    $date = $_POST['d_fanage'];
    $agent = $_SESSION['agent'];
    $status = "A";

    $ins = $conn->prepare("INSERT INTO fanages (login, coderuche, f_fannes, f_marquees, d_fanage, status) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->bind_param("ssiiss", $agent, $code, $fannes, $marquees, $date, $status);
    
    if ($ins->execute()) {
        $last_id = $conn->insert_id; 
        header("Location: fanages.php?code=" . urlencode($code) . "&view_id=" . $last_id);
        exit;
    }
}

// 3. Récupération de l'historique
$stmt_hist = $conn->prepare("SELECT * FROM fanages WHERE coderuche = ? AND status = 'A' ORDER BY d_fanage DESC");
$stmt_hist->bind_param("s", $code);
$stmt_hist->execute();
$historique = $stmt_hist->get_result();

// 4. Récupération des données pour l'analyse
$reco_data = null;
if ($view_id) {
    $stmt_reco = $conn->prepare("SELECT * FROM fanages WHERE id_l = ? AND coderuche = ?");
    $stmt_reco->bind_param("is", $view_id, $code);
    $stmt_reco->execute();
    $reco_data = $stmt_reco->get_result()->fetch_assoc();
}

include 'include/header.php';
?>

<div class="nav-back" style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-outline">← Retour aux ruches</a>
</div>

<div class="header-action">
    <h2>Gestion : <?= htmlspecialchars($ruche['nomcomplet']) ?></h2>
    <small>Code Ruche : <?= htmlspecialchars($ruche['coderuche']) ?></small>
</div>

<!-- ZONE DE RECOMMANDATION (Logique WinDev) -->
<?php if ($reco_data): 
    $f = (float)$reco_data['f_fannes'];
    $m = (float)$reco_data['f_marquees'];
    $rPourcentage = ($m > 0) ? round(($m / $f) * 100, 1) : 0;
    
    $classe_fond = "";
    $message = "";
    
    if ($rPourcentage < 40) {
        $classe_fond = "bg-rouge";
        $message = "Il faut souffler.";
    } elseif ($rPourcentage >= 40 && $rPourcentage < 70) {
        $classe_fond = "bg-orange";
        $message = "Possibilité de soufflage.";
    } else {
        $classe_fond = "bg-vert";
        $message = "Ne soufflez pas.";
    }
?>
    <div class="recommandation-box <?= $classe_fond ?>">
        <h3>Analyse du <?= date('d/m/Y H:i', strtotime($reco_data['d_fanage'])) ?></h3>
        <p class="pourcentage-text"><?= $rPourcentage ?> %</p>
        <p class="conseil-text"><?= $message ?></p>
    </div>
<?php endif; ?>

<div class="fanages-grid">
    <aside class="sidebar-form">
        <h3>📥 Nouveau Pointage</h3>
        <form method="POST">
            <input type="hidden" name="action_add" value="1">
            <div class="form-group">
                <label>Date / Heure</label>
                <input type="datetime-local" name="d_fanage" value="<?= date('Y-m-d\TH:i') ?>" required>
            </div>
            <div class="form-group">
                <label>Fleurs Fanées</label>
                <input type="number" name="f_fannes" min="0" required>
            </div>
            <div class="form-group">
                <label>Fleurs Marquées</label>
                <input type="number" name="f_marquees" min="1" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Valider et Analyser</button>
        </form>
    </aside>

    <main class="table-wrapper">
        <h3>Historique des relevés</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Agent</th>
                    <th>Fanées</th>
                    <th>Marquées</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($f = $historique->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($f['d_fanage'])) ?></td>
                    <td><strong><?= htmlspecialchars($f['login']) ?></strong></td>
                    <td><?= (int)$f['f_fannes'] ?></td>
                    <td><?= (int)$f['f_marquees'] ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="fanages.php?code=<?= urlencode($code) ?>&view_id=<?= $f['id_l'] ?>" class="btn btn-info btn-small">👁️ Voir</a>
                            <form action="delete_fanage.php" method="POST" onsubmit="return confirm('Supprimer ce relevé ?');" style="margin:0;">
                                <input type="hidden" name="id" value="<?= $f['id_l'] ?>">
                                <input type="hidden" name="code_retour" value="<?= $code ?>">
                                <button type="submit" class="btn btn-danger btn-small">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include 'include/footer.php'; ?>