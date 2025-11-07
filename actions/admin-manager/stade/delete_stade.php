<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';
checkAdminOrRespo();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id_terrain'])) {
        echo json_encode(['success' => false, 'message' => 'ID du terrain manquant']);
        exit;
    }
    
    // Vérifier que le terrain existe
    $stmt = $pdo->prepare("SELECT id_terrain, id_responsable FROM terrain WHERE id_terrain = :id");
    $stmt->execute([':id' => $data['id_terrain']]);
    $terrain = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$terrain) {
        echo json_encode(['success' => false, 'message' => 'Terrain non trouvé']);
        exit;
    }
    
    // Vérification des permissions
    if ($_SESSION['user_role'] === 'responsable' && $terrain['id_responsable'] !== $_SESSION['user_email']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas permission de supprimer ce terrain']);
        exit;
    }
    
    // Vérifier s'il y a des réservations actives
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservation WHERE id_terrain = :id 
                           AND statut IN ('en_attente', 'confirmee') AND date_reservation >= NOW()");
    $stmt->execute([':id' => $data['id_terrain']]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer ce terrain car il a des réservations actives']);
        exit;
    }
    
    // Supprimer le terrain
    $stmt = $pdo->prepare("DELETE FROM terrain WHERE id_terrain = :id");
    $result = $stmt->execute([':id' => $data['id_terrain']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Terrain supprimé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du terrain']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur delete_terrain: " . $e->getMessage());
    if ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer ce terrain car il est lié à d\'autres données']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du terrain']);
    }
}
?>