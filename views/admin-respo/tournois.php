<?php
/**
 * Tournaments Management View
 * 
 * Displays tournament management interface with CRUD operations.
 * Admin and Responsable access (responsable can only manage their own tournaments).
 *
 * @package views/admin-respo
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
        <?php include '../../includes/sidebar.php'; ?>

        <main class="flex-1 ml-64 p-8 transition-all duration-300">
            <!-- Header Section -->
            <div class="mb-8 animate-slide-in">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-blue-600 mb-2">
                            Gestion des Tournois
                        </h1>
                        <p class="text-gray-600 text-lg">Organisez et gérez les tournois de football</p>
                    </div>
                    <button onclick="openAddModal()" class="group relative bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105 transform">
                        Créer un tournoi
                    </button>
                </div>
            </div>

            <!-- Filters and Search Section -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6 mb-6 animate-slide-in">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            Rechercher
                        </label>
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Nom du tournoi..." 
                                class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            Statut
                        </label>
                        <select id="filterStatut" class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="">Tous les statuts</option>
                            <option value="planifie">Planifié</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Terminé</option>
                            <option value="annule">Annulé</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            Catégorie
                        </label>
                        <select id="filterType" class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="">Toutes les catégories</option>
                            <option value="Senior">Senior</option>
                            <option value="U-21">U-21</option>
                            <option value="Open">Open</option>
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

            <!-- Loader -->
            <div id="loader" class="hidden flex justify-center items-center py-16">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-emerald-200 border-t-emerald-600"></div>
            </div>
        </main>
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
                            Catégorie *
                        </label>
                        <select id="type_tournoi" name="type_tournoi" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="">Sélectionner...</option>
                            <option value="Senior">Senior</option>
                            <option value="U-21">U-21</option>
                            <option value="Open">Open</option>
                        </select>
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
                            Statut *
                        </label>
                        <select id="statut" name="statut" required 
                            class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                            <option value="planifie">Planifié</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Terminé</option>
                            <option value="annule">Annulé</option>
                        </select>
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

    <script src="../../assets/js/tournois.js"></script>

</body>

</html>
