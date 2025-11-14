<?php
/**
 * AJAX endpoint to fetch refreshed tournament data for players.
 */

require_once '../../../config/database.php';
require_once '../../../check_auth.php';
require_once '../../../includes/player/tournament_helpers.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $playerEmail = $_SESSION['user_email'] ?? null;
    $data = getPlayerTournamentData($pdo, $playerEmail);

    // Create a hash of the data to detect changes
    // This is more reliable than timestamps for change detection
    $dataHash = md5(json_encode([
        'tournaments' => $data['allTournaments'],
        'grouped' => $data['grouped'],
        'counts' => $data['counts']
    ]));
    
    // Get actual modification timestamp
    // Check both tournaments and tournament teams for changes
    $lastUpdateSql = "SELECT GREATEST(
        COALESCE((SELECT MAX(UNIX_TIMESTAMP(NOW())) FROM tournoi LIMIT 1), 0),
        COALESCE((SELECT MAX(UNIX_TIMESTAMP(NOW())) FROM tournoi_equipe LIMIT 1), 0),
        COALESCE((SELECT MAX(UNIX_TIMESTAMP(NOW())) FROM demande_tournoi LIMIT 1), 0)
    ) as last_update";
    
    try {
        $lastUpdateStmt = $pdo->query($lastUpdateSql);
        $lastUpdateRow = $lastUpdateStmt->fetch(PDO::FETCH_ASSOC);
        $lastUpdate = $lastUpdateRow['last_update'] ?? time();
    } catch (PDOException $e) {
        // If query fails, use current timestamp
        $lastUpdate = time();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'dataVersion' => $data['dataVersion'],
        'grouped' => $data['grouped'],
        'counts' => $data['counts'],
        'tournaments' => $data['allTournaments'],
        'playerHasTeams' => !empty($data['playerTeams']),
        'last_update' => $lastUpdate,
        'data_hash' => $dataHash,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('Player get_tournaments error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Impossible de récupérer les tournois pour le moment.',
    ]);
}

