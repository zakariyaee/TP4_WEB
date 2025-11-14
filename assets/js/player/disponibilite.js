// Variables globales
let isEditing = false;
let currentDispoId = null;
let currentTab = 'tous';
let allDisponibilites = [];
let filters = {
    position: '',
    niveau: '',
    ville: '',
    date: ''
};

// Configuration localStorage
const STORAGE_KEY = 'terrainbook_disponibilites';
const CACHE_DURATION = 30000; // 30 secondes

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Charger depuis localStorage ou API
    loadDisponibilites();
    
    // Écouter les changements dans d'autres onglets
    window.addEventListener('storage', handleStorageChange);
    
    // Synchroniser périodiquement avec le serveur
    setInterval(syncWithServer, 1000); // Vérifier toutes les secondes
    
    // Gérer les formulaires
    document.getElementById('form-disponibilite').addEventListener('submit', handleSubmit);
    document.getElementById('form-invitation').addEventListener('submit', handleInvitation);
});

// Gérer les changements de localStorage depuis d'autres onglets
function handleStorageChange(e) {
    if (e.key === STORAGE_KEY) {
        console.log('Mise à jour détectée depuis un autre onglet');
        loadFromLocalStorage();
    } else if (e.key === 'sync_disponibilites') {
        // Signal de synchronisation : recharger depuis le serveur
        console.log('Signal de synchronisation reçu, rechargement des disponibilités...');
        invalidateCache();
    }
}

// Charger les données depuis localStorage ou API
function loadDisponibilites() {
    const cached = getFromLocalStorage();
    
    if (cached && !isCacheExpired(cached.timestamp)) {
        // Utiliser le cache
        console.log('Chargement depuis localStorage');
        allDisponibilites = cached.data;
        renderDisponibilites();
    } else {
        // Charger depuis l'API
        console.log('Chargement depuis l\'API');
        fetchFromServer();
    }
}

// Récupérer depuis localStorage
function getFromLocalStorage() {
    try {
        const data = localStorage.getItem(STORAGE_KEY);
        return data ? JSON.parse(data) : null;
    } catch (error) {
        console.error('Erreur lecture localStorage:', error);
        return null;
    }
}

// Sauvegarder dans localStorage
function saveToLocalStorage(data) {
    try {
        const cacheData = {
            data: data,
            timestamp: Date.now()
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cacheData));
        console.log('Données sauvegardées dans localStorage');
    } catch (error) {
        console.error('Erreur sauvegarde localStorage:', error);
    }
}

// Vérifier si le cache est expiré
function isCacheExpired(timestamp) {
    return (Date.now() - timestamp) > CACHE_DURATION;
}

// Charger depuis le serveur
function fetchFromServer() {
    fetch('../../../actions/player/disponibilite/get_disponibilites.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allDisponibilites = data.disponibilites;
                saveToLocalStorage(data.disponibilites);
                renderDisponibilites();
            }
        })
        .catch(error => {
            console.error('Erreur chargement API:', error);
            // En cas d'erreur, utiliser le cache même expiré
            const cached = getFromLocalStorage();
            if (cached) {
                allDisponibilites = cached.data;
                renderDisponibilites();
            }
        });
}

// Synchroniser avec le serveur (en arrière-plan)
function syncWithServer() {
    const cached = getFromLocalStorage();
    if (!cached || isCacheExpired(cached.timestamp)) {
        console.log('Synchronisation avec le serveur...');
        fetchFromServer();
    }
}

// Charger uniquement depuis localStorage
function loadFromLocalStorage() {
    const cached = getFromLocalStorage();
    if (cached) {
        allDisponibilites = cached.data;
        renderDisponibilites();
    }
}

// Invalider le cache (forcer le rechargement)
function invalidateCache() {
    localStorage.removeItem(STORAGE_KEY);
    fetchFromServer();
}

// Changer d'onglet
function switchTab(tab) {
    currentTab = tab;
    
    document.getElementById('tab-tous').classList.remove('active');
    document.getElementById('tab-mes').classList.remove('active');
    document.getElementById(`tab-${tab}`).classList.add('active');
    
    if (tab === 'mes') {
        document.getElementById('btn-add-container').classList.remove('hidden');
        document.getElementById('list-title').textContent = 'Mes disponibilités';
    } else {
        document.getElementById('btn-add-container').classList.add('hidden');
        document.getElementById('list-title').textContent = 'Tous les joueurs disponibles';
    }
    
    renderDisponibilites();
}

// Appliquer les filtres
function applyFilters() {
    filters.position = document.getElementById('filter-position').value;
    filters.niveau = document.getElementById('filter-niveau').value;
    filters.ville = document.getElementById('filter-ville').value;
    filters.date = document.getElementById('filter-date').value;
    
    renderDisponibilites();
}

// Afficher les disponibilités
function renderDisponibilites() {
    let filteredDispos = allDisponibilites.filter(dispo => {
        // Dans l'onglet "Mes disponibilités", afficher toutes les disponibilités de l'utilisateur
        if (currentTab === 'mes') {
            if (dispo.email_joueur !== currentUserEmail) {
                return false;
            }
            // Afficher toutes les disponibilités (actives et inactives) pour l'utilisateur connecté
        } else {
            // Dans l'onglet "Tous", ne montrer que les disponibilités actives des autres utilisateurs
            // (Les disponibilités de l'utilisateur connecté ne devraient pas apparaître ici normalement)
            if (dispo.email_joueur === currentUserEmail) {
                return false;
            }
            // Le serveur devrait déjà filtrer, mais on double-vérifie côté client
            if (dispo.statut !== 'actif') {
                return false;
            }
        }
        
        // Appliquer les autres filtres
        if (filters.position && dispo.position !== filters.position) return false;
        if (filters.niveau && dispo.niveau !== filters.niveau) return false;
        if (filters.ville && dispo.ville !== filters.ville) return false;
        if (filters.date && !dispo.date_debut.startsWith(filters.date)) return false;
        
        return true;
    });
    
    const container = document.getElementById('disponibilites-list');
    
    if (filteredDispos.length === 0) {
        container.innerHTML = `
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-calendar-times text-4xl mb-3"></i>
                <p>Aucune disponibilité trouvée</p>
                ${currentTab === 'mes' ? `
                    <button onclick="openModal()" class="mt-4 text-emerald-600 hover:text-emerald-700 font-medium">
                        Ajouter votre première disponibilité
                    </button>
                ` : ''}
            </div>
        `;
        return;
    }

    container.innerHTML = filteredDispos.map(dispo => createDispoHTML(dispo)).join('');
}

// Créer le HTML pour une disponibilité
function createDispoHTML(dispo) {
    const positionColors = {
        'Attaquant': 'bg-red-100 text-red-700',
        'Milieu': 'bg-blue-100 text-blue-700',
        'Défenseur': 'bg-green-100 text-green-700',
        'Gardien': 'bg-yellow-100 text-yellow-700'
    };

    const isMyDispo = dispo.email_joueur === currentUserEmail;
    const isInactive = dispo.statut === 'inactif';

    return `
        <div class="p-6 hover:bg-gray-50 transition-colors fade-in ${isInactive && isMyDispo ? 'opacity-75 bg-gray-50' : ''}" data-dispo-id="${dispo.id_disponibilite}">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        ${!isMyDispo ? `
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 bg-gradient-to-br from-emerald-600 to-green-700 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-sm">${dispo.nom_joueur.charAt(0)}${dispo.prenom_joueur.charAt(0)}</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">${dispo.nom_joueur} ${dispo.prenom_joueur}</div>
                                    <div class="text-xs text-gray-500">${dispo.ville_joueur || 'Ville non spécifiée'}</div>
                                </div>
                            </div>
                            <span class="text-gray-300">|</span>
                        ` : ''}
                        ${isInactive && isMyDispo ? `
                            <span class="px-3 py-1 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">
                                <i class="fas fa-pause-circle mr-1"></i>Inactive
                            </span>
                        ` : ''}
                        <span class="px-3 py-1 ${isInactive && isMyDispo ? 'bg-gray-100 text-gray-500' : 'bg-emerald-100 text-emerald-700'} rounded-full text-sm font-medium">
                            ${dispo.date_formatted}
                        </span>
                        <span class="text-gray-600">
                            <i class="fas fa-clock mr-1"></i>
                            ${dispo.heure_debut_formatted} - ${dispo.heure_fin_formatted}
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-medium ${positionColors[dispo.position] || 'bg-gray-100 text-gray-700'}">
                            ${dispo.position}
                        </span>
                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                            ${dispo.niveau}
                        </span>
                    </div>
                    
                    ${dispo.nom_terrain ? `
                        <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                            <i class="fas fa-map-marker-alt text-emerald-600"></i>
                            <span>${dispo.nom_terrain} - ${dispo.ville}</span>
                            <span class="text-gray-400">|</span>
                            <span>${dispo.categorie}</span>
                        </div>
                    ` : ''}

                    ${dispo.rayon_km ? `
                        <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                            <i class="fas fa-location-arrow text-emerald-600"></i>
                            <span>Rayon: ${dispo.rayon_km} km</span>
                        </div>
                    ` : ''}

                    ${dispo.description ? `
                        <p class="text-sm text-gray-600 mt-2">${dispo.description}</p>
                    ` : ''}
                </div>

                <div class="flex items-center gap-3 ml-4">
                    ${isMyDispo ? `
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">
                                ${dispo.statut === 'actif' ? 'Actif' : 'Inactif'}
                            </span>
                            <div class="relative">
                                <input type="checkbox" 
                                       class="toggle-checkbox absolute opacity-0 w-0 h-0" 
                                       id="toggle-${dispo.id_disponibilite}"
                                       ${dispo.statut === 'actif' ? 'checked' : ''}
                                       onchange="toggleStatut(${dispo.id_disponibilite}, this.checked)">
                                <label for="toggle-${dispo.id_disponibilite}" 
                                       class="block w-12 h-6 ${dispo.statut === 'actif' ? 'bg-emerald-500' : 'bg-gray-300'} rounded-full cursor-pointer relative transition-colors duration-200">
                                    <span class="toggle-label absolute ${dispo.statut === 'actif' ? 'left-6' : 'left-1'} top-1 w-4 h-4 bg-white rounded-full transition-all duration-200"></span>
                                </label>
                            </div>
                        </div>

                        <button onclick="editDisponibilite(${dispo.id_disponibilite})"
                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                            <i class="fas fa-edit"></i>
                        </button>

                        <button onclick="deleteDisponibilite(${dispo.id_disponibilite})"
                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : `
                        <button onclick="openInvitationModal(${dispo.id_disponibilite}, '${dispo.email_joueur}', '${dispo.nom_joueur} ${dispo.prenom_joueur}')"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium flex items-center gap-2">
                            <i class="fas fa-paper-plane"></i>
                            Inviter
                        </button>
                    `}
                </div>
            </div>
        </div>
    `;
}

// Ouvrir/Fermer modals
function openModal() {
    isEditing = false;
    currentDispoId = null;
    document.getElementById('modal-title').textContent = 'Ajouter ma disponibilité';
    document.getElementById('form-disponibilite').reset();
    document.getElementById('id_disponibilite').value = '';
    document.getElementById('modal-disponibilite').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-disponibilite').classList.add('hidden');
    document.getElementById('form-disponibilite').reset();
    isEditing = false;
    currentDispoId = null;
}

function openInvitationModal(idDispo, emailJoueur, nomJoueur) {
    if (mesEquipes.length === 0) {
        showNotification('error', 'Vous devez d\'abord créer une équipe pour inviter un joueur');
        return;
    }
    
    document.getElementById('inv_id_disponibilite').value = idDispo;
    document.getElementById('inv_email_joueur').value = emailJoueur;
    document.getElementById('modal-invitation').classList.remove('hidden');
}

function closeInvitationModal() {
    document.getElementById('modal-invitation').classList.add('hidden');
    document.getElementById('form-invitation').reset();
}

// Gérer la soumission du formulaire de disponibilité
function handleSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    data.date_debut = `${data.date_debut} ${data.heure_debut}`;
    data.date_fin = `${data.date_debut.split(' ')[0]} ${data.heure_fin}`;
    
    delete data.heure_debut;
    delete data.heure_fin;
    
    const url = isEditing 
        ? '../../../actions/player/disponibilite/update_disponibilite.php'
        : '../../../actions/player/disponibilite/create_disponibilite.php';
    
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('success', result.message);
            closeModal();
            invalidateCache(); // Forcer le rechargement depuis le serveur
        } else {
            showNotification('error', result.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('error', 'Une erreur est survenue');
    });
}

// Gérer l'envoi d'invitation (AJAX, pas email)
function handleInvitation(e) {
    e.preventDefault();
    
    const data = {
        id_disponibilite: document.getElementById('inv_id_disponibilite').value,
        email_destinataire: document.getElementById('inv_email_joueur').value,
        id_equipe: document.getElementById('inv_id_equipe').value,
        message: document.getElementById('inv_message').value
    };
    
    fetch('../../../actions/player/disponibilite/send_invitation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('success', 'Invitation envoyée avec succès');
            closeInvitationModal();
            
            // Invalider le cache des invitations envoyées pour que l'invitation apparaisse immédiatement
            if (typeof Storage !== 'undefined') {
                // Supprimer le cache des invitations envoyées
                localStorage.removeItem('terrainbook_sent_invitations');
                // Envoyer un signal de synchronisation pour les autres onglets
                localStorage.setItem('sync_sent_invitations', Date.now().toString());
            }
        } else {
            showNotification('error', result.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('error', 'Erreur lors de l\'envoi de l\'invitation');
    });
}

// Éditer une disponibilité
function editDisponibilite(id) {
    isEditing = true;
    currentDispoId = id;
    
    fetch(`../../../actions/player/disponibilite/get_disponibilite.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const dispo = data.disponibilite;
                
                document.getElementById('id_disponibilite').value = dispo.id_disponibilite;
                document.getElementById('date_debut').value = dispo.date_debut.split(' ')[0];
                document.getElementById('heure_debut').value = dispo.date_debut.split(' ')[1].substring(0, 5);
                document.getElementById('heure_fin').value = dispo.date_fin.split(' ')[1].substring(0, 5);
                document.getElementById('position').value = dispo.position;
                document.getElementById('niveau').value = dispo.niveau;
                document.getElementById('id_terrain').value = dispo.id_terrain || '';
                document.getElementById('rayon_km').value = dispo.rayon_km || '';
                document.getElementById('description').value = dispo.description || '';
                
                document.getElementById('modal-title').textContent = 'Modifier ma disponibilité';
                document.getElementById('modal-disponibilite').classList.remove('hidden');
            } else {
                showNotification('error', data.message || 'Erreur lors du chargement');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('error', 'Erreur lors du chargement');
        });
}

// Toggle statut actif/inactif
function toggleStatut(id, isActive) {
    const statut = isActive ? 'actif' : 'inactif';
    const checkbox = document.getElementById(`toggle-${id}`);
    
    // Désactiver le toggle pendant la mise à jour
    if (checkbox) checkbox.disabled = true;
    
    fetch('../../../actions/player/disponibilite/update_statut.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_disponibilite: id, statut: statut })
    })
    .then(response => response.json())
    .then(result => {
        if (checkbox) checkbox.disabled = false;
        
        if (result.success) {
            showNotification('success', `Disponibilité ${isActive ? 'activée' : 'désactivée'}`);
            
            // Mettre à jour immédiatement dans le tableau local
            const dispoIndex = allDisponibilites.findIndex(d => d.id_disponibilite === id);
            if (dispoIndex !== -1) {
                allDisponibilites[dispoIndex].statut = statut;
            }
            
            // Recharger depuis le serveur pour s'assurer que les données sont à jour
            // et que les autres utilisateurs voient le changement
            invalidateCache();
            
            // Notifier les autres onglets ouverts (si multi-onglets)
            if (typeof Storage !== 'undefined') {
                localStorage.setItem('sync_disponibilites', Date.now().toString());
            }
        } else {
            showNotification('error', result.message);
            if (checkbox) checkbox.checked = !isActive;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('error', 'Erreur lors de la mise à jour');
        if (checkbox) {
            checkbox.disabled = false;
            checkbox.checked = !isActive;
        }
    });
}

// Supprimer une disponibilité
function deleteDisponibilite(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette disponibilité ?')) {
        return;
    }
    
    fetch('../../../actions/player/disponibilite/delete_disponibilite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_disponibilite: id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('success', 'Disponibilité supprimée');
            invalidateCache();
        } else {
            showNotification('error', result.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('error', 'Erreur lors de la suppression');
    });
}

// Afficher une notification
function showNotification(type, message) {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 fade-in`;
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-10px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}