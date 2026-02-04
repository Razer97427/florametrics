<?php
session_start();

if (!isset($_SESSION['agent'])) { 
    header("Location: login.php"); 
    exit; 
}

require '../config.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin');

if ($isAdmin) {
    $sql = "SELECT r.*, 
            GROUP_CONCAT(CONCAT(ar.n_agent, '|', ar.status) SEPARATOR ', ') as info_agents
            FROM ruches r
            LEFT JOIN agent_ruches ar ON r.coderuche = ar.coderuche
            GROUP BY r.coderuche
            ORDER BY r.nomcomplet ASC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT r.* FROM ruches r
            INNER JOIN agent_ruches ar ON r.coderuche = ar.coderuche
            WHERE ar.n_agent = ? 
            AND ar.status = 'A'
            ORDER BY r.nomcomplet ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['agent']);
}

$stmt->execute();
$result = $stmt->get_result();
$nb_ruches = $result->num_rows;

include 'include/header.php';
?>

<style>
    .container-fluid { padding: 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    
    /* Design du cadre "Aucune ruche" */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 2px dashed #ccc;
        margin-top: 20px;
    }
    .empty-state-icon { font-size: 64px; margin-bottom: 20px; display: block; }
    .empty-state h3 { color: #333; margin-bottom: 10px; }
    .empty-state p { color: #666; margin-bottom: 25px; }

    /* Tableau Admin */
    .admin-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .admin-table th { background: #2e7d32; color: #eee; padding: 15px; text-align: left; }
    .admin-table td { padding: 12px 15px; border-bottom: 1px solid #eee; }

    /* Badges Agents */
    .agent-pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        margin: 3px;
        font-weight: 500;
    }
    .status-active { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-inactive { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    .dot-green { background-color: #28a745; }
    .dot-red { background-color: #dc3545; }

    /* Boutons */
    .btn-main { 
        background: #2e7d32; 
        color: white; 
        padding: 10px 20px; 
        border-radius: 6px; 
        text-decoration: none; 
        font-weight: bold;
        transition: 0.3s;
    }
    .btn-main:hover { background: #1b5e20; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
    .btn-group { display: flex; gap: 5px; }
    .btn-sm { padding: 6px 10px; font-size: 12px; border-radius: 4px; text-decoration: none; }
    .btn-view { background: #007bff; color: white; }
    .btn-edit { background: #6c757d; color: white; }
    .btn-del { background: #dc3545; color: white; }
</style>

<div class="container-fluid">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2><?= $isAdmin ? "Gestion Administrative" : "Mes Ruches" ?></h2>
            <p style="margin:0; color: #666;">Bienvenue, <strong><?= htmlspecialchars($_SESSION['nom']) ?></strong></p>
        </div>
        <?php if ($nb_ruches > 0): ?>
            <a href="ajouter_ruche.php" class="btn-main">+ <?= $isAdmin ? "Créer" : "Lier" ?> une ruche</a>
        <?php endif; ?>
    </div>

    <?php if ($nb_ruches === 0): ?>
        <div class="empty-state">
            <span class="empty-state-icon">🐝</span>
            <h3><?= $isAdmin ? "Le rucher est vide" : "Aucune ruche associée" ?></h3>
            <p><?= $isAdmin ? "Il n'y a actuellement aucune ruche enregistrée dans la base de données." : "Vous n'avez aucune ruche dans votre liste personnelle pour le moment." ?></p>
            <a href="ajouter_ruche.php" class="btn-main">
                <?= $isAdmin ? "Créer la première ruche" : "Lier ma première ruche" ?>
            </a>
        </div>

    <?php else: ?>

        <?php if ($isAdmin): ?>
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom de la Ruche</th>
                        <th>Agent(s) responsable(s)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['coderuche']) ?></strong></td>
                            <td><?= htmlspecialchars($r['nomcomplet']) ?></td>
                            <td>
                                <?php 
                                if (!empty($r['info_agents'])) {
                                    $pairs = explode(', ', $r['info_agents']);
                                    foreach ($pairs as $p) {
                                        $data = explode('|', $p);
                                        $name = $data[0];
                                        $status = $data[1] ?? 'N';
                                        $class = ($status === 'A') ? 'status-active' : 'status-inactive';
                                        $dot = ($status === 'A') ? 'dot-green' : 'dot-red';
                                        $label = ($status === 'A') ? 'Actif' : 'Inactif';

                                        echo "<span class='agent-pill $class'>";
                                        echo "<span class='dot $dot'></span>";
                                        echo htmlspecialchars($name) . " ($label)";
                                        echo "</span>";
                                    }
                                } else {
                                    echo "<i style='color:gray;'>Aucun agent lié</i>";
                                }
                                ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="fanages.php?code=<?= urlencode($r['coderuche']) ?>" class="btn-sm btn-view">👁️</a>
                                    <a href="edit_ruche.php?code=<?= urlencode($r['coderuche']) ?>" class="btn-sm btn-edit">✏️</a>
                                    <a href="delete_ruches.php?code=<?= urlencode($r['coderuche']) ?>&admin=1" class="btn-sm btn-del" onclick="return confirm('Supprimer définitivement ?')">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                <?php while ($r = $result->fetch_assoc()): ?>
                    <div style="background: white; padding: 20px; border-radius: 10px; border-left: 6px solid #2e7d32; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 1.1em; color: #2e7d32;"><?= htmlspecialchars($r['nomcomplet']) ?></strong><br>
                            <small style="color: #888;">Code: <?= htmlspecialchars($r['coderuche']) ?></small>
                        </div>
                        <a href="fanages.php?code=<?= urlencode($r['coderuche']) ?>" class="btn-sm btn-view" style="padding: 8px 15px;">Gérer</a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php include 'include/footer.php'; ?>