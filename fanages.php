<?php
session_start();
if (!isset($_SESSION['agent'])) { header("Location: login.php"); exit; }
require '../config.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin');
$code = $_GET['code'] ?? '';
$view_rang = $_GET['view_rang'] ?? null; // On utilise le rang pour la reco
$mode = $_GET['mode'] ?? null; 
$sid_view = $_GET['sid'] ?? null; // Pour voir une session spécifique (id_l de ent_fanages)

// 1. Infos de la ruche
$stmt = $conn->prepare("SELECT * FROM ruches WHERE coderuche = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$ruche = $stmt->get_result()->fetch_assoc();

if (!$ruche) { header("Location: index.php"); exit; }

$agent = $_SESSION['agent']; // On garde uniquement le login

// --- LOGIQUE DE SESSION (Modèle En-tête / Détail) ---
if ($mode === 'new') {
    // Nouvelle session : on n'a pas encore d'id_l, il sera créé au moment du POST
    $id_session_actuelle = 'Nouvelle';
    $prochain_rang = 1; // Rang suggéré par défaut, mais modifiable dans le formulaire

} elseif ($mode === 'continue') {
    // On cherche l'id_l de l'en-tête le plus récent pour CETTE ruche ET CET AGENT (Collaboratif !)
    $stmt_last_sess = $conn->prepare("SELECT MAX(id_l) as last_id FROM ent_fanages WHERE coderuche = ? AND login = ? AND status = 'A'");
    $stmt_last_sess->bind_param("ss", $code, $agent);
    $stmt_last_sess->execute();
    $id_session_actuelle = $stmt_last_sess->get_result()->fetch_assoc()['last_id'] ?? null;
    
    if (!$id_session_actuelle) { 
        header("Location: fanages.php?code=$code&mode=new"); 
        exit; 
    }

    // On cherche le rang max dans les détails pour CETTE session
    $stmt_rang = $conn->prepare("SELECT MAX(rang) as max_r FROM det_fanages WHERE id_l = ? AND status = 'A'");
    $stmt_rang->bind_param("i", $id_session_actuelle);
    $stmt_rang->execute();
    $prochain_rang = ($stmt_rang->get_result()->fetch_assoc()['max_r'] ?? 0) + 1;

} elseif ($mode === 'view') {
    $id_session_actuelle = $sid_view;
    // Mode lecture seule
}

// ==========================================
// TRAITEMENT DES FORMULAIRES (POST)
// ==========================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 2a. Suppression d'une session complète (Action indépendante)
    if (isset($_POST['action_delete']) && $isAdmin) {
        $id_to_delete = (int)$_POST['action_delete'];
        
        // Supprimer les détails
        $del_det = $conn->prepare("UPDATE det_fanages set STATUS = 'N' WHERE id_l = ?");
        $del_det->bind_param("i", $id_to_delete);
        $del_det->execute();

        // Supprimer l'en-tête
        $del_ent = $conn->prepare("UPDATE ent_fanages set STATUS = 'N' WHERE id_l = ?");
        $del_ent->bind_param("i", $id_to_delete);
        $del_ent->execute();

        header("Location: fanages.php?code=$code");
        exit;
    }

    // 2b. Fusion des sessions sélectionnées
    if (isset($_POST['action_merge']) && !$mode) {
        $merge_ids = $_POST['merge_ids'] ?? [];
        
        if (count($merge_ids) >= 2) {
            try {
                // On cherche la date la plus récente parmi les sessions à fusionner
                $placeholders = str_repeat('?,', count($merge_ids) - 1) . '?';
                $types = str_repeat('i', count($merge_ids));
                $stmt_date = $conn->prepare("SELECT MAX(d_fanage) as max_date FROM ent_fanages WHERE id_l IN ($placeholders)");
                $stmt_date->bind_param($types, ...$merge_ids);
                $stmt_date->execute();
                $max_date = $stmt_date->get_result()->fetch_assoc()['max_date'] ?? date('Y-m-d H:i:s');

                // Création du nouvel en-tête de session fusionnée
                $ins_ent = $conn->prepare("INSERT INTO ent_fanages (login, coderuche, total_f_fannees, total_f_marquees, d_fanage, status, libelle, rang_depart, rang_fin) VALUES (?, ?, 0, 0, ?, 'A', 'Session Fusionnée', 0, 0)");
                $ins_ent->bind_param("sss", $agent, $code, $max_date);
                $ins_ent->execute();
                $new_id_sess = $conn->insert_id;

                // Copie des détails et passage des anciennes au statut 'F'
                foreach ($merge_ids as $old_id) {
                    $ins_det = $conn->prepare("INSERT INTO det_fanages (login, coderuche, rang, f_fannees, f_marquees, d_fanage, status, id_l) SELECT login, coderuche, rang, f_fannees, f_marquees, d_fanage, 'A', ? FROM det_fanages WHERE id_l = ? AND status = 'A'");
                    $ins_det->bind_param("ii", $new_id_sess, $old_id);
                    $ins_det->execute();

                    $upd_old_ent = $conn->prepare("UPDATE ent_fanages SET status = 'F' WHERE id_l = ?");
                    $upd_old_ent->bind_param("i", $old_id);
                    $upd_old_ent->execute();

                    $upd_old_det = $conn->prepare("UPDATE det_fanages SET status = 'F' WHERE id_l = ? AND status = 'A'");
                    $upd_old_det->bind_param("i", $old_id);
                    $upd_old_det->execute();
                }

                // Recalcul des totaux
                $upd_totaux = $conn->prepare("
                    UPDATE ent_fanages 
                    SET total_f_fannees = (SELECT SUM(f_fannees) FROM det_fanages WHERE id_l = ? AND status = 'A'),
                        total_f_marquees = (SELECT SUM(f_marquees) FROM det_fanages WHERE id_l = ? AND status = 'A'),
                        rang_depart = (SELECT MIN(rang) FROM det_fanages WHERE id_l = ? AND status = 'A'),
                        rang_fin = (SELECT MAX(rang) FROM det_fanages WHERE id_l = ? AND status = 'A')
                    WHERE id_l = ?
                ");
                $upd_totaux->bind_param("iiiii", $new_id_sess, $new_id_sess, $new_id_sess, $new_id_sess, $new_id_sess);
                $upd_totaux->execute();

                $upd_lib = $conn->prepare("
                    UPDATE ent_fanages 
                    SET libelle = CONCAT('Rang ', rang_depart, ' à ', rang_fin)
                    WHERE id_l = ?
                ");
                $upd_lib->bind_param("i", $new_id_sess);
                $upd_lib->execute();

                header("Location: fanages.php?code=$code");
                exit;
                
            } catch (Exception $e) {
                $erreur_merge = "Erreur lors de la fusion : certains rangs se chevauchent peut-être.";
            }
        } else {
            $erreur_merge = "Veuillez sélectionner au moins 2 sessions pour les fusionner.";
        }
    }

    // 2c. Ajout d'un rang (Saisie)
    if (isset($_POST['action_add']) && in_array($mode, ['new', 'continue'])) {
        $fannes = (int)$_POST['f_fannes'];
        $marquees = (int)$_POST['f_marquees'];
        $date = $_POST['d_fanage'];
        $rang_form = (int)$_POST['rang'];

        if ($marquees <= $fannes) {
            
            if ($mode === 'new') {
                $libelle = "Rang " . $rang_form . " à " . $rang_form;
                $ins_ent = $conn->prepare("INSERT INTO ent_fanages (login, coderuche, total_f_fannees, total_f_marquees, d_fanage, status, libelle, rang_depart, rang_fin) VALUES (?, ?, 0, 0, ?, 'A', ?, ?, ?)");
                $ins_ent->bind_param("ssssii", $agent, $code, $date, $libelle, $rang_form, $rang_form);
                $ins_ent->execute();
                $id_sess = $conn->insert_id; 
            } else {
                $id_sess = (int)$_POST['id_session'];
            }

            $ins_det = $conn->prepare("INSERT INTO det_fanages (login, coderuche, rang, f_fannees, f_marquees, d_fanage, status, id_l) VALUES (?, ?, ?, ?, ?, ?, 'A', ?)");
            $ins_det->bind_param("ssiiisi", $agent, $code, $rang_form, $fannes, $marquees, $date, $id_sess);
            $ins_det->execute();

            $upd_totaux = $conn->prepare("
                UPDATE ent_fanages 
                SET total_f_fannees = (SELECT SUM(f_fannees) FROM det_fanages WHERE id_l = ? AND status = 'A'),
                    total_f_marquees = (SELECT SUM(f_marquees) FROM det_fanages WHERE id_l = ? AND status = 'A'),
                    rang_depart = (SELECT MIN(rang) FROM det_fanages WHERE id_l = ? AND status = 'A'),
                    rang_fin = (SELECT MAX(rang) FROM det_fanages WHERE id_l = ? AND status = 'A')
                WHERE id_l = ?
            ");
            $upd_totaux->bind_param("iiiii", $id_sess, $id_sess, $id_sess, $id_sess, $id_sess);
            $upd_totaux->execute();

            $upd_lib = $conn->prepare("
                UPDATE ent_fanages 
                SET libelle = CONCAT('Rang ', rang_depart, ' à ', rang_fin)
                WHERE id_l = ?
            ");
            $upd_lib->bind_param("i", $id_sess);
            $upd_lib->execute();

            header("Location: fanages.php?code=$code&mode=continue&view_rang=" . $rang_form);
            exit;
        } else {
            $erreur = "Calcul impossible : plus de fleurs marquées que de fleurs fanées.";
        }
    }
}

// 3. Vérification de l'existence d'un historique global pour la ruche
$check_hist = $conn->prepare("SELECT COUNT(*) as total FROM ent_fanages WHERE coderuche = ? AND status = 'A'");
$check_hist->bind_param("s", $code);
$check_hist->execute();
$has_history = ($check_hist->get_result()->fetch_assoc()['total'] > 0);

// 4. Récupérations des données selon le contexte
$historique_session = null;
$toutes_sessions = null;

if ($mode) {
    if ($id_session_actuelle !== 'Nouvelle') {
        $sql = "SELECT * FROM det_fanages WHERE id_l = ? AND status = 'A' ORDER BY d_fanage DESC, rang DESC";
        $stmt_h = $conn->prepare($sql);
        $stmt_h->bind_param("i", $id_session_actuelle);
        $stmt_h->execute();
        $historique_session = $stmt_h->get_result();
    }
} else {
    // Tableau de bord
    $sql_all = "SELECT e.id_l, 
                       e.login,
                       e.d_fanage as derniere_date, 
                       e.total_f_fannees, 
                       e.total_f_marquees,
                       e.libelle,
                       (SELECT COUNT(*) FROM det_fanages d WHERE d.id_l = e.id_l AND d.status = 'A') as nb_releves,
                       (SELECT COUNT(*) FROM ent_fanages e2 WHERE e2.coderuche = e.coderuche AND e2.status = 'A' AND DATE(e2.d_fanage) = DATE(e.d_fanage)) as sessions_ce_jour
                FROM ent_fanages e 
                WHERE e.coderuche = ? AND e.status = 'A'
                ORDER BY e.d_fanage DESC, e.id_l DESC";
    $stmt_all = $conn->prepare($sql_all);
    $stmt_all->bind_param("s", $code);
    $stmt_all->execute();
    $toutes_sessions = $stmt_all->get_result();
}

// 5. Récupération des données pour la RECOMMANDATION
$reco_data = null;
if ($view_rang && $id_session_actuelle && $id_session_actuelle !== 'Nouvelle') {
    $stmt_reco = $conn->prepare("SELECT * FROM det_fanages WHERE id_l = ? AND rang = ? AND status = 'A'");
    $stmt_reco->bind_param("ii", $id_session_actuelle, $view_rang);
    $stmt_reco->execute();
    $reco_data = $stmt_reco->get_result()->fetch_assoc();
}

// Fonction utilitaire pour les couleurs des badges
function getBadgeStyle($pourcentage) {
    if ($pourcentage < 40) return "background-color: #dc3545; color: white;"; // Rouge
    if ($pourcentage < 70) return "background-color: #fd7e14; color: white;"; // Orange
    return "background-color: #28a745; color: white;"; // Vert
}

include 'include/header.php';
?>

<style>
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
</style>

<div class="nav-back" style="margin-bottom: 20px;">
    <?php if ($mode): ?>
        <a href="fanages.php?code=<?= $code ?>" class="btn btn-primary">← Retour au résumé de la ruche</a>
    <?php else: ?>
        <a href="index.php" class="btn btn-primary">← Retour à la liste des ruches</a>
    <?php endif; ?>
</div>

<div class="header-action" style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 5px solid #1a792a; margin-bottom: 20px;">
    <h2 style="margin: 0 0 10px 0;">Ruche : <?= htmlspecialchars($ruche['nomcomplet']) ?></h2>
    <span style="background-color: #1a792a; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">
        Code : <?= htmlspecialchars($ruche['coderuche']) ?>
    </span>
</div>

<!-- ========================================== -->
<!-- ZONE DE RECOMMANDATION (Logique WinDev)    -->
<!-- ========================================== -->
<?php if ($reco_data): 
    $f = (float)$reco_data['f_fannees']; 
    $m = (float)$reco_data['f_marquees'];
    $rPourcentage = ($f > 0) ? round(($m / $f) * 100, 1) : 0; 
    $style_fond = "";
    $message = "";
    
    if ($rPourcentage < 40) {
        $style_fond = "background-color: #dc3545; color: white;";
        $message = "Il faut souffler.";
    } elseif ($rPourcentage >= 40 && $rPourcentage < 70) {
        $style_fond = "background-color: #fd7e14; color: white;";
        $message = "Possibilité de soufflage.";
    } else {
        $style_fond = "background-color: #28a745; color: white;";
        $message = "Ne soufflez pas.";
    }
?>
    <div class="recommandation-box" style="<?= $style_fond ?> padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; color: white;">Analyse du relevé (Rang <?= $reco_data['rang'] ?>)</h3>
        <p style="font-size: 1.5em; font-weight: bold; margin: 10px 0; color: white;"><?= $rPourcentage ?> %</p>
        <p style="font-size: 1.2em; margin-bottom: 0; color: white;"><?= $message ?></p>
        <div style="font-size: 0.8em; margin-top: 10px; opacity: 0.8; color: white;">Saisi le <?= date('d/m/Y à H:i', strtotime($reco_data['d_fanage'])) ?></div>
    </div>
<?php endif; ?>

<!-- ========================================== -->
<!-- ÉTAPE 1 : DASHBOARD (Aucun mode sélectionné) -->
<!-- ========================================== -->
<?php if (!$mode): ?>
    <div style="text-align: center; margin: 30px 0;">
        <?php if (!$has_history): ?>
            <div style="padding: 40px; background: #e9ecef; border-radius: 10px;">
                <h3 style="color: #495057;">C'est la première visite pour cette ruche !</h3>
                <p style="color: #6c757d; margin-bottom: 20px;">Aucune donnée de fanage n'a encore été enregistrée.</p>
                <a href="fanages.php?code=<?= $code ?>&mode=new" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.2em;">
                    🚀 Commencer la première session
                </a>
            </div>
        <?php else: ?>
            <div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 30px;">
                <a href="fanages.php?code=<?= $code ?>&mode=continue" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1em; font-weight: bold;">
                    🔄 Reprendre ma dernière session
                </a>
                <a href="fanages.php?code=<?= $code ?>&mode=new" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1em;">
                    🆕 Démarrer une nouvelle session
                </a>
            </div>

            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;">

            <?php if(isset($erreur_merge)): ?>
                <div style="background-color: #dc3545; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                    <?= htmlspecialchars($erreur_merge) ?>
                </div>
            <?php endif; ?>

            <h3 style="text-align: left; color: #333; margin-bottom: 20px;">📚 Historique des Sessions Précédentes</h3>

            <main class="table-wrapper">
                <form method="POST" action="">
                    <?php 
                    // On prépare la liste des sessions pour savoir s'il y a des partielles à l'avance
                    $liste_sessions = [];
                    $nb_sessions_partielles = 0;
                    if ($toutes_sessions) {
                        while ($sess = $toutes_sessions->fetch_assoc()) {
                            $liste_sessions[] = $sess;
                            if ($sess['sessions_ce_jour'] > 1) {
                                $nb_sessions_partielles++;
                            }
                        }
                    }
                    ?>
                    <table class="admin-table">
                    <thead>
                        <tr>
                            <?php if ($nb_sessions_partielles > 1): ?>
                                <th style="width: 60px; text-align: center;">Fusion</th>
                            <?php endif; ?>
                            <th>Session</th>
                            <th>Dernière saisie</th>
                            <th style="text-align: center;">Nbr Relevés</th>
                            <th style="text-align: center;">Moyenne %</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($liste_sessions as $sess): 
                            $moy_p = ($sess['total_f_fannees'] > 0) ? round(($sess['total_f_marquees'] / $sess['total_f_fannees']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <?php if ($nb_sessions_partielles > 1): ?>
                                <td style="text-align: center;">
                                    <?php if ($sess['sessions_ce_jour'] > 1): ?>
                                        <!-- Ajout de la classe "merge-checkbox" pour le JavaScript -->
                                        <input type="checkbox" name="merge_ids[]" value="<?= $sess['id_l'] ?>" style="transform: scale(1.5); cursor: pointer;" class="merge-checkbox">
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td>
                                <strong><?= htmlspecialchars($sess['libelle'] ?? 'Session') ?></strong>
                                <?php if ($sess['sessions_ce_jour'] > 1): ?>
                                    <span title="Attention donnée partielle à fusionner" style="cursor: help; font-size: 1.2em; margin-left: 5px;">⚠️</span>
                                <?php endif; ?>
                                <br>
                                <small style="color: grey;">Agent : <?= htmlspecialchars($sess['login']) ?> | ID: <?= $sess['id_l'] ?></small>
                            </td>
                            <td><?= date('d/m/Y à H:i', strtotime($sess['derniere_date'])) ?></td>
                            <td style="text-align: center;"><span style="background: #e9ecef; padding: 3px 8px; border-radius: 10px;"><?= $sess['nb_releves'] ?></span></td>
                            <td style="text-align: center;">
                                <span style="padding: 5px 10px; border-radius: 12px; font-weight: bold; <?= getBadgeStyle($moy_p) ?>">
                                    <?= $moy_p ?>%
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <a href="fanages.php?code=<?= $code ?>&mode=view&sid=<?= $sess['id_l'] ?>" class="btn btn-small" style="background: #2e7d32; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; display: inline-block;">👁️ Détails</a>
                                
                                <?php if ($isAdmin): ?>
                                    <button type="submit" name="action_delete" value="<?= $sess['id_l'] ?>" class="btn btn-danger btn-small" style="padding: 6px 12px; border-radius: 4px; display: inline-block;" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette session et tous ses relevés ?');">Supprimer 🗑️</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    </table>

                    <?php if ($nb_sessions_partielles > 1): ?>
                    <!-- Le conteneur est caché par défaut (display: none) -->
                    <div id="fusion-container" style="text-align: left; margin-top: 15px; display: none;">
                        <button type="submit" name="action_merge" value="1" class="btn btn-primary" style="font-weight: bold; border: none; padding: 10px 20px;">
                            🔗 Fusionner les sessions sélectionnées
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </main>
        <?php endif; ?>
    </div>
    
    <!-- Script pour afficher/masquer le bouton de fusion -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.merge-checkbox');
            const fusionContainer = document.getElementById('fusion-container');

            if (checkboxes.length > 0 && fusionContainer) {
                checkboxes.forEach(function(box) {
                    box.addEventListener('change', function() {
                        // Compter combien de cases sont cochées
                        let checkedCount = document.querySelectorAll('.merge-checkbox:checked').length;
                        
                        // Si au moins 2 cases sont cochées, on affiche le bouton
                        if (checkedCount >= 2) {
                            fusionContainer.style.display = 'block';
                        } else {
                            fusionContainer.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
<?php endif; ?>

<!-- ========================================== -->
<!-- ÉTAPE 2 : MODE SAISIE OU LECTURE ($mode existe) -->
<!-- ========================================== -->
<?php if ($mode): ?>
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; color: #1a792a;">
            <?= ($mode === 'view') ? 'Consultation' : 'Enregistrement' ?> - Session n°<?= $id_session_actuelle ?>
        </h3>
    </div>

    <div class="fanages-grid" style="display: flex; gap: 20px; flex-wrap: wrap;">
        
        <?php if ($mode === 'new' || $mode === 'continue'): ?>
            <aside style="flex: 1; min-width: 300px; background: #fefefe; padding: 25px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h3 style="border-bottom: 2px solid #1a792a; padding-bottom: 10px;">Nouveau Relevé</h3>
                <?php if(isset($erreur)) echo "<p style='color:red; background: #ffe6e6; padding: 10px; border-radius: 4px;'>$erreur</p>"; ?>
                
                <form method="POST">
                    <input type="hidden" name="action_add" value="1">
                    <input type="hidden" name="id_session" value="<?= ($id_session_actuelle === 'Nouvelle') ? '' : $id_session_actuelle ?>">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Rang (Modifiable)</label>
                        <input type="number" name="rang" value="<?= $prochain_rang ?>" required style="width:100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: #f0f8ff;">
                        <small style="color: grey;">Vous pouvez modifier ce numéro si vous commencez un rang spécifique.</small>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Date / Heure</label>
                        <input type="datetime-local" name="d_fanage" value="<?= date('Y-m-d\TH:i') ?>" required style="width:100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Fleurs Fanées</label>
                        <input type="number" name="f_fannes" min="0" required style="width:100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Fleurs Marquées</label>
                        <input type="number" name="f_marquees" min="0" required style="width:100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="width:100%; padding: 12px; font-size: 1.1em; font-weight: bold;">
                        ✅ Enregistrer ce relevé
                    </button>
                </form>
            </aside>
        <?php endif; ?>

        <main style="flex: 2; min-width: 400px;" class="table-wrapper">
            <h3 style="margin-top: 0; color: #333;">Relevés de cette session</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Date/Heure</th>
                        <th>Fanées</th>
                        <th>Marquées</th>
                        <th>Résultat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($historique_session && $historique_session->num_rows > 0): ?>
                        <?php while ($f = $historique_session->fetch_assoc()): 
                            $p = ($f['f_fannees'] > 0) ? round(($f['f_marquees'] / $f['f_fannees']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><strong>Rang <?= $f['rang'] ?></strong></td>
                            <td style="color: #666;"> <?= date('d/m/Y H:i', strtotime($f['d_fanage'])) ?></td>
                            <!-- <td style="color: #666;"><?= date('H:i', strtotime($f['d_fanage'])) ?></td> -->
                            <td><?= $f['f_fannees'] ?></td>
                            <td><?= $f['f_marquees'] ?></td>
                            <td>
                                <span style="padding: 4px 10px; border-radius: 12px; font-weight: bold; <?= getBadgeStyle($p) ?>">
                                    <?= $p ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="padding: 30px; text-align: center; color: #999; font-style: italic;">Aucune donnée saisie pour le moment. Remplissez le formulaire !</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
<?php endif; ?>

<?php include 'include/footer.php'; ?>