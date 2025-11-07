<?php
require_once 'config/database.php';

// Récupérer les statistiques
try {
    // Nombre total de terrains (tous les terrains)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM terrain");
    $totalTerrains = $stmt->fetch()['total'] ?? 0;
    
    // Nombre de terrains disponibles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM terrain WHERE disponibilite = 'disponible'");
    $totalTerrainsDisponibles = $stmt->fetch()['total'] ?? 0;
    
    // Nombre total d'utilisateurs (joueurs seulement)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateur WHERE role = 'joueur' AND statut_compte = 'actif'");
    $totalUsers = $stmt->fetch()['total'] ?? 0;
    
    // Récupérer les terrains par catégorie avec leurs informations
    $categories = ['Mini Foot', 'Terrain Moyen', 'Grand Terrain'];
    $terrainsByCategory = [];
    
    foreach ($categories as $categorie) {
        // Compter les terrains disponibles pour cette catégorie
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM terrain WHERE categorie = :categorie AND disponibilite = 'disponible'");
        $stmt->execute([':categorie' => $categorie]);
        $disponibles = $stmt->fetch()['count'] ?? 0;
        
        // Récupérer les prix min et max, et une taille représentative
        $stmt = $pdo->prepare("
            SELECT 
                MIN(prix_heure) as prix_min,
                MAX(prix_heure) as prix_max,
                MIN(taille) as taille_min,
                MAX(taille) as taille_max
            FROM terrain 
            WHERE categorie = :categorie
        ");
        $stmt->execute([':categorie' => $categorie]);
        $result = $stmt->fetch();
        
        // Déterminer les dimensions - utiliser les valeurs réelles si disponibles
        if ($result && !empty($result['taille_min'])) {
            if ($result['taille_min'] === $result['taille_max']) {
                $dimensions = $result['taille_min'];
            } else {
                $dimensions = $result['taille_min'] . ' - ' . $result['taille_max'];
            }
        } else {
            // Valeurs par défaut basées sur la catégorie
            $dimensions = $categorie === 'Mini Foot' ? '70×40m' : 
                         ($categorie === 'Terrain Moyen' ? '90×50m' : '105×68m');
        }
        
        // Capacité basée sur la catégorie
        $capacite = $categorie === 'Mini Foot' ? '5 vs 5' : 
                   ($categorie === 'Terrain Moyen' ? '7 vs 7' : '11 vs 11');
        
        $terrainsByCategory[$categorie] = [
            'disponibles' => $disponibles,
            'prix_min' => $result ? floatval($result['prix_min'] ?? 0) : 0,
            'prix_max' => $result ? floatval($result['prix_max'] ?? 0) : 0,
            'description' => $categorie === 'Mini Foot' ? 'Idéal petits groupes' : 
                           ($categorie === 'Terrain Moyen' ? 'Matchs amicaux' : 'Compétitions pro'),
            'dimensions' => $dimensions,
            'capacite' => $capacite
        ];
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données: " . $e->getMessage());
    // Valeurs par défaut en cas d'erreur
    $totalTerrains = 0;
    $totalTerrainsDisponibles = 0;
    $totalUsers = 0;
    $terrainsByCategory = [];
}
?>