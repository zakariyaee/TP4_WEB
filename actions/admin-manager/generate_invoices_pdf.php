<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Vérifier l'authentification
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['admin', 'responsable'])) {
    http_response_code(403);
    exit('Non autorisé');
}

// Récupérer l'ID de la facture
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    exit('ID facture manquant');
}

$id_facture = intval($_GET['id']);

try {
    // Récupérer les détails de la facture
    $sql = "SELECT 
                f.*,
                e.nom_equipe,
                e.email_equipe,
                u.email as email_client,
                u.nom as nom_client,
                u.prenom as prenom_client,
                u.num_tele,
                t.nom_te as nom_terrain,
                t.localisation,
                r.date_reservation,
                r.id_creneau,
                c.heure_debut,
                c.heure_fin,
                CONCAT('INV-', YEAR(f.date_facture), '-', LPAD(f.id_facture, 4, '0')) as numero_facture
            FROM facture f
            LEFT JOIN equipe e ON f.id_equipe = e.id_equipe
            LEFT JOIN reservation r ON f.id_reservation = r.id_reservation
            LEFT JOIN terrain t ON r.id_terrain = t.id_terrain
            LEFT JOIN creneau c ON r.id_creneau = c.id_creneaux
            LEFT JOIN utilisateur u ON r.id_joueur = u.email
            WHERE f.id_facture = :id_facture";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_facture' => $id_facture]);
    $facture = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$facture) {
        http_response_code(404);
        exit('Facture introuvable');
    }
    
    // Récupérer les objets de la réservation
    $sqlObjets = "SELECT o.nom_objet, o.prix, ro.quantite, (o.prix * ro.quantite) as total
                  FROM reservation_objet ro
                  JOIN objet o ON ro.id_object = o.id_object
                  WHERE ro.id_reservation = :id_reservation";
    $stmtObjets = $pdo->prepare($sqlObjets);
    $stmtObjets->execute(['id_reservation' => $facture['id_reservation']]);
    $objets = $stmtObjets->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer la TVA
    $montant_ht = $facture['montant_total'] / (1 + $facture['tva'] / 100);
    $montant_tva = $facture['montant_total'] - $montant_ht;
    
    // Créer le HTML de la facture
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; }
            .header { margin-bottom: 30px; border-bottom: 3px solid #16a34a; padding-bottom: 20px; }
            .header-left { float: left; width: 50%; }
            .header-right { float: right; width: 50%; text-align: right; }
            .company { font-size: 24px; font-weight: bold; color: #16a34a; margin-bottom: 10px; }
            .invoice-title { font-size: 32px; font-weight: bold; color: #333; }
            .invoice-number { font-size: 18px; color: #666; margin-top: 5px; }
            .clear { clear: both; }
            .info-section { margin: 30px 0; }
            .info-box { display: inline-block; width: 48%; vertical-align: top; }
            .info-box-right { float: right; }
            .info-title { font-weight: bold; font-size: 14px; margin-bottom: 10px; color: #16a34a; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th { background: #16a34a; color: white; padding: 12px; text-align: left; font-weight: bold; }
            td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .total-section { margin-top: 30px; }
            .total-row { margin: 5px 0; text-align: right; }
            .total-label { display: inline-block; width: 200px; font-weight: bold; }
            .total-value { display: inline-block; width: 150px; text-align: right; }
            .grand-total { font-size: 20px; color: #16a34a; font-weight: bold; border-top: 2px solid #16a34a; padding-top: 10px; margin-top: 10px; }
            .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #666; font-size: 10px; }
            .notes { background: #f9fafb; padding: 15px; border-left: 4px solid #16a34a; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="header-left">
                    <div class="company">GESTION TERRAINS</div>
                    <div>Football & Sports</div>
                    <div>123 Avenue Mohammed V</div>
                    <div>Tétouan, Maroc</div>
                    <div>Tél: +212 539 123 456</div>
                </div>
                <div class="header-right">
                    <div class="invoice-title">FACTURE</div>
                    <div class="invoice-number">' . htmlspecialchars($facture['numero_facture']) . '</div>
                    <div>Date: ' . date('d/m/Y', strtotime($facture['date_facture'])) . '</div>
                </div>
                <div class="clear"></div>
            </div>
            
            <div class="info-section">
                <div class="info-box">
                    <div class="info-title">FACTURER À:</div>
                    <div><strong>' . htmlspecialchars($facture['prenom_client'] . ' ' . $facture['nom_client']) . '</strong></div>
                    <div>Équipe: ' . htmlspecialchars($facture['nom_equipe']) . '</div>
                    <div>' . htmlspecialchars($facture['email_client']) . '</div>
                    <div>' . htmlspecialchars($facture['num_tele']) . '</div>
                </div>
                
                <div class="info-box info-box-right">
                    <div class="info-title">DÉTAILS RÉSERVATION:</div>
                    <div><strong>Terrain:</strong> ' . htmlspecialchars($facture['nom_terrain']) . '</div>
                    <div><strong>Lieu:</strong> ' . htmlspecialchars($facture['localisation']) . '</div>
                    <div><strong>Date:</strong> ' . date('d/m/Y', strtotime($facture['date_reservation'])) . '</div>
                    <div><strong>Horaire:</strong> ' . substr($facture['heure_debut'], 0, 5) . ' - ' . substr($facture['heure_fin'], 0, 5) . '</div>
                </div>
                <div class="clear"></div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-center">Quantité</th>
                        <th class="text-right">Prix unitaire</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Location terrain</strong> - ' . htmlspecialchars($facture['nom_terrain']) . '</td>
                        <td class="text-center">1</td>
                        <td class="text-right">' . number_format($facture['montant_terrain'], 2, ',', ' ') . ' DH</td>
                        <td class="text-right">' . number_format($facture['montant_terrain'], 2, ',', ' ') . ' DH</td>
                    </tr>';
    
    // Ajouter les objets
    foreach ($objets as $objet) {
        $html .= '
                    <tr>
                        <td>' . htmlspecialchars($objet['nom_objet']) . '</td>
                        <td class="text-center">' . $objet['quantite'] . '</td>
                        <td class="text-right">' . number_format($objet['prix'], 2, ',', ' ') . ' DH</td>
                        <td class="text-right">' . number_format($objet['total'], 2, ',', ' ') . ' DH</td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>
            
            <div class="total-section">
                <div class="total-row">
                    <span class="total-label">Sous-total HT:</span>
                    <span class="total-value">' . number_format($montant_ht, 2, ',', ' ') . ' DH</span>
                </div>
                <div class="total-row">
                    <span class="total-label">TVA (' . $facture['tva'] . '%):</span>
                    <span class="total-value">' . number_format($montant_tva, 2, ',', ' ') . ' DH</span>
                </div>
                <div class="total-row grand-total">
                    <span class="total-label">TOTAL TTC:</span>
                    <span class="total-value">' . number_format($facture['montant_total'], 2, ',', ' ') . ' DH</span>
                </div>
            </div>';
    
    if ($facture['notes']) {
        $html .= '
            <div class="notes">
                <strong>Notes:</strong><br>
                ' . nl2br(htmlspecialchars($facture['notes'])) . '
            </div>';
    }
    
    $html .= '
            <div class="footer">
                <p><strong>Modes de paiement acceptés:</strong> Espèces • Carte bancaire • Virement</p>
                <p>Merci de votre confiance !</p>
                <p>En cas de questions, contactez-nous à contact@tour.com</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Générer le PDF avec Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Créer le dossier si nécessaire
    $year = date('Y');
    $dir = "../../factures/$year";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    $pdfFile = "$dir/facture_{$facture['numero_facture']}.pdf";
    file_put_contents($pdfFile, $dompdf->output());
    
    // Mettre à jour la base de données
    $updateSql = "UPDATE facture SET 
                    fichier_pdf = :fichier,
                    statut = 'generee'
                  WHERE id_facture = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        'fichier' => "/factures/$year/facture_{$facture['numero_facture']}.pdf",
        'id' => $id_facture
    ]);
    
    // Envoyer le PDF au navigateur
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="facture_' . $facture['numero_facture'] . '.pdf"');
    echo $dompdf->output();
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Erreur lors de la génération du PDF: ' . $e->getMessage());
}
?>