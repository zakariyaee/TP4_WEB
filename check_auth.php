<?php
/**
 * Fichier de vérification d'authentification commun
 * À inclure au début de chaque page protégée
 */

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function checkAuth() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../auth/login.php');
        exit;
    }
}

/**
 * Vérifie si l'utilisateur a le rôle requis
 * @param array $allowed_roles - Tableau des rôles autorisés
 */
function checkRole($allowed_roles = []) {
    checkAuth();
    
    if (!isset($_SESSION['user_role'])) {
        header('Location: ../auth/login.php');
        exit;
    }
    
    // Si des rôles spécifiques sont requis
    if (!empty($allowed_roles) && !in_array($_SESSION['user_role'], $allowed_roles)) {
        redirectToDashboard($_SESSION['user_role']);
    }
}

/**
 * Redirige vers le dashboard approprié selon le rôle
 * @param string $role - Le rôle de l'utilisateur
 */
function redirectToDashboard($role) {
    switch ($role) {
        case 'admin':
        case 'responsable':
            header('Location: views/admin-manager/dashboard.php');
            break;
        case 'joueur':
            header('Location: views/player/accueil.php');
            break;
        default:
            header('Location: ../auth/login.php');
    }
    exit;
}

/**
 * Vérifie si l'utilisateur est admin ou responsable
 */
function checkAdminOrRespo() {
    checkRole(['admin', 'responsable']);
}

/**
 * Vérifie si l'utilisateur est uniquement admin
 */
function checkAdminOnly() {
    checkRole(['admin']);
}

/**
 * Vérifie si l'utilisateur est joueur
 */
function checkJoueur() {
    checkRole(['joueur']);
}