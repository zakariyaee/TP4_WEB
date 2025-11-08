<?php
require_once '../../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Session invalide']);
    exit;
}

// On spécifie que la réponse sera au format JSON.
header('Content-Type: application/json');
if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'responsable'){

try {
    // Calcul du nombre total de terrains actifs (disponibles)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain WHERE disponibilite = 'disponible' And id_responsable=:responsable_id");
    $stmt->bindParam(':responsable_id', $_SESSION['user_email']);
    $stmt->execute();
    $total_terrains = (int)$stmt->fetchColumn();

    // Calcul du nombre total de joueurs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE role = 'joueur'");
    $stmt->execute();
    $total_joueurs = (int)$stmt->fetchColumn();

    // Calcul du nombre total de réservations confirmées
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservation WHERE statut = 'confirmee' AND id_terrain IN (SELECT id_terrain FROM terrain WHERE id_responsable=:responsable_id)");
    $stmt->bindParam(':responsable_id', $_SESSION['user_email']);
    $stmt->execute();
    $total_reservations = (int)$stmt->fetchColumn();

    // Calcul des revenus du mois en cours
    $revenue_total = 0;

    // --- Revenu des réservations de terrains ---
    $stmt = $pdo->prepare("
        SELECT SUM(t.prix_heure) AS total_terrains
        FROM reservation r
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        WHERE MONTH(r.date_reservation) = MONTH(CURRENT_DATE())
          AND YEAR(r.date_reservation) = YEAR(CURRENT_DATE())
          AND r.statut = 'confirmee' AND t.id_responsable=:responsable_id
    ");
    $stmt->bindParam(':responsable_id', $_SESSION['user_email']);
    $stmt->execute();
    $terrain_total = (float)($stmt->fetchColumn() ?: 0);

    // --- Revenu des réservations d’objets ---
    $stmt = $pdo->prepare("
        SELECT SUM(o.prix * ro.quantite) AS total_objets
        FROM reservation_objet ro
        INNER JOIN objet o ON ro.id_object = o.id_object
        WHERE MONTH(ro.Date_reservation_objet) = MONTH(CURRENT_DATE())
          AND YEAR(ro.Date_reservation_objet) = YEAR(CURRENT_DATE())
          AND ro.id_reservation IN (
              SELECT r.id_reservation
              FROM reservation r
              INNER JOIN terrain t ON r.id_terrain = t.id_terrain
              WHERE r.statut = 'confirmee' AND t.id_responsable=:responsable_id
          )
    ");
    $stmt->bindParam(':responsable_id', $_SESSION['user_email']);
    $stmt->execute();
    $objet_total = (float)($stmt->fetchColumn() ?: 0);

    // --- Total général ---
    $revenue_total = $terrain_total + $objet_total;

    // Calcul des données pour le graphique des jours de la semaine
    $graphe_jour = [
        'Lundi' => 0,
        'Mardi' => 0,
        'Mercredi' => 0,
        'Jeudi' => 0,
        'Vendredi' => 0,
        'Samedi' => 0,
        'Dimanche' => 0
    ];

    $stmt = $pdo->prepare("
        SELECT DAYNAME(date_reservation) as jour, COUNT(*) as total
        FROM reservation
        WHERE WEEK(date_reservation) = WEEK(CURRENT_DATE())
        AND YEAR(date_reservation) = YEAR(CURRENT_DATE())
        GROUP BY DAYNAME(date_reservation)
    ");
    $stmt->execute();
    $jours_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($jours_data as $jour) {
        $jour_fr = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche'
        ];
        
        if (isset($jour_fr[$jour['jour']])) {
            $graphe_jour[$jour_fr[$jour['jour']]] = (int)$jour['total'];
        }
    }

    // Calcul des revenus mensuels (6 derniers mois)
    $revenus_mensuels = array_fill(0, 6, 0);
    $stmt = $pdo->prepare("
        SELECT MONTH(r.date_reservation) as mois, SUM(t.prix_heure) as total
        FROM reservation r
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        WHERE r.date_reservation >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        AND r.statut = 'confirmee'
        AND t.id_responsable=:responsable_id
        GROUP BY MONTH(r.date_reservation)
        ORDER BY r.date_reservation
    ");
    $stmt->bindParam(':responsable_id', $_SESSION['user_email']);
    $stmt->execute();
    $revenus_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $mois_actuel = (int)date('n');
    foreach ($revenus_data as $index => $revenu) {
        if ($index < 6) {
            $revenus_mensuels[$index] = (float)$revenu['total'];
        }
    }

    // Calcul des pourcentages de types de terrains
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain where id_responsable=:responsable_id");
    $stmt->bindParam(':responsable_id', $_SESSION['user_email']);
    $stmt->execute();
    $total_all_terrains = (int)$stmt->fetchColumn();

    $total_moyenne = 0;
    $total_minifoot = 0;
    $total_grand = 0;

    if ($total_all_terrains > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain WHERE taille = '90x50m' AND id_responsable=:responsable_id");
        $stmt->bindParam(':responsable_id', $_SESSION['user_email']);
        $stmt->execute();
        $total_moyenne = round(((int)$stmt->fetchColumn() / $total_all_terrains) * 100, 1);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain WHERE taille = '70x40m' AND id_responsable=:responsable_id");
        $stmt->bindParam(':responsable_id', $_SESSION['user_email']);
        $stmt->execute();
        $total_minifoot = round(((int)$stmt->fetchColumn() / $total_all_terrains) * 100, 1);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain WHERE taille = '105x68m' AND id_responsable=:responsable_id");
        $stmt->bindParam(':responsable_id', $_SESSION['user_email']);
        $stmt->execute();
        $total_grand = round(((int)$stmt->fetchColumn() / $total_all_terrains) * 100, 1);
    }

    // Réponse JSON avec toutes les statistiques
    echo json_encode([
        'success' => true,
        'total_terrains' => $total_terrains,
        'total_joueurs' => $total_joueurs,
        'total_reservations' => $total_reservations,
        'revenue_total' => round($revenue_total, 2),
        'graphe_jour' => $graphe_jour,
        'revenus_mensuels' => $revenus_mensuels,
        'total_moyenne' => $total_moyenne,
        'total_minifoot' => $total_minifoot,
        'total_Grand' => $total_grand
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur', 
        'error' => $e->getMessage()
    ]);
}}
else{

    try {
    // Calcul du nombre total de terrains actifs (disponibles)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain WHERE disponibilite = 'disponible' ");
    $stmt->execute();
    $total_terrains = (int)$stmt->fetchColumn();

    // Calcul du nombre total de joueurs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE role = 'joueur'");
    $stmt->execute();
    $total_joueurs = (int)$stmt->fetchColumn();

    // Calcul du nombre total de réservations confirmées
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservation WHERE statut = 'confirmee'");
    $stmt->execute();
    $total_reservations = (int)$stmt->fetchColumn();

    // Calcul des revenus du mois en cours
    $revenue_total = 0;

    // --- Revenu des réservations de terrains ---
    $stmt = $pdo->prepare("
        SELECT SUM(t.prix_heure) AS total_terrains
        FROM reservation r
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        WHERE MONTH(r.date_reservation) = MONTH(CURRENT_DATE())
          AND YEAR(r.date_reservation) = YEAR(CURRENT_DATE())
          AND r.statut = 'confirmee' 
    ");
    $stmt->execute();
    $terrain_total = (float)($stmt->fetchColumn() ?: 0);

    // --- Revenu des réservations d’objets ---
    $stmt = $pdo->prepare("
        SELECT SUM(o.prix * ro.quantite) AS total_objets
        FROM reservation_objet ro
        INNER JOIN objet o ON ro.id_object = o.id_object
        WHERE MONTH(ro.Date_reservation_objet) = MONTH(CURRENT_DATE())
          AND YEAR(ro.Date_reservation_objet) = YEAR(CURRENT_DATE())
          AND ro.id_reservation IN (
              SELECT r.id_reservation
              FROM reservation r
              INNER JOIN terrain t ON r.id_terrain = t.id_terrain
              WHERE r.statut = 'confirmee' 
          )
    ");
    $stmt->execute();
    $objet_total = (float)($stmt->fetchColumn() ?: 0);

    // --- Total général ---
    $revenue_total = $terrain_total + $objet_total;

    // Calcul des données pour le graphique des jours de la semaine
    $graphe_jour = [
        'Lundi' => 0,
        'Mardi' => 0,
        'Mercredi' => 0,
        'Jeudi' => 0,
        'Vendredi' => 0,
        'Samedi' => 0,
        'Dimanche' => 0
    ];

    $stmt = $pdo->prepare("
        SELECT DAYNAME(date_reservation) as jour, COUNT(*) as total
        FROM reservation
        WHERE WEEK(date_reservation) = WEEK(CURRENT_DATE())
        AND YEAR(date_reservation) = YEAR(CURRENT_DATE())
        GROUP BY DAYNAME(date_reservation)
    ");
    $stmt->execute();
    $jours_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($jours_data as $jour) {
        $jour_fr = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche'
        ];
        
        if (isset($jour_fr[$jour['jour']])) {
            $graphe_jour[$jour_fr[$jour['jour']]] = (int)$jour['total'];
        }
    }

    // Calcul des revenus mensuels (6 derniers mois)
    $revenus_mensuels = array_fill(0, 6, 0);
    $stmt = $pdo->prepare("
        SELECT MONTH(r.date_reservation) as mois, SUM(t.prix_heure) as total
        FROM reservation r
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        WHERE r.date_reservation >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        AND r.statut = 'confirmee'
        GROUP BY MONTH(r.date_reservation)
        ORDER BY r.date_reservation
    ");
    $stmt->execute();
    $revenus_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $mois_actuel = (int)date('n');
    foreach ($revenus_data as $index => $revenu) {
        if ($index < 6) {
            $revenus_mensuels[$index] = (float)$revenu['total'];
        }
    }

    // Calcul des pourcentages de types de terrains
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain");
    $stmt->execute();
    $total_all_terrains = (int)$stmt->fetchColumn();

    $total_moyenne = 0;
    $total_minifoot = 0;
    $total_grand = 0;

    if ($total_all_terrains > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain WHERE taille = '90x50m'");
        $stmt->execute();
        $total_moyenne = round(((int)$stmt->fetchColumn() / $total_all_terrains) * 100, 1);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain WHERE taille = '70x40m' ");
        $stmt->execute();
        $total_minifoot = round(((int)$stmt->fetchColumn() / $total_all_terrains) * 100, 1);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM terrain WHERE taille = '105x68m' ");
        $stmt->execute();
        $total_grand = round(((int)$stmt->fetchColumn() / $total_all_terrains) * 100, 1);
    }

    // Réponse JSON avec toutes les statistiques
    echo json_encode([
        'success' => true,
        'total_terrains' => $total_terrains,
        'total_joueurs' => $total_joueurs,
        'total_reservations' => $total_reservations,
        'revenue_total' => round($revenue_total, 2),
        'graphe_jour' => $graphe_jour,
        'revenus_mensuels' => $revenus_mensuels,
        'total_moyenne' => $total_moyenne,
        'total_minifoot' => $total_minifoot,
        'total_Grand' => $total_grand
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur', 
        'error' => $e->getMessage()
    ]);
}
}
