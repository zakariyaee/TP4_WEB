<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT email, nom, prenom 
        FROM utilisateur 
        WHERE role = 'responsable' AND statut_compte = 'actif'
        ORDER BY nom, prenom
    ");
    
    $responsables = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'responsables' => $responsables
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur get_responsables: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des responsables'
    ]);
}
?>