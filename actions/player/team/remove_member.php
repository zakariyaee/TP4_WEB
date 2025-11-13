<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$teamId = isset($payload['id_equipe']) ? (int) $payload['id_equipe'] : 0;
$memberEmail = trim($payload['id_joueur'] ?? '');
$playerEmail = $_SESSION['user_email'] ?? null;

if ($teamId <= 0 || $memberEmail === '' || !$playerEmail) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

if (strcasecmp($memberEmail, $playerEmail) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Utilisez la fonctionnalité quitter l'équipe pour vous retirer"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT role_equipe
        FROM equipe_joueur
        WHERE id_equipe = :id AND id_joueur = :email
        FOR UPDATE
    ");
    $stmt->execute([
        ':id' => $teamId,
        ':email' => $playerEmail,
    ]);
    $currentRole = $stmt->fetchColumn();
    if (!$currentRole || strcasecmp($currentRole, 'capitaine') !== 0) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Seul le capitaine peut retirer un membre']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT role_equipe
        FROM equipe_joueur
        WHERE id_equipe = :id AND id_joueur = :member
        FOR UPDATE
    ");
    $stmt->execute([
        ':id' => $teamId,
        ':member' => $memberEmail,
    ]);
    $memberRole = $stmt->fetchColumn();
    if (!$memberRole) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Membre introuvable dans cette équipe']);
        exit;
    }

    if (strcasecmp($memberRole, 'capitaine') === 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Impossible de retirer un autre capitaine']);
        exit;
    }

    $stmt = $pdo->prepare("
        DELETE FROM equipe_joueur
        WHERE id_equipe = :id AND id_joueur = :member
    ");
    $stmt->execute([
        ':id' => $teamId,
        ':member' => $memberEmail,
    ]);

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Membre retiré de l\'équipe']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Player remove team member error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du membre']);
}

