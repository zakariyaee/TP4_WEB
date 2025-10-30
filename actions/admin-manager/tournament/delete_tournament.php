<?php
/**
 * Delete tournament
 * 
 * Deletes tournament with validation checks (no teams registered, no matches scheduled).
 * Admin and Responsable access (responsable can only delete their own tournaments).
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$idTournoi = intval($data['id_tournoi'] ?? 0);

if ($idTournoi <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid tournament ID']);
    exit;
}

try {
    // Check that tournament exists and permissions
    $stmt = $pdo->prepare("
        SELECT t.*, tr.id_responsable 
        FROM tournoi t
        LEFT JOIN terrain tr ON t.id_terrain = tr.id_terrain
        WHERE t.id_tournoi = ?
    ");
    $stmt->execute([$idTournoi]);
    $tournoi = $stmt->fetch();
    
    if (!$tournoi) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournament not found']);
        exit;
    }
    
    if ($_SESSION['user_role'] === 'responsable') {
        if ($tournoi['id_terrain'] && $tournoi['id_responsable'] !== $_SESSION['user_email']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this tournament']);
            exit;
        }
    }
    
    // Check if there are teams registered
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tournoi_equipe WHERE id_tournoi = ?");
    $stmt->execute([$idTournoi]);
    $hasTeams = $stmt->fetch();
    
    if ($hasTeams['count'] > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Cannot delete tournament with registered teams']);
        exit;
    }
    
    // Check if there are scheduled matches
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM match_tournoi WHERE id_tournoi = ?");
    $stmt->execute([$idTournoi]);
    $hasMatches = $stmt->fetch();
    
    if ($hasMatches['count'] > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Cannot delete tournament with scheduled matches']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Delete team registrations
    $stmt = $pdo->prepare("DELETE FROM tournoi_equipe WHERE id_tournoi = ?");
    $stmt->execute([$idTournoi]);
    
    // Delete tournament
    $stmt = $pdo->prepare("DELETE FROM tournoi WHERE id_tournoi = ?");
    $stmt->execute([$idTournoi]);
    
    $pdo->commit();
    http_response_code(204);
    echo json_encode(['success' => true, 'message' => 'Tournament deleted successfully']);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error delete_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting tournament']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Unexpected error delete_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting tournament']);
}
?>
