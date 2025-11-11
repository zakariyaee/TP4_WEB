<?php
// actions/admin-manager/slot/get_slots.php (VERSION AMÉLIORÉE)
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    $terrain = $_GET['terrain'] ?? '';
    $jour = $_GET['jour'] ?? '';
    $disponibilite = $_GET['disponibilite'] ?? '';
    
    // NOUVEAU : Paramètres pour la période
    $date_debut = $_GET['date_debut'] ?? date('Y-m-d'); // Date de début (par défaut aujourd'hui)
    $date_fin = $_GET['date_fin'] ?? date('Y-m-d', strtotime('+7 days')); // Date de fin (par défaut +7 jours)

    $query = "
        SELECT 
            c.id_creneaux,
            c.jour_semaine,
            c.heure_debut,
            c.heure_fin,
            c.disponibilite as creneau_disponibilite,
            c.id_terrain,
            t.nom_te as nom_terrain,
            t.prix_heure,
            t.localisation,
            t.categorie,
            t.type,
            t.taille,
            t.id_responsable,
            r.id_reservation,
            r.date_reservation,
            DATE(r.date_reservation) as date_reservation_only,
            r.statut as reservation_statut,
            e1.id_equipe,
            e1.nom_equipe as equipe_nom,
            e1.email_equipe,
            e2.id_equipe as equipe_adverse_id,
            e2.nom_equipe as equipe_adverse_nom,
            u.nom as responsable_nom,
            u.prenom as responsable_prenom
        FROM creneau c
        INNER JOIN terrain t ON c.id_terrain = t.id_terrain
        LEFT JOIN utilisateur u ON t.id_responsable = u.email
        LEFT JOIN reservation r ON c.id_creneaux = r.id_creneau 
            AND r.statut IN ('confirmee', 'en_attente')
            AND DATE(r.date_reservation) BETWEEN :date_debut AND :date_fin
        LEFT JOIN equipe e1 ON r.id_equipe = e1.id_equipe
        LEFT JOIN equipe e2 ON r.id_equipe_adverse = e2.id_equipe
        WHERE 1=1
    ";

    $params = [
        ':date_debut' => $date_debut,
        ':date_fin' => $date_fin
    ];

    if ($_SESSION['user_role'] === 'responsable') {
        $query .= " AND t.id_responsable = :user_email";
        $params[':user_email'] = $_SESSION['user_email'];
    }

    if (!empty($terrain)) {
        $query .= " AND c.id_terrain = :terrain";
        $params[':terrain'] = $terrain;
    }

    if (!empty($jour)) {
        $query .= " AND c.jour_semaine = :jour";
        $params[':jour'] = $jour;
    }

    $query .= " ORDER BY 
        FIELD(c.jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'),
        c.heure_debut ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données
    $creneaux = [];
    foreach ($results as $row) {
        $creneau = [
            'id_creneaux' => $row['id_creneaux'],
            'jour_semaine' => $row['jour_semaine'],
            'heure_debut' => substr($row['heure_debut'], 0, 5),
            'heure_fin' => substr($row['heure_fin'], 0, 5),
            'id_terrain' => $row['id_terrain'],
            'nom_terrain' => $row['nom_terrain'],
            'prix_heure' => number_format($row['prix_heure'], 2, '.', ''),
            'disponibilite' => ($row['id_reservation'] === null) ? 1 : 0,
            'terrain_info' => [
                'localisation' => $row['localisation'],
                'categorie' => $row['categorie'],
                'type' => $row['type'],
                'taille' => $row['taille'],
                'responsable' => $row['responsable_nom'] ? 
                    $row['responsable_nom'] . ' ' . $row['responsable_prenom'] : 
                    'Non assigné'
            ]
        ];

        if ($row['id_reservation']) {
            $creneau['reservation_info'] = [
                'id_reservation' => $row['id_reservation'],
                'date_reservation' => $row['date_reservation'],
                'date_reservation_only' => $row['date_reservation_only'], // NOUVEAU
                'statut' => $row['reservation_statut'],
                'equipe_id' => $row['id_equipe'],
                'equipe_nom' => $row['equipe_nom'],
                'equipe_email' => $row['email_equipe'],
                'equipe_adverse_id' => $row['equipe_adverse_id'],
                'equipe_adverse' => $row['equipe_adverse_nom'],
                'type_match' => $row['equipe_adverse_nom'] ? 'Match' : 'Entraînement'
            ];
        } else {
            $creneau['reservation_info'] = null;
        }

        $creneaux[] = $creneau;
    }

    echo json_encode([
        'success' => true,
        'creneaux' => $creneaux,
        'count' => count($creneaux),
        'filters' => [
            'terrain' => $terrain,
            'jour' => $jour,
            'disponibilite' => $disponibilite,
            'date_debut' => $date_debut,
            'date_fin' => $date_fin
        ],
        'user_role' => $_SESSION['user_role']
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des créneaux',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}