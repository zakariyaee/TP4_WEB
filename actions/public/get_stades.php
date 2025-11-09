<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $categorie = $_GET['categorie'] ?? '';
    $ville = $_GET['ville'] ?? '';
    $search = $_GET['search'] ?? '';
    $disponibilite = $_GET['disponibilite'] ?? '';
    
    $sql = "SELECT t.*, CONCAT(u.nom, ' ', u.prenom) as responsable_nom
            FROM terrain t
            LEFT JOIN utilisateur u ON t.id_responsable = u.email
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($categorie)) {
        $sql .= " AND t.categorie = :categorie";
        $params[':categorie'] = $categorie;
    }
    
    if (!empty($ville)) {
        $sql .= " AND t.ville = :ville";
        $params[':ville'] = $ville;
    }
    
    if (!empty($disponibilite)) {
        $sql .= " AND t.disponibilite = :disponibilite";
        $params[':disponibilite'] = $disponibilite;
    }
    
    if (!empty($search)) {
        $sql .= " AND (t.nom_te LIKE :search OR t.localisation LIKE :search OR t.ville LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $sql .= " ORDER BY 
                CASE t.disponibilite 
                    WHEN 'disponible' THEN 1 
                    WHEN 'indisponible' THEN 2 
                    WHEN 'maintenance' THEN 3 
                END,
                t.categorie, 
                t.nom_te";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $terrains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'terrains' => $terrains
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des terrains'
    ]);
}

