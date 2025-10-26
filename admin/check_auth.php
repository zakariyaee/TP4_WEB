<?php

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

// Vérifier si l'utilisateur est admin
if ($_SESSION['user_role'] !== 'admin') {
    // Rediriger vers le dashboard approprié
    switch ($_SESSION['user_role']) {
        case 'responsable':
            header('Location: ../responsable/dashboard.php');
            break;
        case 'joueur':
            header('Location: ../joueur/accueil.php');
            break;
        default:
            header('Location: ../auth/login.php');
    }
    exit;
}
?>