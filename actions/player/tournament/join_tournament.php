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

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$idTournoi = isset($data['id_tournoi']) ? (int) $data['id_tournoi'] : 0;
$idEquipe = isset($data['id_equipe']) ? (int) $data['id_equipe'] : 0;

if ($idTournoi <= 0 || $idEquipe <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tournoi ou équipe invalide.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT 1 FROM equipe_joueur WHERE id_equipe = :id AND id_joueur = :email');
    $stmt->execute([
        ':id' => $idEquipe,
        ':email' => $_SESSION['user_email']
    ]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez inscrire que vos propres équipes.']);
        exit;
    }
} catch (PDOException $e) {
    error_log('Player join tournament team check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la vérification de l’équipe.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id_tournoi, size, statut, date_debut, date_fin FROM tournoi WHERE id_tournoi = :id');
    $stmt->execute([':id' => $idTournoi]);
    $tournoi = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tournoi) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournoi introuvable.']);
        exit;
    }
} catch (PDOException $e) {
    error_log('Player join tournament fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du tournoi.']);
    exit;
}

$start = !empty($tournoi['date_debut']) ? new DateTimeImmutable($tournoi['date_debut']) : null;
$end = !empty($tournoi['date_fin']) ? new DateTimeImmutable($tournoi['date_fin']) : null;
$now = new DateTimeImmutable('now');

$statutTournoi = strtolower($tournoi['statut'] ?? '');
if ($statutTournoi === 'annule') {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Ce tournoi est annulé.']);
    exit;
}
if ($statutTournoi === 'termine' || ($end && $now > $end)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Ce tournoi est déjà terminé.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tournoi_equipe WHERE id_tournoi = :id');
    $stmt->execute([':id' => $idTournoi]);
    $nbInscrits = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Player join tournament count error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors du contrôle des places disponibles.']);
    exit;
}

$capacite = isset($tournoi['size']) ? (int) $tournoi['size'] : 0;
if ($capacite > 0 && $nbInscrits >= $capacite) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Ce tournoi est complet.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT 1 FROM tournoi_equipe WHERE id_tournoi = :tournoi AND id_equipe = :equipe');
    $stmt->execute([
        ':tournoi' => $idTournoi,
        ':equipe' => $idEquipe
    ]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Cette équipe est déjà inscrite à ce tournoi.']);
        exit;
    }
} catch (PDOException $e) {
    error_log('Player join tournament duplicate check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la vérification de votre inscription.']);
    exit;
}

try {
    $sql = 'INSERT INTO tournoi_equipe (id_tournoi, id_equipe, statut_participation) VALUES (:tournoi, :equipe, :statut)';
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':tournoi' => $idTournoi,
            ':equipe' => $idEquipe,
            ':statut' => 'invitee'
        ]);
    } catch (PDOException $eInsert) {
        if ($eInsert->getCode() === '42S22' || stripos($eInsert->getMessage(), 'Unknown column') !== false) {
            $sqlFallback = 'INSERT INTO tournoi_equipe (id_tournoi, id_equipe) VALUES (:tournoi, :equipe)';
            $stmtFallback = $pdo->prepare($sqlFallback);
            $stmtFallback->execute([
                ':tournoi' => $idTournoi,
                ':equipe' => $idEquipe
            ]);
        } else {
            throw $eInsert;
        }
    }

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Votre équipe est inscrite !']);
} catch (PDOException $e) {
    error_log('Player join tournament insert error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de votre inscription au tournoi.']);
}

