<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

$id_tournoi = $_GET['id_tournoi'] ?? '';
$search = $_GET['search'] ?? '';

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
        if ($tournoi['id_terrain'] && $tournoi['id_responsable'] && $tournoi['id_responsable'] !== $_SESSION['user_email']) {
            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas permission de voir ce tournoi']);
            exit;
        }
    }
    
    // Récupérer les équipes disponibles (non inscrites)
    $sql = "SELECT e.*, u.nom as createur_nom, u.prenom as createur_prenom
            FROM equipe e
            LEFT JOIN utilisateur u ON e.email_equipe = u.email
            WHERE e.id_equipe NOT IN (
                SELECT id_equipe FROM tournoi_equipe WHERE id_tournoi = ?
            )";
    
    $params = [$id_tournoi];
    
    if (!empty($search)) {
        $sql .= " AND (e.nom_equipe LIKE ? OR e.email_equipe LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY e.nom_equipe ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $equipes = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'equipes' => $equipes, 'count' => count($equipes)]);
    
} catch (PDOException $e) {
    error_log("Erreur get_equipes_disponibles: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des équipes']);
}
?>
