<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

$playerEmail = $_SESSION['user_email'] ?? null;
$teamId = isset($_GET['id_equipe']) ? (int) $_GET['id_equipe'] : 0;

if (!$playerEmail || $teamId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 1 
        FROM equipe_joueur 
        WHERE id_equipe = :id AND id_joueur = :email
    ");
    $stmt->execute([
        ':id' => $teamId,
        ':email' => $playerEmail,
    ]);
    if (!$stmt->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Vous ne faites pas partie de cette équipe']);
        exit;
    }

    $membersQuery = "
        SELECT 
            ej.id_joueur,
            ej.role_equipe,
            ej.date_adhesion,
            u.nom,
            u.prenom,
            u.email,
            u.num_tele
        FROM equipe_joueur ej
        LEFT JOIN utilisateur u ON u.email = ej.id_joueur
        WHERE ej.id_equipe = :id
        ORDER BY 
            CASE WHEN LOWER(ej.role_equipe) = 'capitaine' THEN 0 ELSE 1 END,
            u.nom ASC
    ";

    $stmt = $pdo->prepare($membersQuery);
    $stmt->execute([':id' => $teamId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'members' => $members,
    ]);
} catch (PDOException $e) {
    error_log('Player get team members error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des membres']);
}

