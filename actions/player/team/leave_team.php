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
$playerEmail = $_SESSION['user_email'] ?? null;

if ($teamId <= 0 || !$playerEmail) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => "Identifiant d'équipe invalide"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id_equipe, id_joueur, role_equipe
        FROM equipe_joueur
        WHERE id_equipe = :id AND id_joueur = :email
        FOR UPDATE
    ");
    $stmt->execute([
        ':id' => $teamId,
        ':email' => $playerEmail,
    ]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$membership) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Vous ne faites pas partie de cette équipe"]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id_joueur, role_equipe
        FROM equipe_joueur
        WHERE id_equipe = :id
        ORDER BY date_adhesion ASC
        FOR UPDATE
    ");
    $stmt->execute([':id' => $teamId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        DELETE FROM equipe_joueur
        WHERE id_equipe = :id AND id_joueur = :email
    ");
    $stmt->execute([
        ':id' => $teamId,
        ':email' => $playerEmail,
    ]);

    $message = "Vous avez quitté l'équipe";

    if (count($members) <= 1) {
        $pdo->prepare('DELETE FROM tournoi_equipe WHERE id_equipe = :id')->execute([':id' => $teamId]);
        $pdo->prepare('DELETE FROM equipe WHERE id_equipe = :id')->execute([':id' => $teamId]);
        $message .= " et l'équipe a été supprimée faute de membres.";
    } elseif (strcasecmp($membership['role_equipe'], 'capitaine') === 0) {
        foreach ($members as $member) {
            if ($member['id_joueur'] !== $playerEmail) {
                $pdo->prepare("
                    UPDATE equipe_joueur
                    SET role_equipe = 'capitaine'
                    WHERE id_equipe = :id AND id_joueur = :email
                ")->execute([
                    ':id' => $teamId,
                    ':email' => $member['id_joueur'],
                ]);
                $message .= " et un nouveau capitaine a été désigné.";
                break;
            }
        }
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Player leave team error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la sortie de l'équipe"]);
}

