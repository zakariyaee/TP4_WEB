// ============================================
// SCRIPT COMPLET - GESTION DES RÉSERVATIONS UTILISATEUR
// VERSION CORRIGÉE AVEC displayReservationForm
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

const params = new URLSearchParams(window.location.search);

// ============================================
// 2. GESTION DES TABS (ONGLETS)
// ============================================
function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');
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
        if (xhrStats.readyState === 4 && xhrStats.status === 200) {
            try {
                const response = JSON.parse(xhrStats.responseText);
                if (response.success) {
                    updateReservationStatsUser(response.stats);
                    console.log('✅ Statistiques chargées avec succès');
                }
            } catch (e) {
                console.error('❌ Erreur parsing stats:', e);
            }
        }
    };
    
    xhrStats.send();
}

// ============================================
// 4. MISE À JOUR DES STATISTIQUES
// ============================================
function updateReservationStatsUser(stats) {
    if (totalReservation && stats.prochaine_reservation !== undefined) {
        totalReservation.textContent = stats.prochaine_reservation;
    }
    if (completedReservations && stats.reservation_confirmee !== undefined) {
        completedReservations.textContent = stats.reservation_confirmee;
    }
    if (canceledReservations && stats.reservation_en_attente !== undefined) {
        canceledReservations.textContent = stats.reservation_en_attente;
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
        if (xhrUpcoming.readyState === 4 && xhrUpcoming.status === 200) {
            try {
                const response = JSON.parse(xhrUpcoming.responseText);
                if (response.success) {
                    updateFetchReservations(response.prochaines_reservations);
                    if (response.historique) {
                        updateHistoriqueReservations(response.historique);
                    }
                    console.log('✅ Réservations chargées avec succès');
                }
            } catch (e) {
                console.error('❌ Erreur parsing réservations:', e);
            }
        }
    };
    
    xhrUpcoming.send();
}

// ============================================
// 6. MISE À JOUR - PROCHAINES RÉSERVATIONS
// ============================================
function updateFetchReservations(data) {
    const sectionProchaines = document.getElementById('section-prochaines');
    if (!sectionProchaines) return;
    
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
    
    let html = '';
    data.data.forEach(reservation => {
        html += buildReservationCard(reservation);
    });
    
    sectionProchaines.innerHTML = html;
}

// ============================================
// 7. CONSTRUCTION D'UNE CARTE DE RÉSERVATION
// ============================================
function buildReservationCard(r) {
    const statusClass = r.statut === 'confirmee' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700';
    const statusLabel = r.statut === 'confirmee' ? 'Confirmée' : 'Annulée';
    const date = new Date(r.date_reservation);
    const dateFormatted = r.date_formatted || date.toLocaleDateString('fr-FR');
    
    const joursRestants = parseInt(r.jours_restants) || 0;
    const canModify = joursRestants > 2 && r.statut === 'confirmee';
    const canCancel = joursRestants > 2 && r.statut === 'confirmee';
    
    console.log(`Réservation ${r.id_reservation}: jours=${joursRestants}, canModify=${canModify}`);
    
    let alertModification = '';
    if (joursRestants >= 0 && r.statut === 'confirmee') {
        if (joursRestants > 2) {
            alertModification = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                    <p class="text-green-700 text-sm">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Modification possible</strong> - Il reste ${joursRestants} jour${joursRestants > 1 ? 's' : ''} avant la réservation
                    </p>
                </div>
            `;
        } else {
            alertModification = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                    <p class="text-red-700 text-sm font-medium">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Modification bloquée</strong> - Moins de 48 heures avant la réservation (${joursRestants} jour${joursRestants > 1 ? 's' : ''} restant${joursRestants > 1 ? 's' : ''})
                    </p>
                </div>
            `;
        }
    }
    
    return `
        <div class="bg-white rounded-xl shadow-md p-6 fade-in">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-xl font-bold text-gray-900">${escapeHtml(r.nom_terrain)}</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">${statusLabel}</span>
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
                        </div>` : ''}
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
                ${canModify ? `
                <button onclick="editReservation(${r.id_reservation})" class="flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition font-medium flex items-center justify-center gap-2">
                    <i class="fas fa-edit"></i> Modifier
                </button>` : ''}
                ${canCancel ? `
                <button onclick="cancelReservation(${r.id_reservation})" class="flex-1 bg-white border border-red-300 text-red-600 px-4 py-2 rounded-lg hover:bg-red-50 transition font-medium flex items-center justify-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </button>` : ''}
                ${!canModify && !canCancel && r.statut === 'confirmee' ? `
                <div class="flex-1 bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-center font-medium cursor-not-allowed flex items-center justify-center gap-2">
                    <i class="fas fa-lock"></i> Modifications bloquées (moins de 48h)
                </div>` : ''}
            </div>
        </div>
    `;
}

// ============================================
// 8. MISE À JOUR - HISTORIQUE RÉSERVATIONS
// ============================================
function updateHistoriqueReservations(data) {
    const sectionHistorique = document.getElementById('section-historique');
    if (!sectionHistorique) return;
    
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
    const statusClass = r.statut === 'terminee' ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-700';
    const statusLabel = r.statut === 'terminee' ? 'Terminée' : (r.statut === 'annulee' ? 'Annulée' : 'Passée');
    const date = new Date(r.date_reservation);
    const dateFormatted = r.date_formatted || date.toLocaleDateString('fr-FR');
    
    return `
        <div class="bg-white rounded-xl shadow-md p-6 fade-in">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-xl font-bold text-gray-900">${escapeHtml(r.nom_terrain)}</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">${statusLabel}</span>
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
                        </div>` : ''}
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
// 10. ANNULER UNE RÉSERVATION
// ============================================
function cancelReservation(id) {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) return;
    
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
// 11. GESTION DU MODAL DE MODIFICATION
// ============================================
function editReservation(id) {
    currentReservationId = id;
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('show');
    loadReservationData(id);
}

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
    
    xhr.send();
}

// ============================================
// 12. AFFICHER LE FORMULAIRE DE MODIFICATION
// ============================================
function displayReservationForm(data) {
    const content = document.getElementById('editModalContent');
    
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
    
    const creneauxOptions = data.creneaux_disponibles?.map(creneau => 
        `<option value="${creneau.id_creneaux}" ${creneau.id_creneaux == data.id_creneau ? 'selected' : ''}>
            ${creneau.heure_debut.substring(0, 5)} - ${creneau.heure_fin.substring(0, 5)}
        </option>`
    ).join('') || '';
    
    content.innerHTML = `
        <form id="editReservationForm" class="space-y-6">
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

            <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                <div class="flex items-center justify-between">
                    <span class="text-gray-700 font-medium">Prix total estimé:</span>
                    <span class="text-2xl font-bold text-emerald-600" id="edit_prix_total">
                        ${parseFloat(data.prix_total).toFixed(2)} DH
                    </span>
                </div>
            </div>

            ${data.jours_restants > 2 ? `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-700 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        Vous pouvez modifier cette réservation. Il reste <strong>${data.jours_restants} jour${data.jours_restants > 1 ? 's' : ''}</strong> avant la date prévue.
                    </p>
                </div>
            ` : `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <p class="text-red-700 text-sm font-medium">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Attention:</strong> Il reste seulement ${data.jours_restants} jour${data.jours_restants > 1 ? 's' : ''} avant la réservation.
                    </p>
                </div>
            `}
        </form>
    `;
}

// ============================================
// 13. CHARGER CRÉNEAUX DISPONIBLES
// ============================================
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

// ============================================
// 14. SAUVEGARDER LES MODIFICATIONS
// ============================================
function saveReservation() {
    if (!currentReservationId) return;
    
    const form = document.getElementById('editReservationForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...';
    
    const formData = {
        id_reservation: currentReservationId,
        date_reservation: document.getElementById('edit_date_reservation').value,
        id_creneau: document.getElementById('edit_id_creneau').value,
        id_equipe: document.getElementById('edit_id_equipe').value,
        id_equipe_adverse: document.getElementById('edit_id_equipe_adverse').value || null
    };
    
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
                    localStorage.setItem('update_reservationsPlayer', 'true');
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
    
    xhr.send(JSON.stringify(formData));
}

// ============================================
// 15. FERMER LE MODAL
// ============================================
function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('show');
    currentReservationId = null;
    originalReservationData = null;
}

// ============================================
// 16. SYSTÈME DE NOTIFICATIONS
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
// 17. UTILITAIRES
// ============================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// 18. INITIALISATION AU CHARGEMENT
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
// 19. SYNCHRONISATION MULTI-ONGLETS
// ============================================
window.addEventListener('storage', function(event) {
    if (event.key === 'update_reservations' || event.key === 'update_reservationsPlayer') {
        console.log('🔄 Mise à jour depuis un autre onglet');
        loadStatsReservationUser();
        fetchUpcomingReservations();
    }
});

// ============================================
// 20. ÉVÉNEMENTS GLOBAUX DU MODAL
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