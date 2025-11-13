<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

$playerEmail = $_SESSION['user_email'] ?? null;

if (!$playerEmail) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            te.id_tournoi,
            te.id_equipe,
            COALESCE(te.statut_participation, 'invitee') AS statut_participation,
            e.nom_equipe,
            ej.role_equipe,
            t.nom_t AS nom_tournoi,
            t.date_debut,
            t.date_fin,
            t.prix_inscription,
            t.description,
            t.statut AS statut_tournoi,
            tr.nom_te AS terrain_nom,
            tr.ville,
            tr.localisation
        FROM tournoi_equipe te
        INNER JOIN equipe e ON e.id_equipe = te.id_equipe
        INNER JOIN equipe_joueur ej ON ej.id_equipe = e.id_equipe AND ej.id_joueur = :email
        LEFT JOIN tournoi t ON t.id_tournoi = te.id_tournoi
        LEFT JOIN terrain tr ON tr.id_terrain = t.id_terrain
        ORDER BY 
            CASE WHEN COALESCE(te.statut_participation, 'invitee') = 'invitee' THEN 0 ELSE 1 END,
            t.date_debut ASC
    ");
    $stmt->execute([':email' => $playerEmail]);
    $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'invitations' => $invitations,
    ]);
} catch (PDOException $e) {
    error_log('Player get invitations error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des invitations']);
}

