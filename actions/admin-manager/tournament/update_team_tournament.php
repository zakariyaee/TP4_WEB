<?php
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

$id_tournoi = intval($data['id_tournoi'] ?? 0);
$id_equipe = intval($data['id_equipe'] ?? 0);
$statut = $data['statut_participation'] ?? '';

if ($id_tournoi <= 0 || $id_equipe <= 0 || !$statut) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants ou invalides']);
    exit;
}

// Sanitize allowed statut values
$allowed = ['confirmee','invitee'];
if (!in_array($statut, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valeur de statut invalide']);
    exit;
}

try {
    // Check responsable permissions on tournament
    $stmt = $pdo->prepare("SELECT t.*, tr.id_responsable FROM tournoi t LEFT JOIN terrain tr ON t.id_terrain = tr.id_terrain WHERE t.id_tournoi = ?");
    $stmt->execute([$id_tournoi]);
    $tournoi = $stmt->fetch();
    if (!$tournoi) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournoi introuvable']);
        exit;
    }
    if ($_SESSION['user_role'] === 'responsable') {
        if ($tournoi['id_terrain'] && $tournoi['id_responsable'] && $tournoi['id_responsable'] !== $_SESSION['user_email']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission refusée']);
            exit;
        }
    }

    // Ensure registration exists
    $stmt = $pdo->prepare("SELECT id FROM tournoi_equipe WHERE id_tournoi = ? AND id_equipe = ?");
    $stmt->execute([$id_tournoi, $id_equipe]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "L'équipe n'est pas inscrite à ce tournoi"]);
        exit;
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE tournoi_equipe SET statut_participation = ? WHERE id_tournoi = ? AND id_equipe = ?");
    $stmt->execute([$statut, $id_tournoi, $id_equipe]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Statut de participation mis à jour']);

} catch (PDOException $e) {
    error_log('Erreur update_team_tournament: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut de participation']);
}
?>


