<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $id_terrain = $_GET['id_terrain'] ?? '';
    $date = $_GET['date'] ?? '';
    
    if (empty($id_terrain) || empty($date)) {
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        exit;
    }
    
    // Convertir la date en jour de la semaine
    $timestamp = strtotime($date);
    $jour_semaine = '';
    
    $jours = [
        'Monday' => 'Lundi',
        'Tuesday' => 'Mardi',
        'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi',
        'Friday' => 'Vendredi',
        'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche'
    ];
    
    $jour_semaine = $jours[date('l', $timestamp)] ?? '';
    
    if (empty($jour_semaine)) {
        echo json_encode(['success' => false, 'message' => 'Date invalide']);
        exit;
    }
    
    // Récupérer les créneaux disponibles pour ce terrain et ce jour
    $sql = "
        SELECT 
            c.id_creneaux,
            c.heure_debut,
            c.heure_fin,
            c.disponibilite
        FROM creneau c
        WHERE c.id_terrain = :id_terrain
        AND c.jour_semaine = :jour_semaine
        AND c.disponibilite = 1
        AND c.id_creneaux NOT IN (
            SELECT r.id_creneau 
            FROM reservation r
            WHERE r.id_creneau = c.id_creneaux
            AND DATE(r.date_reservation) = :date
            AND r.statut IN ('en_attente', 'confirmee')
        )
        ORDER BY c.heure_debut
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_terrain' => $id_terrain,
        ':jour_semaine' => $jour_semaine,
        ':date' => $date
    ]);
    
    $creneaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les heures
    foreach ($creneaux as &$creneau) {
        $creneau['heure_debut'] = substr($creneau['heure_debut'], 0, 5);
        $creneau['heure_fin'] = substr($creneau['heure_fin'], 0, 5);
        $creneau['libelle'] = $creneau['heure_debut'] . ' - ' . $creneau['heure_fin'];
    }
    
    echo json_encode([
        'success' => true,
        'creneaux' => $creneaux
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur get_creneaux_disponibles: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des créneaux'
    ]);
}


