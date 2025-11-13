<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

$playerEmail = $_SESSION['user_email'] ?? null;
if (!$playerEmail) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}

try {
    $baseQuery = "
        SELECT 
            e.id_equipe,
            e.nom_equipe,
            e.email_equipe,
            ej.role_equipe,
            ej.date_adhesion,
            e.date_creation,
            (
                SELECT COUNT(*) 
                FROM equipe_joueur ej2 
                WHERE ej2.id_equipe = e.id_equipe
            ) AS membre_count,
            (
                SELECT COUNT(DISTINCT te.id_tournoi) 
                FROM tournoi_equipe te 
                WHERE te.id_equipe = e.id_equipe
            ) AS tournoi_count,
            (
                SELECT COUNT(DISTINCT te2.id_tournoi)
                FROM tournoi_equipe te2
                INNER JOIN tournoi t2 ON t2.id_tournoi = te2.id_tournoi
                WHERE te2.id_equipe = e.id_equipe
                AND (t2.date_fin IS NULL OR t2.date_fin >= CURRENT_DATE())
            ) AS tournoi_avenir_count
        FROM equipe e
        INNER JOIN equipe_joueur ej ON ej.id_equipe = e.id_equipe
        WHERE ej.id_joueur = :email
        ORDER BY 
            CASE WHEN LOWER(ej.role_equipe) = 'capitaine' THEN 0 ELSE 1 END,
            e.nom_equipe ASC
    ";

    $stmt = $pdo->prepare($baseQuery);
    $stmt->execute([':email' => $playerEmail]);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle optional columns (date_creation)
    if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
        try {
            $fallbackQuery = "
                SELECT 
                    e.id_equipe,
                    e.nom_equipe,
                    e.email_equipe,
                    ej.role_equipe,
                    ej.date_adhesion,
                    NULL AS date_creation,
                    (
                        SELECT COUNT(*) 
                        FROM equipe_joueur ej2 
                        WHERE ej2.id_equipe = e.id_equipe
                    ) AS membre_count,
                    (
                        SELECT COUNT(DISTINCT te.id_tournoi) 
                        FROM tournoi_equipe te 
                        WHERE te.id_equipe = e.id_equipe
                    ) AS tournoi_count,
                    (
                        SELECT COUNT(DISTINCT te2.id_tournoi)
                        FROM tournoi_equipe te2
                        INNER JOIN tournoi t2 ON t2.id_tournoi = te2.id_tournoi
                        WHERE te2.id_equipe = e.id_equipe
                        AND (t2.date_fin IS NULL OR t2.date_fin >= CURRENT_DATE())
                    ) AS tournoi_avenir_count
                FROM equipe e
                INNER JOIN equipe_joueur ej ON ej.id_equipe = e.id_equipe
                WHERE ej.id_joueur = :email
                ORDER BY 
                    CASE WHEN LOWER(ej.role_equipe) = 'capitaine' THEN 0 ELSE 1 END,
                    e.nom_equipe ASC
            ";
            $stmt = $pdo->prepare($fallbackQuery);
            $stmt->execute([':email' => $playerEmail]);
            $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $fallbackError) {
            error_log('Player get teams fallback error: ' . $fallbackError->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des équipes']);
            exit;
        }
    } else {
        error_log('Player get teams error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des équipes']);
        exit;
    }
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'teams' => $teams,
    'count' => count($teams),
]);

