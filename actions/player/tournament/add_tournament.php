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

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$nomTournoi = trim($data['nom_tournoi'] ?? '');
$dateDebut = $data['date_debut'] ?? '';
$dateFin = $data['date_fin'] ?? '';
$size = isset($data['size']) ? (int) $data['size'] : 0;
$prixInscription = $data['prix_inscription'] ?? null;
$idTerrain = isset($data['id_terrain']) && $data['id_terrain'] !== '' ? (int) $data['id_terrain'] : null;
$description = trim($data['description'] ?? '');
$regles = trim($data['regles'] ?? '');

$errors = [];

if ($nomTournoi === '') {
    $errors[] = 'Le nom du tournoi est requis.';
}
if ($dateDebut === '' || $dateFin === '') {
    $errors[] = 'Les dates de début et de fin sont requises.';
}
if ($size < 2) {
    $errors[] = 'Le nombre d’équipes doit être au minimum de 2.';
}

$startDate = DateTime::createFromFormat('Y-m-d', $dateDebut);
$endDate = DateTime::createFromFormat('Y-m-d', $dateFin);
if (!$startDate || !$endDate) {
    $errors[] = 'Les dates doivent être au format AAAA-MM-JJ.';
} elseif ($startDate > $endDate) {
    $errors[] = 'La date de fin doit être postérieure à la date de début.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

if ($idTerrain) {
    try {
        $stmt = $pdo->prepare('SELECT id_terrain FROM terrain WHERE id_terrain = :id');
        $stmt->execute([':id' => $idTerrain]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Terrain introuvable.']);
            exit;
        }
    } catch (PDOException $e) {
        error_log('Player tournament terrain check error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la vérification du terrain.']);
        exit;
    }
}

$startDateImmutable = new DateTimeImmutable($dateDebut);
$endDateImmutable = new DateTimeImmutable($dateFin);
$now = new DateTimeImmutable('now');

if ($now < $startDateImmutable) {
    $statut = 'planifie';
} elseif ($now > $endDateImmutable) {
    $statut = 'termine';
} else {
    $statut = 'en_cours';
}

$prixValue = null;
if ($prixInscription !== null && $prixInscription !== '') {
    $prixValue = (float) $prixInscription;
    if ($prixValue < 0) {
        $prixValue = 0;
    }
}

try {
    $pdo->beginTransaction();

    $baseParams = [
        ':nom' => $nomTournoi,
        ':date_debut' => $dateDebut,
        ':date_fin' => $dateFin,
        ':size' => $size,
        ':description' => $description !== '' ? $description : null,
        ':statut' => $statut,
        ':id_terrain' => $idTerrain,
    ];

    $variantParams = [
        [
            'sql' => "INSERT INTO tournoi (nom_t, date_debut, date_fin, size, description, statut, id_terrain, email_organisateur, prix_inscription, regles)
                      VALUES (:nom, :date_debut, :date_fin, :size, :description, :statut, :id_terrain, :organisateur, :prix, :regles)",
            'params' => $baseParams + [
                ':organisateur' => $_SESSION['user_email'],
                ':prix' => $prixValue,
                ':regles' => $regles !== '' ? $regles : null,
            ],
        ],
        [
            'sql' => "INSERT INTO tournoi (nom_t, date_debut, date_fin, size, description, statut, id_terrain, prix_inscription, regles)
                      VALUES (:nom, :date_debut, :date_fin, :size, :description, :statut, :id_terrain, :prix, :regles)",
            'params' => $baseParams + [
                ':prix' => $prixValue,
                ':regles' => $regles !== '' ? $regles : null,
            ],
        ],
        [
            'sql' => "INSERT INTO tournoi (nom_t, date_debut, date_fin, size, description, statut, id_terrain)
                      VALUES (:nom, :date_debut, :date_fin, :size, :description, :statut, :id_terrain)",
            'params' => $baseParams,
        ],
    ];

    $inserted = false;
    foreach ($variantParams as $variant) {
        try {
            $stmt = $pdo->prepare($variant['sql']);
            $stmt->execute($variant['params']);
            $inserted = true;
            break;
        } catch (PDOException $e) {
            if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
                continue;
            }
            throw $e;
        }
    }

    if (!$inserted) {
        throw new PDOException('Impossible de créer le tournoi (colonnes incompatibles).');
    }

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Votre tournoi a été créé avec succès.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Player add tournament error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du tournoi.']);
}

