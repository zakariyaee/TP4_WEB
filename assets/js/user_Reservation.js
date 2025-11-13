// ============================================
// SCRIPT COMPLET - GESTION DES RÉSERVATIONS UTILISATEUR
// ============================================

// ============================================
// 1. VARIABLES GLOBALES
// ============================================
let totalReservation = document.getElementById('totalReservations');
let completedReservations = document.getElementById('ConfirmedReservations');
let canceledReservations = document.getElementById('pendingReservations');
let currentTab = 'prochaines';
let currentReservationId = null;
let originalReservationData = null;

// Récupérer les paramètres de l'URL si nécessaire
const params = new URLSearchParams(window.location.search);

// ============================================
// 2. GESTION DES TABS (ONGLETS)
// ============================================
function switchTab(tab) {
    currentTab = tab;
    
    // Mettre à jour les onglets visuellement
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');
    
    // Afficher/masquer les sections
    document.getElementById('section-prochaines').classList.toggle('hidden', tab !== 'prochaines');
    document.getElementById('section-historique').classList.toggle('hidden', tab !== 'historique');
}

// ============================================
// 3. FONCTION AJAX - STATISTIQUES RÉSERVATIONS
// ============================================
function loadStatsReservationUser() {
    const xhrStats = new XMLHttpRequest();
    const url = '../../actions/player/reservation_Stats_user.php?' + params.toString();
    
    xhrStats.open('GET', url, true);
    xhrStats.withCredentials = true;
    
    xhrStats.onreadystatechange = function() {
        if (xhrStats.readyState === 4) {
            if (xhrStats.status === 200) {
                try {
                    const response = JSON.parse(xhrStats.responseText);
                    
                    if (response.success) {
                        updateReservationStatsUser(response.stats);
                        console.log('✅ Statistiques chargées avec succès');
                    } else {
                        console.error('❌ Erreur dans la réponse:', response.message);
                        showNotification('error', response.message || 'Erreur lors du chargement');
                    }
                } catch (e) {
                    console.error('❌ Erreur de parsing JSON:', e, xhrStats.responseText);
                    showNotification('error', 'Erreur lors du traitement des données');
                }
            } else {
                console.error('❌ Erreur HTTP:', xhrStats.status, xhrStats.statusText);
                showNotification('error', 'Erreur de connexion au serveur');
            }
        }
    };
    
    xhrStats.onerror = function() {
        console.error('❌ Erreur réseau lors de la requête AJAX');
        showNotification('error', 'Erreur réseau');
    };
    
    xhrStats.send();
}

// ============================================
// 4. MISE À JOUR DES STATISTIQUES
// ============================================
function updateReservationStatsUser(stats) {
    if (stats && typeof stats.prochaine_reservation !== 'undefined') {
        if (totalReservation) {
            totalReservation.textContent = stats.prochaine_reservation;
        }
    }
    
    if (stats && typeof stats.reservation_confirmee !== 'undefined') {
        if (completedReservations) {
            completedReservations.textContent = stats.reservation_confirmee;
        }
    }
    
    if (stats && typeof stats.reservation_en_attente !== 'undefined') {
        if (canceledReservations) {
            canceledReservations.textContent = stats.reservation_en_attente;
        }
    }
}

// ============================================
// 5. FONCTION AJAX - PROCHAINES RÉSERVATIONS
// ============================================
function fetchUpcomingReservations() {
    const xhrUpcoming = new XMLHttpRequest();
    const url = '../../actions/player/fetch_upcoming_reservations.php?' + params.toString();

    xhrUpcoming.open('GET', url, true);
    xhrUpcoming.withCredentials = true;
    
    xhrUpcoming.onreadystatechange = function() {
        if (xhrUpcoming.readyState === 4) {
            if (xhrUpcoming.status === 200) {
                try {
                    const response = JSON.parse(xhrUpcoming.responseText);
                    
                    if (response.success) {
                        // Mise à jour des prochaines réservations
                        updateFetchReservations(response.prochaines_reservations);
                        
                        // Mise à jour de l'historique si présent
                        if (response.historique) {
                            updateHistoriqueReservations(response.historique);
                        }
                        
                        console.log('✅ Réservations chargées avec succès');
                    } else {
                        console.error('❌ Erreur dans la réponse:', response.message);
                        showNotification('error', response.message || 'Erreur lors du chargement');
                    }
                } catch (e) {
                    console.error('❌ Erreur de parsing JSON:', e, xhrUpcoming.responseText);
                    showNotification('error', 'Erreur lors du traitement des données');
                }
            } else {
                console.error('❌ Erreur HTTP:', xhrUpcoming.status, xhrUpcoming.statusText);
                showNotification('error', 'Erreur de connexion au serveur');
            }
        }
    };
    
    xhrUpcoming.onerror = function() {
        console.error('❌ Erreur réseau lors de la requête AJAX');
        showNotification('error', 'Erreur réseau');
    };
    
    xhrUpcoming.send();
}

// ============================================
// 6. MISE À JOUR - PROCHAINES RÉSERVATIONS
// ============================================
function updateFetchReservations(data) {
    const sectionProchaines = document.getElementById('section-prochaines');
    
    if (!sectionProchaines) {
        console.error('❌ Section prochaines réservations introuvable');
        return;
    }
    
    // Si pas de données ou tableau vide
    if (!data || !data.data || data.data.length === 0) {
        sectionProchaines.innerHTML = `
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucune réservation</h3>
                <p class="text-gray-600 mb-6">Vous n'avez pas encore de réservations à venir</p>
                <a href="reserver.php" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition font-medium">
                    <i class="fas fa-plus"></i>
                    Réserver un terrain
                </a>
            </div>
        `;
        return;
    }
    
    // Construire le HTML des réservations
    let html = '';
    data.data.forEach(reservation => {
        html += buildReservationCard(reservation);
    });
    
    sectionProchaines.innerHTML = html;
    
    // Réattacher les événements aux boutons
    attachReservationActions();
}

// ============================================
// 7. CONSTRUCTION D'UNE CARTE DE RÉSERVATION
// ============================================
function buildReservationCard(r) {
    const statusClass = r.statut === 'confirmee' 
        ? 'bg-green-100 text-green-700' 
        : 'bg-orange-100 text-orange-700';
    
    const statusLabel = r.statut === 'confirmee' ? 'Confirmée' : 'En attente';
    
    const date = new Date(r.date_reservation);
    const dateFormatted = r.date_formatted || date.toLocaleDateString('fr-FR');
    
    let alertModification = '';
    if (r.jours_restants && r.jours_restants > 0) {
        alertModification = `
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                <p class="text-green-700 text-sm">
                    <i class="fas fa-info-circle mr-1"></i>
                    Modification possible (${r.jours_restants} jours restants)
                </p>
            </div>
        `;
    }
    
    const showModifyBtn = r.jours_restants && r.jours_restants > 0 && r.statut === 'confirmee';
    const showCancelBtn = r.statut !== 'annulee' && (r.jours_restants && r.jours_restants > 0 || !r.jours_restants);
    
    return `
        <div class="bg-white rounded-xl shadow-md p-6 fade-in">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-xl font-bold text-gray-900">${escapeHtml(r.nom_terrain)}</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                            ${statusLabel}
                        </span>
                    </div>

                    <div class="flex items-center gap-4 mb-4 text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar text-emerald-600"></i>
                            <span>${dateFormatted}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-clock text-emerald-600"></i>
                            <span>${r.heure_debut.substring(0, 5)} - ${r.heure_fin.substring(0, 5)}</span>
                        </div>
                        ${r.localisation ? `
                        <div class="flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-emerald-600"></i>
                            <span>${escapeHtml(r.localisation)}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <span class="text-gray-600 text-sm">Votre équipe:</span>
                            <p class="font-medium text-gray-900">${escapeHtml(r.nom_equipe_joueur || '-')}</p>
                        </div>
                        <div>
                            <span class="text-gray-600 text-sm">Équipe adverse:</span>
                            <p class="font-medium text-gray-900">${escapeHtml(r.nom_equipe_adverse || '—')}</p>
                        </div>
                        <div>
                            <span class="text-gray-600 text-sm">Prix total:</span>
                            <p class="font-bold text-emerald-600 text-lg">${parseFloat(r.prix_total).toFixed(2)} DH</p>
                        </div>
                    </div>

                    ${alertModification}
                </div>
            </div>

            <div class="flex gap-3 mt-4">
                ${showModifyBtn ? `
                <button onclick="editReservation(${r.id_reservation})" class="flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition font-medium flex items-center justify-center gap-2">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                ` : ''}
                ${showCancelBtn ? `
                <button onclick="cancelReservation(${r.id_reservation})" class="flex-1 bg-white border border-red-300 text-red-600 px-4 py-2 rounded-lg hover:bg-red-50 transition font-medium flex items-center justify-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </button>
                ` : ''}
            </div>
        </div>
    `;
}

// ============================================
// 8. MISE À JOUR - HISTORIQUE RÉSERVATIONS
// ============================================
function updateHistoriqueReservations(data) {
    const sectionHistorique = document.getElementById('section-historique');
    
    if (!sectionHistorique) {
        console.error('❌ Section historique introuvable');
        return;
    }
    
    if (!data || !data.data || data.data.length === 0) {
        sectionHistorique.innerHTML = `
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucun historique</h3>
                <p class="text-gray-600">Aucun match joué ou annulé pour le moment</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    data.data.forEach(reservation => {
        html += buildHistoriqueCard(reservation);
    });
    
    sectionHistorique.innerHTML = html;
}

// ============================================
// 9. CONSTRUCTION CARTE HISTORIQUE
// ============================================
function buildHistoriqueCard(r) {
    const statusClass = r.statut === 'terminee' 
        ? 'bg-gray-100 text-gray-700' 
        : 'bg-red-100 text-red-700';
    
    const statusLabel = r.statut === 'terminee' ? 'Terminée' : (r.statut === 'annulee' ? 'Annulée' : 'Passée');
    
    const date = new Date(r.date_reservation);
    const dateFormatted = r.date_formatted || date.toLocaleDateString('fr-FR');
    
    return `
        <div class="bg-white rounded-xl shadow-md p-6 fade-in">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-xl font-bold text-gray-900">${escapeHtml(r.nom_terrain)}</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                            ${statusLabel}
                        </span>
                    </div>

                    <div class="flex items-center gap-4 mb-4 text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar text-gray-500"></i>
                            <span>${dateFormatted}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-clock text-gray-500"></i>
                            <span>${r.heure_debut.substring(0, 5)} - ${r.heure_fin.substring(0, 5)}</span>
                        </div>
                        ${r.localisation ? `
                        <div class="flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-gray-500"></i>
                            <span>${escapeHtml(r.localisation)}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <span class="text-gray-600 text-sm">Votre équipe:</span>
                            <p class="font-medium text-gray-900">${escapeHtml(r.nom_equipe_joueur || '-')}</p>
                        </div>
                        <div>
                            <span class="text-gray-600 text-sm">Équipe adverse:</span>
                            <p class="font-medium text-gray-900">${escapeHtml(r.nom_equipe_adverse || '—')}</p>
                        </div>
                        <div>
                            <span class="text-gray-600 text-sm">Prix total:</span>
                            <p class="font-bold text-gray-600">${parseFloat(r.prix_total).toFixed(2)} DH</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// ============================================
// 10. ATTACHER LES ÉVÉNEMENTS AUX BOUTONS
// ============================================
function attachReservationActions() {
    // Les événements sont maintenant gérés via onclick dans le HTML
    // Pas besoin de réattacher manuellement
}

// ============================================
// 11. ANNULER UNE RÉSERVATION
// ============================================
function cancelReservation(id) {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        return;
    }
    
    fetch('../../actions/player/cancel_reservation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_reservation: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Réservation annulée avec succès');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('error', data.message || 'Erreur lors de l\'annulation');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('error', 'Erreur lors de l\'annulation');
    });
}

// ============================================
// 12. GESTION DU MODAL DE MODIFICATION
// ============================================

/**
 * Ouvrir le modal et charger les données de la réservation
 */
function editReservation(id) {
    currentReservationId = id;
    
    // Afficher le modal
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('show');
    
    // Charger les données via AJAX
    loadReservationData(id);
}

/**
 * Charger les données d'une réservation via AJAX
 */
function loadReservationData(id) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '../../actions/player/get_reservation.php?id=' + id, true);
    xhr.withCredentials = true;
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        originalReservationData = response.data;
                        displayReservationForm(response.data);
                    } else {
                        showNotification('error', response.message || 'Erreur lors du chargement');
                        closeEditModal();
                    }
                } catch (e) {
                    console.error('Erreur parsing:', e);
                    showNotification('error', 'Erreur lors du traitement des données');
                    closeEditModal();
                }
            } else {
                showNotification('error', 'Erreur de connexion au serveur');
                closeEditModal();
            }
        }
    };
    
    xhr.onerror = function() {
        showNotification('error', 'Erreur réseau');
        closeEditModal();
    };
    
    xhr.send();
}

/**
 * Afficher le formulaire avec les données de la réservation
 */
function displayReservationForm(data) {
    const content = document.getElementById('editModalContent');
    
    // Préparer les options d'équipes
    const equipesOptions = data.equipes_disponibles?.map(equipe => 
        `<option value="${equipe.id_equipe}" ${equipe.id_equipe == data.id_equipe ? 'selected' : ''}>
            ${escapeHtml(equipe.nom_equipe)}
        </option>`
    ).join('') || '';
    
    const equipesAdverseOptions = data.equipes_disponibles?.map(equipe => 
        `<option value="${equipe.id_equipe}" ${equipe.id_equipe == data.id_equipe_adverse ? 'selected' : ''}>
            ${escapeHtml(equipe.nom_equipe)}
        </option>`
    ).join('') || '';
    
    // Préparer les options de créneaux
    const creneauxOptions = data.creneaux_disponibles?.map(creneau => 
        `<option value="${creneau.id_creneaux}" ${creneau.id_creneaux == data.id_creneau ? 'selected' : ''}>
            ${creneau.heure_debut.substring(0, 5)} - ${creneau.heure_fin.substring(0, 5)}
        </option>`
    ).join('') || '';
    
    content.innerHTML = `
        <form id="editReservationForm" class="space-y-6">
            <!-- Informations du terrain (non modifiables) -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h3 class="font-semibold text-gray-900 mb-3">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    Informations du terrain
                </h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Terrain:</span>
                        <p class="font-medium text-gray-900">${escapeHtml(data.nom_terrain)}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Catégorie:</span>
                        <p class="font-medium text-gray-900">${escapeHtml(data.categorie_terrain)}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Localisation:</span>
                        <p class="font-medium text-gray-900">${escapeHtml(data.localisation || '-')}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Prix/heure:</span>
                        <p class="font-medium text-emerald-600">${parseFloat(data.prix_heure).toFixed(2)} DH</p>
                    </div>
                </div>
            </div>

            <!-- Date de réservation -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar mr-2 text-emerald-600"></i>
                    Date de réservation
                </label>
                <input 
                    type="date" 
                    id="edit_date_reservation" 
                    name="date_reservation"
                    value="${data.date_reservation_only}"
                    min="${new Date().toISOString().split('T')[0]}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    required
                    onchange="loadAvailableCreneaux(this.value)"
                >
            </div>

            <!-- Créneau horaire -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-clock mr-2 text-emerald-600"></i>
                    Créneau horaire
                </label>
                <select 
                    id="edit_id_creneau" 
                    name="id_creneau"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    required
                >
                    ${creneauxOptions || '<option value="">Aucun créneau disponible</option>'}
                </select>
            </div>

            <!-- Votre équipe -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-users mr-2 text-emerald-600"></i>
                    Votre équipe
                </label>
                <select 
                    id="edit_id_equipe" 
                    name="id_equipe"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    required
                >
                    ${equipesOptions || '<option value="">Aucune équipe disponible</option>'}
                </select>
            </div>

            <!-- Équipe adverse (optionnel) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-users mr-2 text-gray-600"></i>
                    Équipe adverse (optionnel)
                </label>
                <select 
                    id="edit_id_equipe_adverse" 
                    name="id_equipe_adverse"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                >
                    <option value="">Aucune équipe adverse</option>
                    ${equipesAdverseOptions}
                </select>
            </div>

            <!-- Prix estimé -->
            <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                <div class="flex items-center justify-between">
                    <span class="text-gray-700 font-medium">Prix total estimé:</span>
                    <span class="text-2xl font-bold text-emerald-600" id="edit_prix_total">
                        ${parseFloat(data.prix_total).toFixed(2)} DH
                    </span>
                </div>
            </div>

            <!-- Alerte de délai -->
            ${data.jours_restants > 0 ? `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-700 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        Vous pouvez modifier cette réservation jusqu'à <strong>${data.jours_restants} jours</strong> avant la date prévue.
                    </p>
                </div>
            ` : ''}
        </form>
    `;
}

/**
 * Charger les créneaux disponibles pour une date donnée
 */
function loadAvailableCreneaux(date) {
    if (!originalReservationData) return;
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `../../actions/player/get_creneaux.php?date=${date}&id_terrain=${originalReservationData.id_terrain}`, true);
    xhr.withCredentials = true;
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    updateCreneauxSelect(response.creneaux);
                }
            } catch (e) {
                console.error('Erreur:', e);
            }
        }
    };
    
    xhr.send();
}

/**
 * Mettre à jour le select des créneaux
 */
function updateCreneauxSelect(creneaux) {
    const select = document.getElementById('edit_id_creneau');
    
    if (!creneaux || creneaux.length === 0) {
        select.innerHTML = '<option value="">Aucun créneau disponible</option>';
        return;
    }
    
    select.innerHTML = creneaux.map(c => 
        `<option value="${c.id_creneaux}">
            ${c.heure_debut.substring(0, 5)} - ${c.heure_fin.substring(0, 5)}
        </option>`
    ).join('');
}

/**
 * Sauvegarder les modifications
 */
function saveReservation() {
    if (!currentReservationId) return;
    
    const form = document.getElementById('editReservationForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Désactiver le bouton de sauvegarde
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...';
    
    // Préparer les données
    const formData = {
        id_reservation: currentReservationId,
        date_reservation: document.getElementById('edit_date_reservation').value,
        id_creneau: document.getElementById('edit_id_creneau').value,
        id_equipe: document.getElementById('edit_id_equipe').value,
        id_equipe_adverse: document.getElementById('edit_id_equipe_adverse').value || null
    };
    
    // Envoyer via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../../actions/player/update_reservation.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.withCredentials = true;
    
    xhr.onload = function() {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Enregistrer les modifications';
        
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    showNotification('success', 'Réservation modifiée avec succès');
                    closeEditModal();
                    
                    // Recharger les données
                    setTimeout(() => {
                        fetchUpcomingReservations();
                        loadStatsReservationUser();
                    }, 500);
                } else {
                    showNotification('error', response.message || 'Erreur lors de la modification');
                }
            } catch (e) {
                console.error('Erreur:', e);
                showNotification('error', 'Erreur lors du traitement de la réponse');
            }
        } else {
            showNotification('error', 'Erreur de connexion au serveur');
        }
    };
    
    xhr.onerror = function() {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Enregistrer les modifications';
        showNotification('error', 'Erreur réseau');
    };
    
    xhr.send(JSON.stringify(formData));
}

/**
 * Fermer le modal
 */
function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('show');
    currentReservationId = null;
    originalReservationData = null;
}

// ============================================
// 13. SYSTÈME DE NOTIFICATIONS
// ============================================
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
        </div>`;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-10px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================
// 14. UTILITAIRES
// ============================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// 15. INITIALISATION AU CHARGEMENT
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Initialisation du système de réservations utilisateur');
    
    // Charger les statistiques
    loadStatsReservationUser();
    
    // Charger les prochaines réservations
    fetchUpcomingReservations();
    
    // Rafraîchissement automatique toutes les 30 secondes
    setInterval(() => {
        loadStatsReservationUser();
        fetchUpcomingReservations();
    }, 30000);
});

// ============================================
// 16. SYNCHRONISATION MULTI-ONGLETS
// ============================================
window.addEventListener('storage', function(event) {
    if (event.key === 'update_reservations') {
        console.log('🔄 Mise à jour depuis un autre onglet');
        loadStatsReservationUser();
        fetchUpcomingReservations();
    }
});

// ============================================
// 17. ÉVÉNEMENTS GLOBAUX DU MODAL
// ============================================

// Fermer le modal en cliquant en dehors
document.addEventListener('click', function(e) {
    const modal = document.getElementById('editModal');
    if (e.target === modal) {
        closeEditModal();
    }
});

// Fermer le modal avec la touche Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('editModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeEditModal();
        }
    }
});