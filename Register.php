<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Get full name and split it into Nom and Prenom
$fullName = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$password = $data['password'] ?? '';

// Validate input
if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}

// Split name into Nom and Prenom
$nameParts = explode(' ', $fullName, 2);
$nom = $nameParts[0];
$prenom = isset($nameParts[1]) ? $nameParts[1] : '';

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

// Validate password strength
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT Email FROM utilisateur WHERE Email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user (Role default is 'user')
    $stmt = $pdo->prepare("INSERT INTO utilisateur (Nom, Prenom, Email, Password, Num_Tele, Role, Date_Creation) VALUES (?, ?, ?, ?, ?, 'user', NOW())");
    $stmt->execute([$nom, $prenom, $email, $hashedPassword, $phone]);

    echo json_encode([
        'success' => true, 
        'message' => 'Compte créé avec succès! Vous pouvez maintenant vous connecter.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}