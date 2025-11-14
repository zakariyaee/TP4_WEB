<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../check_auth.php';

checkJoueur();

try {
    $email_joueur = $_SESSION['user_email'];
    
    if (!isset($_GET['id'])) {
        throw new Exception('ID de réservation manquant');
    }
    
    $id_reservation = (int)$_GET['id'];
    
    // Récupérer les données de la réservation
    $stmt = $pdo->prepare("
        SELECT 
            r.id_reservation,
            r.date_reservation,
            DATE(r.date_reservation) AS date_reservation_only,
            r.id_creneau,
            r.id_equipe,
            r.id_equipe_adverse,
            r.id_terrain,
            r.statut,
            
            t.nom_te AS nom_terrain,
            t.categorie AS categorie_terrain,
            t.localisation,
            t.prix_heure,
            
            cr.heure_debut,
            cr.heure_fin,
            
            (t.prix_heure * TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin)) AS prix_total,
            
            DATEDIFF(r.date_reservation, NOW()) AS jours_restants
            
        FROM reservation r
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        INNER JOIN creneau cr ON r.id_creneau = cr.id_creneaux
        WHERE r.id_reservation = :id 
        AND r.id_joueur = :email
    ");
    
    $stmt->execute([
        ':id' => $id_reservation,
        ':email' => $email_joueur
    ]);
    
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        throw new Exception('Réservation introuvable');
    }
    
    // Récupérer les équipes disponibles - CORRIGÉ
    $stmtEquipes = $pdo->prepare("
        SELECT DISTINCT e.id_equipe, e.nom_equipe
        FROM equipe e
        INNER JOIN equipe_joueur ej ON e.id_equipe = ej.id_equipe
        WHERE ej.id_joueur = :email
    ");
    $stmtEquipes->execute([':email' => $email_joueur]);
    $equipes = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les créneaux disponibles
    $stmtCreneaux = $pdo->prepare("
        SELECT id_creneaux, heure_debut, heure_fin
        FROM creneau
        WHERE id_terrain = :terrain
        ORDER BY heure_debut
    ");
    $stmtCreneaux->execute([':terrain' => $reservation['id_terrain']]);
    $creneaux = $stmtCreneaux->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => array_merge($reservation, [
            'equipes_disponibles' => $equipes,
            'creneaux_disponibles' => $creneaux
        ])
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("Erreur get_reservation : " . $e->getMessage());
}
?>