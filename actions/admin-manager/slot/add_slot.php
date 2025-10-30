<?php
// actions/admin-manager/slot/add_slot.php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

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
    
    // Vérifier les chevauchements de créneaux (CORRIGÉ)
    // Un créneau chevauche un autre SI ET SEULEMENT SI :
    // - Il commence AVANT la fin de l'autre ET
    // - Il finit APRÈS le début de l'autre
    // Les créneaux consécutifs (ex: 11:00-12:00 et 12:00-13:00) ne chevauchent PAS
    $checkStmt = $pdo->prepare("
        SELECT id_creneaux, heure_debut, heure_fin
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
    
    $overlaps = $checkStmt->fetchAll();
    
    // Vérifier s'il y a un vrai chevauchement (pas juste consécutif)
    foreach ($overlaps as $existing) {
        // Vérifier si c'est un vrai chevauchement et pas juste consécutif
        // Consécutif : nouveau.debut == existing.fin OU nouveau.fin == existing.debut
        $isConsecutive = ($data['heure_debut'] == $existing['heure_fin']) || 
                        ($data['heure_fin'] == $existing['heure_debut']);
        
        if (!$isConsecutive) {
            echo json_encode([
                'success' => false, 
                'message' => 'Ce créneau chevauche un créneau existant (' . 
                            substr($existing['heure_debut'], 0, 5) . ' - ' . 
                            substr($existing['heure_fin'], 0, 5) . ')'
            ]);
            exit;
        }
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
    
    echo json_encode([
        'success' => true, 
        'message' => 'Créneau ajouté avec succès',
        'id_creneaux' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur add_creneau: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du créneau: ' . $e->getMessage()]);
}
?>