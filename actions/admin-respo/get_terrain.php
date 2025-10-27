<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'ID du terrain manquant'
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM terrain WHERE id_terrain = :id");
    $stmt->execute([':id' => $id]);
    $terrain = $stmt->fetch();
    
    if ($terrain) {
        echo json_encode([
            'success' => true,
            'terrain' => $terrain
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Terrain non trouvé'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur get_terrain: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération du terrain'
    ]);
}
?>