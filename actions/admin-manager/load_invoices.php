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
    $userRole = $_SESSION['user_role'];
    $userEmail = $_SESSION['user_email'];
    
    // Construire la requête selon le rôle
    if ($userRole === 'responsable') {
        // RESPONSABLE : Voir uniquement les factures de ses terrains
        $sql = "
            SELECT 
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
                r.statut AS statut_reservation,
                t.nom_te AS nom_terrain,
                u.nom AS nom_client_nom,
                u.prenom AS nom_client_prenom,
                u.email AS email_client,
                CONCAT('INV-', YEAR(f.date_facture), '-', LPAD(f.id_facture, 3, '0')) AS numero_facture,
                CASE
                    WHEN r.statut IN ('confirmee', 'terminee') AND f.date_envoi IS NOT NULL THEN 'payee'
                    WHEN r.statut = 'annulee' THEN 'annulee'
                    ELSE 'attente'
                END AS statut_paiement
            FROM facture f
            LEFT JOIN equipe e ON f.id_equipe = e.id_equipe
            LEFT JOIN reservation r ON f.id_reservation = r.id_reservation
            LEFT JOIN terrain t ON r.id_terrain = t.id_terrain
            LEFT JOIN utilisateur u ON r.id_joueur = u.email
            WHERE t.id_responsable = :email
            ORDER BY f.date_facture DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $userEmail]);
        
    } else {
        // ADMIN : Voir toutes les factures
        $sql = "
            SELECT 
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
                r.statut AS statut_reservation,
                t.nom_te AS nom_terrain,
                u.nom AS nom_client_nom,
                u.prenom AS nom_client_prenom,
                u.email AS email_client,
                CONCAT('INV-', YEAR(f.date_facture), '-', LPAD(f.id_facture, 3, '0')) AS numero_facture,
                CASE
                    WHEN r.statut IN ('confirmee', 'terminee') AND f.date_envoi IS NOT NULL THEN 'payee'
                    WHEN r.statut = 'annulee' THEN 'annulee'
                    ELSE 'attente'
                END AS statut_paiement
            FROM facture f
            LEFT JOIN equipe e ON f.id_equipe = e.id_equipe
            LEFT JOIN reservation r ON f.id_reservation = r.id_reservation
            LEFT JOIN terrain t ON r.id_terrain = t.id_terrain
            LEFT JOIN utilisateur u ON r.id_joueur = u.email
            ORDER BY f.date_facture DESC
        ";
        
        $stmt = $pdo->query($sql);
    }
    
    $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données
    foreach ($factures as &$facture) {
        $facture['nom_client'] = $facture['nom_client_prenom'] . ' ' . $facture['nom_client_nom'];
        unset($facture['nom_client_nom'], $facture['nom_client_prenom']);
    }
    
    // ============================================
    // CALCUL DES STATISTIQUES (3 UNIQUEMENT)
    // ============================================
    $stats = [
        'total' => 0,      // Montant total de toutes les factures
        'payees' => 0,     // Montant des factures payées
        'attente' => 0     // Montant des factures non payées (en attente)
    ];
    
    foreach ($factures as $facture) {
        $montant = floatval($facture['montant_total']);
        
        // Toujours ajouter au total
        $stats['total'] += $montant;
        
        // Répartir selon le statut de paiement
        switch ($facture['statut_paiement']) {
            case 'payee':
                // Facture payée (confirmée/terminée ET envoyée)
                $stats['payees'] += $montant;
                break;
                
            case 'attente':
            case 'annulee':
            default:
                // Facture non payée (en attente, annulée, etc.)
                $stats['attente'] += $montant;
                break;
        }
    }
    
    // Arrondir les montants à 2 décimales
    $stats['total'] = round($stats['total'], 2);
    $stats['payees'] = round($stats['payees'], 2);
    $stats['attente'] = round($stats['attente'], 2);
    $_SESSION['total_argents'] = $stats['total'];
    $_SESSION['total_payees'] = $stats['payees'];
    $_SESSION['total_attente'] = $stats['attente'];
    
    // Retourner la réponse JSON
    echo json_encode([
        'success' => true,
        'factures' => $factures,
        'stats' => $stats,
        'user_role' => $userRole, // Pour debug
        'debug' => [
            'total_factures' => count($factures),
            'nb_payees' => count(array_filter($factures, fn($f) => $f['statut_paiement'] === 'payee')),
            'nb_attente' => count(array_filter($factures, fn($f) => $f['statut_paiement'] !== 'payee'))
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des factures: ' . $e->getMessage()
    ]);
}
?>