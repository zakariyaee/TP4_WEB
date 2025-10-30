<?php
/**
 * Edit user
 * 
 * Updates user information including role changes.
 * Manages related records in Joueur table based on role changes.
 * Admin only access.
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOnly();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$originalEmail = trim($data['originalEmail'] ?? '');
$nom = trim($data['nom'] ?? '');
$prenom = trim($data['prenom'] ?? '');
$userRole = trim($data['role'] ?? '');
$accountStatus = trim($data['statut_compte'] ?? 'actif');
$userPassword = $data['password'] ?? '';

if (empty($originalEmail) || empty($nom) || empty($prenom) || empty($userRole)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate email format (even if it doesn't change)
if (!filter_var($originalEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

if (!in_array($userRole, ['admin','responsable','joueur'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

try {
    // Get current user role
    $stmt = $pdo->prepare("SELECT role FROM Utilisateur WHERE email = ?");
    $stmt->execute([$originalEmail]);
    $currentUser = $stmt->fetch();
    
    if (!$currentUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $oldRole = $currentUser['role'];
    
    $pdo->beginTransaction();

    // Update password if provided
    if (!empty($userPassword)) {
        if (strlen($userPassword) < 6) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password too short']);
            exit;
        }
        $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE Utilisateur SET nom = ?, prenom = ?, role = ?, statut_compte = ?, mot_de_passe = ? WHERE email = ?");
        $stmt->execute([$nom, $prenom, $userRole, $accountStatus, $hashedPassword, $originalEmail]);
    } else {
        $stmt = $pdo->prepare("UPDATE Utilisateur SET nom = ?, prenom = ?, role = ?, statut_compte = ? WHERE email = ?");
        $stmt->execute([$nom, $prenom, $userRole, $accountStatus, $originalEmail]);
    }

    // Manage related tables based on role change
    if ($oldRole !== $userRole) {
        // Remove from old table if necessary
        if ($oldRole === 'joueur') {
            try {
                $stmt = $pdo->prepare("DELETE FROM Joueur WHERE email = ?");
                $stmt->execute([$originalEmail]);
            } catch (PDOException $e) {
                // Ignore if table or record doesn't exist
                error_log("Note: Unable to delete from Joueur: " . $e->getMessage());
            }
        }
        
        // Add to new table if necessary
        if ($userRole === 'joueur') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO Joueur (email, statut) VALUES (?, 'disponible')");
            $stmt->execute([$originalEmail]);
        }
    } else {
        // If role hasn't changed, ensure record exists
        if ($userRole === 'joueur') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO Joueur (email, statut) VALUES (?, 'disponible')");
            $stmt->execute([$originalEmail]);
        }
    }

    $pdo->commit();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Error edit_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating user']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Unexpected error edit_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating user']);
}
?>