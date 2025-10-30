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

require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$nomTournoi = trim($data['nom_tournoi'] ?? '');
$typeTournoi = trim($data['type_tournoi'] ?? '');
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
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

// Default value if not provided
if ($typeTournoi === '') {
    $typeTournoi = 'Open';
}

if (!in_array($statut, ['planifie', 'en_cours', 'termine', 'annule'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

if (strtotime($dateDebut) >= strtotime($dateFin)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
    exit;
}

if ($idTerrain) {
    // Check that terrain exists and permissions
    $stmt = $pdo->prepare("SELECT id_terrain, id_responsable FROM terrain WHERE id_terrain = ?");
    $stmt->execute([$idTerrain]);
    $terrain = $stmt->fetch();
    
    if (!$terrain) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Terrain not found']);
        exit;
    }
    
    if ($_SESSION['user_role'] === 'responsable' && $terrain['id_responsable'] !== $_SESSION['user_email']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to use this terrain']);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // Attempt with email_organisateur and prix_inscription if columns present
    $sqlWithAll = "INSERT INTO tournoi (nom_t, categorie, date_debut, date_fin, size, description, statut, id_terrain, email_organisateur, prix_inscription)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
            $prixInscription
        ]);
    } catch (PDOException $e1) {
        if ($e1->getCode() === '42S22' || stripos($e1->getMessage(), 'Unknown column') !== false) {
            // Retry without email_organisateur/prix_inscription based on availability
            try {
                $stmt2 = $pdo->prepare("INSERT INTO tournoi (nom_t, categorie, date_debut, date_fin, size, description, statut, id_terrain, prix_inscription)
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt2->execute([
                    $nomTournoi, $typeTournoi, $dateDebut, $dateFin, $nbEquipes, $description, $statut, $idTerrain, $prixInscription
                ]);
            } catch (PDOException $e2) {
                if ($e2->getCode() === '42S22' || stripos($e2->getMessage(), 'Unknown column') !== false) {
                    $stmt3 = $pdo->prepare("INSERT INTO tournoi (nom_t, categorie, date_debut, date_fin, size, description, statut, id_terrain)
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt3->execute([
                        $nomTournoi, $typeTournoi, $dateDebut, $dateFin, $nbEquipes, $description, $statut, $idTerrain
                    ]);
                } else { throw $e2; }
            }
        } else { throw $e1; }
    }

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Tournament created successfully']);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error add_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating tournament'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Unexpected error add_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating tournament'
    ]);
}
?>
