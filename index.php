<?php
include 'actions/admin-manager/data_index.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TerrainBook - Réservez votre terrain de football</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .smooth-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .smooth-hover:hover {
            transform: translateY(-2px);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 via-white to-green-50/20">
    <!-- Header minimaliste -->
    <header class="fixed w-full z-50 bg-white/80 backdrop-blur-xl border-b border-gray-100/50">
        <nav class="container mx-auto px-8 py-3.5">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700 rounded-xl flex items-center justify-center shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white" class="w-6 h-6">
                            <rect x="3" y="4" width="18" height="16" rx="2" ry="2" stroke="white" stroke-width="2" />
                            <line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="2" />
                            <circle cx="12" cy="12" r="1.5" fill="white" />
                        </svg>
                    </div>
                    <span class="text-lg font-semibold text-gray-900 tracking-tight">TerrainBook</span>
                </div>

                <div class="flex items-center gap-7">
                    <a href="#terrains" class="text-sm font-medium text-gray-600 hover:text-green-700 transition-colors">Terrains</a>
                    <a href="#features" class="text-sm font-medium text-gray-600 hover:text-green-700 transition-colors">Avantages</a>

                    <div class="h-4 w-px bg-gray-200"></div>
                    <a href="views/auth/login.php" class="text-sm font-medium text-gray-700 hover:text-green-700 transition-colors">Connexion</a>
                    <a href="views/auth/register.php" class="bg-gradient-to-r from-emerald-600 to-green-700 text-white text-sm font-medium px-5 py-2 rounded-lg hover:shadow-md smooth-hover">
                        Inscription
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section élégant -->
    <section class="pt-28 pb-20 overflow-hidden bg-gradient-to-br from-green-200 via-white to-green-200/100">
        <div class="container mx-auto px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="max-w-xl">
                    <div class="inline-flex items-center gap-2 bg-green-50 border border-green-100 text-green-700 px-3.5 py-1.5 rounded-full text-xs font-semibold mb-6">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Plateforme N°1 au Maroc
                    </div>

                    <h1 class="text-5xl font-bold mb-5 leading-[1.15] text-gray-900">
                        Réservez votre <span class="bg-gradient-to-r from-emerald-600 to-green-700 bg-clip-text text-transparent">terrain</span> en un instant
                    </h1>

                    <p class="text-base text-gray-600 leading-relaxed mb-8">
                        Accédez à <?php echo number_format($totalTerrains); ?> terrains de qualité, réservez en temps réel et gérez vos équipes simplement. La solution complète pour vos matchs de football.
                    </p>

                    <div class="flex items-center gap-3 mb-12">
                        <a href="views/auth/register.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-green-700 text-white px-6 py-3 rounded-lg font-semibold text-sm hover:shadow-lg smooth-hover">
                            Commencer gratuitement
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                        <a href="#terrains" class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold text-sm hover:border-gray-300 smooth-hover">
                            Voir les terrains
                        </a>
                    </div>

                    <!-- Stats raffinées -->
                    <div class="grid grid-cols-2 gap-6 pt-8 border-t border-gray-100">
                        <div>
                            <div class="text-3xl font-bold text-gray-900 mb-0.5"><?php echo number_format($totalTerrains); ?><?php if ($totalTerrains >= 100) echo '+'; ?></div>
                            <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Terrains</div>
                            <div class="text-xs text-gray-400 mt-1"><?php echo $totalTerrainsDisponibles; ?> disponibles</div>
                        </div>
                        <div class="border-l border-gray-100 pl-6">
                            <div class="text-3xl font-bold text-gray-900 mb-0.5"><?php echo $totalUsers >= 1000 ? number_format($totalUsers / 1000, 1) . 'K+' : number_format($totalUsers) . '+'; ?></div>
                            <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Utilisateurs</div>
                            <div class="text-xs text-gray-400 mt-1">Joueurs actifs</div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -inset-4 bg-gradient-to-r from-green-100 to-emerald-100 rounded-3xl blur-2xl opacity-30"></div>
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1459865264687-595d652de67e?w=800"
                            alt="Terrain de football moderne"
                            class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section moderne -->
    <section id="features" class="py-20 bg-white/50">
        <div class="container mx-auto px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <div class="inline-block bg-green-50 border border-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold mb-4">
                    Nos avantages
                </div>
                <h2 class="text-4xl font-bold mb-4 text-gray-900">
                    Tout pour simplifier vos réservations
                </h2>
                <p class="text-gray-600 text-base">
                    Une plateforme pensée pour vous offrir la meilleure expérience
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                <div class="group bg-white border border-green-300 p-7 rounded-2xl smooth-hover hover:shadow-lg hover:border-green-100">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-600 to-green-700 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2.5 text-gray-900">Réservation instantanée</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Réservez en temps réel avec confirmation immédiate.
                    </p>
                </div>

                <div class="group bg-white border border-green-300 p-7 rounded-2xl smooth-hover hover:shadow-lg hover:border-blue-100">
                    <div class="w-11 h-11 bg-gradient-to-br from-yellow-600 to-yellow-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2.5 text-gray-900">Gestion d'équipe intuitive</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Créez et gérez vos équipes facilement. Invitez vos joueurs et organisez vos matchs.
                    </p>
                </div>

                <div class="group bg-white border border-green-300 p-7 rounded-2xl smooth-hover hover:shadow-lg hover:border-purple-100">
                    <div class="w-11 h-11 bg-gradient-to-br from-green-500 to-green-400  rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2.5 text-gray-900">Facturation automatique</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Recevez vos factures automatiquement par email.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Terrains Section premium -->
    <section id="terrains" class="py-20 bg-gradient-to-br from-green-200 via-white to-green-200/100">
        <div class="container mx-auto px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <div class="inline-block bg-green-50 border border-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold mb-4">
                    Nos terrains
                </div>
                <h2 class="text-4xl font-bold mb-4 text-gray-900">
                    Des terrains pour chaque besoin
                </h2>
                <p class="text-gray-600 text-base">
                    Choisissez le terrain adapté à votre match
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                <?php 
                $categoryImages = [
                    'Mini Foot' => 'https://images.pexels.com/photos/1171084/pexels-photo-1171084.jpeg',
                    'Terrain Moyen' => 'https://images.pexels.com/photos/186239/pexels-photo-186239.jpeg',
                    'Grand Terrain' => 'https://images.unsplash.com/photo-1489944440615-453fc2b6a9a9?w=800&h=400&fit=crop'
                ];
                $categoryColors = [
                    'Mini Foot' => 'text-blue-600',
                    'Terrain Moyen' => 'text-green-600',
                    'Grand Terrain' => 'text-purple-600'
                ];
                $featured = 'Terrain Moyen'; // Catégorie featured
                $index = 0;
                foreach ($categories as $categorie): 
                    $data = $terrainsByCategory[$categorie] ?? [
                        'disponibles' => 0,
                        'prix_min' => 0,
                        'prix_max' => 0,
                        'description' => '',
                        'dimensions' => '',
                        'capacite' => ''
                    ];
                    $isFeatured = $categorie === $featured;
                ?>
                <div class="group bg-white <?php echo $isFeatured ? 'border-2 border-green-200' : 'border border-gray-100'; ?> rounded-2xl overflow-hidden smooth-hover hover:shadow-xl relative">
                    <?php if ($isFeatured): ?>
                    <div class="absolute -top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-600 to-green-700"></div>
                    <?php endif; ?>
                    <div class="relative h-52 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($categoryImages[$categorie] ?? 'https://images.unsplash.com/photo-1459865264687-595d652de67e?w=800'); ?>"
                            alt="<?php echo htmlspecialchars($categorie); ?>"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/70 via-gray-900/20 to-transparent"></div>
                        <div class="absolute top-4 right-4">
                            <div class="bg-white/95 backdrop-blur-sm <?php echo $categoryColors[$categorie] ?? 'text-gray-600'; ?> px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm">
                                <?php echo $data['disponibles']; ?> <?php echo $data['disponibles'] <= 1 ? 'disponible' : 'disponibles'; ?>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-5">
                            <h3 class="text-2xl font-bold text-white mb-1"><?php echo htmlspecialchars($categorie); ?></h3>
                            <p class="text-sm text-gray-200 font-medium"><?php echo htmlspecialchars($data['description']); ?></p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-5 pb-5 border-b border-gray-100">
                            <div>
                                <div class="text-xs text-gray-500 font-medium mb-1">Dimensions</div>
                                <div class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($data['dimensions']); ?></div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 font-medium mb-1">Capacité</div>
                                <div class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($data['capacite']); ?></div>
                            </div>
                        </div>
                        <div class="mb-5">
                            <div class="text-xs text-gray-500 font-medium mb-1.5">Tarif horaire</div>
                            <div class="flex items-baseline gap-1">
                                <?php if ($data['prix_min'] > 0 && $data['prix_max'] > 0): ?>
                                    <?php if ($data['prix_min'] == $data['prix_max']): ?>
                                        <span class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-700 bg-clip-text text-transparent"><?php echo number_format($data['prix_min'], 0); ?>DH</span>
                                    <?php else: ?>
                                        <span class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-700 bg-clip-text text-transparent"><?php echo number_format($data['prix_min'], 0); ?>DH - <?php echo number_format($data['prix_max'], 0); ?>DH</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-700 bg-clip-text text-transparent">Sur demande</span>
                                <?php endif; ?>
                                <span class="text-xs text-gray-400">/heure</span>
                            </div>
                        </div>
                        <a href="views/auth/register.php" class="block w-full bg-gradient-to-r from-emerald-600 to-green-700 text-white text-center py-2.5 rounded-lg font-semibold text-sm hover:shadow-md transition-shadow">
                            Voir les terrains
                        </a>
                    </div>
                </div>
                <?php 
                $index++;
                endforeach; 
                ?>
            </div>
        </div>
    </section>

    <!-- CTA Section élégante -->
    <section class="py-20 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700"></div>
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        </div>
        <div class="container mx-auto px-8 text-center relative z-10">
            <h2 class="text-4xl font-bold text-white mb-4">
                Commencez dès maintenant
            </h2>
            <p class="text-lg text-green-50 mb-9 max-w-xl mx-auto">
                Rejoignez notre communauté de plus de <?php echo number_format($totalUsers); ?> joueurs passionnés
            </p>
            <a href="views/auth/register.php" class="inline-flex items-center gap-2 bg-white text-green-700 px-8 py-3.5 rounded-lg font-semibold hover:shadow-xl smooth-hover">
                Créer mon compte gratuitement
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>
    </section>

    <!-- Footer raffiné -->
    <footer class="bg-white py-12 border-t border-gray-100">
        <div class="container mx-auto px-6 lg:px-12">
            <div class="flex flex-col md:flex-row justify-between items-center md:items-start gap-10">

                <!-- Logo + description -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left max-w-md">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700 rounded-xl flex items-center justify-center shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white" class="w-6 h-6">
                                <rect x="3" y="4" width="18" height="16" rx="2" ry="2" stroke="white" stroke-width="2" />
                                <line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="2" />
                                <circle cx="12" cy="12" r="1.5" fill="white" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">TerrainBook</span>
                    </div>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        La plateforme marocaine qui facilite la réservation de terrains de football pour tous les passionnés.
                    </p>
                </div>

                <!-- Liens + réseaux sociaux -->
                <div class="flex flex-col items-center md:items-end text-center md:text-right gap-5">

                    <!-- Liens essentiels -->
                    <div class="flex flex-wrap justify-center md:justify-end gap-6">
                        <a href="#terrains" class="text-sm text-gray-600 hover:text-emerald-600 transition-colors font-medium">Terrains</a>
                        <a href="#features" class="text-sm text-gray-600 hover:text-emerald-600 transition-colors font-medium">Avantages</a>
                        <a href="views/auth/login.php" class="text-sm text-gray-600 hover:text-emerald-600 transition-colors font-medium">Connexion</a>
                    </div>

                    <!-- Réseaux sociaux -->
                    <div class="flex justify-center md:justify-start gap-3">
                        <a href="#" class="w-9 h-9 bg-gray-100 rounded-xl flex items-center justify-center text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12S0 5.446 0 12.073c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </a>
                        <a href="#" class="w-9 h-9 bg-gray-100 rounded-xl flex items-center justify-center text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bas de page -->
            <div class="text-center border-t border-gray-200 pt-6 mt-8">
                <p class="text-sm text-gray-500 font-medium">© 2025 TerrainBook. Tous droits réservés.</p>
            </div>
        </div>
    </footer>


</body>

</html>