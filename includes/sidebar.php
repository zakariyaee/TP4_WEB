<?php
// includes/sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer le rôle de l'utilisateur
$userRole = $_SESSION['user_role'] ?? 'joueur';
$userName = $_SESSION['user_name'] ?? 'Utilisateur';
$userEmail = $_SESSION['user_email'] ?? '';

// Déterminer la page active
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<aside id="sidebar" class="w-64 bg-gradient-to-b from-slate-800 to-slate-900 text-white min-h-screen fixed left-0 top-0 shadow-2xl flex flex-col">
    <!-- Logo et Titre -->
    <div class="p-6 border-b border-slate-700">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 via-green-600 to-teal-600 rounded-xl flex items-center justify-center shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="w-6 h-6">
                    <rect x="3" y="4" width="18" height="16" rx="2" ry="2" stroke="white" stroke-width="2" />
                    <line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="2" />
                    <circle cx="12" cy="12" r="1.5" fill="white" />
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold">TerrainBook</h1>
                <p class="text-xs text-slate-400">Admin Global</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 overflow-y-auto">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 px-3">Navigation</p>

        <ul class="space-y-1">
            <!-- Tableau de bord -->
            <li>
                <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?php echo $currentPage === 'dashboard' ? 'bg-emerald-600 shadow-lg shadow-emerald-600/50' : 'hover:bg-slate-700/50'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="text-sm font-medium">Tableau de bord</span>
                </a>
            </li>

            <!-- Users (Admin only) -->
            <?php if ($userRole === 'admin'): ?>
                <li>
                    <a href="user.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?php echo $currentPage === 'user' ? 'bg-emerald-600 shadow-lg shadow-emerald-600/50' : 'hover:bg-slate-700/50'; ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="text-sm font-medium">Utilisateurs</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Terrains -->
            <li>
                <a href="stades.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?php echo $currentPage === 'stades' ? 'bg-emerald-600 shadow-lg shadow-emerald-600/50' : 'hover:bg-slate-700/50'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M3 7v1a3 3 0 003 3h12a3 3 0 003-3V7m-18 0l2-4h14l2 4M3 7h18" />
                    </svg>
                    <span class="text-sm font-medium">Terrains</span>
                </a>
            </li>
            <!-- Crenaux -->
            <li>
                <a href="slots.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?php echo $currentPage === 'slots' ? 'bg-emerald-600 shadow-lg shadow-emerald-600/50' : 'hover:bg-slate-700/50'; ?>">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 2m6-2A9 9 0 1112 3a9 9 0 019 9z" />
                    </svg>


                    <span class="text-sm font-medium">Creneaux</span>
                </a>
            </li>

            <!-- Réservations -->
            <li>
                <a href="reservations.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?php echo $currentPage === 'reservations' ? 'bg-emerald-600 shadow-lg shadow-emerald-600/50' : 'hover:bg-slate-700/50'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-medium">Réservations</span>
                </a>
            </li>

            <!-- Tournaments -->
            <li>
                <a href="tournament.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?php echo $currentPage === 'tournament' ? 'bg-emerald-600 shadow-lg shadow-emerald-600/50' : 'hover:bg-slate-700/50'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                    <span class="text-sm font-medium">Tournaments</span>
                </a>
            </li>

            <!-- Factures -->
            <li>
                <a href="factures.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?php echo $currentPage === 'factures' ? 'bg-emerald-600 shadow-lg shadow-emerald-600/50' : 'hover:bg-slate-700/50'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-sm font-medium">Factures</span>
                </a>
            </li>

            <!-- Newsletter (Admin seulement) -->
            <?php if ($userRole === 'admin'): ?>
                <li>
                    <a href="newsletter.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?php echo $currentPage === 'newsletter' ? 'bg-emerald-600 shadow-lg shadow-emerald-600/50' : 'hover:bg-slate-700/50'; ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="text-sm font-medium">Newsletter</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- User Profile en bas -->
    <div class="p-4 border-t border-slate-700 bg-slate-900/50">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate"><?php echo $userRole === 'admin' ? 'Admin' : 'Responsable'; ?></p>
                <p class="text-xs text-slate-400 truncate"><?php echo htmlspecialchars($userEmail); ?></p>
            </div>
        </div>

        <a href="../../actions/auth/logout.php" class="flex items-center justify-center gap-2 w-full px-3 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition-colors text-sm font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Déconnexion
        </a>
    </div>
</aside>

<style>
    /* Scrollbar personnalisée pour la sidebar */
    aside::-webkit-scrollbar {
        width: 6px;
    }

    aside::-webkit-scrollbar-track {
        background: rgba(30, 41, 59, 0.5);
        border-radius: 10px;
    }

    aside::-webkit-scrollbar-thumb {
        background: rgba(16, 185, 129, 0.5);
        border-radius: 10px;
    }

    aside::-webkit-scrollbar-thumb:hover {
        background: rgba(16, 185, 129, 0.8);
    }
</style>