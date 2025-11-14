(() => {
    'use strict';

    let currentTab = 'tournaments';
    let requests = [];
    
    // Sync configuration
    const SYNC_ENABLED = typeof window.SyncManager !== 'undefined';

    // Switch between tabs
    window.switchTab = function(tab) {
        currentTab = tab;
        
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'border-b-2', 'border-emerald-600', 'text-emerald-600');
            btn.classList.add('text-gray-600');
        });
        
        const activeTab = document.getElementById(`tab-${tab}`);
        if (activeTab) {
            activeTab.classList.add('active', 'border-b-2', 'border-emerald-600', 'text-emerald-600');
            activeTab.classList.remove('text-gray-600');
        }
        
        // Show/hide sections
        const tournamentsContainer = document.getElementById('tournoisContainer');
        const requestsContainer = document.getElementById('requestsContainer');
        const filtersSection = document.getElementById('filtersSection');
        const filtersRequestsSection = document.getElementById('filtersRequestsSection');
        
        if (tab === 'tournaments') {
            if (tournamentsContainer) tournamentsContainer.classList.remove('hidden');
            if (requestsContainer) requestsContainer.classList.add('hidden');
            if (filtersSection) filtersSection.classList.remove('hidden');
            if (filtersRequestsSection) filtersRequestsSection.classList.add('hidden');
        } else {
            if (tournamentsContainer) tournamentsContainer.classList.add('hidden');
            if (requestsContainer) requestsContainer.classList.remove('hidden');
            if (filtersSection) filtersSection.classList.add('hidden');
            if (filtersRequestsSection) filtersRequestsSection.classList.remove('hidden');
            loadRequests();
        }
    };

    // Load tournament requests
    function loadRequests() {
        const container = document.getElementById('requestsContainer');
        const loader = document.getElementById('loader');
        if (!container) return;

        if (loader) loader.classList.remove('hidden');

        const search = document.getElementById('searchRequestsInput')?.value || '';
        const statut = document.getElementById('filterRequestsStatut')?.value || '';

        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (statut) params.append('statut', statut);

        fetch(`../../actions/admin-manager/tournament/get_requests.php?${params.toString()}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.requests) {
                const hadRequests = requests.length > 0;
                requests = data.requests;
                window.lastRequestsLoadTime = Date.now();
                renderRequests(requests, !hadRequests);
                updateRequestsBadge();
            } else {
                if (requests.length > 0) {
                    requests = [];
                    container.innerHTML = '<div class="text-center py-12 text-gray-500">Aucune demande trouvée</div>';
                } else {
                    container.innerHTML = '<div class="text-center py-12 text-gray-500">Aucune demande trouvée</div>';
                }
                updateRequestsBadge();
            }
        })
        .catch(error => {
            console.error('Error loading requests:', error);
            container.innerHTML = '<div class="text-center py-12 text-red-500">Erreur lors du chargement des demandes</div>';
        })
        .finally(() => {
            if (loader) loader.classList.add('hidden');
        });
    }

    // Render requests list with smart update
    function renderRequests(requestsList, forceFullRefresh = false) {
        const container = document.getElementById('requestsContainer');
        if (!container) return;

        if (!requestsList || requestsList.length === 0) {
            container.innerHTML = '<div class="text-center py-12 text-gray-500"><i class="fas fa-inbox text-5xl mb-4 text-gray-300"></i><p>Aucune demande en attente</p></div>';
            return;
        }

        // If force refresh, replace entire content
        if (forceFullRefresh) {
            container.innerHTML = requestsList.map(request => buildRequestCard(request)).join('');
        } else {
            // Smart update: only update changed/new requests
            const existingIds = new Set();
            container.querySelectorAll('[data-request-id]').forEach(card => {
                existingIds.add(parseInt(card.dataset.requestId));
            });

            const newIds = new Set(requestsList.map(r => r.id_demande));
            
            // Remove deleted requests
            existingIds.forEach(id => {
                if (!newIds.has(id)) {
                    const card = container.querySelector(`[data-request-id="${id}"]`);
                    if (card && card.parentNode) {
                        card.parentNode.removeChild(card);
                    }
                }
            });

            // Update or add requests
            requestsList.forEach(request => {
                const existingCard = container.querySelector(`[data-request-id="${request.id_demande}"]`);
                const newCardHtml = buildRequestCard(request);
                
                if (existingCard) {
                    // Update existing card
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = newCardHtml;
                    const newCard = tempDiv.firstElementChild;
                    existingCard.parentNode.replaceChild(newCard, existingCard);
                } else {
                    // Add new card
                    if (container.children.length === 0 || container.querySelector('.text-center')) {
                        // Container is empty or has empty state
                        container.innerHTML = newCardHtml;
                    } else {
                        container.insertAdjacentHTML('beforeend', newCardHtml);
                    }
                }
            });
        }
        
        // Attach event listeners
        container.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.target.closest('[data-request-id]')?.dataset.requestId);
                if (id) approveRequest(id);
            });
        });

        container.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.target.closest('[data-request-id]')?.dataset.requestId);
                if (id) showRejectModal(id);
            });
        });

        container.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.target.closest('[data-request-id]')?.dataset.requestId);
                if (id) showRequestDetails(id);
            });
        });
    }

    // Build request card HTML
    function buildRequestCard(request) {
        const statutColors = {
            'en_attente': 'bg-yellow-100 text-yellow-800',
            'approuvee': 'bg-green-100 text-green-800',
            'rejetee': 'bg-red-100 text-red-800'
        };
        const statutLabels = {
            'en_attente': 'En attente',
            'approuvee': 'Approuvée',
            'rejetee': 'Rejetée'
        };
        
        const dateDemande = request.date_demande ? new Date(request.date_demande).toLocaleDateString('fr-FR') : '';
        const statut = request.statut || 'en_attente';
        const canAction = statut === 'en_attente';

        return `
            <div data-request-id="${request.id_demande}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">${escapeHtml(request.nom_tournoi || 'Sans nom')}</h3>
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span><i class="fas fa-user mr-1"></i>${escapeHtml((request.organisateur_nom || '') + ' ' + (request.organisateur_prenom || ''))}</span>
                            <span><i class="fas fa-map-marker-alt mr-1"></i>${escapeHtml(request.terrain_nom || 'Terrain non spécifié')}</span>
                            <span><i class="fas fa-calendar mr-1"></i>${dateDemande}</span>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${statutColors[statut] || statutColors['en_attente']}">
                        ${statutLabels[statut] || 'En attente'}
                    </span>
                </div>
                
                ${request.description ? `<p class="text-gray-600 text-sm mb-4 line-clamp-2">${escapeHtml(request.description)}</p>` : ''}
                
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="text-sm text-gray-500">
                        <span><i class="fas fa-calendar-alt mr-1"></i>${escapeHtml(request.date_debut || '')} - ${escapeHtml(request.date_fin || '')}</span>
                        <span class="ml-4"><i class="fas fa-users mr-1"></i>${request.nb_equipes || 0} équipes</span>
                    </div>
                    <div class="flex gap-2">
                        <button class="view-btn px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold transition">
                            <i class="fas fa-eye mr-1"></i>Voir
                        </button>
                        ${canAction ? `
                            <button class="approve-btn px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-sm font-semibold transition">
                                <i class="fas fa-check mr-1"></i>Approuver
                            </button>
                            <button class="reject-btn px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 text-sm font-semibold transition">
                                <i class="fas fa-times mr-1"></i>Rejeter
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    // Approve request
    function approveRequest(id) {
        if (!confirm('Êtes-vous sûr de vouloir approuver cette demande ? Le tournoi sera créé automatiquement.')) {
            return;
        }

        fetch('../../actions/admin-manager/tournament/approve_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_demande: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Demande approuvée avec succès', 'success');
                // Trigger requests update
                triggerRequestsUpdate();
                // Also trigger tournaments update since a tournament was created
                triggerTournamentsUpdate();
            } else {
                showNotification(data.message || 'Erreur lors de l\'approbation', 'error');
            }
        })
        .catch(error => {
            console.error('Error approving request:', error);
            showNotification('Erreur de connexion au serveur', 'error');
        });
    }

    // Show reject modal
    function showRejectModal(id) {
        const commentaire = prompt('Raison du rejet (optionnel):');
        if (commentaire === null) return; // User cancelled

        fetch('../../actions/admin-manager/tournament/reject_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_demande: id, commentaire: commentaire || '' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Demande rejetée avec succès', 'success');
                // Trigger requests update to notify all tabs/browsers
                triggerRequestsUpdate();
            } else {
                showNotification(data.message || 'Erreur lors du rejet', 'error');
            }
        })
        .catch(error => {
            console.error('Error rejecting request:', error);
            showNotification('Erreur de connexion au serveur', 'error');
        });
    }

    // Show request details
    function showRequestDetails(id) {
        const request = requests.find(r => r.id_demande == id);
        if (!request) return;

        const modal = document.getElementById('requestModal');
        const content = document.getElementById('requestModalContent');
        if (!modal || !content) return;

        content.innerHTML = `
            <div class="space-y-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">${escapeHtml(request.nom_tournoi || 'Sans nom')}</h3>
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span><i class="fas fa-user mr-1"></i>${escapeHtml((request.organisateur_nom || '') + ' ' + (request.organisateur_prenom || ''))}</span>
                        <span><i class="fas fa-envelope mr-1"></i>${escapeHtml(request.email_organisateur || '')}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase mb-1">Date de début</div>
                        <div class="font-semibold">${escapeHtml(request.date_debut || '')}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase mb-1">Date de fin</div>
                        <div class="font-semibold">${escapeHtml(request.date_fin || '')}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase mb-1">Nombre d'équipes</div>
                        <div class="font-semibold">${request.nb_equipes || 0}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-xs text-gray-500 uppercase mb-1">Prix d'inscription</div>
                        <div class="font-semibold">${request.prix_inscription ? request.prix_inscription + ' DH' : 'Gratuit'}</div>
                    </div>
                </div>

                <div>
                    <div class="text-xs text-gray-500 uppercase mb-2">Terrain</div>
                    <div class="font-semibold">${escapeHtml(request.terrain_nom || 'Terrain non spécifié')}</div>
                    ${request.terrain_ville ? `<div class="text-sm text-gray-600">${escapeHtml(request.terrain_ville)}</div>` : ''}
                </div>

                ${request.description ? `
                <div>
                    <div class="text-xs text-gray-500 uppercase mb-2">Description</div>
                    <div class="text-gray-700 whitespace-pre-line">${escapeHtml(request.description)}</div>
                </div>
                ` : ''}

                ${request.regles ? `
                <div>
                    <div class="text-xs text-gray-500 uppercase mb-2">Règles</div>
                    <div class="text-gray-700 whitespace-pre-line">${escapeHtml(request.regles)}</div>
                </div>
                ` : ''}

                ${request.commentaire_reponse ? `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="text-xs text-red-600 uppercase mb-2">Commentaire du responsable</div>
                    <div class="text-red-800">${escapeHtml(request.commentaire_reponse)}</div>
                </div>
                ` : ''}

                ${request.statut === 'en_attente' ? `
                <div class="flex gap-3 pt-4 border-t">
                    <button onclick="window.approveRequestFromModal(${request.id_demande})" 
                            class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-check mr-2"></i>Approuver
                    </button>
                    <button onclick="window.rejectRequestFromModal(${request.id_demande})" 
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-times mr-2"></i>Rejeter
                    </button>
                </div>
                ` : ''}
            </div>
        `;

        modal.classList.remove('hidden');
    }

    // Close request modal
    window.closeRequestModal = function() {
        const modal = document.getElementById('requestModal');
        if (modal) modal.classList.add('hidden');
    };

    // Approve/reject from modal
    window.approveRequestFromModal = function(id) {
        closeRequestModal();
        approveRequest(id);
    };

    window.rejectRequestFromModal = function(id) {
        closeRequestModal();
        showRejectModal(id);
    };

    // Update requests badge
    function updateRequestsBadge() {
        const badge = document.getElementById('requestsBadge');
        if (!badge) return;
        
        const pendingCount = requests.filter(r => r.statut === 'en_attente').length;
        if (pendingCount > 0) {
            badge.textContent = pendingCount;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.getElementById('notification');
        if (!notification) return;

        const colors = {
            success: 'bg-emerald-600',
            error: 'bg-red-600',
            warning: 'bg-yellow-600',
            info: 'bg-blue-600'
        };

        notification.className = `fixed top-6 right-6 px-6 py-4 rounded-xl shadow-2xl z-50 backdrop-blur-sm animate-slide-in ${colors[type] || colors.info} text-white`;
        notification.textContent = message;
        notification.classList.remove('hidden');

        setTimeout(() => {
            notification.classList.add('hidden');
        }, 4000);
    }

    // Event listeners for filters
    const searchInput = document.getElementById('searchRequestsInput');
    const filterStatut = document.getElementById('filterRequestsStatut');

    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (currentTab === 'requests') loadRequests();
            }, 300);
        });
    }

    if (filterStatut) {
        filterStatut.addEventListener('change', () => {
            if (currentTab === 'requests') loadRequests();
        });
    }

    /**
     * Setup universal sync for requests
     */
    function setupRequestsSync() {
        if (!SYNC_ENABLED) {
            console.warn('[Requests] SyncManager not available');
            return;
        }

        // Register requests channel
        window.SyncManager.register('tournament_requests', (data) => {
            console.log('[Requests] Sync update received:', data);
            if (currentTab === 'requests') {
                loadRequests();
            }
        }, {
            pollInterval: 2000, // Poll every 2 seconds (calmer for admin)
            storageKey: 'sync_tournament_requests_update',
            checkEndpoint: '../../actions/admin-manager/tournament/get_requests.php'
        });

        // Also listen to tournaments channel (when request is approved, tournament is created)
        window.SyncManager.register('tournaments_from_requests', (data) => {
            console.log('[Requests] Tournament update received:', data);
            if (currentTab === 'requests') {
                loadRequests(); // Reload to update request status
            }
        }, {
            pollInterval: 2000, // Poll every 2 seconds
            storageKey: 'sync_tournaments_update'
        });

        console.log('[Requests] Universal sync enabled');
    }

    /**
     * Trigger requests update (notify all tabs/browsers)
     */
    function triggerRequestsUpdate() {
        if (SYNC_ENABLED) {
            window.SyncManager.notify('tournament_requests', {
                source: 'admin_action',
                action: 'update_requests'
            });
        }
        // Always refresh locally
        loadRequests();
    }

    /**
     * Trigger tournaments update (when request is approved)
     */
    function triggerTournamentsUpdate() {
        if (SYNC_ENABLED) {
            window.SyncManager.notify('tournaments', {
                source: 'admin_action',
                action: 'approve_request'
            });
        }
    }
    
    // Initialize sync
    setupRequestsSync();
    
    // Initial load if on requests tab
    if (currentTab === 'requests') {
        loadRequests();
    }
})();
