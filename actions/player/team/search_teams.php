<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

$playerEmail = $_SESSION['user_email'] ?? null;
$search = trim($_GET['search'] ?? '');
$limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 30;
$maxMembers = 15;

if (!$playerEmail) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}

try {
    $sql = "
        SELECT 
            e.id_equipe,
            e.nom_equipe,
            e.email_equipe,
            e.date_creation,
            (
                SELECT COUNT(*) 
                FROM equipe_joueur ej 
                WHERE ej.id_equipe = e.id_equipe
            ) AS membre_count
        FROM equipe e
        WHERE e.id_equipe NOT IN (
            SELECT id_equipe FROM equipe_joueur WHERE id_joueur = :email
        )
    ";
    $params = [':email' => $playerEmail];

    if ($search !== '') {
        $sql .= " AND (e.nom_equipe LIKE :search OR e.email_equipe LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    $sql .= " ORDER BY membre_count ASC, e.nom_equipe ASC LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        if ($key === ':limit') continue;
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'teams' => array_map(static function ($team) use ($maxMembers) {
            $team['membre_count'] = (int) ($team['membre_count'] ?? 0);
            $team['max_members'] = $maxMembers;
            $team['is_open'] = $team['membre_count'] < $maxMembers;
            return $team;
        }, $teams),
        'max_members' => $maxMembers,
    ]);
} catch (PDOException $e) {
    error_log('Player search teams error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche des équipes']);
}

