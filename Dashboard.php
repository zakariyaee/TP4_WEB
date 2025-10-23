<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TerrainBook - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="bg-emerald-500 rounded-lg p-2 mr-3">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C11.5 2 11 2.19 10.59 2.59L10 3.17L9.41 2.59C9 2.19 8.5 2 8 2H5C3.89 2 3 2.89 3 4V8C3 9.1 3.89 10 5 10H6.5C7.08 10 7.65 9.79 8.09 9.42C9.1 11.55 10.9 13.07 13 13.72V16H11C9.89 16 9 16.89 9 18V20H7V22H17V20H15V18C15 16.89 14.11 16 13 16H11V13.72C13.1 13.07 14.9 11.55 15.91 9.42C16.35 9.79 16.92 10 17.5 10H19C20.11 10 21 9.1 21 8V4C21 2.89 20.11 2 19 2H16C15.5 2 15 2.19 14.59 2.59L14 3.17L13.41 2.59C13 2.19 12.5 2 12 2M5 4H8L9 5L8 6H5V4M16 4H19V6H16L15 5L16 4M6.5 8H7V8.5C7 9.88 7.39 11.16 8.03 12.27C7.5 11.94 7 11.5 6.5 11V8M17.5 8V11C17 11.5 16.5 11.94 15.97 12.27C16.61 11.16 17 9.88 17 8.5V8H17.5Z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-800">TerrainBook</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Bonjour, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Tableau de bord</h1>
            <p class="text-gray-600 mb-6">Bienvenue sur votre espace personnel!</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-emerald-50 rounded-lg p-6 border-l-4 border-emerald-500">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Profil</h3>
                    <p class="text-gray-600 text-sm">Gérez vos informations personnelles</p>
                </div>
                
                <div class="bg-blue-50 rounded-lg p-6 border-l-4 border-blue-500">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Terrains</h3>
                    <p class="text-gray-600 text-sm">Consultez vos terrains enregistrés</p>
                </div>
                
                <div class="bg-purple-50 rounded-lg p-6 border-l-4 border-purple-500">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Paramètres</h3>
                    <p class="text-gray-600 text-sm">Configurez votre compte</p>
                </div>
            </div>

            <div class="mt-8 bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Informations du compte</h2>
                <div class="space-y-2">
                    <p class="text-gray-700"><strong>Nom:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p class="text-gray-700"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>