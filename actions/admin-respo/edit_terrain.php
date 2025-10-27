<?php
// actions/admin-respo/edit_terrain.php
require_once '../../config/database.php';
require_once '../../check_auth.php';

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
    $stmt = $pdo->prepare("SELECT id_terrain, image, id_responsable FROM terrain WHERE id_terrain = :id");
    $stmt->execute([':id' => $data['id_terrain']]);
    $terrain = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$terrain) {
        echo json_encode(['success' => false, 'message' => 'Terrain non trouvé']);
        exit;
    }
    
    // Vérification des permissions
    if ($_SESSION['user_role'] === 'responsable' && $terrain['id_responsable'] !== $_SESSION['user_email']) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas permission de modifier ce terrain']);
        exit;
    }
    
    // Validation des champs requis
    $required = ['nom_te', 'categorie', 'type', 'taille', 'prix_heure', 'disponibilite', 'localisation'];
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
    
    // Vérifier que le nom n'est pas déjà utilisé par un autre terrain
    $stmt = $pdo->prepare("SELECT id_terrain FROM terrain WHERE nom_te = :nom AND id_terrain != :id");
    $stmt->execute([':nom' => $data['nom_te'], ':id' => $data['id_terrain']]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Un autre terrain avec ce nom existe déjà']);
        exit;
    }
    
    // Traiter la nouvelle image
    $imageName = $terrain['image'];
    if (!empty($data['image']) && strpos($data['image'], 'data:image') === 0) {
        if ($terrain['image']) {
            $oldImagePath = __DIR__ . '/../../assets/images/terrains/' . $terrain['image'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
        
        $imageName = processImageUpload($data['image'], $data['nom_te']);
        
        if (!$imageName) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload de l\'image']);
            exit;
        }
    } elseif ($data['image'] === '' && $terrain['image']) {
        $oldImagePath = __DIR__ . '/../../assets/images/terrains/' . $terrain['image'];
        if (file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
        $imageName = null;
    }
    
    // Déterminer l'ID responsable
    $id_responsable = $_SESSION['user_role'] === 'responsable' ? $_SESSION['user_email'] : 
                      (!empty($data['id_responsable']) ? $data['id_responsable'] : $terrain['id_responsable']);
    
    // Mise à jour du terrain
    $sql = "UPDATE terrain SET nom_te = :nom_te, categorie = :categorie, type = :type, taille = :taille, 
            prix_heure = :prix_heure, localisation = :localisation, disponibilite = :disponibilite, 
            id_responsable = :id_responsable, image = :image WHERE id_terrain = :id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':nom_te' => $data['nom_te'],
        ':categorie' => $data['categorie'],
        ':type' => $data['type'],
        ':taille' => $data['taille'],
        ':prix_heure' => $data['prix_heure'],
        ':localisation' => $data['localisation'],
        ':disponibilite' => $data['disponibilite'],
        ':id_responsable' => $id_responsable,
        ':image' => $imageName,
        ':id' => $data['id_terrain']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Terrain modifié avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification du terrain']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur edit_terrain: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification du terrain']);
}

function processImageUpload($base64Image, $terrainName) {
    $uploadDir = __DIR__ . '/../../assets/images/terrains/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
        $format = $matches[1];
        $base64Data = substr($base64Image, strpos($base64Image, ',') + 1);
        $imageData = base64_decode($base64Data);
        
        if ($imageData === false) {
            return null;
        }
        
        $fileName = sanitizeFileName($terrainName) . '_' . time() . '.' . $format;
        $filePath = $uploadDir . $fileName;
        
        if (file_put_contents($filePath, $imageData)) {
            return $fileName;
        }
    }
    
    return null;
}

function sanitizeFileName($name) {
    $name = preg_replace('/[^a-zA-Z0-9-_]/', '_', $name);
    return substr($name, 0, 50);
}
?>