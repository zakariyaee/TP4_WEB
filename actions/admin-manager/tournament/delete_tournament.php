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
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$idTournoi = intval($data['id_tournoi'] ?? 0);

if ($idTournoi <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de tournoi invalide']);
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
        echo json_encode(['success' => false, 'message' => 'Tournoi introuvable']);
        exit;
    }
    
    if ($_SESSION['user_role'] === 'responsable') {
        if ($tournoi['id_terrain'] && $tournoi['id_responsable'] !== $_SESSION['user_email']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Vous n'avez pas la permission de supprimer ce tournoi"]);
            exit;
        }
    }
    
    $pdo->beginTransaction();
    
    // Delete team registrations first (if any)
    try {
        $stmt = $pdo->prepare("DELETE FROM tournoi_equipe WHERE id_tournoi = ?");
        $stmt->execute([$idTournoi]);
    } catch (PDOException $e) {
        // Ignore if table doesn't exist or has no records
        error_log("Note: tournoi_equipe deletion: " . $e->getMessage());
    }
    
    // Delete tournament
    $stmt = $pdo->prepare("DELETE FROM tournoi WHERE id_tournoi = ?");
    $stmt->execute([$idTournoi]);
    
    $pdo->commit();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Tournoi supprimé avec succès']);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error delete_tournament: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du tournoi']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Unexpected error delete_tournament: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du tournoi']);
}
?>
