<?php
/**
 * Get single user by email
 * 
 * Retrieves user details from database by email address.
 * Admin only access.
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOnly();

header('Content-Type: application/json');

$userEmail = $_GET['email'] ?? '';
if (empty($userEmail)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email requis']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT email, nom, prenom, num_tele, role, statut_compte, DATE_FORMAT(date_creation, '%Y-%m-%d %H:%i:%s') AS date_creation FROM Utilisateur WHERE email = ?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
        exit;
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'user' => $user]);
} catch (PDOException $e) {
    error_log('Erreur get_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération de l'utilisateur"]);
} catch (Exception $e) {
    error_log('Erreur inattendue get_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération de l'utilisateur"]);
}
?>


