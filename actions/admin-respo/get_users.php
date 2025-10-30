<?php
/**
 * Get users list with filters
 * 
 * Retrieves users from database with optional search, role and status filters.
 * Admin only access.
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOnly();

header('Content-Type: application/json');

try {
    $searchTerm = $_GET['search'] ?? '';
    $filterRole = $_GET['role'] ?? '';
    $filterStatus = $_GET['statut'] ?? '';
    
    // Pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $page = max(1, $page); // Ensure page is at least 1
    $limit = max(1, min(100, $limit)); // Limit between 1 and 100
    $offset = ($page - 1) * $limit;

    // Build WHERE clause for filters
    $whereClause = "WHERE 1=1";
    $params = [];

    if (!empty($searchTerm)) {
        $whereClause .= " AND (nom LIKE :s OR prenom LIKE :s OR email LIKE :s)";
        $params[':s'] = "%$searchTerm%";
    }
    if (!empty($filterRole)) {
        $whereClause .= " AND role = :role";
        $params[':role'] = $filterRole;
    }
    if (!empty($filterStatus)) {
        $whereClause .= " AND statut_compte = :statut";
        $params[':statut'] = $filterStatus;
    }

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM Utilisateur $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetch()['total'];
    $totalPages = ceil($totalUsers / $limit);

    // Get paginated users with optimized query
    $sql = "SELECT email, nom, prenom, role, statut_compte 
            FROM Utilisateur $whereClause 
            ORDER BY FIELD(role, 'admin','responsable','joueur'), nom, prenom 
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind all parameters including pagination
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'users' => $users,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers,
            'limit' => $limit,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1
        ]
    ]);
} catch (PDOException $e) {
    error_log('Error get_users: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error retrieving users']);
} catch (Exception $e) {
    error_log('Unexpected error get_users: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error retrieving users']);
}
?>


