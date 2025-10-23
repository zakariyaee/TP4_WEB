<?php
$page_title = "TerrainBook - Accueil";
include 'header.php';
?>

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-green-600 to-green-800 relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"60\" height=\"60\" viewBox=\"0 0 60 60\"><g fill=\"%23ffffff\"><circle cx=\"30\" cy=\"30\" r=\"2\"/></g></svg>');"></div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 md:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="text-white">
                    <h1 class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
                        Réservez votre terrain de football en quelques clics
                    </h1>
                    <p class="text-xl mb-8 text-green-100">
                        Trouvez le terrain parfait, réservez instantanément et créez votre équipe pour jouer avec vos amis.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button class="bg-white text-green-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-colors">
                            Commencer maintenant
                        </button>
                        <button class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-green-600 transition-colors">
                            En savoir plus
                        </button>
                    </div>
                </div>
                
                <!-- Right Content - Image -->
                <div class="relative">
                    <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-2xl p-6 border border-white border-opacity-30">
                        <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-xl h-80 flex items-center justify-center">
                            <div class="text-center text-white">
                                <svg class="w-24 h-24 mx-auto mb-4 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                                <p class="text-lg font-semibold">Terrain de Football</p>
                                <p class="text-sm opacity-80">Image du terrain</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Section -->
        <div class="relative bg-green-700 py-16">
            <div class="max-w-7xl mx-auto px-4 md:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div class="text-white">
                        <div class="text-4xl md:text-5xl font-bold mb-2">150+</div>
                        <div class="text-lg text-green-200">Terrains</div>
                    </div>
                    <div class="text-white">
                        <div class="text-4xl md:text-5xl font-bold mb-2">5000+</div>
                        <div class="text-lg text-green-200">Joueurs</div>
                    </div>
                    <div class="text-white">
                        <div class="text-4xl md:text-5xl font-bold mb-2">200+</div>
                        <div class="text-lg text-green-200">Matchs/jour</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="bg-white py-20">
        <div class="max-w-7xl mx-auto px-4 md:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-16">
                Pourquoi choisir TerrainPro ?
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Réservation instantanée</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Réservez votre terrain en temps réel avec confirmation immédiate
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Gestion d'équipe</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Créez votre équipe, invitez vos joueurs et gérez vos matchs facilement
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Paiement sécurisé</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Transactions sécurisées et factures automatiques par email
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Field Categories Section -->
    <section class="bg-gray-50 py-20">
        <div class="max-w-7xl mx-auto px-4 md:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-4">
                Nos Terrains par Catégorie
            </h2>
            <p class="text-lg text-gray-600 text-center mb-16">
                Choisissez la taille qui correspond à vos besoins et réservez votre créneau idéal
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Mini Foot Card -->
                <div class="rounded-2xl overflow-hidden relative h-80 text-white transition-transform hover:-translate-y-1 bg-gradient-to-br from-blue-900 to-blue-600">
                    <div class="absolute top-4 right-4 bg-white bg-opacity-90 border border-blue-300 px-4 py-2 rounded-full text-sm font-medium text-gray-800">4 terrains</div>
                    <div class="p-8 h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Mini Foot</h3>
                            <p class="text-sm opacity-90 mb-6">Parfait pour les petits groupes</p>
                            <div class="flex flex-col gap-2 mb-6">
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-300">Dimensions</span>
                                    <span class="text-white font-medium">20x12m</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                    <span class="text-gray-300">Joueurs</span>
                                    <span class="text-white font-medium">5 vs 5</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-300">À partir de</span>
                                    <span class="text-white font-medium">30-40€/heure</span>
                                </div>
                            </div>
                        </div>
                        <button class="bg-terrain-green text-white border-none px-6 py-3 rounded-lg font-medium cursor-pointer flex items-center justify-center gap-2 hover:bg-green-600 transition-colors">
                            Voir les terrains
                            <span>→</span>
                        </button>
                    </div>
                </div>

                <!-- Medium Field Card -->
                <div class="rounded-2xl overflow-hidden relative h-80 text-white transition-transform hover:-translate-y-1 bg-gradient-to-br from-green-900 to-green-600">
                    <div class="absolute top-4 right-4 bg-white bg-opacity-90 border border-green-300 px-4 py-2 rounded-full text-sm font-medium text-gray-800">3 terrains</div>
                    <div class="p-8 h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Terrain Moyen</h3>
                            <p class="text-sm opacity-90 mb-6">Idéal pour les matchs amicaux</p>
                            <div class="flex flex-col gap-2 mb-6">
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-300">Dimensions</span>
                                    <span class="text-white font-medium">35x20m</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                    <span class="text-gray-300">Joueurs</span>
                                    <span class="text-white font-medium">7 vs 7</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-300">À partir de</span>
                                    <span class="text-white font-medium">50-70€/heure</span>
                                </div>
                            </div>
                        </div>
                        <button class="bg-terrain-green text-white border-none px-6 py-3 rounded-lg font-medium cursor-pointer flex items-center justify-center gap-2 hover:bg-green-600 transition-colors">
                            Voir les terrains
                            <span>→</span>
                        </button>
                    </div>
                </div>

                <!-- Large Field Card -->
                <div class="rounded-2xl overflow-hidden relative h-80 text-white transition-transform hover:-translate-y-1 bg-gradient-to-br from-purple-900 to-purple-600">
                    <div class="absolute top-4 right-4 bg-white bg-opacity-90 border border-purple-300 px-4 py-2 rounded-full text-sm font-medium text-gray-800">2 terrains</div>
                    <div class="p-8 h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Grand Terrain</h3>
                            <p class="text-sm opacity-90 mb-6">Pour les compétitions professionnelles</p>
                            <div class="flex flex-col gap-2 mb-6">
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-300">Dimensions</span>
                                    <span class="text-white font-medium">50x30m</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                    <span class="text-gray-300">Joueurs</span>
                                    <span class="text-white font-medium">11 vs 11</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-300">À partir de</span>
                                    <span class="text-white font-medium">80-120€/heure</span>
                                </div>
                            </div>
                        </div>
                        <button class="bg-terrain-green text-white border-none px-6 py-3 rounded-lg font-medium cursor-pointer flex items-center justify-center gap-2 hover:bg-green-600 transition-colors">
                            Voir les terrains
                            <span>→</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include 'footer.php'; ?>
