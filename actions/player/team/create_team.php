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
$playerName = $_SESSION['user_name'] ?? null;

$teamName = trim($payload['nom_equipe'] ?? '');
$teamEmail = trim($payload['email_equipe'] ?? '');

if ($teamName === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => "Le nom de l'équipe est requis"]);
    exit;
}

if ($teamEmail === '') {
    $teamEmail = $playerEmail;
}

try {
    $pdo->beginTransaction();

    $insertTeamSql = "
        INSERT INTO equipe (nom_equipe, email_equipe, date_creation)
        VALUES (:nom, :email, NOW())
    ";

    try {
        $stmt = $pdo->prepare($insertTeamSql);
        $stmt->execute([
            ':nom' => $teamName,
            ':email' => $teamEmail,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
            $stmt = $pdo->prepare("
                INSERT INTO equipe (nom_equipe, email_equipe)
                VALUES (:nom, :email)
            ");
            $stmt->execute([
                ':nom' => $teamName,
                ':email' => $teamEmail,
            ]);
        } else {
            throw $e;
        }
    }

    $teamId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO equipe_joueur (id_joueur, id_equipe, role_equipe, date_adhesion)
        VALUES (:joueur, :equipe, 'capitaine', NOW())
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
                VALUES (:joueur, :equipe, 'capitaine')
            ");
            $stmtFallback->execute([
                ':joueur' => $playerEmail,
                ':equipe' => $teamId,
            ]);
        } else {
            throw $e;
        }
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => "Équipe créée avec succès",
        'team' => [
            'id_equipe' => $teamId,
            'nom_equipe' => $teamName,
            'email_equipe' => $teamEmail,
            'role_equipe' => 'capitaine',
            'createur' => $playerName,
        ],
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "Une équipe avec ce nom ou cette adresse e-mail existe déjà"]);
        exit;
    }
    error_log('Player create team error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la création de l'équipe"]);
}

