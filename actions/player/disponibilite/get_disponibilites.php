<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $email_joueur = $_SESSION['user_email'];
    
    // Récupérer les disponibilités avec les infos des joueurs
    // Pour l'utilisateur connecté : toutes ses disponibilités (actives et inactives)
    // Pour les autres utilisateurs : seulement les disponibilités actives
    $stmt = $pdo->prepare("
        SELECT d.*,
               u.nom as nom_joueur,
               u.prenom as prenom_joueur,
               u.ville as ville_joueur,
               t.nom_te as nom_terrain,
               t.ville,
               t.categorie,
               DATE_FORMAT(d.date_debut, '%d/%m/%Y') as date_formatted,
               DATE_FORMAT(d.date_debut, '%H:%i') as heure_debut_formatted,
               DATE_FORMAT(d.date_fin, '%H:%i') as heure_fin_formatted
        FROM disponibilite d
        INNER JOIN utilisateur u ON d.email_joueur = u.email
        LEFT JOIN terrain t ON d.id_terrain = t.id_terrain
        WHERE d.date_debut >= NOW()
          AND (
              -- Toutes les disponibilités de l'utilisateur connecté (actives et inactives)
              d.email_joueur = :email
              OR
              -- Seulement les disponibilités actives des autres utilisateurs
              (d.email_joueur != :email AND d.statut = 'actif')
          )
        ORDER BY d.date_debut ASC, d.date_creation DESC
    ");
    
    $stmt->execute([':email' => $email_joueur]);
    $disponibilites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter le total
    $total = count($disponibilites);
    
    echo json_encode([
        'success' => true,
        'disponibilites' => $disponibilites,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
    ]);
}