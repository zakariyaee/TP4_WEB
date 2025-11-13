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

$playerEmail = $_SESSION['user_email'] ?? null;
$teamId = isset($payload['id_equipe']) ? (int)$payload['id_equipe'] : 0;

if ($teamId <= 0) {
    $teamCode = trim($payload['team_code'] ?? '');
    if ($teamCode !== '' && ctype_digit($teamCode)) {
        $teamId = (int) $teamCode;
    }
}

if ($teamId <= 0 || !$playerEmail) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => "Identifiant d'équipe invalide"]);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id_equipe, nom_equipe FROM equipe WHERE id_equipe = :id');
    $stmt->execute([':id' => $teamId]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Équipe introuvable"]);
        exit;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM equipe_joueur WHERE id_equipe = :id AND id_joueur = :email');
    $stmt->execute([
        ':id' => $teamId,
        ':email' => $playerEmail,
    ]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "Vous faites déjà partie de cette équipe"]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO equipe_joueur (id_joueur, id_equipe, role_equipe, date_adhesion)
        VALUES (:joueur, :equipe, 'membre', NOW())
    ");
    try {
        $stmt->execute([
            ':joueur' => $playerEmail,
            ':equipe' => $teamId,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
            $stmtFallback = $pdo->prepare("
                INSERT INTO equipe_joueur (id_joueur, id_equipe, role_equipe)
                VALUES (:joueur, :equipe, 'membre')
            ");
            $stmtFallback->execute([
                ':joueur' => $playerEmail,
                ':equipe' => $teamId,
            ]);
        } else {
            throw $e;
        }
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => "Vous avez rejoint l'équipe {$team['nom_equipe']} avec succès",
    ]);
} catch (PDOException $e) {
    error_log('Player join team error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de l'adhésion à l'équipe"]);
}

