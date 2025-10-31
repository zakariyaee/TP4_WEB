<?php
/**
 * Add new user
 * 
 * Creates a new user account with validation and role-based record creation.
 * Creates related record in Joueur table if role is 'joueur'.
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

$nom = trim($data['nom'] ?? '');
$prenom = trim($data['prenom'] ?? '');
$userEmail = trim($data['email'] ?? '');
$userRole = trim($data['role'] ?? '');
$accountStatus = trim($data['statut_compte'] ?? 'actif');
$userPassword = $data['password'] ?? '';

if (empty($nom) || empty($prenom) || empty($userEmail) || empty($userRole) || empty($userPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs requis doivent être remplis']);
    exit;
}

if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

if (!in_array($userRole, ['admin','responsable','joueur'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
    exit;
}

if (strlen($userPassword) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mot de passe trop court']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT email FROM Utilisateur WHERE email = ?");
    $stmt->execute([$userEmail]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email déjà utilisé']);
        exit;
    }

    $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    // Insert user record
    $stmt = $pdo->prepare("INSERT INTO Utilisateur (email, nom, prenom, mot_de_passe, role, statut_compte) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$userEmail, $nom, $prenom, $hashedPassword, $userRole, $accountStatus ?: 'actif']);

    // Create player record if role is joueur
    if ($userRole === 'joueur') {
        $stmt = $pdo->prepare("INSERT INTO Joueur (email, statut) VALUES (?, 'disponible')");
        $stmt->execute([$userEmail]);
    }

    // No additional record required for 'responsable' role

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté avec succès']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Erreur add_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de l'ajout de l'utilisateur"]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Erreur inattendue add_user: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de l'ajout de l'utilisateur"]);
}
?>
