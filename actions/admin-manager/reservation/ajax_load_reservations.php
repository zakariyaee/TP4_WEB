<?php
/**
 * AJAX - Chargement des réservations (corrigé)
 * Retourne les réservations et statistiques au format JSON
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once '../../../config/database.php'; // Connexion PDO

try {
    // === Filtres ===
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filterDate = $_GET['date'] ?? '';
    $filterStatus = $_GET['status'] ?? '';

    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'responsable') {
        $responsable_id = $_SESSION['user_email'];
        
        // === Requête principale ===
        $sql = "
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

        $params = [':responsable_id' => $responsable_id];

        // === Filtres dynamiques ===
        if ($search !== '') {
            $sql .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR t.nom_te LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if ($filterDate !== '') {
            $sql .= " AND DATE(r.date_reservation) = :filterDate";
            $params[':filterDate'] = $filterDate;
        }

        if ($filterStatus !== '') {
            $sql .= " AND r.statut = :filterStatus";
            $params[':filterStatus'] = $filterStatus;
        }

        $sql .= "
            GROUP BY r.id_reservation, u.nom, u.prenom, t.nom_te, t.categorie, 
                     r.date_reservation, c.heure_debut, c.heure_fin, t.prix_heure, r.statut
            ORDER BY r.date_reservation DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // === Statistiques globales ===
        $statsSQL = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN r.statut = 'confirmee' THEN 1 ELSE 0 END) AS confirmed,
                SUM(CASE WHEN r.statut = 'en_attente' THEN 1 ELSE 0 END) AS pending,
                COALESCE(SUM(
                    CASE WHEN r.statut = 'confirmee' THEN
                        (t.prix_heure * TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin)) +
                        COALESCE((
                            SELECT SUM(o.prix * ro.quantite)
                            FROM reservation_objet ro
                            INNER JOIN objet o ON ro.id_object = o.id_object
                            WHERE ro.id_reservation = r.id_reservation
                        ), 0)
                    ELSE 0 END
                ), 0) AS revenue
            FROM reservation r
            INNER JOIN terrain t ON r.id_terrain = t.id_terrain
            INNER JOIN creneau c ON r.id_creneau = c.id_creneaux
            INNER JOIN utilisateur u ON r.id_joueur = u.email
            WHERE t.id_responsable = :responsable_id
        ";

        if ($search !== '') {
            $statsSQL .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR t.nom_te LIKE :search)";
        }
        if ($filterDate !== '') {
            $statsSQL .= " AND DATE(r.date_reservation) = :filterDate";
        }
        if ($filterStatus !== '') {
            $statsSQL .= " AND r.statut = :filterStatus";
        }

        $statsStmt = $pdo->prepare($statsSQL);
        $statsStmt->execute($params);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    } else {
        // Admin - toutes les réservations
        $sql = "
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

        $params = [];

        if ($search !== '') {
            $sql .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR t.nom_te LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if ($filterDate !== '') {
            $sql .= " AND DATE(r.date_reservation) = :filterDate";
            $params[':filterDate'] = $filterDate;
        }

        if ($filterStatus !== '') {
            $sql .= " AND r.statut = :filterStatus";
            $params[':filterStatus'] = $filterStatus;
        }

        $sql .= "
            GROUP BY r.id_reservation, u.nom, u.prenom, t.nom_te, t.categorie, 
                     r.date_reservation, c.heure_debut, c.heure_fin, t.prix_heure, r.statut
            ORDER BY r.date_reservation DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // === Statistiques globales ===
        $statsSQL = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN r.statut = 'confirmee' THEN 1 ELSE 0 END) AS confirmed,
                SUM(CASE WHEN r.statut = 'en_attente' THEN 1 ELSE 0 END) AS pending,
                COALESCE(SUM(
                    CASE WHEN r.statut = 'confirmee' THEN
                        (t.prix_heure * TIMESTAMPDIFF(HOUR, c.heure_debut, c.heure_fin)) +
                        COALESCE((
                            SELECT SUM(o.prix * ro.quantite)
                            FROM reservation_objet ro
                            INNER JOIN objet o ON ro.id_object = o.id_object
                            WHERE ro.id_reservation = r.id_reservation
                        ), 0)
                    ELSE 0 END
                ), 0) AS revenue
            FROM reservation r
            INNER JOIN terrain t ON r.id_terrain = t.id_terrain
            INNER JOIN creneau c ON r.id_creneau = c.id_creneaux
            INNER JOIN utilisateur u ON r.id_joueur = u.email
            WHERE 1=1
        ";

        if ($search !== '') {
            $statsSQL .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR t.nom_te LIKE :search)";
        }
        if ($filterDate !== '') {
            $statsSQL .= " AND DATE(r.date_reservation) = :filterDate";
        }
        if ($filterStatus !== '') {
            $statsSQL .= " AND r.statut = :filterStatus";
        }

        $statsStmt = $pdo->prepare($statsSQL);
        $statsStmt->execute($params);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    }

    $statsFormatted = [
        'total' => (int)($stats['total'] ?? 0),
        'confirmed' => (int)($stats['confirmed'] ?? 0),
        'pending' => (int)($stats['pending'] ?? 0),
        'revenue' => number_format((float)($stats['revenue'] ?? 0), 2)
    ];

    // === Réponse JSON ===
    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'stats' => $statsFormatted,
        'filters' => [
            'search' => $search,
            'date' => $filterDate,
            'status' => $filterStatus
        ],
        'count' => count($reservations),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage(),
        'reservations' => [],
        'stats' => ['total' => 0, 'confirmed' => 0, 'pending' => 0, 'revenue' => '0.00']
    ], JSON_UNESCAPED_UNICODE);
}