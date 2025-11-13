<?php
/**
 * Newsletter dashboard bootstrap endpoint.
 *
 * Returns stats, campaigns and promotions datasets.
 *
 * @package actions/admin-manager/newsletter
 */

require_once '../../../config/database.php';
require_once '../../../check_auth.php';
require_once __DIR__ . '/newsletter_service.php';

checkAdminOrRespo();
header('Content-Type: application/json');

try {
    ensureNewsletterSchema($pdo);
    updateExpiredPromotions($pdo);

    $promotionsPage = isset($_GET['promotions_page']) ? (int) $_GET['promotions_page'] : 1;
    $promotionsLimit = isset($_GET['promotions_limit']) ? (int) $_GET['promotions_limit'] : 3;
    $promotionsPage = max(1, $promotionsPage);
    $promotionsLimit = max(1, min(50, $promotionsLimit));

    $stats = getNewsletterStats($pdo);
    $campaigns = getNewsletterCampaigns($pdo);
    $promotions = getNewsletterPromotions($pdo, $promotionsPage, $promotionsLimit);

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'campaigns' => $campaigns,
        'promotions' => $promotions,
    ]);
} catch (PDOException $e) {
    error_log('Erreur newsletter/get_dashboard: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Erreur lors du chargement du tableau de bord: " . $e->getMessage(),
    ]);
} catch (Throwable $e) {
    error_log('Erreur inattendue newsletter/get_dashboard: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Erreur inattendue lors du chargement du tableau de bord: " . $e->getMessage(),
    ]);
}

