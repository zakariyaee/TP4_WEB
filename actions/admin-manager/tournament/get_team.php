<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

$id = intval($_GET['id_equipe'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "ID d'équipe requis"]);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id_equipe, nom_equipe, email_equipe FROM equipe WHERE id_equipe = ?');
    $stmt->execute([$id]);
    $equipe = $stmt->fetch();
    if (!$equipe) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Team not found']);
        exit;
    }
    http_response_code(200);
    echo json_encode(['success' => true, 'equipe' => $equipe]);
} catch (PDOException $e) {
    error_log('Erreur get_team: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération de l'équipe"]);
}
?>


