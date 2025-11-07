<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID du terrain manquant']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM terrain WHERE id_terrain = :id");
    $stmt->execute([':id' => $id]);
    $terrain = $stmt->fetch();
    
    if (!$terrain) {
        echo json_encode(['success' => false, 'message' => 'Terrain non trouvé']);
        exit;
    }
    
    // Vérifier les permissions pour les responsables
    if ($_SESSION['user_role'] === 'responsable' && $terrain['id_responsable'] !== $_SESSION['user_email']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas permission d\'accéder à ce terrain']);
        exit;
    }
    
    echo json_encode(['success' => true, 'terrain' => $terrain]);
    
} catch (PDOException $e) {
    error_log("Erreur get_terrain: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du terrain']);
}
?>