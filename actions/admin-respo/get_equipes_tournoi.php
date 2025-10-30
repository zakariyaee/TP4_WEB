<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

$id_tournoi = $_GET['id_tournoi'] ?? '';
if (empty($id_tournoi)) {
    echo json_encode(['success' => false, 'message' => 'ID du tournoi requis']);
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
            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas permission de voir ce tournoi']);
            exit;
        }
    }
    
    // Récupérer les équipes inscrites
    $sql = "SELECT e.*, te.date_inscription, te.statut_participation AS statut_inscription
            FROM equipe e
            INNER JOIN tournoi_equipe te ON e.id_equipe = te.id_equipe
            WHERE te.id_tournoi = ?
            ORDER BY te.date_inscription ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_tournoi]);
    $equipes = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'equipes' => $equipes, 'count' => count($equipes)]);
    
} catch (PDOException $e) {
    error_log("Erreur get_equipes_tournoi: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des équipes']);
}
?>
