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

    if (!is_array($data)) {
        $data = [];
    } else {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }
    }
    
    // Validation des champs requis
    $required = ['nom_te', 'categorie', 'type', 'taille', 'prix_heure', 'disponibilite', 'ville', 'localisation'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
            exit;
        }
    }
    
    // Validation du prix
    if (!is_numeric($data['prix_heure']) || $data['prix_heure'] < 0) {
        echo json_encode(['success' => false, 'message' => 'Le prix doit être un nombre positif']);
        exit;
    }

    if (!empty($data['ville'])) {
        if (function_exists('mb_convert_case')) {
            $data['ville'] = mb_convert_case($data['ville'], MB_CASE_TITLE, 'UTF-8');
        } else {
            $data['ville'] = ucwords(strtolower($data['ville']));
        }
    }

    $villeLength = function_exists('mb_strlen') ? mb_strlen($data['ville']) : strlen($data['ville']);
    if ($villeLength > 100) {
        echo json_encode(['success' => false, 'message' => 'La ville ne peut pas dépasser 100 caractères']);
        exit;
    }
    
    // Vérifier que le nom du terrain n'existe pas déjà
    $stmt = $pdo->prepare("SELECT id_terrain FROM terrain WHERE nom_te = :nom");
    $stmt->execute([':nom' => $data['nom_te']]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Un terrain avec ce nom existe déjà']);
        exit;
    }
    
    // Traiter l'image
    $imageName = null;
    if (!empty($data['image']) && strpos($data['image'], 'data:image') === 0) {
        $imageName = processImageUpload($data['image'], $data['nom_te']);
        
        if (!$imageName) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload de l\'image']);
            exit;
        }
    }
    
    // Pour les responsables, assigner le terrain à leur email
    $id_responsable = $_SESSION['user_role'] === 'responsable' ? $_SESSION['user_email'] : 
                      (!empty($data['id_responsable']) ? $data['id_responsable'] : null);
    
    // Insertion du terrain
    $sql = "INSERT INTO terrain (nom_te, categorie, type, taille, prix_heure, ville, localisation, disponibilite, id_responsable, image) 
            VALUES (:nom_te, :categorie, :type, :taille, :prix_heure, :ville, :localisation, :disponibilite, :id_responsable, :image)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':nom_te' => $data['nom_te'],
        ':categorie' => $data['categorie'],
        ':type' => $data['type'],
        ':taille' => $data['taille'],
        ':prix_heure' => $data['prix_heure'],
        ':ville' => $data['ville'],
        ':localisation' => $data['localisation'],
        ':disponibilite' => $data['disponibilite'],
        ':id_responsable' => $id_responsable,
        ':image' => $imageName
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Terrain ajouté avec succès', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du terrain']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur add_terrain: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du terrain']);
}

function processImageUpload($base64Image, $terrainName) {
    // CORRECTION: Ajout du / à la fin et chemin absolu
    $uploadDir = __DIR__ . '/../../../assets/images/terrains/';
    
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Impossible de créer le répertoire: " . $uploadDir);
            return null;
        }
    }
    
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
        $format = $matches[1];
        $base64Data = substr($base64Image, strpos($base64Image, ',') + 1);
        $imageData = base64_decode($base64Data);
        
        if ($imageData === false) {
            error_log("Échec du décodage base64");
            return null;
        }
        
        $fileName = sanitizeFileName($terrainName) . '_' . time() . '.' . $format;
        $filePath = $uploadDir . $fileName;
        
        if (file_put_contents($filePath, $imageData)) {
            error_log("Image sauvegardée: " . $filePath);
            return $fileName;
        } else {
            error_log("Échec de l'écriture du fichier: " . $filePath);
        }
    }
    
    return null;
}

function sanitizeFileName($name) {
    $name = preg_replace('/[^a-zA-Z0-9-_]/', '_', $name);
    return substr($name, 0, 50);
}
?>