<?php
/**
 * Users Management View
 * 
 * Displays user management interface with CRUD operations.
 * Admin only access - redirects responsables to dashboard.
 *
 * @package views/admin-manager
 * @return void
 */

require_once '../../config/database.php';
require_once '../../check_auth.php';

// Check that user is admin only - redirect responsables
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

checkAdminOnly();

$pageTitle = "Gestion des Utilisateurs";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 via-blue-50/30 to-emerald-50/20 min-h-screen">
    <div class="flex">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="flex-1 ml-64 p-8 transition-all duration-300">
            <!-- Header Section -->
            <div class="mb-8 animate-slide-in">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Gestion des Utilisateurs</h1>
                        <p class="text-gray-600 text-sm mt-1">Administrez les comptes: admin, responsables, joueurs</p>
                    </div>
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <button onclick="openAddModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors shadow text-sm">
                            <i class="fas fa-plus"></i>
                            <span>Ajouter un utilisateur</span>   
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Rechercher
                        </label>
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Rechercher par nom, prénom ou email..." 
                                class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Rôle
                        </label>
                        <select id="filterRole" class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="">Tous les rôles</option>
                            <option value="admin">Admin</option>
                            <option value="responsable">Responsable</option>
                            <option value="joueur">Joueur</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Statut
                        </label>
                        <select id="filterStatut" class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="">Tous les statuts</option>
                            <option value="actif">Actif</option>
                            <option value="suspendu">Suspendu</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notification -->
            <div id="notification" class="hidden fixed top-6 right-6 px-6 py-4 rounded-xl shadow-2xl z-50 backdrop-blur-sm animate-slide-in"></div>

            <!-- Table Section -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden animate-slide-in">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="px-8 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Utilisateur
                                </th>
                                <th class="px-8 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-8 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Rôle
                                </th>
                                <th class="px-8 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-8 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody id="usersTable" class="bg-white divide-y divide-gray-100">
                        </tbody>
                    </table>
                </div>
                <div id="loader" class="hidden flex justify-center items-center py-16">
                    <div class="animate-spin rounded-full h-16 w-16 border-4 border-emerald-200 border-t-emerald-600"></div>
                </div>
                <div id="paginationContainer"></div>
            </div>

            <!-- Items per page selector -->
            <div class="mt-4 flex items-center justify-end gap-3">
                <label class="text-sm font-semibold text-gray-700">Éléments par page :</label>
                <select id="itemsPerPage" class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-white cursor-pointer">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </main>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-slide-in">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-100">
            <div class="sticky top-0 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white px-8 py-6 flex justify-between items-center shadow-lg">
                <h2 id="modalTitle" class="text-2xl font-bold">Ajouter un utilisateur</h2>
                <button onclick="closeModal()" class="text-white/80 hover:text-white hover:bg-white/20 px-4 py-2 rounded-lg transition-all duration-200">
                    ×
                </button>
            </div>

            <form id="userForm" class="p-8 space-y-6 overflow-y-auto flex-1">
                <input type="hidden" id="originalEmail" name="originalEmail">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nom *
                        </label>
                        <input type="text" id="nom" name="nom" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Prénom *
                        </label>
                        <input type="text" id="prenom" name="prenom" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Email *
                        </label>
                        <input type="email" id="email" name="email" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Rôle *
                        </label>
                        <select id="role" name="role" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="">Sélectionner...</option>
                            <option value="admin">Admin</option>
                            <option value="responsable">Responsable</option>
                            <option value="joueur">Joueur</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Statut *
                        </label>
                        <select id="statut_compte" name="statut_compte" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="actif">Actif</option>
                            <option value="suspendu">Suspendu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Mot de passe 
                            <span id="passwordHint" class="text-gray-400 text-xs font-normal">(au moins 6 caractères)</span>
                        </label>
                        <input type="password" id="password" name="password" 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                            placeholder="••••••">
                    </div>
                </div>

                <div class="flex gap-4 pt-6 border-t border-gray-200 mt-6">
                    <button type="submit" id="submitBtn" 
                        class="group flex-1 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-6 py-4 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                        Enregistrer
                    </button>
                    <button type="button" onclick="closeModal()" 
                        class="px-6 py-4 border-2 border-gray-300 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 font-semibold text-gray-700">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-slide-in">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 transform transition-all duration-300 scale-100">
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Confirmer la suppression</h3>
                <p class="text-gray-600 mb-8 leading-relaxed">Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible et ne peut pas être annulée.</p>
                <div class="flex gap-4">
                    <button onclick="confirmDelete()" 
                        class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                        Supprimer
                    </button>
                    <button onclick="closeDeleteModal()" 
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-xl font-semibold transition-all duration-200">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/user.js"></script>

</body>

</html>
