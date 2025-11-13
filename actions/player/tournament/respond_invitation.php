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
$tournamentId = isset($payload['id_tournoi']) ? (int) $payload['id_tournoi'] : 0;
$decision = strtolower(trim($payload['decision'] ?? ''));
$playerEmail = $_SESSION['user_email'] ?? null;

if ($teamId <= 0 || $tournamentId <= 0 || !$playerEmail || !in_array($decision, ['accept', 'decline'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT role_equipe
        FROM equipe_joueur
        WHERE id_equipe = :team AND id_joueur = :player
        FOR UPDATE
    ");
    $stmt->execute([
        ':team' => $teamId,
        ':player' => $playerEmail,
    ]);
    $role = $stmt->fetchColumn();

    if (!$role || strcasecmp($role, 'capitaine') !== 0) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Seul le capitaine peut répondre à une invitation']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT statut_participation
        FROM tournoi_equipe
        WHERE id_tournoi = :tournoi AND id_equipe = :team
        FOR UPDATE
    ");
    $stmt->execute([
        ':tournoi' => $tournamentId,
        ':team' => $teamId,
    ]);
    $currentStatus = $stmt->fetchColumn();
    if (!$currentStatus) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invitation introuvable']);
        exit;
    }

    if ($decision === 'accept') {
        $stmt = $pdo->prepare("
            UPDATE tournoi_equipe
            SET statut_participation = 'confirmee'
            WHERE id_tournoi = :tournoi AND id_equipe = :team
        ");
        $stmt->execute([
            ':tournoi' => $tournamentId,
            ':team' => $teamId,
        ]);
        $pdo->commit();
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Invitation acceptée. Votre équipe est inscrite !']);
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM tournoi_equipe
            WHERE id_tournoi = :tournoi AND id_equipe = :team
        ");
        $stmt->execute([
            ':tournoi' => $tournamentId,
            ':team' => $teamId,
        ]);
        $pdo->commit();
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Invitation refusée.']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Player respond invitation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors du traitement de la réponse']);
}

