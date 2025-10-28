<?php
// actions/admin/upload_image.php

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
    // Vérifier qu'un fichier a été envoyé
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => 'Aucun fichier n\'a été uploadé'
        ]);
        exit;
    }

    $file = $_FILES['image'];

    // Validation du type MIME
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        echo json_encode([
            'success' => false,
            'message' => 'Le format de fichier n\'est pas autorisé'
        ]);
        exit;
    }

    // Validation de la taille (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        echo json_encode([
            'success' => false,
            'message' => 'Le fichier est trop volumineux (max 5MB)'
        ]);
        exit;
    }

    // Créer le répertoire s'il n'existe pas
    $uploadDir = '../../assets/images/terrains/';
    
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode([
                'success' => false,
                'message' => 'Impossible de créer le répertoire d\'upload'
            ]);
            exit;
        }
    }

    // Générer un nom de fichier unique
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'terrain_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;

    // Redimensionner et optimiser l'image
    $image = null;
    
    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file['tmp_name']);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file['tmp_name']);
            break;
    }

    if (!$image) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la lecture de l\'image'
        ]);
        exit;
    }

    // Redimensionner l'image (max 1200x800)
    $maxWidth = 1200;
    $maxHeight = 800;
    $width = imagesx($image);
    $height = imagesy($image);

    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Préserver la transparence pour PNG/GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }

    // Sauvegarder l'image
    $saved = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $saved = imagejpeg($image, $filePath, 85);
            break;
        case 'image/png':
            $saved = imagepng($image, $filePath, 8);
            break;
        case 'image/gif':
            $saved = imagegif($image, $filePath);
            break;
        case 'image/webp':
            $saved = imagewebp($image, $filePath, 85);
            break;
    }

    imagedestroy($image);

    if (!$saved) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la sauvegarde de l\'image'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Image uploadée avec succès',
        'fileName' => $fileName,
        'preview' => $uploadDir . $fileName
    ]);

} catch (Exception $e) {
    error_log("Erreur upload_image: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'upload'
    ]);
}