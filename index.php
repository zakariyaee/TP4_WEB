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
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-lg font-semibold text-gray-900 tracking-tight">TerrainBook</span>
                </div>
                
                <div class="flex items-center gap-7">
                    <a href="#terrains" class="text-sm font-medium text-gray-600 hover:text-green-700 transition-colors">Terrains</a>
                    <a href="#features" class="text-sm font-medium text-gray-600 hover:text-green-700 transition-colors">Avantages</a>
                    
                    <div class="h-4 w-px bg-gray-200"></div>
                    <a href="auth/login.php" class="text-sm font-medium text-gray-700 hover:text-green-700 transition-colors">Connexion</a>
                    <a href="auth/register.php" class="bg-gradient-to-r from-emerald-600 to-green-700 text-white text-sm font-medium px-5 py-2 rounded-lg hover:shadow-md smooth-hover">
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
                        Accédez à plus de 150 terrains de qualité, réservez en temps réel et gérez vos équipes simplement. La solution complète pour vos matchs de football.
                    </p>
                    
                    <div class="flex items-center gap-3 mb-12">
                        <a href="auth/register.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-green-700 text-white px-6 py-3 rounded-lg font-semibold text-sm hover:shadow-lg smooth-hover">
                            Commencer gratuitement
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="#terrains" class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold text-sm hover:border-gray-300 smooth-hover">
                            Voir les terrains
                        </a>
                    </div>
                    
                    <!-- Stats raffinées -->
                    <div class="grid grid-cols-3 gap-6 pt-8 border-t border-gray-100">
                        <div>
                            <div class="text-3xl font-bold text-gray-900 mb-0.5">150+</div>
                            <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Terrains</div>
                        </div>
                        <div class="border-l border-gray-100 pl-6">
                            <div class="text-3xl font-bold text-gray-900 mb-0.5">5K+</div>
                            <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Utilisateurs</div>
                        </div>
                        <div class="border-l border-gray-100 pl-6">
                            <div class="text-3xl font-bold text-gray-900 mb-0.5">98%</div>
                            <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Satisfaction</div>
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
                <div class="group bg-white border border-gray-100 p-7 rounded-2xl smooth-hover hover:shadow-lg hover:border-green-100">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-600 to-green-700 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2.5 text-gray-900">Réservation instantanée</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Réservez en temps réel avec confirmation immédiate.
                    </p>
                </div>

                <div class="group bg-white border border-gray-100 p-7 rounded-2xl smooth-hover hover:shadow-lg hover:border-blue-100">
                    <div class="w-11 h-11 bg-gradient-to-br from-yellow-600 to-yellow-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2.5 text-gray-900">Gestion d'équipe intuitive</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Créez et gérez vos équipes facilement. Invitez vos joueurs et organisez vos matchs.
                    </p>
                </div>

                <div class="group bg-white border border-gray-100 p-7 rounded-2xl smooth-hover hover:shadow-lg hover:border-purple-100">
                    <div class="w-11 h-11 bg-gradient-to-br from-green-500 to-green-400  rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
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
    <section id="terrains" class="py-20 bg-gradient-to-br from-green-50 via-white to-green-50/100">
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
                <!-- Mini Foot -->
                <div class="group bg-white border border-gray-100 rounded-2xl overflow-hidden smooth-hover hover:shadow-xl">
                    <div class="relative h-52 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1624880357913-a8539238245b?w=800&h=400&fit=crop" 
                             alt="Mini Foot" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/70 via-gray-900/20 to-transparent"></div>
                        <div class="absolute top-4 right-4">
                            <div class="bg-white/95 backdrop-blur-sm text-blue-600 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm">
                                4 disponibles
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-5">
                            <h3 class="text-2xl font-bold text-white mb-1">Mini Foot</h3>
                            <p class="text-sm text-gray-200 font-medium">Idéal petits groupes</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-5 pb-5 border-b border-gray-100">
                            <div>
                                <div class="text-xs text-gray-500 font-medium mb-1">Dimensions</div>
                                <div class="text-base font-bold text-gray-900">20×12m</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 font-medium mb-1">Capacité</div>
                                <div class="text-base font-bold text-gray-900">5 vs 5</div>
                            </div>
                        </div>
                        <div class="mb-5">
                            <div class="text-xs text-gray-500 font-medium mb-1.5">Tarif horaire</div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-700 bg-clip-text text-transparent">30€ - 40€</span>
                                <span class="text-xs text-gray-400">/heure</span>
                            </div>
                        </div>
                        <a href="auth/register.php" class="block w-full bg-gradient-to-r from-emerald-600 to-green-700 text-white text-center py-2.5 rounded-lg font-semibold text-sm hover:shadow-md transition-shadow">
                            Voir les terrains
                        </a>
                    </div>
                </div>

                <!-- Terrain Moyen (Featured) -->
                <div class="group bg-white border-2 border-green-200 rounded-2xl overflow-hidden smooth-hover hover:shadow-xl relative">
                    <div class="absolute -top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-600 to-green-700"></div>
                    <div class="relative h-52 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1543326727-cf6c39e8f84c?w=800&h=400&fit=crop" 
                             alt="Terrain Moyen" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/70 via-gray-900/20 to-transparent"></div>
                        <div class="absolute top-4 right-4">
                            <div class="bg-white/95 backdrop-blur-sm text-green-600 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm">
                                3 disponibles
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-5">
                            <h3 class="text-2xl font-bold text-white mb-1">Terrain Moyen</h3>
                            <p class="text-sm text-gray-200 font-medium">Matchs amicaux</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-5 pb-5 border-b border-gray-100">
                            <div>
                                <div class="text-xs text-gray-500 font-medium mb-1">Dimensions</div>
                                <div class="text-base font-bold text-gray-900">35×20m</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 font-medium mb-1">Capacité</div>
                                <div class="text-base font-bold text-gray-900">7 vs 7</div>
                            </div>
                        </div>
                        <div class="mb-5">
                            <div class="text-xs text-gray-500 font-medium mb-1.5">Tarif horaire</div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-700 bg-clip-text text-transparent">50€ - 70€</span>
                                <span class="text-xs text-gray-400">/heure</span>
                            </div>
                        </div>
                        <a href="auth/register.php" class="block w-full bg-gradient-to-r from-emerald-600 to-green-700 text-white text-center py-2.5 rounded-lg font-semibold text-sm hover:shadow-md transition-shadow">
                            Voir les terrains
                        </a>
                    </div>
                </div>

                <!-- Grand Terrain -->
                <div class="group bg-white border border-gray-100 rounded-2xl overflow-hidden smooth-hover hover:shadow-xl">
                    <div class="relative h-52 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1489944440615-453fc2b6a9a9?w=800&h=400&fit=crop" 
                             alt="Grand Terrain" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/70 via-gray-900/20 to-transparent"></div>
                        <div class="absolute top-4 right-4">
                            <div class="bg-white/95 backdrop-blur-sm text-purple-600 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm">
                                2 disponibles
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-5">
                            <h3 class="text-2xl font-bold text-white mb-1">Grand Terrain</h3>
                            <p class="text-sm text-gray-200 font-medium">Compétitions pro</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-5 pb-5 border-b border-gray-100">
                            <div>
                                <div class="text-xs text-gray-500 font-medium mb-1">Dimensions</div>
                                <div class="text-base font-bold text-gray-900">50×30m</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 font-medium mb-1">Capacité</div>
                                <div class="text-base font-bold text-gray-900">11 vs 11</div>
                            </div>
                        </div>
                        <div class="mb-5">
                            <div class="text-xs text-gray-500 font-medium mb-1.5">Tarif horaire</div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-700 bg-clip-text text-transparent">80€ - 120€</span>
                                <span class="text-xs text-gray-400">/heure</span>
                            </div>
                        </div>
                        <a href="auth/register.php" class="block w-full bg-gradient-to-r from-emerald-600 to-green-700 text-white text-center py-2.5 rounded-lg font-semibold text-sm hover:shadow-md transition-shadow">
                            Voir les terrains
                        </a>
                    </div>
                </div>
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
                Rejoignez notre communauté de plus de 5000 joueurs passionnés
            </p>
            <a href="auth/register.php" class="inline-flex items-center gap-2 bg-white text-green-700 px-8 py-3.5 rounded-lg font-semibold hover:shadow-xl smooth-hover">
                Créer mon compte gratuitement
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </section>

    <!-- Footer raffiné -->
    <footer class="bg-gray-900 text-white py-14">
        <div class="container mx-auto px-8">
            <div class="grid md:grid-cols-4 gap-10 mb-10">
                <div>
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="w-9 h-9 bg-gradient-to-br from-emerald-600 to-green-700 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-lg font-semibold">TerrainBook</span>
                    </div>
                    <p class="text-sm text-gray-400 leading-relaxed">
                        La solution complète pour réserver vos terrains de football au Maroc.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold text-sm mb-4 text-white">Navigation</h4>
                    <ul class="space-y-2.5">
                        <li><a href="#terrains" class="text-sm text-gray-400 hover:text-green-400 transition-colors">Terrains</a></li>
                        <li><a href="#features" class="text-sm text-gray-400 hover:text-green-400 transition-colors">Avantages</a></li>
                        <li><a href="#apropos" class="text-sm text-gray-400 hover:text-green-400 transition-colors">À propos</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-sm mb-4 text-white">Support</h4>
                    <ul class="space-y-2.5">
                        <li><a href="#" class="text-sm text-gray-400 hover:text-green-400 transition-colors">Centre d'aide</a></li>
                        <li><a href="#" class="text-sm text-gray-400 hover:text-green-400 transition-colors">Contact</a></li>
                        <li><a href="#" class="text-sm text-gray-400 hover:text-green-400 transition-colors">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-sm mb-4 text-white">Légal</h4>
                    <ul class="space-y-2.5">
                        <li><a href="#" class="text-sm text-gray-400 hover:text-green-400 transition-colors">CGU</a></li>
                        <li><a href="#" class="text-sm text-gray-400 hover:text-green-400 transition-colors">Confidentialité</a></li>
                        <li><a href="#" class="text-sm text-gray-400 hover:text-green-400 transition-colors">Mentions légales</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-7">
                <p class="text-sm text-gray-400 text-center">© 2025 TerrainBook. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>