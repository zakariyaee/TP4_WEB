<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

header('Content-Type: application/json');

// Vérifier l'authentification
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['admin', 'responsable'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    
    // Requête pour récupérer toutes les factures avec détails
    $sql = "SELECT 
                f.id_facture,
                f.date_facture,
                f.montant_total,
                f.montant_terrain,
                f.montant_objets,
                f.tva,
                f.statut,
                f.fichier_pdf,
                f.date_envoi,
                f.notes,
                e.nom_equipe,
                e.email_equipe,
                r.id_reservation,
                r.date_reservation,
                r.statut as statut_reservation,
                t.nom_te as nom_terrain,
                u.nom as nom_client_nom,
                u.prenom as nom_client_prenom,
                u.email as email_client,
                CONCAT('INV-', YEAR(f.date_facture), '-', LPAD(f.id_facture, 3, '0')) as numero_facture,
                CASE 
                    WHEN r.statut IN ('confirmee', 'terminee') AND f.date_envoi IS NOT NULL THEN 'payee'
                    WHEN r.statut = 'annulee' THEN 'annulee'
                    WHEN DATEDIFF(NOW(), r.date_reservation) > 7 AND f.date_envoi IS NULL THEN 'retard'
                    ELSE 'attente'
                END as statut_paiement
            FROM facture f
            LEFT JOIN equipe e ON f.id_equipe = e.id_equipe
            LEFT JOIN reservation r ON f.id_reservation = r.id_reservation
            LEFT JOIN terrain t ON r.id_terrain = t.id_terrain
            LEFT JOIN utilisateur u ON r.id_joueur = u.email
            ORDER BY f.date_facture DESC";
    
    $stmt = $pdo->query($sql);
    $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données
    foreach ($factures as &$facture) {
        $facture['nom_client'] = $facture['nom_client_prenom'] . ' ' . $facture['nom_client_nom'];
        unset($facture['nom_client_nom'], $facture['nom_client_prenom']);
    }
    
    // Calculer les statistiques
    $stats = [
        'total' => 0,
        'payees' => 0,
        'attente' => 0,
        'retard' => 0
    ];
    
    foreach ($factures as $facture) {
        $montant = floatval($facture['montant_total']);
        $stats['total'] += $montant;
        
        switch ($facture['statut_paiement']) {
            case 'payee':
                $stats['payees'] += $montant;
                break;
            case 'attente':
                $stats['attente'] += $montant;
                break;
            case 'retard':
                $stats['retard']++;
                break;
        }
    }
    
    // Arrondir les montants
    $stats['total'] = round($stats['total'], 2);
    $stats['payees'] = round($stats['payees'], 2);
    $stats['attente'] = round($stats['attente'], 2);
    
    echo json_encode([
        'success' => true,
        'factures' => $factures,
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des factures: ' . $e->getMessage()
    ]);
}
?>