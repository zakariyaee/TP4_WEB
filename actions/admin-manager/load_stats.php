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
        $sql = "
            SELECT 
                f.montant_total,
                f.date_envoi,
                r.statut AS statut_reservation
            FROM facture f
            LEFT JOIN reservation r ON f.id_reservation = r.id_reservation
            LEFT JOIN terrain t ON r.id_terrain = t.id_terrain
            WHERE t.id_responsable = :email
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $userEmail]);
    } else {
        $sql = "
            SELECT 
                f.montant_total,
                f.date_envoi,
                r.statut AS statut_reservation
            FROM facture f
            LEFT JOIN reservation r ON f.id_reservation = r.id_reservation
        ";
        
        $stmt = $pdo->query($sql);
    }
    
    $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul des statistiques
    $stats = [
        'total' => 0,
        'payees' => 0,
        'attente' => 0
    ];
    
    foreach ($factures as $facture) {
        $montant = floatval($facture['montant_total']);
        $stats['total'] += $montant;
        
        // Déterminer le statut de paiement
        $isPaid = in_array($facture['statut_reservation'], ['confirmee', 'terminee']) 
                  && !is_null($facture['date_envoi']);
        
        if ($isPaid) {
            $stats['payees'] += $montant;
        } else {
            $stats['attente'] += $montant;
        }
    }
    
    // Arrondir les montants
    $stats['total'] = round($stats['total'], 2);
    $stats['payees'] = round($stats['payees'], 2);
    $stats['attente'] = round($stats['attente'], 2);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur : ' . $e->getMessage()
    ]);
}
?>