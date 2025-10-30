<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
    $stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
        exit;
    }

    // Check account status
    if ($user['statut_compte'] !== 'actif') {
        echo json_encode(['success' => false, 'message' => 'Votre compte est ' . $user['statut_compte']]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['mot_de_passe'])) {
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
        exit;
    }

    // Set session variables
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nom'] . ' ' . $user['prenom'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;

    // Déterminer l'URL de redirection selon le rôle
    $redirectUrl = '';
    switch ($user['role']) {
        case 'admin':
            $redirectUrl = '../admin-manager/dashboard.php';
            break;
        case 'responsable':
            $redirectUrl = '../admin-manager/dashboard.php';
            break;
        case 'joueur':
            $redirectUrl = '../player/accueil.php';
            break;
        default:
            $redirectUrl = '../index.php';
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Connexion réussie! Redirection...',
        'redirect' => $redirectUrl,
        'user' => [
            'email' => $user['email'],
            'name' => $user['nom'] . ' ' . $user['prenom'],
            'role' => $user['role']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur login: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion. Veuillez réessayer.']);
}
?>