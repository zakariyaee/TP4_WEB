<?php

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

// Vérifier si l'utilisateur est joueur
if ($_SESSION['user_role'] !== 'joueur') {
    // Rediriger vers le dashboard approprié
    switch ($_SESSION['user_role']) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'responsable':
            header('Location: ../responsable/dashboard.php');
            break;
        default:
            header('Location: ../auth/login.php');
    }
    exit;
}
?>