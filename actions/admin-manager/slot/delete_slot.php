<?php
// actions/admin-respo/delete_creneau.php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id_creneaux'])) {
        echo json_encode(['success' => false, 'message' => 'ID du créneau manquant']);
        exit;
    }
    
    // Vérifier que le créneau existe et les permissions
    $stmt = $pdo->prepare("
        SELECT c.*, t.id_responsable
        FROM creneau c
        INNER JOIN terrain t ON c.id_terrain = t.id_terrain
        WHERE c.id_creneaux = :id
    ");
    $stmt->execute([':id' => $data['id_creneaux']]);
    $creneau = $stmt->fetch();
    
    if (!$creneau) {
        echo json_encode(['success' => false, 'message' => 'Créneau introuvable']);
        exit;
    }
    
    if ($_SESSION['user_role'] === 'responsable' && $creneau['id_responsable'] !== $_SESSION['user_email']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas permission de supprimer ce créneau']);
        exit;
    }
    
    // Vérifier s'il y a des réservations actives pour ce créneau
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reservation 
        WHERE id_creneau = :id_creneau 
        AND statut IN ('en_attente', 'confirmee')
    ");
    $checkStmt->execute([':id_creneau' => $data['id_creneaux']]);
    $hasReservations = $checkStmt->fetch();
    
    if ($hasReservations['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Impossible de supprimer ce créneau car il a des réservations actives']);
        exit;
    }
    
    // Suppression
    $stmt = $pdo->prepare("DELETE FROM creneau WHERE id_creneaux = :id");
    $stmt->execute([':id' => $data['id_creneaux']]);
    
    echo json_encode(['success' => true, 'message' => 'Créneau supprimé avec succès']);
    
} catch (PDOException $e) {
    error_log("Erreur delete_creneau: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du créneau']);
}
?>