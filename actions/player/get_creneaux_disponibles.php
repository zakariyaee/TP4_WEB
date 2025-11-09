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
    
    // Récupérer TOUS les créneaux avec leur statut de réservation
    $sql = "
        SELECT 
            c.id_creneaux,
            c.heure_debut,
            c.heure_fin,
            c.disponibilite,
            r.id_reservation,
            r.statut as statut_reservation,
            r.date_reservation,
            e.nom_equipe as equipe_reservee,
            CASE 
                WHEN r.id_reservation IS NOT NULL THEN 1
                ELSE 0
            END as est_reserve
        FROM creneau c
        LEFT JOIN reservation r ON (
            r.id_creneau = c.id_creneaux
            AND DATE(r.date_reservation) = :date
            AND r.statut IN ('en_attente', 'confirmee')
        )
        LEFT JOIN equipe e ON r.id_equipe = e.id_equipe
        WHERE c.id_terrain = :id_terrain
        AND c.jour_semaine = :jour_semaine
        AND c.disponibilite = 1
        ORDER BY c.heure_debut
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_terrain' => $id_terrain,
        ':jour_semaine' => $jour_semaine,
        ':date' => $date
    ]);
    
    $creneaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log pour vérifier
    error_log("Date recherchée: " . $date);
    error_log("Jour semaine: " . $jour_semaine);
    error_log("ID terrain: " . $id_terrain);
    error_log("Nombre de créneaux trouvés: " . count($creneaux));
    
    // Formater les heures et ajouter des informations
    foreach ($creneaux as &$creneau) {
        // Debug
        if ($creneau['id_reservation']) {
            error_log("Créneau réservé trouvé: " . $creneau['id_creneaux'] . " - Réservation: " . $creneau['id_reservation']);
        }
        
        $creneau['heure_debut'] = substr($creneau['heure_debut'], 0, 5);
        $creneau['heure_fin'] = substr($creneau['heure_fin'], 0, 5);
        $creneau['libelle'] = $creneau['heure_debut'] . ' - ' . $creneau['heure_fin'];
        
        // Convertir est_reserve en booléen AVANT de modifier le libellé
        $est_reserve = (int)$creneau['est_reserve'] === 1;
        
        // Ajouter un message si réservé
        if ($est_reserve) {
            $creneau['libelle'] .= ' (Réservé';
            if ($creneau['equipe_reservee']) {
                $creneau['libelle'] .= ' - ' . $creneau['equipe_reservee'];
            }
            $creneau['libelle'] .= ')';
        }
        
        // Convertir est_reserve en booléen pour JavaScript
        $creneau['est_reserve'] = $est_reserve;
        
        // Nettoyer les champs de debug pour la réponse
        unset($creneau['date_reservation']);
    }
    
    echo json_encode([
        'success' => true,
        'creneaux' => $creneaux,
        'jour_semaine' => $jour_semaine,
        'date' => $date,
        'debug' => [
            'id_terrain' => $id_terrain,
            'date_recherche' => $date,
            'jour_semaine' => $jour_semaine,
            'nb_creneaux' => count($creneaux)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur get_creneaux_disponibles: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des créneaux: ' . $e->getMessage()
    ]);
}