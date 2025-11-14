<?php
/**
 * Tournaments Management View
 * 
 * Displays tournament management interface with CRUD operations.
 * Admin and Responsable access (responsable can only manage their own tournaments).
 *
 * @package views/admin-manager
 * @return void
 */

require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();

$pageTitle = "Gestion des Tournois";
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
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 via-blue-50/30 to-emerald-50/20 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../../includes/sidebar.php'; ?>
        <main class="flex-1 ml-64 p-8 transition-all duration-300">
            <!-- Header Section -->
            <div class="mb-8 animate-slide-in">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Gestion des Tournois</h1>
                        <p class="text-gray-600 text-sm mt-1">Organisez et gérez les tournois de football</p>
                    </div>
                    <button onclick="openAddModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors shadow text-sm">
                        <i class="fas fa-plus"></i>
                        <span>Ajouter un tournoi</span>
                    </button>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="bg-white rounded-lg shadow-sm mb-4">
                <div class="flex border-b border-gray-200">
                    <button id="tab-tournaments" onclick="switchTab('tournaments')" class="tab-button active px-6 py-4 font-semibold text-emerald-600 border-b-2 border-emerald-600 transition-colors">
                        <i class="fas fa-trophy mr-2"></i>
                        Tournois
                    </button>
                    <button id="tab-requests" onclick="switchTab('requests')" class="tab-button px-6 py-4 font-semibold text-gray-600 hover:text-emerald-600 transition-colors">
                        <i class="fas fa-inbox mr-2"></i>
                        Demandes en attente
                        <span id="requestsBadge" class="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full hidden">0</span>
                    </button>
                </div>
            </div>

            <!-- Filters and Search Section (for tournaments) -->
            <div id="filtersSection" class="bg-white rounded-lg shadow-sm p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Rechercher
                        </label>
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Nom du tournoi..." 
                                class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Statut
                        </label>
                        <select id="filterStatut" class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="">Tous les statuts</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Terminé</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Filters and Search Section (for requests) -->
            <div id="filtersRequestsSection" class="bg-white rounded-lg shadow-sm p-4 mb-4 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Rechercher
                        </label>
                        <div class="relative">
                            <input type="text" id="searchRequestsInput" placeholder="Nom du tournoi, organisateur..." 
                                class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Statut
                        </label>
                        <select id="filterRequestsStatut" class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente">En attente</option>
                            <option value="approuvee">Approuvée</option>
                            <option value="rejetee">Rejetée</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notification Message -->
            <div id="notification" class="hidden fixed top-6 right-6 px-6 py-4 rounded-xl shadow-2xl z-50 backdrop-blur-sm animate-slide-in"></div>

            <!-- Tournaments List -->
            <div id="tournoisContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in">
                <!-- Tournaments will be loaded here via AJAX -->
            </div>

            <!-- Requests List -->
            <div id="requestsContainer" class="hidden space-y-4 animate-fade-in">
                <!-- Requests will be loaded here via AJAX -->
            </div>

            <!-- Loader -->
            <div id="loader" class="hidden flex justify-center items-center py-16">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-emerald-200 border-t-emerald-600"></div>
            </div>
        </main>
    </div>

    <!-- Inscrits (Teams/Matches) Modal -->
    <div id="inscritsModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-slide-in">
        <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[92vh] overflow-hidden flex flex-col">
            <div class="sticky top-0 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white px-8 py-6 flex justify-between items-center shadow-lg">
                <h2 class="text-2xl font-bold">Inscrits du tournoi</h2>
                <button onclick="closeRegisteredTeamsModal()" class="text-white/80 hover:text-white hover:bg-white/20 px-4 py-2 rounded-lg transition-all duration-200">×</button>
            </div>

            <div class="p-8 overflow-y-auto flex-1">
                <!-- Equipes content -->
                <div id="equipesSection">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm text-gray-600"><span id="equipesCount">0</span> équipe(s) inscrite(s)</div>
                        
                    </div>
                    <div class="hidden md:flex text-xs font-semibold text-gray-500 px-2 pb-2">
                        <div class="flex-1">Équipe</div>
                        <div class="w-40">Statut</div>
                        <div class="w-32 text-right">Actions</div>
                    </div>
                    <div id="equipesList" class="divide-y divide-gray-100"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Equipe Modal -->
    <div id="editEquipeModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-slide-in">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden flex flex-col">
            <div class="sticky top-0 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-bold">Modifier l'équipe</h3>
                <button onclick="closeEditTeamModal()" class="text-white/80 hover:text-white hover:bg-white/20 px-3 py-1.5 rounded-lg">×</button>
            </div>
            <form id="editEquipeForm" class="p-6 space-y-4">
                <input type="hidden" id="edit_id_equipe">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nom de l'équipe *</label>
                    <input id="edit_nom_equipe" type="text" required class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email *</label>
                    <input id="edit_email_equipe" type="email" required class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" id="editEquipeSubmit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold">Modifier</button>
                    <button type="button" onclick="closeEditTeamModal()" class="px-4 py-2 border-2 border-gray-300 rounded-lg">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Equipe Confirmation Modal -->
    <div id="deleteEquipeModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-slide-in">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 transform transition-all duration-300 scale-100">
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Confirmer la suppression</h3>
                <p class="text-gray-600 mb-8 leading-relaxed">Êtes-vous sûr de vouloir retirer cette équipe du tournoi ? Cette action est irréversible et ne peut pas être annulée.</p>
                <div class="flex gap-4">
                    <button onclick="confirmRemoveTeam()" id="confirmRemoveEquipeBtn" class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                        Supprimer
                    </button>
                    <button onclick="closeDeleteTeamModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-xl font-semibold transition-all duration-200">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Tournament Modal -->
    <div id="tournoiModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-slide-in">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-100">
            <div class="sticky top-0 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white px-8 py-6 flex justify-between items-center shadow-lg">
                <h2 id="modalTitle" class="text-2xl font-bold">Créer un tournoi</h2>
                <button onclick="closeModal()" class="text-white/80 hover:text-white hover:bg-white/20 px-4 py-2 rounded-lg transition-all duration-200">
                    ×
                </button>
            </div>

            <form id="tournoiForm" class="p-8 space-y-6 overflow-y-auto flex-1">
                <input type="hidden" id="tournoiId" name="id_tournoi">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nom du tournoi *
                        </label>
                        <input type="text" id="nom_tournoi" name="nom_tournoi" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Date de début *
                        </label>
                        <input type="date" id="date_debut" name="date_debut" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Date de fin *
                        </label>
                        <input type="date" id="date_fin" name="date_fin" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nombre d'équipes *
                        </label>
                        <input type="number" id="nb_equipes" name="nb_equipes" min="2" max="32" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Prix d'inscription (DH)
                        </label>
                        <input type="number" id="prix_inscription" name="prix_inscription" min="0" step="0.01" 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Terrain *
                        </label>
                        <select id="id_terrain" name="id_terrain" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="">Sélectionner un terrain...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Statut (calculé automatiquement)
                        </label>
                        <input type="text" id="statutDisplay" readonly
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl bg-gray-100 text-gray-700 cursor-not-allowed">
                        <input type="hidden" id="statut" name="statut" value="planifie">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea id="description" name="description" rows="3" 
                        class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Règles du tournoi
                    </label>
                    <textarea id="regles" name="regles" rows="4" 
                        class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white"></textarea>
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
                <p class="text-gray-600 mb-8 leading-relaxed">Êtes-vous sûr de vouloir supprimer ce tournoi ? Cette action est irréversible et ne peut pas être annulée.</p>
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

    <!-- Request Details Modal -->
    <div id="requestModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-slide-in">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-6 flex justify-between items-center shadow-lg">
                <h2 class="text-2xl font-bold">Détails de la demande</h2>
                <button onclick="closeRequestModal()" class="text-white/80 hover:text-white hover:bg-white/20 px-4 py-2 rounded-lg transition-all duration-200">×</button>
            </div>
            <div id="requestModalContent" class="p-8 overflow-y-auto flex-1">
                <!-- Request details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Universal Sync Manager - Load BEFORE other scripts -->
    <script src="../../assets/js/sync-manager.js"></script>
    <script src="../../assets/js/tournament.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/tournament.js'); ?>"></script>
    <script src="../../assets/js/tournament_requests.js?v=<?php echo time(); ?>"></script>

</body>

</html>
