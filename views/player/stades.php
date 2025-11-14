<?php
require_once '../../config/database.php';

// Récupérer les terrains par catégorie (tous les statuts)
try {
    $categories = ['Mini Foot', 'Terrain Moyen', 'Grand Terrain'];
    $terrainsByCategory = [];

    foreach ($categories as $categorie) {
        $stmt = $pdo->prepare("
            SELECT t.*, CONCAT(u.nom, ' ', u.prenom) as responsable_nom
            FROM terrain t
            LEFT JOIN utilisateur u ON t.id_responsable = u.email
            WHERE t.categorie = :categorie
            ORDER BY 
                CASE t.disponibilite 
                    WHEN 'disponible' THEN 1 
                    WHEN 'indisponible' THEN 2 
                    WHEN 'maintenance' THEN 3 
                END,
                t.nom_te
        ");
        $stmt->execute([':categorie' => $categorie]);
        $terrainsByCategory[$categorie] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer toutes les villes (tous les statuts)
    $stmt = $pdo->query("SELECT DISTINCT ville FROM terrain ORDER BY ville");
    $villes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données: " . $e->getMessage());
    $terrainsByCategory = [];
    $villes = [];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terrains - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header dynamique selon l'état de connexion -->
    <?php if (isset($_SESSION['user_email']) && $_SESSION['user_role'] === 'joueur'): ?>
        <!-- Header pour utilisateur connecté -->
        <?php include 'includes/header.php'; ?>
    <?php else: ?>
        <!-- Header pour visiteur non connecté -->
        <header class="bg-white shadow-sm sticky top-0 z-50">
            <nav class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <a href="../../index.php" class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700 rounded-xl flex items-center justify-center shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white" class="w-6 h-6">
                                <rect x="3" y="4" width="18" height="16" rx="2" ry="2" stroke="white" stroke-width="2" />
                                <line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="2" />
                                <circle cx="12" cy="12" r="1.5" fill="white" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">TerrainBook</span>
                    </a>
                    <div class="flex items-center gap-4">
                        <a href="../auth/login.php" class="text-sm font-medium text-gray-700 hover:text-emerald-600 transition-colors">Connexion</a>
                        <a href="../auth/register.php" class="bg-gradient-to-r from-emerald-600 to-green-700 text-white text-sm font-medium px-5 py-2 rounded-lg hover:shadow-md transition-shadow">
                            Inscription
                        </a>
                    </div>
                </div>
            </nav>
        </header>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700 text-white py-16">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Découvrez nos terrains</h1>
            <p class="text-lg text-green-50 max-w-2xl mx-auto">
                Parcourez notre sélection de terrains de qualité dans différentes villes du Maroc
            </p>
        </div>
    </section>

    <!-- Filtres -->
    <section class="py-8 bg-white border-b border-gray-200">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <div class="flex flex-col md:flex-row gap-4 flex-1 w-full md:w-auto">
                    <div class="flex-1 md:w-64">
                        <input type="text" id="searchInput" placeholder="Rechercher un terrain..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div class="w-full md:w-48">
                        <select id="filterVille" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Toutes les villes</option>
                            <?php foreach ($villes as $ville): ?>
                                <option value="<?php echo htmlspecialchars($ville); ?>"><?php echo htmlspecialchars($ville); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-full md:w-48">
                        <select id="filterDisponibilite" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Tous les statuts</option>
                            <option value="disponible">Disponible</option>
                            <option value="indisponible">Indisponible</option>
                            <option value="maintenance">En maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap justify-center md:justify-start mt-4 md:mt-0">
                    <button id="btn-all" onclick="filterByCategory('')" class="px-4 py-2 rounded-lg border border-gray-300 hover:border-emerald-500 transition-colors">
                        Tous
                    </button>
                    <button id="btn-mini" onclick="filterByCategory('Mini Foot')" class="px-4 py-2 rounded-lg border border-gray-300  hover:border-emerald-500 transition-colors">
                        Mini Foot
                    </button>
                    <button id="btn-moyen" onclick="filterByCategory('Terrain Moyen')" class="px-4 py-2 rounded-lg border border-gray-300 hover:border-emerald-500 transition-colors">
                        Terrain Moyen
                    </button>
                    <button id="btn-grand" onclick="filterByCategory('Grand Terrain')" class="px-4 py-2 rounded-lg border border-gray-300 hover:border-emerald-500 transition-colors">
                        Grand Terrain
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Liste des terrains par catégorie -->
    <section class="py-12">
        <div class="container mx-auto px-6">
            <!-- Container pour les terrains chargés via AJAX -->
            <div id="terrains-container">
                <!-- Loader initial -->
                <div class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-4xl text-emerald-600 mb-4"></i>
                    <p class="text-gray-600 text-lg">Chargement des terrains...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700 text-white">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold mb-4">Prêt à réserver votre terrain ?</h2>
            <p class="text-lg text-green-50 mb-8 max-w-xl mx-auto">
                Créez un compte gratuitement et commencez à réserver dès maintenant
            </p>
            <a href="../auth/register.php" class="inline-flex items-center gap-2 bg-white text-emerald-700 px-8 py-3.5 rounded-lg font-semibold hover:shadow-xl transition-shadow">
                Créer mon compte
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-12 border-t border-gray-100">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700 rounded-xl flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white" class="w-6 h-6">
                            <rect x="3" y="4" width="18" height="16" rx="2" ry="2" stroke="white" stroke-width="2" />
                            <line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="2" />
                            <circle cx="12" cy="12" r="1.5" fill="white" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">TerrainBook</span>
                </div>
                <div class="flex gap-6">
                    <a href="../../index.php" class="text-sm text-gray-600 hover:text-emerald-600 transition-colors">Accueil</a>
                    <a href="../auth/login.php" class="text-sm text-gray-600 hover:text-emerald-600 transition-colors">Connexion</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Script pour savoir si l'utilisateur est connecté -->
    <script>
        document.body.dataset.userRole = '<?php echo $_SESSION['user_role'] ?? ''; ?>';
    </script>
    <script src="../../assets/js/player/terrains.js"></script>
</body>
</html>
