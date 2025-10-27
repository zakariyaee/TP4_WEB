<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Récupérer les paramètres de filtrage
    $search = $_GET['search'] ?? '';
    $categorie = $_GET['categorie'] ?? '';
    $disponibilite = $_GET['disponibilite'] ?? '';
    $responsable = $_GET['responsable'] ?? '';
    
    // Construction de la requête
    $sql = "SELECT t.*, 
            CONCAT(u.nom, ' ', u.prenom) as responsable_nom,
            u.email as responsable_email
            FROM terrain t
            LEFT JOIN utilisateur u ON t.id_responsable = u.email
            WHERE 1=1";
    
    $params = [];
    
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
    
    if (!empty($responsable)) {
        $sql .= " AND t.id_responsable = :responsable";
        $params[':responsable'] = $responsable;
    }
    
    // Trier par ID décroissant : les plus récents en premier
    $sql .= " ORDER BY t.id_terrain DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $terrains = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'terrains' => $terrains,
        'count' => count($terrains)
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur get_terrains: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des terrains'
    ]);
}
?>