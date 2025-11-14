<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $categorie = $_GET['categorie'] ?? '';
    $ville = $_GET['ville'] ?? '';
    $search = $_GET['search'] ?? '';
    $disponibilite = $_GET['disponibilite'] ?? 'disponible'; // Par défaut: disponibles uniquement
    
    $sql = "SELECT t.*, CONCAT(u.nom, ' ', u.prenom) as responsable_nom
            FROM terrain t
            LEFT JOIN utilisateur u ON t.id_responsable = u.email
            WHERE 1=1";
    
    $params = [];
    
    // Filtre par catégorie
    if (!empty($categorie)) {
        $sql .= " AND t.categorie = :categorie";
        $params[':categorie'] = $categorie;
    }
    
    // Filtre par ville
    if (!empty($ville)) {
        $sql .= " AND t.ville = :ville";
        $params[':ville'] = $ville;
    }
    
    // Filtre par disponibilité
    if (!empty($disponibilite)) {
        $sql .= " AND t.disponibilite = :disponibilite";
        $params[':disponibilite'] = $disponibilite;
    }
    
    // Recherche textuelle
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
    
    // Regrouper par catégorie
    $categories = [
        'Mini Foot' => [],
        'Terrain Moyen' => [],
        'Grand Terrain' => []
    ];
    
    foreach ($terrains as $terrain) {
        if (isset($categories[$terrain['categorie']])) {
            $categories[$terrain['categorie']][] = $terrain;
        }
    }
    
    // Calculer les statistiques
    $stats = [
        'total' => count($terrains),
        'disponibles' => count(array_filter($terrains, fn($t) => $t['disponibilite'] === 'disponible')),
        'indisponibles' => count(array_filter($terrains, fn($t) => $t['disponibilite'] === 'indisponible')),
        'maintenance' => count(array_filter($terrains, fn($t) => $t['disponibilite'] === 'maintenance'))
    ];
    
    echo json_encode([
        'success' => true,
        'terrains' => $categories,
        'stats' => $stats,
        'total' => count($terrains)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur load_terrains: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des terrains'
    ]);
}
?>