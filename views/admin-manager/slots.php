<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

$pageTitle = "Gestion des Créneaux";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<link rel="stylesheet" href="../../assets/css/slots.css">

<body class="bg-gray-50">
    <div class="flex">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="flex-1 ml-64 p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Gestion des Créneaux</h1>
                        <p class="text-gray-600 text-sm mt-1">Visualisez et gérez tous les créneaux horaires par terrain</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="openAddModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors shadow text-sm">
                            <i class="fas fa-plus"></i>
                            <span>Ajouter un créneau</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <!-- Filtres AMÉLIORÉS -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <!-- Terrain -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Terrain</label>
            <input type="text" id="filterTerrain" list="filterTerrainList" placeholder="Rechercher un terrain..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            <input type="hidden" id="filterTerrainId">
        </div>
        
        <!-- NOUVEAU : Période -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Période</label>
            <select id="filterPeriode" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="current_week">Cette semaine</option>
                <option value="next_week">Semaine prochaine</option>
                <option value="current_month">Ce mois</option>
                <option value="custom">Personnalisée</option>
            </select>
        </div>
        
        <!-- NOUVEAU : Dates personnalisées (masqué par défaut) -->
        <div id="customDatesContainer" class="hidden">
            <label class="block text-xs font-medium text-gray-700 mb-1">Du</label>
            <input type="date" id="filterDateDebut" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <div id="customDatesContainer2" class="hidden">
            <label class="block text-xs font-medium text-gray-700 mb-1">Au</label>
            <input type="date" id="filterDateFin" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        
        <!-- Jour -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Jour de la semaine</label>
            <select id="filterJour" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">Tous les jours</option>
                <option value="Lundi">Lundi</option>
                <option value="Mardi">Mardi</option>
                <option value="Mercredi">Mercredi</option>
                <option value="Jeudi">Jeudi</option>
                <option value="Vendredi">Vendredi</option>
                <option value="Samedi">Samedi</option>
                <option value="Dimanche">Dimanche</option>
            </select>
        </div>
        
        <!-- Disponibilité -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Disponibilité</label>
            <select id="filterDisponibilite" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">Tous</option>
                <option value="1">Disponibles</option>
                <option value="0">Réservés</option>
            </select>
        </div>
    </div>
</div>

            <!-- Message de notification -->
            <div id="notification" class="hidden fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 text-sm"></div>

            <!-- Container des créneaux -->
            <div id="creneauxContainer">
                <!-- Les créneaux seront chargés ici -->
            </div>

            <!-- Loader -->
            <div id="loader" class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-emerald-600"></div>
            </div>
        </main>
    </div>

    <!-- Modal Ajouter/Modifier Créneau -->
    <div id="creneauModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-5 py-3 flex justify-between items-center">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-800">Ajouter un créneau</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="creneauForm" class="p-5 space-y-3">
                <input type="hidden" id="creneauId" name="id_creneaux">

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Terrain *</label>
                    <input type="text" id="id_terrain_input" list="terrainList" placeholder="Rechercher un terrain..." required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <input type="hidden" id="id_terrain" name="id_terrain">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Jour de la semaine *</label>
                    <select id="jour_semaine" name="jour_semaine" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Sélectionner un jour...</option>
                        <option value="Lundi">Lundi</option>
                        <option value="Mardi">Mardi</option>
                        <option value="Mercredi">Mercredi</option>
                        <option value="Jeudi">Jeudi</option>
                        <option value="Vendredi">Vendredi</option>
                        <option value="Samedi">Samedi</option>
                        <option value="Dimanche">Dimanche</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Heure de début *</label>
                        <input type="time" id="heure_debut" name="heure_debut" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Heure de fin *</label>
                        <input type="time" id="heure_fin" name="heure_fin" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>

                <div class="flex gap-2 pt-3 border-t">
                    <button type="submit" id="submitBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-save mr-1"></i>Enregistrer
                    </button>
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-sm w-full p-5">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-10 w-10 rounded-full bg-red-100 mb-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                </div>
                <h3 class="text-base font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                <p class="text-xs text-gray-500 mb-4">Êtes-vous sûr de vouloir supprimer ce créneau ? Cette action est irréversible.</p>
                <div class="flex gap-2">
                    <button onclick="confirmDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                        Supprimer
                    </button>
                    <button onclick="closeDeleteModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <datalist id="terrainList">
        <!-- Sera rempli dynamiquement par JavaScript -->
    </datalist>
    
    <datalist id="filterTerrainList">
        <!-- Sera rempli dynamiquement par JavaScript -->
    </datalist>

    <script src="../../assets/js/slots.js"></script>
</body>

</html>