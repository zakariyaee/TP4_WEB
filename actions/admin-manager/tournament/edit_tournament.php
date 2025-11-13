<?php
/**
 * Edit tournament
 * 
 * Updates tournament information with validation and permission checks.
 * Handles flexible column schema (prix_inscription, regles).
 * Admin and Responsable access (responsable can only edit their own tournaments).
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

$idTournoi = intval($data['id_tournoi'] ?? 0);
$nomTournoi = trim($data['nom_tournoi'] ?? '');
$typeTournoi = isset($data['type_tournoi']) && $data['type_tournoi'] !== '' ? trim($data['type_tournoi']) : null;
$dateDebut = $data['date_debut'] ?? '';
$dateFin = $data['date_fin'] ?? '';
$nbEquipes = intval($data['nb_equipes'] ?? 0);
$idTerrain = $data['id_terrain'] ?? null;
$statut = trim($data['statut'] ?? 'planifie');
$description = trim($data['description'] ?? '');
$regles = trim($data['regles'] ?? '');
$prixInscription = isset($data['prix_inscription']) ? floatval($data['prix_inscription']) : null;

if ($idTournoi <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de tournoi invalide']);
    exit;
}

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

try {
    // Check that tournament exists and permissions
    $stmt = $pdo->prepare(
        "SELECT t.*, tr.id_responsable 
        FROM tournoi t
        LEFT JOIN terrain tr ON t.id_terrain = tr.id_terrain
        WHERE t.id_tournoi = ?"
    );
    $stmt->execute([$idTournoi]);
    $tournoi = $stmt->fetch();
    
    if (!$tournoi) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournoi introuvable']);
        exit;
    }
    
    if ($_SESSION['user_role'] === 'responsable') {
        if ($tournoi['id_terrain'] && $tournoi['id_responsable'] !== $_SESSION['user_email']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Vous n'avez pas la permission de modifier ce tournoi"]);
            exit;
        }
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
    
    $pdo->beginTransaction();
    
    // Build UPDATE query with available columns
    $updateFields = ['nom_t = ?', 'categorie = ?', 'date_debut = ?', 'date_fin = ?', 'size = ?', 'id_terrain = ?', 'statut = ?', 'description = ?'];
    $updateValues = [$nomTournoi, $typeTournoi, $dateDebut, $dateFin, $nbEquipes, $idTerrain, $statut, $description];
    
    // Add prix_inscription if provided
    if ($prixInscription !== null) {
        $updateFields[] = 'prix_inscription = ?';
        $updateValues[] = $prixInscription;
    }
    
    // Add regles if provided and column exists
    if (!empty($regles)) {
        $updateFields[] = 'regles = ?';
        $updateValues[] = $regles;
    }
    
    $updateValues[] = $idTournoi;
    
    $sql = "UPDATE tournoi SET " . implode(', ', $updateFields) . " WHERE id_tournoi = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);
    } catch (PDOException $e) {
        // If error on optional columns, retry without
        if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
            $basicFields = ['nom_t = ?', 'categorie = ?', 'date_debut = ?', 'date_fin = ?', 'size = ?', 'id_terrain = ?', 'statut = ?', 'description = ?'];
            $basicValues = [$nomTournoi, $typeTournoi, $dateDebut, $dateFin, $nbEquipes, $idTerrain, $statut, $description, $idTournoi];
            $sql = "UPDATE tournoi SET " . implode(', ', $basicFields) . " WHERE id_tournoi = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($basicValues);
        } else {
            throw $e;
        }
    }
    
    $pdo->commit();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Tournoi mis à jour avec succès']);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error edit_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du tournoi']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Unexpected error edit_tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du tournoi']);
}
?>
