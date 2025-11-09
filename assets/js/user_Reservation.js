// ============================================
// 1. VARIABLES GLOBALES
// ============================================
let totalReservation = document.getElementById('totalReservations');
let completedReservations = document.getElementById('ConfirmedReservations');
let canceledReservations = document.getElementById('canceledReservations');

// Créer l'objet XMLHttpRequest global
const xhr = new XMLHttpRequest();

// Récupérer les paramètres de l'URL si nécessaire
const params = new URLSearchParams(window.location.search);

// ============================================
// 2. GESTION DES TABS
// ============================================
const tabs = document.querySelectorAll('button[class*="flex-1"]');
tabs.forEach(tab => {
    tab.addEventListener('click', function() {
        tabs.forEach(t => {
            t.classList.remove('border-teal-600', 'text-gray-800');
            t.classList.add('text-gray-600');
        });
        this.classList.add('border-teal-600', 'text-gray-800');
        this.classList.remove('text-gray-600');
    });
});

// ============================================
// 3. GESTION DES BOUTONS D'ACTION
// ============================================
document.querySelectorAll('button').forEach(button => {
    button.addEventListener('click', function(e) {
        const text = this.textContent.trim();
        
        if (text.includes('Modifier')) {
            alert('Redirection vers la page de modification...');
        } else if (text.includes('Annuler')) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
                alert('Réservation annulée');
            }
        } 
    });
});

// ============================================
// 4. FONCTION AJAX - STATISTIQUES RÉSERVATIONS
// ============================================
function loadStatsReservationUser() {
    const xhrStats = new XMLHttpRequest();
    const url = '../../actions/admin-manager/player/reservation_Stats_user.php?' + params.toString();
    
    xhrStats.open('GET', url, true);
    xhrStats.withCredentials = true;
    
    xhrStats.onreadystatechange = function() {
        if (xhrStats.readyState === 4) {
            if (xhrStats.status === 200) {
                try {
                    const response = JSON.parse(xhrStats.responseText);
                    
                    if (response.success) {
                        // Mise à jour des statistiques
                        updateReservationStatsUser(response.stats);
                        console.log('✅ Statistiques chargées avec succès');
                    } else {
                        console.error('❌ Erreur dans la réponse:', response.message);
                        showNotification(response.message || 'Erreur lors du chargement', 'error');
                    }
                } catch (e) {
                    console.error('❌ Erreur de parsing JSON:', e, xhrStats.responseText);
                    showNotification('Erreur lors du traitement des données', 'error');
                }
            } else {
                console.error('❌ Erreur HTTP:', xhrStats.status, xhrStats.statusText);
                showNotification('Erreur de connexion au serveur', 'error');
            }
        }
    };
    
    xhrStats.onerror = function() {
        console.error('❌ Erreur réseau lors de la requête AJAX');
        showNotification('Erreur réseau', 'error');
    };
    
    xhrStats.send();
}

// ============================================
// 5. MISE À JOUR DES STATISTIQUES
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
// 6. FONCTION AJAX - PROCHAINES RÉSERVATIONS
// ============================================
function fetchUpcomingReservations() {
    const xhrUpcoming = new XMLHttpRequest();
    const url = '../../actions/admin-manager/player/fetch_upcoming_reservations.php?' + params.toString();

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
                        showNotification(response.message || 'Erreur lors du chargement', 'error');
                    }
                } catch (e) {
                    console.error('❌ Erreur de parsing JSON:', e, xhrUpcoming.responseText);
                    showNotification('Erreur lors du traitement des données', 'error');
                }
            } else {
                console.error('❌ Erreur HTTP:', xhrUpcoming.status, xhrUpcoming.statusText);
                showNotification('Erreur de connexion au serveur', 'error');
            }
        }
    };
    
    xhrUpcoming.onerror = function() {
        console.error('❌ Erreur réseau lors de la requête AJAX');
        showNotification('Erreur réseau', 'error');
    };
    
    xhrUpcoming.send();
}

// ============================================
// 7. MISE À JOUR - PROCHAINES RÉSERVATIONS
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
            <div class="col-span-full bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucune réservation</h3>
                <p class="text-gray-600 mb-6">Vous n'avez pas encore de réservations à venir</p>
                <a href="/views/terrains.php" class="bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-700 transition font-medium">
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
// 8. CONSTRUCTION D'UNE CARTE DE RÉSERVATION
// ============================================
function buildReservationCard(r) {
    // Classes de badge selon le statut
    const statusClass = r.statut === 'confirmee' 
        ? 'bg-green-100 text-green-700' 
        : 'bg-orange-100 text-orange-700';
    
    const statusLabel = r.statut === 'confirmee' ? 'Confirmée' : 'En attente';
    
    // Formatage de la date
    const date = new Date(r.date_reservation);
    const dateFormatted = date.toLocaleDateString('fr-FR');
    
    // Affichage de l'alerte de modification si possible
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
    
    return `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-semibold text-gray-800">${escapeHtml(r.nom_terrain)}</h3>
                <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                    ${statusLabel}
                </span>
            </div>

            <div class="flex items-center gap-4 mb-4 text-gray-600">
                <div class="flex items-center gap-2">
                    <i class="far fa-calendar"></i>
                    <span>${dateFormatted}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="far fa-clock"></i>
                    <span>${r.heure_debut.substring(0, 5)} - ${r.heure_fin.substring(0, 5)}</span>
                </div>
            </div>

            <div class="space-y-2 mb-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Votre équipe:</span>
                    <span class="font-medium text-gray-800">${escapeHtml(r.nom_equipe_joueur || '-')}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Équipe adverse:</span>
                    <span class="font-medium text-gray-800">${escapeHtml(r.nom_equipe_adverse || '—')}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Prix total:</span>
                    <span class="font-bold text-teal-600">${parseFloat(r.prix_total).toFixed(2)} DH</span>
                </div>
            </div>

            ${alertModification}

            <div class="flex gap-3">
                <button class="btn-modifier flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition font-medium flex items-center justify-center gap-2" data-id="${r.id_reservation}">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button class="btn-annuler flex-1 bg-white border border-red-300 text-red-600 px-4 py-2 rounded-lg hover:bg-red-50 transition font-medium flex items-center justify-center gap-2" data-id="${r.id_reservation}">
                    <i class="fas fa-trash-alt"></i> Annuler
                </button>
            </div>
        </div>
    `;
}

// ============================================
// 9. MISE À JOUR - HISTORIQUE RÉSERVATIONS
// ============================================
function updateHistoriqueReservations(data) {
    const sectionHistorique = document.getElementById('section-historique');
    
    if (!sectionHistorique) {
        console.error('❌ Section historique introuvable');
        return;
    }
    
    if (!data || !data.data || data.data.length === 0) {
        sectionHistorique.innerHTML = `
            <div class="col-span-full bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
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
// 10. CONSTRUCTION CARTE HISTORIQUE
// ============================================
function buildHistoriqueCard(r) {
    const statusClass = r.statut === 'terminee' 
        ? 'bg-gray-100 text-gray-700' 
        : 'bg-red-100 text-red-700';
    
    const statusLabel = r.statut === 'terminee' ? 'Terminée' : 'Annulée';
    
    const date = new Date(r.date_reservation);
    const dateFormatted = date.toLocaleDateString('fr-FR');
    
    return `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-semibold text-gray-800">${escapeHtml(r.nom_terrain)}</h3>
                <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                    ${statusLabel}
                </span>
            </div>

            <div class="flex items-center gap-4 mb-4 text-gray-600">
                <div class="flex items-center gap-2">
                    <i class="far fa-calendar"></i>
                    <span>${dateFormatted}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="far fa-clock"></i>
                    <span>${r.heure_debut.substring(0, 5)} - ${r.heure_fin.substring(0, 5)}</span>
                </div>
            </div>

            <div class="space-y-2 mb-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Votre équipe:</span>
                    <span class="font-medium text-gray-800">${escapeHtml(r.nom_equipe_joueur)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Équipe adverse:</span>
                    <span class="font-medium text-gray-800">${escapeHtml(r.nom_equipe_adverse || '—')}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Prix total:</span>
                    <span class="font-bold text-teal-600">${parseFloat(r.prix_total).toFixed(2)} DH</span>
                </div>
            </div>
        </div>
    `;
}

// ============================================
// 11. ATTACHER LES ÉVÉNEMENTS AUX BOUTONS
// ============================================
function attachReservationActions() {
    // Boutons Modifier
    document.querySelectorAll('.btn-modifier').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            modifierReservation(id);
        });
    });
    
    // Boutons Annuler
    document.querySelectorAll('.btn-annuler').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            annulerReservation(id);
        });
    });
}

// ============================================
// 12. ACTIONS SUR RÉSERVATIONS
// ============================================
function modifierReservation(id) {
    console.log('Modifier réservation:', id);
    // Redirection vers la page de modification
    window.location.href = `/views/player/modifier-reservation.php?id=${id}`;
}

function annulerReservation(id) {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        return;
    }
    
    const xhrCancel = new XMLHttpRequest();
    xhrCancel.open('POST', '../../actions/admin-manager/player/cancel_reservation.php', true);
    xhrCancel.setRequestHeader('Content-Type', 'application/json');
    xhrCancel.withCredentials = true;
    
    xhrCancel.onload = function() {
        if (xhrCancel.status === 200) {
            try {
                const response = JSON.parse(xhrCancel.responseText);
                if (response.success) {
                    showNotification('Réservation annulée avec succès', 'success');
                    // Recharger les données
                    fetchUpcomingReservations();
                    loadStatsReservationUser();
                } else {
                    showNotification(response.message || 'Erreur lors de l\'annulation', 'error');
                }
            } catch (e) {
                showNotification('Erreur lors de l\'annulation', 'error');
            }
        }
    };
    
    xhrCancel.send(JSON.stringify({ id_reservation: id }));
}

// ============================================
// 13. SYSTÈME DE NOTIFICATIONS
// ============================================
function showNotification(message, type = 'info') {
    // Chercher ou créer l'élément de notification
    let notification = document.getElementById('notification');
    
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        document.body.appendChild(notification);
    }
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 text-white ${colors[type]}`;
    notification.textContent = message;
    notification.classList.remove('hidden');
    
    setTimeout(() => {
        notification.classList.add('hidden');
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
    }, 3000);
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

        //      function annulerReservation(id) {
        //     if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        //         fetch('../../actions/joueur/annuler_reservation.php', {
        //             method: 'POST',
        //             headers: {'Content-Type': 'application/json'},
        //             body: JSON.stringify({id_reservation: id})
        //         })
        //         .then(res => res.json())
        //         .then(data => {
        //             if (data.success) {
        //                 alert('Réservation annulée avec succès');
        //                 location.reload();
        //             } else {
        //                 alert('Erreur: ' + data.message);
        //             }
        //         });
        //     }
        // }

                
    
        