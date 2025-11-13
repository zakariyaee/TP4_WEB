<?php
/**
 * Add new tournament
 *
 * Creates a new tournament with validation and permission checks.
 * Handles flexible column schema (email_organisateur, prix_inscription).
 * Admin and Responsable access (responsable can only create on their terrains).
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$nomTournoi = trim($data['nom_tournoi'] ?? '');
$typeTournoi = isset($data['type_tournoi']) && $data['type_tournoi'] !== '' ? trim($data['type_tournoi']) : null;
$dateDebut = $data['date_debut'] ?? '';
$dateFin = $data['date_fin'] ?? '';
$nbEquipes = intval($data['nb_equipes'] ?? 0);
$prixInscription = isset($data['prix_inscription']) ? floatval($data['prix_inscription']) : null;
$idTerrain = isset($data['id_terrain']) ? intval($data['id_terrain']) : null;
$statut = trim($data['statut'] ?? 'planifie');
$description = trim($data['description'] ?? '');
$regles = trim($data['regles'] ?? '');

// Validation
if (empty($nomTournoi) || empty($dateDebut) || empty($dateFin) || $nbEquipes < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs requis doivent être remplis']);
    exit;
}

if (!in_array($statut, ['planifie', 'en_cours', 'termine', 'annule'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

if (strtotime($dateDebut) >= strtotime($dateFin)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La date de fin doit être après la date de début']);
    exit;
}

if ($idTerrain) {
    // Check that terrain exists and permissions
    $stmt = $pdo->prepare("SELECT id_terrain, id_responsable FROM terrain WHERE id_terrain = ?");
    $stmt->execute([$idTerrain]);
    $terrain = $stmt->fetch();

    if (!$terrain) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Terrain introuvable']);
        exit;
    }

    if ($_SESSION['user_role'] === 'responsable' && $terrain['id_responsable'] !== $_SESSION['user_email']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => "Vous n'avez pas la permission d'utiliser ce terrain"]);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // Attempt with optional columns if they exist
    $sqlWithAll = "INSERT INTO tournoi (nom_t, categorie, date_debut, date_fin, size, description, statut, id_terrain, email_organisateur, prix_inscription, regles)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sqlWithAll);
    try {
        $stmt->execute([
            $nomTournoi,
            $typeTournoi,
            $dateDebut,
            $dateFin,
            $nbEquipes,
            $description,
            $statut,
            $idTerrain,
            $_SESSION['user_email'] ?? null,
            $prixInscription,
            $regles ?: null
        ]);
    } catch (PDOException $e1) {
        if ($e1->getCode() === '42S22' || stripos($e1->getMessage(), 'Unknown column') !== false) {
            try {
                $stmt2 = $pdo->prepare("INSERT INTO tournoi (nom_t, categorie, date_debut, date_fin, size, description, statut, id_terrain, prix_inscription, regles)
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->execute([
                    $nomTournoi,
                    $typeTournoi,
                    $dateDebut,
                    $dateFin,
                    $nbEquipes,
                    $description,
                    $statut,
                    $idTerrain,
                    $prixInscription,
                    $regles ?: null
                ]);
            } catch (PDOException $e2) {
                if ($e2->getCode() === '42S22' || stripos($e2->getMessage(), 'Unknown column') !== false) {
                    $stmt3 = $pdo->prepare("INSERT INTO tournoi (nom_t, categorie, date_debut, date_fin, size, description, statut, id_terrain)
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt3->execute([
                        $nomTournoi,
                        $typeTournoi,
                        $dateDebut,
                        $dateFin,
                        $nbEquipes,
                        $description,
                        $statut,
                        $idTerrain
                    ]);
                } else {
                    throw $e2;
                }
            }
        } else {
            throw $e1;
        }
    }

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Tournoi créé avec succès']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error add_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création du tournoi'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Unexpected error add_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création du tournoi'
    ]);
}

