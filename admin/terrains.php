<?php
require_once '../config/database.php';
require_once 'check_auth.php';

$pageTitle = "Gestion des Terrains";
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

<body class="bg-gray-50">
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 ml-64 p-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Gestion des Terrains</h1>
                        <p class="text-gray-600 mt-2">Gérez tous les terrains de football</p>
                    </div>
                    <button onclick="openAddModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg flex items-center gap-2 transition-colors shadow-lg">
                        <i class="fas fa-plus"></i>
                        <span>Ajouter un terrain</span>
                    </button>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                        <input type="text" id="searchInput" placeholder="Nom du terrain..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
                        <select id="filterCategorie" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Toutes</option>
                            <option value="Grand Terrain">Grand Terrain</option>
                            <option value="Terrain Moyen">Terrain Moyen</option>
                            <option value="Mini Foot">Mini Foot</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Disponibilité</label>
                        <select id="filterDisponibilite" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Tous</option>
                            <option value="disponible">Disponible</option>
                            <option value="indisponible">Indisponible</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Responsable</label>
                        <select id="filterResponsable" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Tous</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Message de notification -->
            <div id="notification" class="hidden fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50"></div>

            <!-- Liste des terrains -->
            <div id="terrainsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Les terrains seront chargés ici via AJAX -->
            </div>

            <!-- Loader -->
            <div id="loader" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
            </div>
        </main>
    </div>

    <!-- Modal Ajouter/Modifier Terrain -->
    <div id="terrainModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h2 id="modalTitle" class="text-2xl font-bold text-gray-800">Ajouter un terrain</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <form id="terrainForm" class="p-6 space-y-4">
                <input type="hidden" id="terrainId" name="id_terrain">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom du terrain *</label>
                        <input type="text" id="nom_te" name="nom_te" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie *</label>
                        <select id="categorie" name="categorie" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Sélectionner...</option>
                            <option value="Grand Terrain">Grand Terrain</option>
                            <option value="Terrain Moyen">Terrain Moyen</option>
                            <option value="Mini Foot">Mini Foot</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                        <select id="type" name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Sélectionner...</option>
                            <option value="Gazon naturel">Gazon naturel</option>
                            <option value="Gazon synthétique">Gazon synthétique</option>
                            <option value="Terre battue">Terre battue</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Taille *</label>
                        <select id="taille" name="taille" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Sélectionner...</option>
                            <option value="105x68m">105x68m (Standard FIFA)</option>
                            <option value="100x60m">100x60m</option>
                            <option value="90x50m">90x50m</option>
                            <option value="70x40m">70x40m (5 vs 5)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prix par heure (DH) *</label>
                        <input type="number" id="prix_heure" name="prix_heure" step="0.01" min="0" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Disponibilité *</label>
                        <select id="disponibilite" name="disponibilite" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="disponible">Disponible</option>
                            <option value="indisponible">Indisponible</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Localisation *</label>
                    <textarea id="localisation" name="localisation" rows="2" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Responsable *</label>
                    <select id="id_responsable" name="id_responsable" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Sélectionner un responsable...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Image (URL)</label>
                    <input type="text" id="image" name="image" placeholder="nom_image.jpg" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div class="flex gap-3 pt-4 border-t">
                    <button type="submit" id="submitBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                    <button type="button" onclick="closeModal()" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                <p class="text-sm text-gray-500 mb-6">Êtes-vous sûr de vouloir supprimer ce terrain ? Cette action est irréversible.</p>
                <div class="flex gap-3">
                    <button onclick="confirmDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Supprimer
                    </button>
                    <button onclick="closeDeleteModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium transition-colors">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentTerrainId = null;
        let deleteTerrainId = null;

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadTerrains();
            loadResponsables();
            setupEventListeners();
        });

        // Configuration des écouteurs d'événements
        function setupEventListeners() {
            // Recherche en temps réel
            document.getElementById('searchInput').addEventListener('input', debounce(loadTerrains, 500));

            // Filtres
            document.getElementById('filterCategorie').addEventListener('change', loadTerrains);
            document.getElementById('filterDisponibilite').addEventListener('change', loadTerrains);
            document.getElementById('filterResponsable').addEventListener('change', loadTerrains);

            // Formulaire
            document.getElementById('terrainForm').addEventListener('submit', handleSubmit);
        }

        // Fonction debounce pour la recherche
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Charger tous les terrains
        function loadTerrains() {
            const search = document.getElementById('searchInput').value;
            const categorie = document.getElementById('filterCategorie').value;
            const disponibilite = document.getElementById('filterDisponibilite').value;
            const responsable = document.getElementById('filterResponsable').value;

            showLoader();

            const xhr = new XMLHttpRequest();
            xhr.open('GET', `../actions/admin-respo/get_terrains.php?search=${encodeURIComponent(search)}&categorie=${categorie}&disponibilite=${disponibilite}&responsable=${responsable}`, true);

            xhr.onload = function() {
                hideLoader();

                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            displayTerrains(response.terrains);
                        } else {
                            showNotification(response.message || 'Erreur lors du chargement des terrains', 'error');
                        }
                    } catch (e) {
                        showNotification('Erreur lors du traitement de la réponse', 'error');
                    }
                } else {
                    showNotification('Erreur de connexion au serveur', 'error');
                }
            };

            xhr.onerror = function() {
                hideLoader();
                showNotification('Erreur réseau', 'error');
            };

            xhr.send();
        }

        // Afficher les terrains
        function displayTerrains(terrains) {
            const container = document.getElementById('terrainsContainer');

            if (terrains.length === 0) {
                container.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-search text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">Aucun terrain trouvé</p>
            </div>
        `;
                return;
            }

            container.innerHTML = terrains.map(terrain => `
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
            <div class="relative h-48 bg-gradient-to-br from-emerald-400 to-teal-600">
                ${terrain.image ? `
                    <img src="../assets/images/terrains/${terrain.image}" alt="${terrain.nom_te}" class="w-full h-full object-cover">
                ` : `
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-futbol text-white text-6xl opacity-50"></i>
                    </div>
                `}
                <div class="absolute top-3 right-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${getDisponibiliteClass(terrain.disponibilite)}">
                        ${getDisponibiliteLabel(terrain.disponibilite)}
                    </span>
                </div>
                <div class="absolute top-3 left-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white text-gray-800">
                        ${terrain.categorie}
                    </span>
                </div>
            </div>
            
            <div class="p-5">
                <h3 class="text-xl font-bold text-gray-800 mb-2">${terrain.nom_te}</h3>
                
                <div class="space-y-2 mb-4 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-emerald-600 w-4"></i>
                        <span class="truncate">${terrain.localisation}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-layer-group text-emerald-600 w-4"></i>
                        <span>${terrain.type}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-expand-arrows-alt text-emerald-600 w-4"></i>
                        <span>${terrain.taille}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user-tie text-emerald-600 w-4"></i>
                        <span>${terrain.responsable_nom || 'Non assigné'}</span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="text-2xl font-bold text-emerald-600">
                        ${terrain.prix_heure} DH<span class="text-sm text-gray-500 font-normal">/h</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editTerrain(${terrain.id_terrain})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="openDeleteModal(${terrain.id_terrain})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
        }

        // Charger les responsables
        function loadResponsables() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '../actions/admin-respo/get_responsables.php', true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            populateResponsableSelects(response.responsables);
                        }
                    } catch (e) {
                        console.error('Erreur lors du chargement des responsables');
                    }
                }
            };

            xhr.send();
        }

        // Remplir les listes déroulantes des responsables
        function populateResponsableSelects(responsables) {
            const modalSelect = document.getElementById('id_responsable');
            const filterSelect = document.getElementById('filterResponsable');

            // Modal select
            modalSelect.innerHTML = '<option value="">Sélectionner un responsable...</option>' +
                responsables.map(r => `<option value="${r.email}">${r.nom} ${r.prenom}</option>`).join('');

            // Filter select
            filterSelect.innerHTML = '<option value="">Tous</option>' +
                responsables.map(r => `<option value="${r.email}">${r.nom} ${r.prenom}</option>`).join('');
        }

        // Ouvrir le modal d'ajout
        function openAddModal() {
            currentTerrainId = null;
            document.getElementById('modalTitle').textContent = 'Ajouter un terrain';
            document.getElementById('terrainForm').reset();
            document.getElementById('terrainId').value = '';
            document.getElementById('terrainModal').classList.remove('hidden');
        }

        // Modifier un terrain
        function editTerrain(id) {
            currentTerrainId = id;

            const xhr = new XMLHttpRequest();
            xhr.open('GET', `../actions/admin-respo/get_terrain.php?id=${id}`, true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            const terrain = response.terrain;

                            document.getElementById('modalTitle').textContent = 'Modifier le terrain';
                            document.getElementById('terrainId').value = terrain.id_terrain;
                            document.getElementById('nom_te').value = terrain.nom_te;
                            document.getElementById('categorie').value = terrain.categorie;
                            document.getElementById('type').value = terrain.type;
                            document.getElementById('taille').value = terrain.taille;
                            document.getElementById('prix_heure').value = terrain.prix_heure;
                            document.getElementById('disponibilite').value = terrain.disponibilite;
                            document.getElementById('localisation').value = terrain.localisation;
                            document.getElementById('id_responsable').value = terrain.id_responsable || '';
                            document.getElementById('image').value = terrain.image || '';

                            document.getElementById('terrainModal').classList.remove('hidden');
                        } else {
                            showNotification(response.message || 'Erreur lors du chargement du terrain', 'error');
                        }
                    } catch (e) {
                        showNotification('Erreur lors du traitement de la réponse', 'error');
                    }
                }
            };

            xhr.send();
        }

        // Gérer la soumission du formulaire
        function handleSubmit(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...';

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', currentTerrainId ? '../actions/admin-respo/edit_terrain.php' : '../actions/admin-respo/add_terrain.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer';

                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            showNotification(response.message, 'success');
                            closeModal();
                            loadTerrains();
                        } else {
                            showNotification(response.message || 'Erreur lors de l\'enregistrement', 'error');
                        }
                    } catch (e) {
                        showNotification('Erreur lors du traitement de la réponse', 'error');
                    }
                } else {
                    showNotification('Erreur de connexion au serveur', 'error');
                }
            };

            xhr.onerror = function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer';
                showNotification('Erreur réseau', 'error');
            };

            xhr.send(JSON.stringify(data));
        }

        // Ouvrir le modal de suppression
        function openDeleteModal(id) {
            deleteTerrainId = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        // Fermer le modal de suppression
        function closeDeleteModal() {
            deleteTerrainId = null;
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Confirmer la suppression
        function confirmDelete() {
            if (!deleteTerrainId) return;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../actions/admin-respo/delete_terrain.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            showNotification(response.message, 'success');
                            closeDeleteModal();
                            loadTerrains();
                        } else {
                            showNotification(response.message || 'Erreur lors de la suppression', 'error');
                        }
                    } catch (e) {
                        showNotification('Erreur lors du traitement de la réponse', 'error');
                    }
                }
            };

            xhr.send(JSON.stringify({
                id_terrain: deleteTerrainId
            }));
        }

        // Fermer le modal principal
        function closeModal() {
            document.getElementById('terrainModal').classList.add('hidden');
            document.getElementById('terrainForm').reset();
            currentTerrainId = null;
        }

        // Afficher/masquer le loader
        function showLoader() {
            document.getElementById('loader').classList.remove('hidden');
        }

        function hideLoader() {
            document.getElementById('loader').classList.add('hidden');
        }

        // Afficher une notification
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');

            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };

            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle',
                warning: 'fa-exclamation-triangle'
            };

            notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 text-white ${colors[type]}`;
            notification.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas ${icons[type]} text-xl"></i>
            <span>${message}</span>
        </div>
    `;

            notification.classList.remove('hidden');

            setTimeout(() => {
                notification.classList.add('hidden');
            }, 4000);
        }

        // Fonctions utilitaires
        function getDisponibiliteClass(disponibilite) {
            const classes = {
                'disponible': 'bg-green-500 text-white',
                'indisponible': 'bg-red-500 text-white',
                'maintenance': 'bg-yellow-500 text-white'
            };
            return classes[disponibilite] || 'bg-gray-500 text-white';
        }

        function getDisponibiliteLabel(disponibilite) {
            const labels = {
                'disponible': 'Disponible',
                'indisponible': 'Indisponible',
                'maintenance': 'Maintenance'
            };
            return labels[disponibilite] || disponibilite;
        }
    </script>
</body>

</html>