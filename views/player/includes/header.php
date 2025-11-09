<header class="bg-white shadow-sm sticky top-0 z-50">
    <nav class="container mx-auto px-6 py-3">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <a href="accueil.php" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700 rounded-xl flex items-center justify-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white" class="w-6 h-6">
                        <rect x="3" y="4" width="18" height="16" rx="2" ry="2" stroke="white" stroke-width="2" />
                        <line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="2" />
                        <circle cx="12" cy="12" r="1.5" fill="white" />
                    </svg>
                </div>
                <div>
                    <span class="text-xl font-bold text-gray-900 block">TerrainBook</span>
                    
                </div>
            </a>

            <!-- Navigation -->
            <div class="hidden md:flex items-center gap-8">
                <a href="stades.php" class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-emerald-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Accueil
                </a>

                <a href="my-reservations.php" class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-emerald-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Mes Réservations
                </a>

                <a href="disponibilite.php" class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-emerald-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Disponibilité
                </a>

                <a href="invitations.php" class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-emerald-600 transition-colors relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Invitations
                   
                </a>

                <a href="tournaments.php" class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-emerald-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Tournois
                </a>
            </div>

            <!-- User menu -->
            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-3 px-4 py-2 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-gradient-to-br from-emerald-600 to-green-700 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                    </div>
                </div>
                
                <a href="../../actions/auth/logout.php" 
                   class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-red-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>
</header>