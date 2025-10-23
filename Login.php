<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['Password'])) {
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
        exit;
    }

    // Set session variables
    $_SESSION['user_id'] = $user['IDUtilisateur'];
    $_SESSION['user_name'] = $user['Nom'] . ' ' . $user['Prenom'];
    $_SESSION['user_email'] = $user['Email'];
    $_SESSION['user_role'] = $user['Role'];
    $_SESSION['logged_in'] = true;

    echo json_encode([
        'success' => true, 
        'message' => 'Connexion réussie! Redirection...',
        'user' => [
            'id' => $user['IDUtilisateur'],
            'name' => $user['Nom'] . ' ' . $user['Prenom'],
            'email' => $user['Email'],
            'role' => $user['Role']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}