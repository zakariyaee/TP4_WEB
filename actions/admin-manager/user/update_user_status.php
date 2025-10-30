<?php
/**
 * Update user status
 * 
 * Updates only the user status field for better performance.
 * Admin only access.
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOnly();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$newStatus = trim($data['statut_compte'] ?? '');

if (empty($email) || empty($newStatus)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

if (!in_array($newStatus, ['actif', 'suspendu'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT email FROM Utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Update only the status field
    $stmt = $pdo->prepare("UPDATE Utilisateur SET statut_compte = ? WHERE email = ?");
    $stmt->execute([$newStatus, $email]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
} catch (PDOException $e) {
    error_log('Error update_user_status: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating user status']);
} catch (Exception $e) {
    error_log('Unexpected error update_user_status: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating user status']);
}
?>
