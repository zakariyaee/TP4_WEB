<?php
/**
 * Chargement des réservations pour un joueur
 * Récupère les réservations confirmées, en attente et l'historique
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once '../../config/database.php';
try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_email'])) {
        throw new Exception('Utilisateur non connecté');
    }

    $email_joueur = $_SESSION['user_email'];

    // ========================================
    // PROCHAINES RÉSERVATIONS (Confirmées + En attente)
    // ========================================
    
    $sqlReservations = "
        SELECT 
            r.id_reservation,
            r.statut,
            r.date_reservation,
            r.date_creation,
            r.statut_equipe_adverse,
            
            -- Informations terrain
            t.id_terrain,
            t.nom_te AS nom_terrain,
            t.categorie AS categorie_terrain,
            t.type AS type_terrain,
            t.taille AS taille_terrain,
            t.localisation,
            t.prix_heure,
            t.image AS image_terrain,
            
            -- Informations créneau
            cr.id_creneaux,
            cr.jour_semaine,
            cr.heure_debut,
            cr.heure_fin,
            TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin) AS duree_heures,
            
            -- Calcul du prix terrain
            (t.prix_heure * TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin)) AS prix_terrain,
            
            -- Informations équipe du joueur
            e1.id_equipe AS id_equipe_joueur,
            e1.nom_equipe AS nom_equipe_joueur,
            e1.email_equipe AS email_equipe_joueur,
            
            -- Informations équipe adverse
            e2.id_equipe AS id_equipe_adverse,
            e2.nom_equipe AS nom_equipe_adverse,
            e2.email_equipe AS email_equipe_adverse,
            
            -- Calcul du prix des objets
            COALESCE((
                SELECT SUM(o.prix * ro.quantite)
                FROM reservation_objet ro
                INNER JOIN objet o ON ro.id_object = o.id_object
                WHERE ro.id_reservation = r.id_reservation
            ), 0) AS prix_objets,
            
            -- Prix total
            (
                (t.prix_heure * TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin)) +
                COALESCE((
                    SELECT SUM(o.prix * ro.quantite)
                    FROM reservation_objet ro
                    INNER JOIN objet o ON ro.id_object = o.id_object
                    WHERE ro.id_reservation = r.id_reservation
                ), 0)
            ) AS prix_total,
            
            -- Liste des objets réservés (optionnel, peut être NULL)
            (
                SELECT GROUP_CONCAT(CONCAT(o.nom_objet, ' (x', ro.quantite, ')') SEPARATOR ', ')
                FROM reservation_objet ro
                INNER JOIN objet o ON ro.id_object = o.id_object
                WHERE ro.id_reservation = r.id_reservation
            ) AS objets_reserves,
            
            -- Calcul des jours restants pour modification
            DATEDIFF(r.date_reservation, NOW()) AS jours_restants
            
        FROM reservation r
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        INNER JOIN creneau cr ON r.id_creneau = cr.id_creneaux
        INNER JOIN equipe e1 ON r.id_equipe = e1.id_equipe
        LEFT JOIN equipe e2 ON r.id_equipe_adverse = e2.id_equipe
        
        WHERE r.id_joueur = :email
        AND r.statut IN ('confirmee', 'en_attente')
        AND r.date_reservation >= CURDATE()
            
        ORDER BY r.date_reservation ASC
    ";
    
    $stmtReservations = $pdo->prepare($sqlReservations);
    $stmtReservations->execute([':email' => $email_joueur]);
    $reservations_prochaines = $stmtReservations->fetchAll(PDO::FETCH_ASSOC);
    
    // Stocker en session
    $_SESSION['reservations_prochaines'] = $reservations_prochaines;

    // ========================================
    // HISTORIQUE (Terminées + Annulées)
    // ========================================
    
    $sqlHistorique = "
        SELECT 
            r.id_reservation,
            r.statut,
            r.date_reservation,
            r.date_creation,
            r.statut_equipe_adverse,
            
            -- Informations terrain
            t.id_terrain,
            t.nom_te AS nom_terrain,
            t.categorie AS categorie_terrain,
            t.type AS type_terrain,
            t.localisation,
            t.prix_heure,
            t.image AS image_terrain,
            
            -- Informations créneau
            cr.id_creneaux,
            cr.jour_semaine,
            cr.heure_debut,
            cr.heure_fin,
            TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin) AS duree_heures,
            
            -- Calcul du prix terrain
            (t.prix_heure * TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin)) AS prix_terrain,
            
            -- Informations équipe du joueur
            e1.id_equipe AS id_equipe_joueur,
            e1.nom_equipe AS nom_equipe_joueur,
            
            -- Informations équipe adverse
            e2.id_equipe AS id_equipe_adverse,
            e2.nom_equipe AS nom_equipe_adverse,
            
            -- Prix total
            (
                (t.prix_heure * TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin)) +
                COALESCE((
                    SELECT SUM(o.prix * ro.quantite)
                    FROM reservation_objet ro
                    INNER JOIN objet o ON ro.id_object = o.id_object
                    WHERE ro.id_reservation = r.id_reservation
                ), 0)
            ) AS prix_total,
            
            -- Liste des objets réservés
            (
                SELECT GROUP_CONCAT(CONCAT(o.nom_objet, ' (x', ro.quantite, ')') SEPARATOR ', ')
                FROM reservation_objet ro
                INNER JOIN objet o ON ro.id_object = o.id_object
                WHERE ro.id_reservation = r.id_reservation
            ) AS objets_reserves
            
        FROM reservation r
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        INNER JOIN creneau cr ON r.id_creneau = cr.id_creneaux
        INNER JOIN equipe e1 ON r.id_equipe = e1.id_equipe
        LEFT JOIN equipe e2 ON r.id_equipe_adverse = e2.id_equipe
        
        WHERE r.id_joueur = :email
        AND (r.statut IN ('terminee', 'annulee') OR r.date_reservation < CURDATE())
            
        ORDER BY r.date_reservation DESC
    ";
    
    $stmtHistorique = $pdo->prepare($sqlHistorique);
    $stmtHistorique->execute([':email' => $email_joueur]);
    $reservations_historique = $stmtHistorique->fetchAll(PDO::FETCH_ASSOC);
    
    // Stocker en session
    $_SESSION['reservations_historique'] = $reservations_historique;

    // ========================================
    // RÉPONSE JSON
    // ========================================
    
    echo json_encode([
        'success' => true,
        'message' => 'Réservations chargées avec succès',
        'prochaines_reservations' => [
            'count' => count($reservations_prochaines),
            'data' => $reservations_prochaines
        ],
        'historique' => [
            'count' => count($reservations_historique),
            'data' => $reservations_historique
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données : ' . $e->getMessage(),
        'prochaines_reservations' => ['count' => 0, 'data' => []],
        'historique' => ['count' => 0, 'data' => []]
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Erreur SQL chargement réservations : " . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur : ' . $e->getMessage(),
        'prochaines_reservations' => ['count' => 0, 'data' => []],
        'historique' => ['count' => 0, 'data' => []]
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Erreur chargement réservations : " . $e->getMessage());
}
?>