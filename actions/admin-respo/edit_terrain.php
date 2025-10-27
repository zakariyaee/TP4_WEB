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
    
    // Validation de l'ID
    if (empty($data['id_terrain'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID du terrain manquant'
        ]);
        exit;
    }
    
    // Validation des champs requis
    $required = ['nom_te', 'categorie', 'type', 'taille', 'prix_heure', 'disponibilite', 'localisation'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Le champ $field est requis"
            ]);
            exit;
        }
    }
    
    // Validation du prix
    if (!is_numeric($data['prix_heure']) || $data['prix_heure'] < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Le prix doit être un nombre positif'
        ]);
        exit;
    }
    
    // Vérifier que le terrain existe
    $stmt = $pdo->prepare("SELECT id_terrain FROM terrain WHERE id_terrain = :id");
    $stmt->execute([':id' => $data['id_terrain']]);
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Terrain non trouvé'
        ]);
        exit;
    }
    
    // Vérifier que le nom n'est pas déjà utilisé par un autre terrain
    $stmt = $pdo->prepare("SELECT id_terrain FROM terrain WHERE nom_te = :nom AND id_terrain != :id");
    $stmt->execute([
        ':nom' => $data['nom_te'],
        ':id' => $data['id_terrain']
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Un autre terrain avec ce nom existe déjà'
        ]);
        exit;
    }
    
    // Mise à jour du terrain
    $sql = "UPDATE terrain 
            SET nom_te = :nom_te,
                categorie = :categorie,
                type = :type,
                taille = :taille,
                prix_heure = :prix_heure,
                localisation = :localisation,
                disponibilite = :disponibilite,
                id_responsable = :id_responsable,
                image = :image
            WHERE id_terrain = :id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':nom_te' => $data['nom_te'],
        ':categorie' => $data['categorie'],
        ':type' => $data['type'],
        ':taille' => $data['taille'],
        ':prix_heure' => $data['prix_heure'],
        ':localisation' => $data['localisation'],
        ':disponibilite' => $data['disponibilite'],
        ':id_responsable' => !empty($data['id_responsable']) ? $data['id_responsable'] : null,
        ':image' => !empty($data['image']) ? $data['image'] : null,
        ':id' => $data['id_terrain']
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Terrain modifié avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la modification du terrain'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur edit_terrain: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la modification du terrain'
    ]);
}
?>