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
   
    
    // Récupérer toutes les factures sans fichier PDF
    $sql = "SELECT id_facture FROM facture WHERE fichier_pdf IS NULL OR statut = 'generee'";
    $stmt = $pdo->query($sql);
    $factures = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $count = 0;
    
    foreach ($factures as $id_facture) {
        // Marquer comme générée
        $updateSql = "UPDATE facture SET 
                        fichier_pdf = :fichier,
                        statut = 'generee'
                      WHERE id_facture = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            'fichier' => '/factures/' . date('Y') . '/facture_' . $id_facture . '.pdf',
            'id' => $id_facture
        ]);
        $count++;
    }
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'message' => $count . ' facture(s) générée(s)'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>