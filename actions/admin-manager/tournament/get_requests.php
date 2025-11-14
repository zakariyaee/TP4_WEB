<?php
/**
 * Get tournament requests list for responsable
 * 
 * Retrieves tournament requests from database for the logged-in responsable.
 * Only shows requests for terrains managed by the responsable.
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    $searchTerm = $_GET['search'] ?? '';
    $filterStatus = $_GET['statut'] ?? '';
    
    $sql = "SELECT 
                d.id_demande,
                d.nom_t AS nom_tournoi,
                d.date_debut,
                d.date_fin,
                d.size AS nb_equipes,
                d.description,
                d.regles,
                d.prix_inscription,
                d.statut,
                d.date_demande,
                d.date_reponse,
                d.commentaire_reponse,
                d.id_terrain,
                d.email_organisateur,
                tr.nom_te AS terrain_nom,
                tr.ville AS terrain_ville,
                u.nom AS organisateur_nom,
                u.prenom AS organisateur_prenom
            FROM demande_tournoi d
            LEFT JOIN terrain tr ON d.id_terrain = tr.id_terrain
            LEFT JOIN Utilisateur u ON d.email_organisateur = u.email
            WHERE 1=1";
    
    $params = [];
    
    // If responsable, show only requests for their terrains
    if ($_SESSION['user_role'] === 'responsable') {
        $sql .= " AND d.id_responsable = :user_email";
        $params[':user_email'] = $_SESSION['user_email'];
    }
    
    if (!empty($searchTerm)) {
        $sql .= " AND (d.nom_t LIKE :search OR d.description LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search)";
        $params[':search'] = "%$searchTerm%";
    }
    
    if (!empty($filterStatus)) {
        $sql .= " AND d.statut = :statut";
        $params[':statut'] = $filterStatus;
    }
    
    $sql .= " ORDER BY d.date_demande DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
    
    // Create a hash of the data to detect changes
    $dataHash = md5(json_encode($requests));
    
    // Get actual modification timestamp
    $lastUpdateSql = "SELECT COALESCE(MAX(UNIX_TIMESTAMP(NOW())), 0) as last_update FROM demande_tournoi";
    
    try {
        $lastUpdateStmt = $pdo->query($lastUpdateSql);
        $lastUpdateRow = $lastUpdateStmt->fetch(PDO::FETCH_ASSOC);
        $lastUpdate = $lastUpdateRow['last_update'] ?? time();
    } catch (PDOException $e) {
        // If query fails, use current timestamp
        $lastUpdate = time();
    }
    
    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'count' => count($requests),
        'last_update' => $lastUpdate,
        'data_hash' => $dataHash,
        'timestamp' => time()
    ]);
    
} catch (PDOException $e) {
    error_log('Get tournament requests error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des demandes.'
    ]);
}
