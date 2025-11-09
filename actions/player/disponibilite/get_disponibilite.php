<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'ID manquant'
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT d.*, 
               t.nom_te as nom_terrain,
               t.ville,
               t.categorie
        FROM disponibilite d
        LEFT JOIN terrain t ON d.id_terrain = t.id_terrain
        WHERE d.id_disponibilite = :id AND d.email_joueur = :email
    ");
    
    $stmt->execute([
        ':id' => $id,
        ':email' => $_SESSION['user_email']
    ]);
    
    $disponibilite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($disponibilite) {
        echo json_encode([
            'success' => true,
            'disponibilite' => $disponibilite
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Disponibilité non trouvée'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}