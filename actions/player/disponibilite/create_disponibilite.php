<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    if (empty($data['date_debut']) || empty($data['date_fin']) || 
        empty($data['position']) || empty($data['niveau'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Tous les champs obligatoires doivent être remplis'
        ]);
        exit;
    }
    
    // Vérifier que la date de fin est après la date de début
    if (strtotime($data['date_fin']) <= strtotime($data['date_debut'])) {
        echo json_encode([
            'success' => false,
            'message' => 'L\'heure de fin doit être après l\'heure de début'
        ]);
        exit;
    }
    
    // Insérer la disponibilité
    $stmt = $pdo->prepare("
        INSERT INTO disponibilite 
        (email_joueur, date_debut, date_fin, position, niveau, id_terrain, rayon_km, description, statut) 
        VALUES (:email, :date_debut, :date_fin, :position, :niveau, :id_terrain, :rayon_km, :description, 'actif')
    ");
    
    $stmt->execute([
        ':email' => $_SESSION['user_email'],
        ':date_debut' => $data['date_debut'],
        ':date_fin' => $data['date_fin'],
        ':position' => $data['position'],
        ':niveau' => $data['niveau'],
        ':id_terrain' => !empty($data['id_terrain']) ? $data['id_terrain'] : null,
        ':rayon_km' => !empty($data['rayon_km']) ? $data['rayon_km'] : null,
        ':description' => !empty($data['description']) ? $data['description'] : null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Disponibilité ajoutée avec succès',
        'id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création: ' . $e->getMessage()
    ]);
}