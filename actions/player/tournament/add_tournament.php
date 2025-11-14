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

// Vérifier que le terrain existe et récupérer le responsable si un terrain est sélectionné
$idResponsable = null;
if ($idTerrain) {
    try {
        $stmt = $pdo->prepare('SELECT id_terrain, id_responsable FROM terrain WHERE id_terrain = :id');
        $stmt->execute([':id' => $idTerrain]);
        $terrain = $stmt->fetch();
        if (!$terrain) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Terrain introuvable.']);
            exit;
        }
        $idResponsable = $terrain['id_responsable'] ?? null;
        
        // Si un terrain est sélectionné, il doit avoir un responsable
        if (!$idResponsable) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ce terrain n\'a pas de responsable assigné.']);
            exit;
        }
    } catch (PDOException $e) {
        error_log('Player tournament terrain check error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la vérification du terrain.']);
        exit;
    }
} else {
    // Si aucun terrain n'est sélectionné, la demande ne peut pas être créée
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vous devez sélectionner un terrain pour créer une demande de tournoi.']);
    exit;
}

$prixValue = null;
if ($prixInscription !== null && $prixInscription !== '') {
    $prixValue = (float) $prixInscription;
    if ($prixValue < 0) {
        $prixValue = 0;
    }
}

// Créer une demande de tournoi au lieu d'un tournoi directement
try {
    $sql = "INSERT INTO demande_tournoi (
                nom_t, date_debut, date_fin, size, description, regles, 
                prix_inscription, id_terrain, email_organisateur, id_responsable, statut
            ) VALUES (
                :nom, :date_debut, :date_fin, :size, :description, :regles,
                :prix, :id_terrain, :organisateur, :responsable, 'en_attente'
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nom' => $nomTournoi,
        ':date_debut' => $dateDebut,
        ':date_fin' => $dateFin,
        ':size' => $size,
        ':description' => $description !== '' ? $description : null,
        ':regles' => $regles !== '' ? $regles : null,
        ':prix' => $prixValue,
        ':id_terrain' => $idTerrain,
                ':organisateur' => $_SESSION['user_email'],
        ':responsable' => $idResponsable
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true, 
        'message' => 'Votre demande de tournoi a été envoyée au responsable du terrain. Vous serez notifié une fois la demande traitée.'
    ]);
} catch (PDOException $e) {
    error_log('Player add tournament request error: ' . $e->getMessage());
    
    // Vérifier si c'est une erreur de table manquante
    if ($e->getCode() === '42S02' || stripos($e->getMessage(), "doesn't exist") !== false || stripos($e->getMessage(), "Unknown table") !== false) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur : la table demande_tournoi n\'existe pas. Veuillez exécuter le script SQL de création.'
        ]);
    } else {
    http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la demande de tournoi.']);
    }
}

