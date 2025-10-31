<?php
/**
 * Delete user
 * 
 * Deletes user and related records from Joueur and Responsable tables.
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
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userEmail = trim($data['email'] ?? '');

if (empty($userEmail)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email requis']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Delete simple dependencies if they exist
    try {
        $stmt = $pdo->prepare("DELETE FROM Joueur WHERE email = ?");
        $stmt->execute([$userEmail]);
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("DELETE FROM Responsable WHERE email = ?");
        $stmt->execute([$userEmail]);
    } catch (Exception $e) {}

    // Delete main user record
    $stmt = $pdo->prepare("DELETE FROM Utilisateur WHERE email = ?");
    $stmt->execute([$userEmail]);

    $pdo->commit();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Erreur delete_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la suppression de l'utilisateur"]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Erreur inattendue delete_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de la suppression de l'utilisateur"]);
}
?>


