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
    
    // Vérifier que le nom du terrain n'existe pas déjà
    $stmt = $pdo->prepare("SELECT id_terrain FROM terrain WHERE nom_te = :nom");
    $stmt->execute([':nom' => $data['nom_te']]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Un terrain avec ce nom existe déjà'
        ]);
        exit;
    }
    
    // Insertion du terrain
    $sql = "INSERT INTO terrain (nom_te, categorie, type, taille, prix_heure, localisation, disponibilite, id_responsable, image) 
            VALUES (:nom_te, :categorie, :type, :taille, :prix_heure, :localisation, :disponibilite, :id_responsable, :image)";
    
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
        ':image' => !empty($data['image']) ? $data['image'] : null
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Terrain ajouté avec succès',
            'id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'ajout du terrain'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur add_terrain: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout du terrain'
    ]);
}
?>