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
<style>
/* Styles pour les cartes de terrain */
.terrain-card {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
    overflow: hidden;
}

.terrain-header {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: white;
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.terrain-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
}

.stats-bar {
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 999px;
    overflow: hidden;
    display: flex;
}

.stats-bar > div {
    height: 100%;
    transition: width 0.3s ease;
}

/* Grid Planning */
.planning-grid {
    display: grid;
    grid-template-columns: 70px repeat(7, 1fr);
    gap: 1px;
    background: #e5e7eb;
    padding: 0;
}

.planning-header {
    background: #f3f4f6;
    padding: 0.375rem;
    text-align: center;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #059669;
    font-size: 0.7rem;
}

.planning-header .text-lg {
    font-size: 0.875rem;
}

.planning-header .text-xs {
    font-size: 0.65rem;
}

.time-label {
    background: #fafafa;
    padding: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #6b7280;
    border-right: 2px solid #e5e7eb;
    font-size: 0.65rem;
}

.time-label i {
    font-size: 0.875rem;
    margin-bottom: 0.125rem;
}

.time-label .text-sm {
    font-size: 0.65rem;
}

.time-label .text-xs {
    font-size: 0.6rem;
}

.planning-cell {
    background: white;
    padding: 0.375rem;
    min-height: 70px;
}

.creneau-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.creneau-item {
    border-radius: 0.375rem;
    padding: 0.375rem;
    font-size: 0.7rem;
    transition: all 0.2s;
    border: 1.5px solid transparent;
}

.creneau-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.creneau-item.available {
    background: linear-gradient(135deg, #f5fdf9ff 0%, #e7fcf1ff 100%);
    border-color: #02764bff;
    color: #005039ff;
}

.creneau-item.reserved {
    background: linear-gradient(135deg, #fee2e2 0%, #fedadaff 100%);
    border-color: #ef4444;
    color: #280101ff;
}

.creneau-time {
    font-weight: 700;
    font-size: 0.75rem;
    margin-bottom: 0.125rem;
    display: flex;
    align-items: center;
}

.creneau-time i {
    font-size: 0.65rem;
}

.reservation-info {
    background: rgba(0, 0, 0, 0.1);
    padding: 0.25rem;
    border-radius: 0.25rem;
    margin: 0.125rem 0;
    font-size: 0.65rem;
}

.reservation-info > div {
    margin: 0.125rem 0;
}

.reservation-info i {
    font-size: 0.6rem;
}

.action-btn {
    padding: 0.25rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.65rem;
    font-weight: 600;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.125rem;
}

.action-btn i {
    font-size: 0.7rem;
}

.action-btn:hover {
    transform: scale(1.05);
}

.empty-state {
    text-align: center;
    color: #9ca3af;
    padding: 0.75rem 0.375rem;
    font-size: 0.65rem;
}

.empty-state i {
    font-size: 1rem;
    margin-bottom: 0.125rem;
    opacity: 0.5;
}

/* Stats badges */
.terrain-header .bg-white\/20 {
    padding: 0.375rem 0.625rem;
    font-size: 0.75rem;
}

.terrain-header .w-2 {
    width: 0.375rem;
    height: 0.375rem;
}

/* Responsive */
@media (max-width: 1280px) {
    .planning-grid {
        grid-template-columns: 60px repeat(7, minmax(90px, 1fr));
        overflow-x: auto;
    }
}

@media (max-width: 768px) {
    .terrain-header {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
        padding: 0.625rem;
    }
    
    .terrain-header h2 {
        font-size: 1rem;
    }

    .planning-grid {
        font-size: 0.65rem;
    }

    .creneau-item {
        padding: 0.25rem;
    }
    
    .action-btn {
        font-size: 0.6rem;
        padding: 0.2rem 0.3rem;
    }
}

/* Animation de chargement */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

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
            <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Terrain</label>
                        <select id="filterTerrain" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Tous les terrains</option>
                        </select>
                    </div>
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
                    <select id="id_terrain" name="id_terrain" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Sélectionner un terrain...</option>
                    </select>
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

    <script>
        let currentCreneauId = null;
        let deleteCreneauId = null;

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            loadTerrains();
            loadCreneaux();
            setupEventListeners();
        });

        // Configuration des écouteurs d'événements
        function setupEventListeners() {
            document.getElementById('filterTerrain').addEventListener('change', loadCreneaux);
            document.getElementById('filterJour').addEventListener('change', loadCreneaux);
            document.getElementById('filterDisponibilite').addEventListener('change', loadCreneaux);
            document.getElementById('creneauForm').addEventListener('submit', handleSubmit);
        }

        // Charger les terrains
        function loadTerrains() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '../../actions/admin-manager/stade/get_stades.php', true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            populateTerrainSelects(response.terrains);
                        }
                    } catch (e) {
                        console.error('Erreur lors du chargement des terrains');
                    }
                }
            };

            xhr.send();
        }

        // Remplir les sélecteurs de terrain
        function populateTerrainSelects(terrains) {
            const modalSelect = document.getElementById('id_terrain');
            const filterSelect = document.getElementById('filterTerrain');

            modalSelect.innerHTML = '<option value="">Sélectionner un terrain...</option>' +
                terrains.map(t => `<option value="${t.id_terrain}">${t.nom_te} - ${t.categorie}</option>`).join('');

            filterSelect.innerHTML = '<option value="">Tous les terrains</option>' +
                terrains.map(t => `<option value="${t.id_terrain}">${t.nom_te}</option>`).join('');
        }

        // Charger les créneaux
        function loadCreneaux() {
            const terrain = document.getElementById('filterTerrain').value;
            const jour = document.getElementById('filterJour').value;
            const disponibilite = document.getElementById('filterDisponibilite').value;

            showLoader();

            const xhr = new XMLHttpRequest();
            xhr.open('GET', `../../actions/admin-manager/slot/get_slots.php?terrain=${terrain}&jour=${encodeURIComponent(jour)}&disponibilite=${disponibilite}`, true);

            xhr.onload = function() {
                hideLoader();

                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            displayCreneaux(response.creneaux);
                        } else {
                            showNotification(response.message || 'Erreur lors du chargement des créneaux', 'error');
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

        // Afficher les créneaux groupés par terrain
        // Remplacer la fonction displayCreneaux dans votre code par celle-ci :

function displayCreneaux(creneaux) {
    const container = document.getElementById('creneauxContainer');

    if (creneaux.length === 0) {
        container.innerHTML = `
            <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-calendar-times text-gray-400 text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Aucun créneau trouvé</h3>
                <p class="text-gray-500">Modifiez vos critères de recherche ou ajoutez de nouveaux créneaux</p>
            </div>
        `;
        return;
    }

    // Grouper par terrain
    const terrainGroups = {};
    creneaux.forEach(creneau => {
        if (!terrainGroups[creneau.id_terrain]) {
            terrainGroups[creneau.id_terrain] = {
                nom: creneau.nom_terrain,
                info: creneau.terrain_info,
                prix: creneau.prix_heure,
                creneaux: []
            };
        }
        terrainGroups[creneau.id_terrain].creneaux.push(creneau);
    });

    container.innerHTML = Object.values(terrainGroups).map(terrain => {
        // Grouper par jour
        const jourGroups = {};
        terrain.creneaux.forEach(creneau => {
            if (!jourGroups[creneau.jour_semaine]) {
                jourGroups[creneau.jour_semaine] = [];
            }
            jourGroups[creneau.jour_semaine].push(creneau);
        });

        const jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        const totalDisponibles = terrain.creneaux.filter(c => c.disponibilite).length;
        const totalReserves = terrain.creneaux.filter(c => !c.disponibilite).length;

        return `
            <div class="terrain-card">
                <!-- Header Terrain -->
                <div class="terrain-header">
                    <div class="flex-1">
                        <h2>${terrain.nom}</h2>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 bg-white/20 px-3 py-1 rounded-lg">
                            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                            <span><strong>${totalDisponibles}</strong> disponibles</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white/20 px-3 py-1 rounded-lg">
                            <div class="w-2 h-2 bg-red-400 rounded-full"></div>
                            <span><strong>${totalReserves}</strong> réservés</span>
                        </div>
                    </div>
                </div>

                <!-- Planning Grid -->
                <div class="planning-grid">
                    <!-- Header vide pour colonne horaire -->
                    <div class="planning-header">HORAIRES</div>
                    
                    <!-- Headers jours -->
                    ${jours.map(jour => `
                        <div class="planning-header">
                            <div class="text-lg font-bold">${jour}</div>
                            <div class="text-xs mt-1 opacity-80">${(jourGroups[jour] || []).length} créneaux</div>
                        </div>
                    `).join('')}

                    <!-- Time slot: Matin -->
                    <div class="time-label">
                        <div class="text-center">
                            <i class="fas fa-sun text-yellow-400"></i>
                            <div class="text-sm">MATIN</div>
                            <div class="text-xs opacity-75">06:00 - 12:00</div>
                        </div>
                    </div>
                    ${jours.map(jour => {
                        const creneauxMatin = (jourGroups[jour] || []).filter(c => {
                            const heure = parseInt(c.heure_debut.split(':')[0]);
                            return heure >= 6 && heure < 12;
                        });
                        return `
                            <div class="planning-cell creneau-cell">
                                ${creneauxMatin.length > 0 ? creneauxMatin.map(c => `
                                    <div class="creneau-item ${c.disponibilite ? 'available' : 'reserved'}">
                                        <div class="creneau-time">
                                            <i class="fas fa-clock mr-1"></i>${c.heure_debut} - ${c.heure_fin}
                                        </div>
                                        ${!c.disponibilite && c.reservation_info ? `
                                            <div class="reservation-info">
                                                <div><i class="fas fa-users mr-1"></i>${c.reservation_info.equipe_nom}</div>
                                                ${c.reservation_info.equipe_adverse ? `<div><i class="fas fa-shield-alt mr-1"></i>vs ${c.reservation_info.equipe_adverse}</div>` : ''}
                                            </div>
                                        ` : ''}
                                        <div class="flex gap-1 mt-2">
                                            <button onclick="editCreneau(${c.id_creneaux})" class="action-btn bg-green-800 text-white hover:bg-green-900">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button onclick="openDeleteModal(${c.id_creneaux})" class="action-btn bg-red-600 text-white hover:bg-red-700">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                `).join('') : '<div class="empty-state"><i class="fas fa-moon"></i><br>Aucun</div>'}
                            </div>
                        `;
                    }).join('')}

                    <!-- Time slot: Après-midi -->
                    <div class="time-label">
                        <div class="text-center">
                            <i class="fas fa-cloud-sun text-orange-400"></i>
                            <div class="text-sm">APRÈS-MIDI</div>
                            <div class="text-xs opacity-75">12:00 - 18:00</div>
                        </div>
                    </div>
                    ${jours.map(jour => {
                        const creneauxApresMidi = (jourGroups[jour] || []).filter(c => {
                            const heure = parseInt(c.heure_debut.split(':')[0]);
                            return heure >= 12 && heure < 18;
                        });
                        return `
                            <div class="planning-cell creneau-cell">
                                ${creneauxApresMidi.length > 0 ? creneauxApresMidi.map(c => `
                                    <div class="creneau-item ${c.disponibilite ? 'available' : 'reserved'}">
                                        <div class="creneau-time">
                                            <i class="fas fa-clock mr-1"></i>${c.heure_debut} - ${c.heure_fin}
                                        </div>
                                        ${!c.disponibilite && c.reservation_info ? `
                                            <div class="reservation-info">
                                                <div><i class="fas fa-users mr-1"></i>${c.reservation_info.equipe_nom}</div>
                                                ${c.reservation_info.equipe_adverse ? `<div><i class="fas fa-shield-alt mr-1"></i>vs ${c.reservation_info.equipe_adverse}</div>` : ''}
                                            </div>
                                        ` : ''}
                                        <div class="flex gap-1 mt-2">
                                            <button onclick="editCreneau(${c.id_creneaux})" class="action-btn bg-green-800 text-white hover:bg-green-900">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button onclick="openDeleteModal(${c.id_creneaux})" class="action-btn bg-red-600 text-white hover:bg-red-700">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                `).join('') : '<div class="empty-state"><i class="fas fa-moon"></i><br>Aucun</div>'}
                            </div>
                        `;
                    }).join('')}

                    <!-- Time slot: Soir -->
                    <div class="time-label">
                        <div class="text-center">
                            <i class="fas fa-moon text-indigo-400"></i>
                            <div class="text-sm">SOIR</div>
                            <div class="text-xs opacity-75">18:00 - 23:00</div>
                        </div>
                    </div>
                    ${jours.map(jour => {
                        const creneauxSoir = (jourGroups[jour] || []).filter(c => {
                            const heure = parseInt(c.heure_debut.split(':')[0]);
                            return heure >= 18;
                        });
                        return `
                            <div class="planning-cell creneau-cell">
                                ${creneauxSoir.length > 0 ? creneauxSoir.map(c => `
                                    <div class="creneau-item ${c.disponibilite ? 'available' : 'reserved'}">
                                        <div class="creneau-time">
                                            <i class="fas fa-clock mr-1"></i>${c.heure_debut} - ${c.heure_fin}
                                        </div>
                                        ${!c.disponibilite && c.reservation_info ? `
                                            <div class="reservation-info">
                                                <div><i class="fas fa-users mr-1"></i>${c.reservation_info.equipe_nom}</div>
                                                ${c.reservation_info.equipe_adverse ? `<div><i class="fas fa-shield-alt mr-1"></i>vs ${c.reservation_info.equipe_adverse}</div>` : ''}
                                            </div>
                                        ` : ''}
                                        <div class="flex gap-1 mt-2">
                                            <button onclick="editCreneau(${c.id_creneaux})" class="action-btn bg-green-800 text-white hover:bg-green-900">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button onclick="openDeleteModal(${c.id_creneaux})" class="action-btn bg-red-600 text-white hover:bg-red-700">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                `).join('') : '<div class="empty-state"><i class="fas fa-moon"></i><br>Aucun</div>'}
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }).join('');
}

        // Ouvrir le modal d'ajout
        function openAddModal() {
            currentCreneauId = null;
            document.getElementById('modalTitle').textContent = 'Ajouter un créneau';
            document.getElementById('creneauForm').reset();
            document.getElementById('creneauId').value = '';
            document.getElementById('creneauModal').classList.remove('hidden');
        }

        // Modifier un créneau
        function editCreneau(id) {
            currentCreneauId = id;

            const xhr = new XMLHttpRequest();
            xhr.open('GET', `../../actions/admin-manager/slot/get_slot.php?id=${id}`, true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            const creneau = response.creneau;
                            document.getElementById('modalTitle').textContent = 'Modifier le créneau';
                            document.getElementById('creneauId').value = creneau.id_creneaux;
                            document.getElementById('id_terrain').value = creneau.id_terrain;
                            document.getElementById('jour_semaine').value = creneau.jour_semaine;
                            document.getElementById('heure_debut').value = creneau.heure_debut;
                            document.getElementById('heure_fin').value = creneau.heure_fin;
                            document.getElementById('creneauModal').classList.remove('hidden');
                        } else {
                            showNotification(response.message || 'Erreur lors du chargement du créneau', 'error');
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
            xhr.open('POST', currentCreneauId ? '../../actions/admin-manager/slot/edit_slot.php' : '../../actions/admin-manager/slot/add_slot.php', true);
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
                            loadCreneaux();
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
            deleteCreneauId = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        // Fermer le modal de suppression
        function closeDeleteModal() {
            deleteCreneauId = null;
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Confirmer la suppression
        function confirmDelete() {
            if (!deleteCreneauId) return;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../../actions/admin-manager/slot/delete_slot.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showNotification(response.message, 'success');
                            closeDeleteModal();
                            loadCreneaux();
                        } else {
                            showNotification(response.message || 'Erreur lors de la suppression', 'error');
                        }
                    } catch (e) {
                        showNotification('Erreur lors du traitement de la réponse', 'error');
                    }
                }
            };

            xhr.send(JSON.stringify({
                id_creneaux: deleteCreneauId
            }));
        }

        // Fermer le modal principal
        function closeModal() {
            document.getElementById('creneauModal').classList.add('hidden');
            document.getElementById('creneauForm').reset();
            currentCreneauId = null;
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
                info: 'bg-green-500',
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
    </script>
</body>

</html>