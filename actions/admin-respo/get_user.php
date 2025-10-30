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

require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOnly();

header('Content-Type: application/json');

$userEmail = $_GET['email'] ?? '';
if (empty($userEmail)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT email, nom, prenom, role, statut_compte FROM Utilisateur WHERE email = ?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'user' => $user]);
} catch (PDOException $e) {
    error_log('Error get_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error retrieving user']);
} catch (Exception $e) {
    error_log('Unexpected error get_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error retrieving user']);
}
?>


