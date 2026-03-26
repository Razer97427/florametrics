<?php
// 1. On bloque toute sortie de texte parasite dès le début
ob_start();

// 2. Désactiver l'affichage des erreurs qui pourraient corrompre le binaire PDF
error_reporting(0); 
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION['agent'])) { exit; }

// Chemin vers votre config (ajustez si export.php est à la racine ou dans un sous-dossier)
require '../config.php'; 

// 3. Chargement de Dompdf
// Assurez-vous que le chemin vers autoload.inc.php est correct
require_once 'lib/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;


// 4. RÉCUPÉRATION DES DONNÉES (Partie manquante dans votre code)
$result = null;

if (isset($_GET['code'])) {
    $code_ruche = $_GET['code'];
    
    // Si on a des dates, on filtre. Sinon, on prend tout (Export Global).
    if (!empty($_GET['date_debut']) && !empty($_GET['date_fin'])) {
        $stmt = $conn->prepare("
            SELECT d.*, e.libelle, e.rang_depart, e.rang_fin, e.d_fanage as session_date, r.nomcomplet 
            FROM det_fanages d
            JOIN ent_fanages e ON d.id_l = e.id_l
            JOIN ruches r ON e.coderuche = r.coderuche
            WHERE e.coderuche = ? 
            AND d.d_fanage BETWEEN ? AND ? 
            AND d.status = 'A'
            ORDER BY d.d_fanage ASC, d.rang ASC");

        // $stmt = $conn->prepare("
        //     SELECT d.*, e.libelle as session_libelle, e.rang_depart, e.rang_fin, e.d_fanage as session_date, r.nomcomplet 
        //     FROM det_fanages d
        //     JOIN ent_fanages e ON d.id_l = e.id_l
        //     JOIN ruches r ON e.coderuche = r.coderuche
        //     WHERE e.coderuche = ? AND d.status = 'A'
        //     ORDER BY d.d_fanage DESC, d.rang ASC");
        
        // On ajoute " 00:00:00" et " 23:59:59" pour inclure toute la journée
        $debut = $_GET['date_debut'] . " 00:00:00";
        $fin = $_GET['date_fin'] . " 23:59:59";
        
        $stmt->bind_param("sss", $code_ruche, $debut, $fin);
    } else {
        // Mode Global classique (tout l'historique)
        $stmt = $conn->prepare("
            SELECT d.*, e.libelle, e.rang_depart, e.rang_fin, e.d_fanage as session_date, r.nomcomplet 
            FROM det_fanages d
            JOIN ent_fanages e ON d.id_l = e.id_l
            JOIN ruches r ON e.coderuche = r.coderuche
            WHERE e.coderuche = ? AND d.status = 'A'
            ORDER BY d.d_fanage ASC, d.rang ASC");
        $stmt->bind_param("s", $code_ruche);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
}

// 5. CONSTRUCTION DU HTML
// $html = '
// <html>
// <head>
//     <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
//     <style>
//         body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
//         .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2e7d32; padding-bottom: 10px; }
//         table { width: 100%; border-collapse: collapse; }
//         th { background: #2e7d32; color: white; padding: 8px; text-transform: uppercase; }
//         td { padding: 7px; border-bottom: 1px solid #eee; text-align: center; }
//         .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 9px; color: #777; }
//     </style>
// </head>
// <body>
//     <div class="header">
//         <h2>Rapport de Suivi de Fanage</h2>
//         <p>Florametrics exporter le - ' . date('d/m/Y H:i') . '</p>
//     </div>
//     <table>
//         <thead>
//             <tr>
//                 <th>Date</th>
//                 <th>Ruche</th>
//                 <th>Rang</th>
//                 <th>F. Fanées</th>
//                 <th>F. Marquées</th>
//                 <th>% Fanage</th>
//             </tr>
//         </thead>
//         <tbody>';

// while ($row = $result->fetch_assoc()) {
//     $p = ($row['f_fannees'] > 0) ? round(($row['f_marquees'] / $row['f_fannees']) * 100, 1) : 0;
//     $html .= "<tr>
//         <td>".date('d/m/Y', strtotime($row['d_fanage']))."</td>
//         <td>".htmlspecialchars($row['nomcomplet'])."</td>
//         <td>".$row['rang']."</td>
//         <td>".$row['f_fannees']."</td>
//         <td>".$row['f_marquees']."</td>
//         <td><strong>$p%</strong></td>
//     </tr>";
// }

// $html .= '</tbody></table>
//     <div class="footer">Page 1/1</div>
// </body>
// </html>';
$html = '
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.4; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #2e7d32; padding-bottom: 10px; }
        
        /* Correction ici : force le titre du mois à prendre toute la largeur */
        .month-title { 
            background: #2e7d32; 
            color: white; 
            padding: 10px 15px; 
            font-size: 16px; 
            font-weight: bold; 
            margin-top: 30px; 
            margin-bottom: 15px; 
            text-transform: uppercase;
            display: block;
            width: 100%;
            clear: both;
        }
        
        .session-group { 
            background: #f1f8e9; 
            border-left: 5px solid #81c784; 
            padding: 10px; 
            margin-top: 15px;
            font-weight: bold; 
            color: #1b5e20; 
            border-top: 1px solid #ddd;
            border-right: 1px solid #ddd;
            display: block;
        }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        th { background: #f8f9fa; color: #555; padding: 8px; border: 1px solid #ddd; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px; border: 1px solid #ddd; text-align: center; word-wrap: break-word; }
        
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; text-align: right; font-size: 8px; color: #999; }
        
        /* Saut de page automatique avant un nouveau mois si trop bas */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RAPPORT DE FANAGE MENSUEL</h1>
        <h2>' . date('d/m/Y H:i') . '</h2>
        <p>Ruche : <strong>' . htmlspecialchars($code_ruche ?? "Sélection multiple") . '</strong></p>
    </div>';

$current_month = null;
$current_session = null;
$first_entry = true;

$mois_fr = [
    'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars', 'April' => 'Avril', 
    'May' => 'Mai', 'June' => 'Juin', 'July' => 'Juillet', 'August' => 'Août', 
    'September' => 'Septembre', 'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
];

while ($row = $result->fetch_assoc()) {
    $date_obj = strtotime($row['d_fanage']);
    $month_name = $mois_fr[date('F', $date_obj)] . ' ' . date('Y', $date_obj);
    
    // 1. RUPTURE PAR MOIS
    if ($current_month !== $month_name) {
        // Si ce n'est pas le premier mois, on ferme le tableau de la session précédente
        if (!$first_entry) {
            $html .= '</tbody></table>';
            $current_session = null; // On réinitialise la session pour forcer l'en-tête sous le nouveau mois
        }
        
        $current_month = $month_name;
        // On peut ajouter class="page-break" ici si on veut un mois par page
        $html .= '<div class="month-title">' . $month_name . '</div>';
    }

    // 2. RUPTURE PAR SESSION
    if ($current_session !== $row['id_l']) {
        if ($current_session !== null && !$first_entry) {
            $html .= '</tbody></table>';
        }
        
        $current_session = $row['id_l'];
        
        $html .= '
        <div class="session-group">
            📅 Session du ' . date('d/m/Y', $date_obj) . ' — Rang ' . $row['rang_depart'] . ' à ' . $row['rang_fin'] . '
        </div>
        <table nobr="true">
            <thead>
                <tr>
                    <th width="20%">Rang</th>
                    <th width="30%">Agent</th>
                    <th width="15%">F. Fanées</th>
                    <th width="15%">F. Marquées</th>
                    <th width="20%">% Fanage</th>
                    <th width="20%">Heure</th>
                </tr>
            </thead>
            <tbody>';
    }

    $p = ($row['f_fannees'] > 0) ? round(($row['f_marquees'] / $row['f_fannees']) * 100, 1) : 0;
    
    $html .= '
        <tr>
            <td>Rang ' . $row['rang'] . '</td>
            <td>' . htmlspecialchars($row['login']) . '</td>
            <td>' . $row['f_fannees'] . '</td>
            <td>' . $row['f_marquees'] . '</td>
            <td><strong>' . $p . '%</strong></td>
            <td><strong>' . date('H:i', $date_obj) . '</strong></td>
        </tr>';
        
    $first_entry = false;
}

$html .= '</tbody></table>
    <div class="footer">Généré le ' . date('d/m/Y H:i') . ' | Florametrics</div>
</body>
</html>';

// 6. NETTOYAGE FINAL DU TAMPON (Supprime le message de maintenance)
ob_end_clean(); 

// 7. GÉNÉRATION DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// // 8. ENVOI AU NAVIGATEUR
// header('Content-Type: application/pdf');
// header('Content-Disposition: attachment; filename="rapport_fanage_'.date('Ymd').'.pdf"');
// echo $dompdf->output();

// 8. DÉTERMINATION DU NOM DU FICHIER
$filename = "rapport_fanage_" . date('Ymd'); // Nom par défaut

if (isset($_GET['date_debut']) && isset($_GET['date_fin'])) {
    $debut = $_GET['date_debut'];
    $fin = $_GET['date_fin'];

    if ($debut === $fin) {
        // Si c'est la même journée
        $filename = "rapport_fanage_" . date('Ymd', strtotime($debut));
    } else {
        // Si c'est une période (ex: rapport_20240301_au_20240315)
        $filename = "rapport_fanage_" . date('Ymd', strtotime($debut)) . "_au_" . date('Ymd', strtotime($fin));
    }
} elseif (isset($_GET['code'])) {
    // Si c'est un export global sans dates précises
    $filename = "rapport_global_" . htmlspecialchars($_GET['code']) . "_" . date('Ymd');
}

// Nettoyage du nom de fichier (supprimer espaces ou caractères spéciaux éventuels)
$filename = str_replace(' ', '_', $filename) . ".pdf";

// ENVOI AU NAVIGATEUR
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $dompdf->output();
exit;