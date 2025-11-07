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
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
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
                    <a href="views/auth/login.php" class="text-sm font-medium text-gray-700 hover:text-emerald-600 transition-colors">Connexion</a>
                    <a href="views/auth/register.php" class="bg-gradient-to-r from-emerald-600 to-green-700 text-white text-sm font-medium px-5 py-2 rounded-lg hover:shadow-md transition-shadow">
                        Inscription
                    </a>
                </div>
            </div>
        </nav>
    </header>

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
            <?php 
            $categoryTitles = [
                'Mini Foot' => 'Mini Foot',
                'Terrain Moyen' => 'Terrains Moyens',
                'Grand Terrain' => 'Grands Terrains'
            ];
            $categoryColors = [
                'Mini Foot' => 'bg-blue-100 text-blue-800',
                'Terrain Moyen' => 'bg-green-100 text-green-800',
                'Grand Terrain' => 'bg-purple-100 text-purple-800'
            ];
            
            foreach ($categories as $categorie): 
                $terrains = $terrainsByCategory[$categorie] ?? [];
                if (empty($terrains)) continue;
            ?>
            <div class="mb-12 category-section" data-category="<?php echo htmlspecialchars($categorie); ?>">
                <div class="flex items-center gap-4 mb-6">
                    <h2 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($categoryTitles[$categorie]); ?></h2>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo $categoryColors[$categorie]; ?>">
                        <?php echo count($terrains); ?> terrain<?php echo count($terrains) > 1 ? 's' : ''; ?>
                    </span>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($terrains as $terrain): ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow terrain-card <?php echo $terrain['disponibilite'] !== 'disponible' ? 'opacity-75' : ''; ?>" 
                         data-name="<?php echo strtolower(htmlspecialchars($terrain['nom_te'])); ?>"
                         data-ville="<?php echo strtolower(htmlspecialchars($terrain['ville'])); ?>"
                         data-category="<?php echo htmlspecialchars($terrain['categorie']); ?>"
                         data-disponibilite="<?php echo htmlspecialchars($terrain['disponibilite']); ?>">
                        <div class="relative h-48 bg-gradient-to-br from-emerald-400 to-teal-600">
                            <?php if (!empty($terrain['image'])): ?>
                                <img src="../../assets/images/terrains/<?php echo htmlspecialchars($terrain['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($terrain['nom_te']); ?>" 
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-futbol text-white text-6xl opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <div class="absolute top-3 right-3">
                                <?php
                                $disponibilite = $terrain['disponibilite'];
                                $badgeClasses = [
                                    'disponible' => 'bg-green-100 text-green-800',
                                    'indisponible' => 'bg-red-100 text-red-800',
                                    'maintenance' => 'bg-yellow-100 text-yellow-800'
                                ];
                                $badgeLabels = [
                                    'disponible' => 'Disponible',
                                    'indisponible' => 'Indisponible',
                                    'maintenance' => 'En maintenance'
                                ];
                                $badgeClass = $badgeClasses[$disponibilite] ?? 'bg-gray-100 text-gray-800';
                                $badgeLabel = $badgeLabels[$disponibilite] ?? 'Inconnu';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/95 backdrop-blur-sm <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($badgeLabel); ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-3"><?php echo htmlspecialchars($terrain['nom_te']); ?></h3>
                            <div class="space-y-2 mb-4 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-city text-emerald-600 w-4"></i>
                                    <span><?php echo htmlspecialchars($terrain['ville']); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-emerald-600 w-4"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($terrain['localisation']); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-layer-group text-emerald-600 w-4"></i>
                                    <span><?php echo htmlspecialchars($terrain['type']); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-expand-arrows-alt text-emerald-600 w-4"></i>
                                    <span><?php echo htmlspecialchars($terrain['taille']); ?></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                <div class="text-2xl font-bold <?php echo $terrain['disponibilite'] === 'disponible' ? 'text-emerald-600' : 'text-gray-400'; ?>">
                                    <?php echo number_format($terrain['prix_heure'], 0); ?> DH
                                    <span class="text-sm text-gray-500 font-normal">/heure</span>
                                </div>
                                <?php if ($terrain['disponibilite'] === 'disponible'): ?>
                                    <a href="views/auth/register.php" 
                                       class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-semibold">
                                        Réserver
                                    </a>
                                <?php else: ?>
                                    <span class="px-4 py-2 bg-gray-200 text-gray-500 rounded-lg text-sm font-semibold cursor-not-allowed">
                                        <?php echo $terrain['disponibilite'] === 'maintenance' ? 'En maintenance' : 'Indisponible'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Message si aucun terrain -->
            <div id="noResults" class="hidden text-center py-12">
                <i class="fas fa-search text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">Aucun terrain trouvé</p>
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
            <a href="views/auth/register.php" class="inline-flex items-center gap-2 bg-white text-emerald-700 px-8 py-3.5 rounded-lg font-semibold hover:shadow-xl transition-shadow">
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
                    <a href="index.php" class="text-sm text-gray-600 hover:text-emerald-600 transition-colors">Accueil</a>
                    <a href="views/auth/login.php" class="text-sm text-gray-600 hover:text-emerald-600 transition-colors">Connexion</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        let currentCategory = '<?php echo isset($_GET['categorie']) ? htmlspecialchars($_GET['categorie']) : ''; ?>';
        
        // Initialiser le filtre de catégorie au chargement
        document.addEventListener('DOMContentLoaded', function() {
            // Activer le bon bouton selon la catégorie
            const buttonMap = {
                '': 'btn-all',
                'Mini Foot': 'btn-mini',
                'Terrain Moyen': 'btn-moyen',
                'Grand Terrain': 'btn-grand'
            };
            
            document.querySelectorAll('button[onclick^="filterByCategory"]').forEach(btn => {
                btn.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600');
                btn.classList.add('border-gray-300');
            });
            
            if (currentCategory && buttonMap[currentCategory]) {
                const activeBtn = document.getElementById(buttonMap[currentCategory]);
                if (activeBtn) {
                    activeBtn.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
                    activeBtn.classList.remove('border-gray-300');
                }
            } else {
                // Par défaut, activer "Tous"
                document.getElementById('btn-all').classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
                document.getElementById('btn-all').classList.remove('border-gray-300');
            }
            
            applyFilters();
        });
        
        function filterByCategory(category) {
            currentCategory = category;
            applyFilters();
            
            // Mettre à jour les boutons actifs
            document.querySelectorAll('button[onclick^="filterByCategory"]').forEach(btn => {
                btn.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600');
                btn.classList.add('border-gray-300');
            });
            event.target.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
            event.target.classList.remove('border-gray-300');
        }
        
        function applyFilters() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const ville = document.getElementById('filterVille').value.toLowerCase();
            const disponibilite = document.getElementById('filterDisponibilite').value.toLowerCase();
            const cards = document.querySelectorAll('.terrain-card');
            const categories = document.querySelectorAll('.category-section');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const name = card.dataset.name;
                const cardVille = card.dataset.ville;
                const cardCategory = card.dataset.category;
                const cardDisponibilite = card.dataset.disponibilite.toLowerCase();
                
                const matchesSearch = !search || name.includes(search);
                const matchesVille = !ville || cardVille === ville;
                const matchesCategory = !currentCategory || cardCategory === currentCategory;
                const matchesDisponibilite = !disponibilite || cardDisponibilite === disponibilite;
                
                if (matchesSearch && matchesVille && matchesCategory && matchesDisponibilite) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Afficher/masquer les sections de catégories
            categories.forEach(section => {
                const sectionCards = section.querySelectorAll('.terrain-card[style="display: block"], .terrain-card:not([style*="display: none"])');
                const hasVisible = Array.from(sectionCards).some(card => card.style.display !== 'none');
                section.style.display = hasVisible ? 'block' : 'none';
            });
            
            // Afficher le message "Aucun résultat"
            document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
        }
        
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('filterVille').addEventListener('change', applyFilters);
        document.getElementById('filterDisponibilite').addEventListener('change', applyFilters);
    </script>
</body>
</html>

