<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../check_auth.php';
checkJoueur();

try {
    $stmt = $pdo->prepare("
        SELECT id_creneaux, heure_debut, heure_fin
        FROM creneau
        WHERE id_terrain = :terrain
        ORDER BY heure_debut
    ");
    $stmt->execute([':terrain' => (int)$_GET['id_terrain']]);
    
    echo json_encode([
        'success' => true,
        'creneaux' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>