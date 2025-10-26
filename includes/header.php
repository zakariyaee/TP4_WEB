<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'TerrainBook - Réservez votre terrain'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'terrain-green': '#28a745',
                        'terrain-dark-green': '#2c5530',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- Header -->
    <header class="bg-white shadow-lg px-4 md:px-8 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-2">
            <div class="w-10 h-10 bg-terrain-green rounded-lg flex items-center justify-center text-white text-xl font-bold">+</div>
            <div>
                <h1 class="text-2xl text-terrain-green font-bold">TerrainBook</h1>
                <p class="text-xs text-gray-600">Réservez votre terrain</p>
            </div>
        </div>
        
        <nav class="flex flex-wrap gap-4 md:gap-8 items-center">
            <a href="index.php" class="flex items-center gap-2 text-gray-800 font-medium hover:text-terrain-green transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                Accueil
            </a>
            <a href="reservations.php" class="flex items-center gap-2 text-gray-800 font-medium hover:text-terrain-green transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                </svg>
                Mes Réservations
            </a>
            <a href="disponibilite.php" class="flex items-center gap-2 text-gray-800 font-medium hover:text-terrain-green transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                Disponibilité
            </a>
            <a href="invitations.php" class="flex items-center gap-2 text-gray-800 font-medium hover:text-terrain-green transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                </svg>
                Invitations
                <span class="bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold ml-2">3</span>
            </a>
            <a href="tournois.php" class="flex items-center gap-2 text-gray-800 font-medium hover:text-terrain-green transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Tournois
            </a>
        </nav>
        
        <div class="flex items-center gap-4">
            <span>Joueur</span>
            <span class="text-gray-600 text-sm">player@terrainbook.com</span>
            <button class="flex items-center gap-2 bg-transparent border-none text-gray-600 cursor-pointer text-sm hover:text-gray-800">
                <span class="w-4 h-4">↗</span>
                Déconnexion
            </button>
        </div>
    </header>
