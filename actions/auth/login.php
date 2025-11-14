<?php
// Activer l'affichage des erreurs pour debug (à retirer en production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // IMPORTANT: Ne pas afficher les erreurs dans la sortie
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Fonction pour envoyer une réponse JSON propre
function sendJsonResponse($success, $message, $data = []) {
    // Nettoyer tout buffer de sortie
    if (ob_get_level()) {
        ob_clean();
    }
    
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Méthode non autorisée');
    }

    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Données JSON invalides');
    }

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $recaptcha_token = $data['recaptcha_token'] ?? '';

    // ========================================
    // VÉRIFICATION reCAPTCHA v3
    // ========================================
    if (empty($recaptcha_token)) {
        sendJsonResponse(false, 'Vérification anti-bot requise');
    }

    // Votre clé secrète reCAPTCHA v3
    $recaptcha_secret = '6LeiRAwsAAAAALxC--kbNXN2tMCJiJPMyH-uooBQ';
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

    $recaptcha_data = [
        'secret' => $recaptcha_secret,
        'response' => $recaptcha_token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($recaptcha_data),
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $verify = @file_get_contents($recaptcha_url, false, $context);
    
    if ($verify === false) {
        error_log("reCAPTCHA: Impossible de contacter le serveur Google");
        // En cas d'erreur de connexion à Google, on continue (ne pas bloquer l'utilisateur)
    } else {
        $captcha_success = json_decode($verify, true);
        
        if (!$captcha_success['success'] || ($captcha_success['score'] ?? 0) < 0.5) {
            error_log("reCAPTCHA failed - Score: " . ($captcha_success['score'] ?? 'N/A'));
            sendJsonResponse(false, 'Vérification anti-bot échouée. Veuillez réessayer.');
        }
    }

    // ========================================
    // VALIDATION DES CHAMPS
    // ========================================
    if (empty($email) || empty($password)) {
        sendJsonResponse(false, 'Tous les champs sont requis');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(false, 'Email invalide');
    }

    // ========================================
    // VÉRIFICATION UTILISATEUR
    // ========================================
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendJsonResponse(false, 'Email ou mot de passe incorrect');
    }

    // Check account status
    if ($user['statut_compte'] !== 'actif') {
        sendJsonResponse(false, 'Votre compte est ' . $user['statut_compte']);
    }

    // Verify password
    if (!password_verify($password, $user['mot_de_passe'])) {
        sendJsonResponse(false, 'Email ou mot de passe incorrect');
    }

    // ========================================
    // CONNEXION RÉUSSIE
    // ========================================
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nom'] . ' ' . $user['prenom'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;

    // Déterminer l'URL de redirection
    $redirectUrl = '';
    
    if (isset($data['redirect']) && $data['redirect'] === 'reserver' && !empty($data['terrain_id'])) {
        if ($user['role'] === 'joueur') {
            $redirectUrl = '../player/reserver.php?id_terrain=' . intval($data['terrain_id']);
        } else {
            $redirectUrl = '../player/stades.php';
        }
    } else {
        switch ($user['role']) {
            case 'admin':
            case 'responsable':
                $redirectUrl = '../admin-manager/user.php';
                break;
            case 'joueur':
                $redirectUrl = '../player/my-reservations.php';
                break;
            default:
                $redirectUrl = '../../index.php';
        }
    }

    sendJsonResponse(true, 'Connexion réussie! Redirection...', [
        'redirect' => $redirectUrl,
        'user' => [
            'email' => $user['email'],
            'name' => $user['nom'] . ' ' . $user['prenom'],
            'role' => $user['role']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur login PDO: " . $e->getMessage());
    sendJsonResponse(false, 'Erreur de connexion à la base de données');
} catch (Exception $e) {
    error_log("Erreur login: " . $e->getMessage());
    sendJsonResponse(false, 'Une erreur est survenue. Veuillez réessayer.');
}
?>