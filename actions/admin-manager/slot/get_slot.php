<?php
// actions/admin-respo/get_creneau.php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID du créneau manquant']);
        exit;
    }
    
    $sql = "SELECT c.*, t.id_responsable
            FROM creneau c
            INNER JOIN terrain t ON c.id_terrain = t.id_terrain
            WHERE c.id_creneaux = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $creneau = $stmt->fetch();
    
    if (!$creneau) {
        echo json_encode(['success' => false, 'message' => 'Créneau non trouvé']);
        exit;
    }
    
    // Vérifier les permissions pour les responsables
    if ($_SESSION['user_role'] === 'responsable' && $creneau['id_responsable'] !== $_SESSION['user_email']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas permission d\'accéder à ce créneau']);
        exit;
    }
    
    // Retirer id_responsable de la réponse
    unset($creneau['id_responsable']);
    
    echo json_encode(['success' => true, 'creneau' => $creneau]);
    
} catch (PDOException $e) {
    error_log("Erreur get_creneau: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du créneau']);
}
?>