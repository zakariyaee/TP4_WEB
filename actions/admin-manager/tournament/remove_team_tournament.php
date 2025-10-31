<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id_tournoi = intval($data['id_tournoi'] ?? 0);
$id_equipe = intval($data['id_equipe'] ?? 0);

if ($id_tournoi <= 0 || $id_equipe <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "L'identifiant du tournoi et celui de l'équipe sont requis"]);
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
    
    // Ensure team is registered (table may not have an auto id)
    $stmt = $pdo->prepare("SELECT 1 FROM tournoi_equipe WHERE id_tournoi = ? AND id_equipe = ? LIMIT 1");
    $stmt->execute([$id_tournoi, $id_equipe]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Cette équipe n'est pas inscrite à ce tournoi"]);
        exit;
    }
    
    // Delete related matches first (if table exists)
    try {
        $stmt = $pdo->prepare("DELETE FROM match_tournoi WHERE id_tournoi = ? AND (id_equipe1 = ? OR id_equipe2 = ?)");
        $stmt->execute([$id_tournoi, $id_equipe, $id_equipe]);
    } catch (PDOException $ignored) {
        // Si la table n'existe pas dans ce schéma, on ignore
    }
    
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM tournoi_equipe WHERE id_tournoi = ? AND id_equipe = ?");
    $stmt->execute([$id_tournoi, $id_equipe]);
    
    $pdo->commit();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => "Équipe retirée avec succès"]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur remove_team_tournament: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors du retrait de l'équipe du tournoi"]);
}
?>
