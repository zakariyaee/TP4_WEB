<?php
// actions/admin-respo/add_creneau.php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation des données requises
    $requiredFields = ['id_terrain', 'jour_semaine', 'heure_debut', 'heure_fin'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
            exit;
        }
    }
    
    // Vérifier que le terrain existe et appartient au responsable si applicable
    $stmt = $pdo->prepare("SELECT id_responsable FROM terrain WHERE id_terrain = :id");
    $stmt->execute([':id' => $data['id_terrain']]);
    $terrain = $stmt->fetch();
    
    if (!$terrain) {
        echo json_encode(['success' => false, 'message' => 'Terrain introuvable']);
        exit;
    }
    
    if ($_SESSION['user_role'] === 'responsable' && $terrain['id_responsable'] !== $_SESSION['user_email']) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez ajouter des créneaux que pour vos propres terrains']);
        exit;
    }
    
    // Validation des heures
    if ($data['heure_debut'] >= $data['heure_fin']) {
        echo json_encode(['success' => false, 'message' => 'L\'heure de fin doit être après l\'heure de début']);
        exit;
    }
    
    // Vérifier les chevauchements de créneaux
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM creneau 
        WHERE id_terrain = :id_terrain 
        AND jour_semaine = :jour_semaine
        AND (
            (heure_debut < :heure_fin AND heure_fin > :heure_debut)
        )
    ");
    
    $checkStmt->execute([
        ':id_terrain' => $data['id_terrain'],
        ':jour_semaine' => $data['jour_semaine'],
        ':heure_debut' => $data['heure_debut'],
        ':heure_fin' => $data['heure_fin']
    ]);
    
    $overlap = $checkStmt->fetch();
    
    if ($overlap['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Ce créneau chevauche un créneau existant']);
        exit;
    }
    
    // Insertion du créneau
    $disponibilite = isset($data['disponibilite']) ? ($data['disponibilite'] == '1' ? 1 : 0) : 1;
    
    $stmt = $pdo->prepare("
        INSERT INTO creneau (id_terrain, jour_semaine, heure_debut, heure_fin, disponibilite)
        VALUES (:id_terrain, :jour_semaine, :heure_debut, :heure_fin, :disponibilite)
    ");
    
    $stmt->execute([
        ':id_terrain' => $data['id_terrain'],
        ':jour_semaine' => $data['jour_semaine'],
        ':heure_debut' => $data['heure_debut'],
        ':heure_fin' => $data['heure_fin'],
        ':disponibilite' => $disponibilite
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Créneau ajouté avec succès']);
    
} catch (PDOException $e) {
    error_log("Erreur add_creneau: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du créneau']);
}
?>