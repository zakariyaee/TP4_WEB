<?php
/**
 * Get tournaments list with filters
 * 
 * Retrieves tournaments from database with optional search, status and type filters.
 * Admin and Responsable access (responsable can only see their own tournaments).
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    $searchTerm = $_GET['search'] ?? '';
    $filterStatus = $_GET['statut'] ?? '';
    $filterType = $_GET['type'] ?? '';
    
    // Adapt to actual DB columns: nom_t, categorie, size, date_debut, date_fin
    // Return aliases expected by frontend: nom_tournoi, type_tournoi, nb_equipes
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
                COALESCE((SELECT COUNT(*) FROM tournoi_equipe te WHERE te.id_tournoi = t.id_tournoi), 0) AS nb_inscrits
            FROM tournoi t
            LEFT JOIN terrain tr ON t.id_terrain = tr.id_terrain
            WHERE 1=1";
    
    $params = [];
    
    // If responsable, show only tournaments from their terrains
    if ($_SESSION['user_role'] === 'responsable') {
        $sql .= " AND (t.id_terrain IN (SELECT id_terrain FROM terrain WHERE id_responsable = :user_email) OR t.id_terrain IS NULL)";
        $params[':user_email'] = $_SESSION['user_email'];
    }
    
    if (!empty($searchTerm)) {
        $sql .= " AND (t.nom_t LIKE :search OR t.description LIKE :search)";
        $params[':search'] = "%$searchTerm%";
    }
    
    if (!empty($filterStatus)) {
        $sql .= " AND t.statut = :statut";
        $params[':statut'] = $filterStatus;
    }
    
    if (!empty($filterType)) {
        $sql .= " AND t.categorie = :type";
        $params[':type'] = $filterType;
    }
    
    $sql .= " ORDER BY t.date_debut DESC, t.id_tournoi DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tournois = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'tournois' => $tournois, 'count' => count($tournois)]);
    
} catch (PDOException $e) {
    error_log("Error get_tournois: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error retrieving tournaments']);
} catch (Exception $e) {
    error_log("Unexpected error get_tournois: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error retrieving tournaments']);
}
?>
