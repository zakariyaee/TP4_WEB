<?php
/**
 * Get terrains list with filters
 * 
 * Retrieves terrains from database with optional search, category, availability and manager filters.
 * Admin and Responsable access (responsable can only see their own terrains).
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    $search = $_GET['search'] ?? '';
    $categorie = $_GET['categorie'] ?? '';
    $disponibilite = $_GET['disponibilite'] ?? '';
    $responsable = $_GET['responsable'] ?? '';
    
    $sql = "SELECT t.*, CONCAT(u.nom, ' ', u.prenom) as responsable_nom, u.email as responsable_email
            FROM terrain t
            LEFT JOIN utilisateur u ON t.id_responsable = u.email
            WHERE 1=1";
    
    $params = [];
    
    // If responsable, show only their terrains
    if ($_SESSION['user_role'] === 'responsable') {
        $sql .= " AND t.id_responsable = :user_email";
        $params[':user_email'] = $_SESSION['user_email'];
    }
    
    if (!empty($search)) {
        $sql .= " AND (t.nom_te LIKE :search OR t.localisation LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($categorie)) {
        $sql .= " AND t.categorie = :categorie";
        $params[':categorie'] = $categorie;
    }
    
    if (!empty($disponibilite)) {
        $sql .= " AND t.disponibilite = :disponibilite";
        $params[':disponibilite'] = $disponibilite;
    }
    
    // Responsables cannot filter by another responsable
    if (!empty($responsable) && $_SESSION['user_role'] === 'admin') {
        $sql .= " AND t.id_responsable = :responsable";
        $params[':responsable'] = $responsable;
    }
    
    $sql .= " ORDER BY t.id_terrain DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $terrains = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'terrains' => $terrains, 'count' => count($terrains)]);
    
} catch (PDOException $e) {
    error_log("Error get_terrains: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des terrains']);
}
?>