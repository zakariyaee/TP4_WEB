<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id_disponibilite'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID manquant'
        ]);
        exit;
    }
    
    // Supprimer la disponibilité
    $stmt = $pdo->prepare("
        DELETE FROM disponibilite 
        WHERE id_disponibilite = :id AND email_joueur = :email
    ");
    
    $stmt->execute([
        ':id' => $data['id_disponibilite'],
        ':email' => $_SESSION['user_email']
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Disponibilité supprimée avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Disponibilité non trouvée'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
    ]);
}