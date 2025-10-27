<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

try {
    // Récupérer les données JSON
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id_terrain'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID du terrain manquant'
        ]);
        exit;
    }
    
    $id = $data['id_terrain'];
    
    // Vérifier que le terrain existe
    $stmt = $pdo->prepare("SELECT id_terrain FROM terrain WHERE id_terrain = :id");
    $stmt->execute([':id' => $id]);
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Terrain non trouvé'
        ]);
        exit;
    }
    
    // Vérifier s'il y a des réservations actives
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reservation 
        WHERE id_terrain = :id 
        AND statut IN ('en_attente', 'confirmee')
        AND date_reservation >= NOW()
    ");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de supprimer ce terrain car il a des réservations actives'
        ]);
        exit;
    }
    
    // Supprimer le terrain
    $stmt = $pdo->prepare("DELETE FROM terrain WHERE id_terrain = :id");
    $result = $stmt->execute([':id' => $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Terrain supprimé avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la suppression du terrain'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur delete_terrain: " . $e->getMessage());
    
    // Vérifier si c'est une erreur de contrainte de clé étrangère
    if ($e->getCode() == '23000') {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de supprimer ce terrain car il est lié à d\'autres données (créneaux, tournois...)'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la suppression du terrain'
        ]);
    }
}
?>
