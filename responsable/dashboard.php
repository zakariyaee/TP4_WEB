<?php
require_once '../config/database.php';
require_once 'check_auth.php';


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

// Vérifier le rôle (admin ou responsable uniquement)
if (!in_array($_SESSION['user_role'], ['admin', 'responsable'])) {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Inclure la sidebar -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Contenu principal avec marge à gauche pour la sidebar -->
    <main class="ml-64 p-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Tableau de bord</h1>
            <p class="text-gray-600 mt-1">Vue d'ensemble de votre plateforme</p>
        </div>
        
        <!-- Votre contenu ici -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Vos cartes statistiques, etc. -->
        </div>
    </main>
</body>
</html>