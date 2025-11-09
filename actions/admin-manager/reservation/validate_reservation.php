<?php

$file = __DIR__ . '/../../../config/database.php';
if (!file_exists($file)) {
    die("Fichier introuvable : $file");
}
require_once $file;

// ===============================
$currentFilterDate = $_GET['date'] ?? '';
$currentFilterStatus = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$_SESSION['currentFilters'] = [
    'date'   => $currentFilterDate,
    'status' => $currentFilterStatus,
    'search' => $searchQuery
];

// ===============================
// partie spécifique pour le responsable

if (isset($_SESSION['user_role']) &&  $_SESSION['user_role'] == 'responsable') {
    $responsable_id = $_SESSION['user_email'];
    
    // Confirmées
    $queryConfirmedReservations = "SELECT COUNT(*) FROM reservation WHERE statut='confirmee' AND id_terrain IN (SELECT id_terrain FROM terrain WHERE id_responsable=:responsable_id)";
    $stmt = $pdo->prepare($queryConfirmedReservations);
    $stmt->bindParam(':responsable_id', $responsable_id);
    $stmt->execute();
    $_SESSION['totalConfirmedReservations'] = $stmt->fetchColumn();

    // En attente
    $queryPendingReservations = "SELECT COUNT(*) FROM reservation WHERE statut='en_attente' AND id_terrain IN (SELECT id_terrain FROM terrain WHERE id_responsable=:responsable_id)";
    $stmt = $pdo->prepare($queryPendingReservations);
    $stmt->bindParam(':responsable_id', $responsable_id);
    $stmt->execute();
    $_SESSION['totalPendingReservations'] = $stmt->fetchColumn();

    // Total
    $queryTotalReservations = "SELECT COUNT(*) FROM reservation WHERE id_terrain IN (SELECT id_terrain FROM terrain WHERE id_responsable=:responsable_id)";
    $stmt = $pdo->prepare($queryTotalReservations);
    $stmt->bindParam(':responsable_id', $responsable_id);
    $stmt->execute();
    $_SESSION['totalReservations'] = $stmt->fetchColumn();

    // Détails réservations
    $queryReservationDetails = "
        SELECT 
            r.id_reservation,
            u.nom,
            u.prenom,
            t.nom_te AS nom_terrain,
            t.categorie AS categorie_terrain,
            r.date_reservation AS date_debut,
            c.heure_debut,
            c.heure_fin,
            TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin) AS duree,
            t.prix_heure,
            (t.prix_heure * TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin)) AS prix_terrain,
            COALESCE((
                SELECT SUM(o.prix * ro.quantite)
                FROM reservation_objet ro
                INNER JOIN objet o ON ro.id_object = o.id_object
                WHERE ro.id_reservation = r.id_reservation
            ), 0) AS prix_extras,
            (t.prix_heure * TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin) +
            COALESCE((
                SELECT SUM(o.prix * ro.quantite)
                FROM reservation_objet ro
                INNER JOIN objet o ON ro.id_object = o.id_object
                WHERE ro.id_reservation = r.id_reservation
            ), 0)) AS prix_total,
            GROUP_CONCAT(CONCAT(o.nom_objet, ' (x', ro.quantite, ')') SEPARATOR ', ') AS extras,
            r.statut
        FROM reservation r
        INNER JOIN utilisateur u ON r.id_joueur = u.email
        INNER JOIN creneau c ON r.id_creneau = c.id_creneaux
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        LEFT JOIN reservation_objet ro ON r.id_reservation = ro.id_reservation
        LEFT JOIN objet o ON ro.id_object = o.id_object
        WHERE t.id_responsable = :responsable_id
    ";

    $queryParams = [':responsable_id' => $responsable_id];

    if (!empty($currentFilterDate)) {
        $queryReservationDetails .= " AND DATE(r.date_reservation) = :filterDate";
        $queryParams[':filterDate'] = $currentFilterDate;
    }

    if (!empty($currentFilterStatus)) {
        $queryReservationDetails .= " AND r.statut = :filterStatus";
        $queryParams[':filterStatus'] = $currentFilterStatus;
    }

    if (!empty($searchQuery)) {
        $queryReservationDetails .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR t.nom_te LIKE :search)";
        $queryParams[':search'] = "%$searchQuery%";
    }

    $queryReservationDetails .= "
        GROUP BY r.id_reservation, u.nom, u.prenom, t.nom_te, t.categorie, 
                 r.date_reservation, c.heure_debut, c.heure_fin, t.prix_heure, r.statut
        ORDER BY r.date_reservation DESC
    ";  

    $stmt = $pdo->prepare($queryReservationDetails);
    $stmt->execute($queryParams);
    $_SESSION['reservationDetail'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Revenu total
    $queryRevenue = "
        SELECT COALESCE(SUM(
            (t.prix_heure * TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin)) +
            COALESCE(( 
                SELECT SUM(o.prix * ro.quantite)
                FROM reservation_objet ro
                INNER JOIN objet o ON ro.id_object = o.id_object
                WHERE ro.id_reservation = r.id_reservation
            ), 0)
        ), 0)
        FROM reservation r
        INNER JOIN creneau c ON r.id_creneau = c.id_creneaux
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        WHERE r.statut = 'confirmee'
        AND t.id_responsable = :responsable_id
    ";

    $stmtRevenue = $pdo->prepare($queryRevenue);
    $stmtRevenue->bindParam(':responsable_id', $responsable_id);
    $stmtRevenue->execute();
    $_SESSION['totalRevenue'] = number_format($stmtRevenue->fetchColumn(), 2);

} else {
    // Confirmées
    $queryConfirmedReservations = "SELECT COUNT(*) FROM reservation WHERE statut='confirmee'";
    $stmt = $pdo->prepare($queryConfirmedReservations);
    $stmt->execute();
    $_SESSION['totalConfirmedReservations'] = $stmt->fetchColumn();

    // En attente
    $queryPendingReservations = "SELECT COUNT(*) FROM reservation WHERE statut='en_attente'";
    $stmt = $pdo->prepare($queryPendingReservations);
    $stmt->execute();
    $_SESSION['totalPendingReservations'] = $stmt->fetchColumn();

    // Total
    $queryTotalReservations = "SELECT COUNT(*) FROM reservation";
    $stmt = $pdo->prepare($queryTotalReservations);
    $stmt->execute();
    $_SESSION['totalReservations'] = $stmt->fetchColumn();

    // Détails réservations
    $queryReservationDetails = "
        SELECT 
            r.id_reservation,
            u.nom,
            u.prenom,
            t.nom_te AS nom_terrain,
            t.categorie AS categorie_terrain,
            r.date_reservation AS date_debut,
            c.heure_debut,
            c.heure_fin,
            TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin) AS duree,
            t.prix_heure,
            (t.prix_heure * TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin)) AS prix_terrain,
            COALESCE((
                SELECT SUM(o.prix * ro.quantite)
                FROM reservation_objet ro
                INNER JOIN objet o ON ro.id_object = o.id_object
                WHERE ro.id_reservation = r.id_reservation
            ), 0) AS prix_extras,
            (t.prix_heure * TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin) +
            COALESCE((
                SELECT SUM(o.prix * ro.quantite)
                FROM reservation_objet ro
                INNER JOIN objet o ON ro.id_object = o.id_object
                WHERE ro.id_reservation = r.id_reservation
            ), 0)) AS prix_total,
            GROUP_CONCAT(CONCAT(o.nom_objet, ' (x', ro.quantite, ')') SEPARATOR ', ') AS extras,
            r.statut
        FROM reservation r
        INNER JOIN utilisateur u ON r.id_joueur = u.email
        INNER JOIN creneau c ON r.id_creneau = c.id_creneaux
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        LEFT JOIN reservation_objet ro ON r.id_reservation = ro.id_reservation
        LEFT JOIN objet o ON ro.id_object = o.id_object
        WHERE 1=1
    ";

    $queryParams = [];

    if (!empty($currentFilterDate)) {
        $queryReservationDetails .= " AND DATE(r.date_reservation) = :filterDate";
        $queryParams[':filterDate'] = $currentFilterDate;
    }

    if (!empty($currentFilterStatus)) {
        $queryReservationDetails .= " AND r.statut = :filterStatus";
        $queryParams[':filterStatus'] = $currentFilterStatus;
    }

    if (!empty($searchQuery)) {
        $queryReservationDetails .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR t.nom_te LIKE :search)";
        $queryParams[':search'] = "%$searchQuery%";
    }

    $queryReservationDetails .= "
        GROUP BY r.id_reservation, u.nom, u.prenom, t.nom_te, t.categorie, 
                 r.date_reservation, c.heure_debut, c.heure_fin, t.prix_heure, r.statut
        ORDER BY r.date_reservation DESC
    ";  

    $stmt = $pdo->prepare($queryReservationDetails);
    $stmt->execute($queryParams);
    $_SESSION['reservationDetail'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Revenu total
    $queryRevenue = "
        SELECT COALESCE(SUM(
            (t.prix_heure * TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin)) +
            COALESCE((
                SELECT SUM(o.prix * ro.quantite)
                FROM reservation_objet ro
                INNER JOIN objet o ON ro.id_object = o.id_object
                WHERE ro.id_reservation = r.id_reservation
            ), 0)
        ), 0)
        FROM reservation r
        INNER JOIN creneau c ON r.id_creneau = c.id_creneaux
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        WHERE r.statut = 'confirmee'
    ";

    $stmtRevenue = $pdo->prepare($queryRevenue);
    $stmtRevenue->execute();
    $_SESSION['totalRevenue'] = number_format($stmtRevenue->fetchColumn(), 2);
}