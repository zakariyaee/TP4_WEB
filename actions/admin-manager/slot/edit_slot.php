<?php
// actions/admin-respo/edit_creneau.php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    if (empty($data['id_creneaux'])) {
        echo json_encode(['success' => false, 'message' => 'ID du créneau manquant']);
        exit;
    }
    
    $requiredFields = ['id_terrain', 'jour_semaine', 'heure_debut', 'heure_fin'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
            exit;
        }
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
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas permission de modifier ce créneau']);
        exit;
    }
    
    // Vérifier le nouveau terrain si changé
    if ($data['id_terrain'] != $creneau['id_terrain']) {
        $stmt = $pdo->prepare("SELECT id_responsable FROM terrain WHERE id_terrain = :id");
        $stmt->execute([':id' => $data['id_terrain']]);
        $terrain = $stmt->fetch();
        
        if (!$terrain) {
            echo json_encode(['success' => false, 'message' => 'Terrain introuvable']);
            exit;
        }
        
        if ($_SESSION['user_role'] === 'responsable' && $terrain['id_responsable'] !== $_SESSION['user_email']) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez déplacer le créneau que vers vos propres terrains']);
            exit;
        }
    }
    
    // Validation des heures
    if ($data['heure_debut'] >= $data['heure_fin']) {
        echo json_encode(['success' => false, 'message' => 'L\'heure de fin doit être après l\'heure de début']);
        exit;
    }
    
    // Vérifier les chevauchements (sauf avec lui-même)
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM creneau 
        WHERE id_terrain = :id_terrain 
        AND jour_semaine = :jour_semaine
        AND id_creneaux != :id_creneaux
        AND (
            (heure_debut < :heure_fin AND heure_fin > :heure_debut)
        )
    ");
    
    $checkStmt->execute([
        ':id_terrain' => $data['id_terrain'],
        ':jour_semaine' => $data['jour_semaine'],
        ':id_creneaux' => $data['id_creneaux'],
        ':heure_debut' => $data['heure_debut'],
        ':heure_fin' => $data['heure_fin']
    ]);
    
    $overlap = $checkStmt->fetch();
    
    if ($overlap['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Ce créneau chevauche un créneau existant']);
        exit;
    }
    
    // Mise à jour
    $disponibilite = isset($data['disponibilite']) ? ($data['disponibilite'] == '1' ? 1 : 0) : 1;
    
    $stmt = $pdo->prepare("
        UPDATE creneau 
        SET id_terrain = :id_terrain,
            jour_semaine = :jour_semaine,
            heure_debut = :heure_debut,
            heure_fin = :heure_fin,
            disponibilite = :disponibilite
        WHERE id_creneaux = :id_creneaux
    ");
    
    $stmt->execute([
        ':id_terrain' => $data['id_terrain'],
        ':jour_semaine' => $data['jour_semaine'],
        ':heure_debut' => $data['heure_debut'],
        ':heure_fin' => $data['heure_fin'],
        ':disponibilite' => $disponibilite,
        ':id_creneaux' => $data['id_creneaux']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Créneau modifié avec succès']);
    
} catch (PDOException $e) {
    error_log("Erreur edit_creneau: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification du créneau']);
}
?>