<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id_disponibilite']) || empty($data['statut'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Données manquantes'
        ]);
        exit;
    }
    
    // Vérifier que la disponibilité appartient au joueur
    $stmt = $pdo->prepare("
        UPDATE disponibilite 
        SET statut = :statut 
        WHERE id_disponibilite = :id AND email_joueur = :email
    ");
    
    $stmt->execute([
        ':statut' => $data['statut'],
        ':id' => $data['id_disponibilite'],
        ':email' => $_SESSION['user_email']
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour'
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
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}