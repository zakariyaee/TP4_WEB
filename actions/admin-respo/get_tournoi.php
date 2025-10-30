<?php
/**
 * Get single tournament by ID
 * 
 * Retrieves tournament details from database by tournament ID.
 * Admin and Responsable access (responsable can only see their own tournaments).
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

$tournamentId = $_GET['id'] ?? '';
if (empty($tournamentId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tournament ID required']);
    exit;
}

try {
    // Adapt to actual columns and expose consistent aliases with frontend
    // Try to include regles if column exists
    $sql = "SELECT 
                t.id_tournoi,
                t.nom_t AS nom_tournoi,
                t.categorie AS type_tournoi,
                t.date_debut,
                t.date_fin,
                t.size AS nb_equipes,
                t.description,
                t.statut,
                t.id_terrain,
                t.prix_inscription,
                tr.nom_te AS terrain_nom,
                tr.localisation AS terrain_localisation
            FROM tournoi t
            LEFT JOIN terrain tr ON t.id_terrain = tr.id_terrain
            WHERE t.id_tournoi = :id";
    
    $params = [':id' => $tournamentId];
    
    // If responsable, check permissions
    if ($_SESSION['user_role'] === 'responsable') {
        $sql .= " AND (t.id_terrain IN (SELECT id_terrain FROM terrain WHERE id_responsable = :user_email) OR t.id_terrain IS NULL)";
        $params[':user_email'] = $_SESSION['user_email'];
    }
    
    try {
        // Try to include regles
        $sqlWithRegles = str_replace('FROM tournoi t', ', t.regles FROM tournoi t', $sql);
        $stmt = $pdo->prepare($sqlWithRegles);
        $stmt->execute($params);
        $tournoi = $stmt->fetch();
    } catch (PDOException $e) {
        // If regles column doesn't exist, use base query
        if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tournoi = $stmt->fetch();
        } else {
            throw $e;
        }
    }
    
    if (!$tournoi) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournament not found']);
        exit;
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'tournoi' => $tournoi]);
    
} catch (PDOException $e) {
    error_log("Error get_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error retrieving tournament']);
} catch (Exception $e) {
    error_log("Unexpected error get_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error retrieving tournament']);
}
?>
