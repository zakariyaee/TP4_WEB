<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

$id_tournoi = $_GET['id_tournoi'] ?? '';
if (empty($id_tournoi)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de tournoi requis']);
    exit;
}

try {
    // Check permissions on tournament
    $stmt = $pdo->prepare("
        SELECT t.*, tr.id_responsable 
        FROM tournoi t
        LEFT JOIN terrain tr ON t.id_terrain = tr.id_terrain
        WHERE t.id_tournoi = ?
    ");
    $stmt->execute([$id_tournoi]);
    $tournoi = $stmt->fetch();
    
    if (!$tournoi) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournoi introuvable']);
        exit;
    }
    
    if ($_SESSION['user_role'] === 'responsable') {
        if ($tournoi['id_terrain'] && $tournoi['id_responsable'] && $tournoi['id_responsable'] !== $_SESSION['user_email']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission refusée']);
            exit;
        }
    }
    
    // Retrieve registered teams
    $sql = "SELECT e.*, te.statut_participation AS statut_inscription
            FROM equipe e
            INNER JOIN tournoi_equipe te ON e.id_equipe = te.id_equipe
            WHERE te.id_tournoi = ?
            ORDER BY e.nom_equipe ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_tournoi]);
    $equipes = $stmt->fetchAll();
    
    http_response_code(200);
    echo json_encode(['success' => true, 'equipes' => $equipes, 'count' => count($equipes)]);
    
} catch (PDOException $e) {
    error_log("Erreur get_teams_tournament: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des équipes']);
}
?>
