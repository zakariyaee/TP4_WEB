<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT id_object, nom_objet, description, prix, disponibilite
        FROM objet
        WHERE disponibilite = 1
        ORDER BY nom_objet
    ");
    
    $objets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'objets' => $objets
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur get_objets: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des objets'
    ]);
}




