<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id_tournoi = intval($data['id_tournoi'] ?? 0);
$id_equipe = intval($data['id_equipe'] ?? 0);

if ($id_tournoi <= 0 || $id_equipe <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID du tournoi et de l\'équipe requis']);
    exit;
}

try {
    // Vérifier les permissions sur le tournoi
    $stmt = $pdo->prepare("
        SELECT t.*, tr.id_responsable 
        FROM tournoi t
        LEFT JOIN terrain tr ON t.id_terrain = tr.id_terrain
        WHERE t.id_tournoi = ?
    ");
    $stmt->execute([$id_tournoi]);
    $tournoi = $stmt->fetch();
    
    if (!$tournoi) {
        echo json_encode(['success' => false, 'message' => 'Tournoi introuvable']);
        exit;
    }
    
    if ($_SESSION['user_role'] === 'responsable') {
        if ($tournoi['id_terrain'] && $tournoi['id_responsable'] !== $_SESSION['user_email']) {
            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas permission de gérer ce tournoi']);
            exit;
        }
    }
    
    // Vérifier que l'équipe est inscrite
    $stmt = $pdo->prepare("SELECT id FROM tournoi_equipe WHERE id_tournoi = ? AND id_equipe = ?");
    $stmt->execute([$id_tournoi, $id_equipe]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cette équipe n\'est pas inscrite à ce tournoi']);
        exit;
    }
    
    // Vérifier s'il y a des matchs programmés pour cette équipe
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM match_tournoi 
        WHERE id_tournoi = ? 
        AND (id_equipe1 = ? OR id_equipe2 = ?)
        AND statut != 'termine'
    ");
    $stmt->execute([$id_tournoi, $id_equipe, $id_equipe]);
    $hasMatches = $stmt->fetch();
    
    if ($hasMatches['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Impossible de retirer cette équipe car elle a des matchs programmés']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM tournoi_equipe WHERE id_tournoi = ? AND id_equipe = ?");
    $stmt->execute([$id_tournoi, $id_equipe]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Équipe retirée avec succès']);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur remove_equipe_tournoi: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors du retrait de l\'équipe']);
}
?>
