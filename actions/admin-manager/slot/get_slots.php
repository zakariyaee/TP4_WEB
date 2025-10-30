<?php
// actions/admin-respo/get_creneaux.php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';
header('Content-Type: application/json');

try {
    $terrain = $_GET['terrain'] ?? '';
    $jour = $_GET['jour'] ?? '';
    $disponibilite = $_GET['disponibilite'] ?? '';

    // Requête principale pour récupérer les créneaux avec leurs informations
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
            r.id_reservation,
            r.date_reservation,
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
            AND DATE(r.date_reservation) >= CURDATE()
        LEFT JOIN equipe e1 ON r.id_equipe = e1.id_equipe
        LEFT JOIN equipe e2 ON r.id_equipe_adverse = e2.id_equipe
        WHERE 1=1
    ";

    $params = [];

    if (!empty($terrain)) {
        $query .= " AND c.id_terrain = ?";
        $params[] = $terrain;
    }

    if (!empty($jour)) {
        $query .= " AND c.jour_semaine = ?";
        $params[] = $jour;
    }

    if ($disponibilite !== '') {
        if ($disponibilite == '1') {
            // Créneaux disponibles (pas de réservation active)
            $query .= " AND r.id_reservation IS NULL";
        } else {
            // Créneaux réservés (avec réservation active)
            $query .= " AND r.id_reservation IS NOT NULL";
        }
    }

    $query .= " ORDER BY 
        FIELD(c.jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'),
        c.heure_debut ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données pour une meilleure organisation
    $creneaux = [];
    foreach ($results as $row) {
        $creneau = [
            'id_creneaux' => $row['id_creneaux'],
            'jour_semaine' => $row['jour_semaine'],
            'heure_debut' => substr($row['heure_debut'], 0, 5), // Format HH:MM
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

        // Ajouter les informations de réservation si le créneau est réservé
        if ($row['id_reservation']) {
            $creneau['reservation_info'] = [
                'id_reservation' => $row['id_reservation'],
                'date_reservation' => $row['date_reservation'],
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
            'disponibilite' => $disponibilite
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des créneaux',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>