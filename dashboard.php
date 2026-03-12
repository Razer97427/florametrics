<?php
session_start();
if (!isset($_SESSION['agent'])) { header("Location: index.php"); exit; }
require 'config.php';

$agent_connecte = $_SESSION['agent'];

// On utilise une jointure pour ne récupérer que les ruches liées à l'agent via agent_ruches
$query = "
    SELECT r.* FROM ruches r
    INNER JOIN agent_ruches ar ON r.coderuche = ar.coderuche
    WHERE ar.n_agent = ?
    ORDER BY r.nomcomplet ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $agent_connecte);
$stmt->execute();
$result = $stmt->get_result();
$nb_ruches = $result->num_rows;

include 'include/header.php';
?>

<div class="actions-bar">
    <div>
        <h2>Mes Ruches</h2>
        <p>Bienvenue, <strong><?= htmlspecialchars($agent_connecte) ?></strong></p>
    </div>
    <?php if ($nb_ruches > 0): ?>
        <a href="ajouter_ruche.php" class="btn btn-primary">➕ Ajouter une ruche</a>
    <?php endif; ?>
</div>

<?php if ($nb_ruches === 0): ?>
    <div class="empty-state">
        <span class="empty-state-icon">🐝</span>
        <h3>Aucune ruche associée</h3>
        <p>Vous n'avez pas encore de ruches dans votre liste personnelle.</p>
        <br>
        <a href="ajouter_ruche.php" class="btn btn-primary">Lier ma première ruche</a>
    </div>
<?php else: ?>
    <div class="list-ruches">
        <?php while ($r = $result->fetch_assoc()): ?>
            <div class="card-ruche">
                <div class="info">
                    <strong><?= htmlspecialchars($r['nomcomplet']) ?></strong><br>
                    <small>ID : <?= htmlspecialchars($r['coderuche']) ?></small>
                </div>
                <div>
                    <a href="fanages.php?code=<?= urlencode($r['coderuche']) ?>" class="btn btn-primary">Gérer</a>
                    <!-- On retire uniquement le lien, on ne supprime pas la ruche globale -->
                    <!-- <a href="delete_ruche.php?code=<?= urlencode($r['coderuche']) ?>" class="btn btn-danger btn-small" onclick="return confirm('Retirer cette ruche de votre liste ?')">Retirer</a> -->
                     <form method="POST" action="delete_ruches.php" style="display: inline-block;" onsubmit="return confirm('Retirer cette ruche de votre liste ?')">
                        <input type="hidden" name="code" value="<?= htmlspecialchars($r['coderuche']) ?>">
                        <button type="submit" class="btn btn-danger btn-small">Retirer</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php include 'include/footer.php'; ?>