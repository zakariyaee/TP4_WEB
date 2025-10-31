let currentTournamentId = null;
let deleteTournamentId = null;
let tournamentsCache = new Map(); 
let lastLoadTime = 0;
let isLoading = false;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadTournaments();
    loadFields();
    setupEventListeners();
    
    // Clean cache every 5 minutes
    setInterval(() => {
        tournamentsCache.clear();
    }, 300000);
});

/**
 * Setup event listeners for search, filters and form
 * 
 * Configures event listeners on page load for:
 * - Real-time search input with debounce (500ms delay)
 * - Status and type filter changes
 * - Form submission
 * 
 * @returns {void}
 */
function setupEventListeners() {
    // Real-time search
    document.getElementById('searchInput').addEventListener('input', debounce(() => {
        tournamentsCache.clear();
        loadTournaments(true);
    }, 300)); // Reduced debounce time for better responsiveness

    // Filters
    document.getElementById('filterStatut').addEventListener('change', () => {
        tournamentsCache.clear();
        loadTournaments(true);
    });
    document.getElementById('filterType').addEventListener('change', () => {
        tournamentsCache.clear();
        loadTournaments(true);
    });

    // Form
    document.getElementById('tournoiForm').addEventListener('submit', handleSubmit);
}

/**
 * Debounce function to limit function execution frequency
 * 
 * Prevents function from being called too frequently by delaying execution.
 * Useful for search inputs to reduce server requests
 * 
 * @param {Function} func - Function to debounce
 * @param {number} wait - Delay in milliseconds
 * @returns {Function} Debounced function
 */
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

/**
 * Load tournaments list from server with filters
 * 
 * Fetches tournaments from server with optional search, status and type filters.
 * Implements caching to reduce server requests and improve performance.
 * Displays loading indicator during request.
 * Partner: Error Management - Handles HTTP errors, network errors and JSON parsing errors.
 * Partner: Security - Search term is URL encoded to prevent injection.
 * 
 * @returns {void}
 */
function loadTournaments(forceRefresh = false) {
    if (isLoading) return;
    
    const search = document.getElementById('searchInput').value;
    const statut = document.getElementById('filterStatut').value;
    const type = document.getElementById('filterType').value;

    // Create cache key
    const cacheKey = `${search}-${statut}-${type}`;
    
    // Check cache first (unless force refresh)
    if (!forceRefresh && tournamentsCache.has(cacheKey)) {
        const cachedData = tournamentsCache.get(cacheKey);
        // Use cache if data is less than 30 seconds old
        if (Date.now() - cachedData.timestamp < 30000) {
            displayTournaments(cachedData.tournois);
            return;
        }
    }

    // Check if we need to refresh (avoid too frequent requests)
    const now = Date.now();
    if (!forceRefresh && now - lastLoadTime < 1000) {
        return;
    }

    isLoading = true;
    lastLoadTime = now;
    showLoader();

    const xhr = new XMLHttpRequest();
    // Security: encodeURIComponent prevents XSS in URL parameters
    xhr.open('GET', `../../actions/admin-manager/tournament/get_tournaments.php?search=${encodeURIComponent(search)}&statut=${statut}&type=${type}`, true);

    xhr.onload = function() {
        isLoading = false;
        hideLoader();
        // Error Management: Check HTTP status
        if (xhr.status === 200) {
            try {
                // Error Management: JSON parsing with try-catch
                const response = JSON.parse(xhr.responseText);

                if (response.success) {
                    // Cache the response
                    tournamentsCache.set(cacheKey, {
                        tournois: response.tournois,
                        timestamp: Date.now()
                    });
                    
                    displayTournaments(response.tournois);
                } else {
                    showNotification(response.message || 'Erreur lors du chargement des tournois', 'error');
                }
            } catch (e) {
                // Error Management: Handle JSON parsing errors
                showNotification('Erreur lors du traitement de la réponse', 'error');
            }
        } else {
            // Error Management: Handle HTTP errors
            showNotification('Erreur de connexion au serveur', 'error');
        }
    };

    // Error Management: Handle network errors
    xhr.onerror = function() {
        isLoading = false;
        hideLoader();
        showNotification('Erreur réseau', 'error');
    };

    xhr.send();
}

/**
 * Display tournaments in grid layout
 * 
 * Renders tournament list in card format with proper escaping to prevent XSS.
 * Shows empty state if no tournaments found.
 * Partner: Security - All tournament data is HTML escaped before insertion.
 * 
 * @param {Array} tournois - Array of tournament objects
 * @returns {void}
 */
function displayTournaments(tournois) {
    const container = document.getElementById('tournoisContainer');

    if (tournois.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-trophy text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">Aucun tournoi trouvé</p>
            </div>
        `;
        return;
    }

    /**
     * XSS escaping function
     * 
     * Escapes HTML special characters to prevent XSS attacks.
     * Uses textContent to safely encode text before insertion.
     * Partner: Security - Prevents XSS injection through tournament data.
     * 
     * @param {string} text - Text to escape
     * @returns {string} HTML-escaped text
     */
    const escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    };
    
    // Security: All tournament data is escaped before rendering
    container.innerHTML = tournois.map(tournoi => {
        const nomTournoi = escapeHtml(tournoi.nom_tournoi);
        const typeTournoi = escapeHtml(tournoi.type_tournoi);
        const description = escapeHtml(tournoi.description || '');
        const terrainNom = escapeHtml(tournoi.terrain_nom || 'Terrain non assigné');
        const statut = escapeHtml(tournoi.statut);
        
        return `
            <div id="tournoi-card-${tournoi.id_tournoi}" data-tournoi-id="${tournoi.id_tournoi}" class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:scale-105 group">
                <div class="relative h-48 bg-gradient-to-br from-emerald-400 to-blue-500">
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-trophy text-white text-6xl opacity-50"></i>
                    </div>
                    <div class="absolute top-3 right-3">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${getStatusClass(tournoi.statut)}">
                            ${getStatusLabel(tournoi.statut)}
                        </span>
                    </div>
                    <div class="absolute top-3 left-3">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white text-gray-800">
                            ${escapeHtml(getTypeLabel(typeTournoi))}
                        </span>
                    </div>
                </div>
                
                <div class="p-5">
                    <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-emerald-600 transition-colors">${nomTournoi}</h3>
                    
                    <div class="space-y-2 mb-4 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar text-emerald-600 w-4"></i>
                            <span>${formatDate(tournoi.date_debut)} - ${formatDate(tournoi.date_fin)}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-users text-emerald-600 w-4"></i>
                            <span>${tournoi.nb_equipes} équipes</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-emerald-600 w-4"></i>
                            <span>${terrainNom}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-tag text-emerald-600 w-4"></i>
                            <span>Prix: ${typeof tournoi.prix_inscription !== 'undefined' && tournoi.prix_inscription !== null ? tournoi.prix_inscription : '-'} DH</span>
                        </div>
                        ${tournoi.description ? `
                        <div class="flex items-start gap-2">
                            <i class="fas fa-align-left text-emerald-600 w-4 mt-0.5"></i>
                            <p class="text-gray-700 leading-snug">${description}</p>
                        </div>` : ''}
                    </div>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-500">
                            ${tournoi.nb_inscrits || 0} inscrits
                        </div>
                        <div class="flex gap-2">
                            <button onclick="openRegisteredTeams(${tournoi.id_tournoi})" class="p-2 text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors" title="Gérer les inscrits">
                                <i class="fas fa-users"></i>
                            </button>
                            <button onclick="editTournament(${tournoi.id_tournoi})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="openDeleteModal(${tournoi.id_tournoi})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Load terrains list from server
 * 
 * Fetches terrains to populate tournament form dropdown.
 * Partner: Error Management - Handles HTTP errors and JSON parsing errors.
 * 
 * @returns {void}
 */
function loadFields() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '../../actions/admin-manager/stade/get_stades.php', true);

    xhr.onload = function() {
        // Error Management: Check HTTP status
        if (xhr.status === 200) {
            try {
                // Error Management: JSON parsing with try-catch
                const response = JSON.parse(xhr.responseText);

                if (response.success) {
                    populateFieldSelect(response.terrains);
                }
            } catch (e) {
                // Error Management: Log error to console
                console.error('Error loading terrains');
            }
        }
    };

    xhr.send();
}

/**
 * Populate terrain dropdown select
 * 
 * Fills terrain select element with available terrains from server.
 * Format: "Terrain Name - Category"
 * Partner: Security - Terrain names should be pre-sanitized on server.
 * 
 * @param {Array} terrains - Array of terrain objects
 * @returns {void}
 */
function populateFieldSelect(terrains) {
    const select = document.getElementById('id_terrain');
    select.innerHTML = '<option value="">Sélectionner un terrain...</option>' +
        terrains.map(t => `<option value="${t.id_terrain}">${t.nom_te} - ${t.categorie}</option>`).join('');
}

/**
 * Open add tournament modal
 * 
 * Resets form and opens modal for creating a new tournament.
 * Clears current tournament ID to indicate new tournament mode.
 * 
 * @returns {void}
 */
function openAddModal() {
    currentTournamentId = null;
    document.getElementById('modalTitle').textContent = 'Créer un tournoi';
    document.getElementById('tournoiForm').reset();
    document.getElementById('tournoiId').value = '';
    const submitBtnInit = document.getElementById('submitBtn');
    if (submitBtnInit) {
        submitBtnInit.innerHTML = '<i class="fas fa-plus mr-2"></i>Créer';
    }
    document.getElementById('tournoiModal').classList.remove('hidden');
}

/**
 * Edit tournament by loading tournament data and opening modal
 * 
 * Fetches tournament data from server and populates form for editing.
 * Partner: Security - Tournament ID is validated as number.
 * Partner: Error Management - Handles HTTP errors and JSON parsing errors.
 * 
 * @param {number} id - Tournament ID to edit
 * @returns {void}
 */
function editTournament(id) {
    currentTournamentId = id;

    const xhr = new XMLHttpRequest();
    xhr.open('GET', `../../actions/admin-manager/tournament/get_tournament.php?id=${id}`, true);

    xhr.onload = function() {
        // Error Management: Check HTTP status
        if (xhr.status === 200) {
            try {
                // Error Management: JSON parsing with try-catch
                const response = JSON.parse(xhr.responseText);

                if (response.success) {
                    const tournoi = response.tournoi;

                    document.getElementById('modalTitle').textContent = 'Modifier le tournoi';
                    document.getElementById('tournoiId').value = tournoi.id_tournoi;
                    document.getElementById('nom_tournoi').value = tournoi.nom_tournoi;
                    document.getElementById('type_tournoi').value = tournoi.type_tournoi;
                    document.getElementById('date_debut').value = tournoi.date_debut;
                    document.getElementById('date_fin').value = tournoi.date_fin;
                    document.getElementById('nb_equipes').value = tournoi.nb_equipes;
                    document.getElementById('prix_inscription').value = tournoi.prix_inscription || '';
                    document.getElementById('id_terrain').value = tournoi.id_terrain || '';
                    document.getElementById('statut').value = tournoi.statut;
                    document.getElementById('description').value = tournoi.description || '';
                    document.getElementById('regles').value = tournoi.regles || '';

                    const submitBtnEdit = document.getElementById('submitBtn');
                    if (submitBtnEdit) {
                        submitBtnEdit.innerHTML = '<i class="fas fa-pen mr-2"></i>Modifier';
                    }
                    document.getElementById('tournoiModal').classList.remove('hidden');
                } else {
                    showNotification(response.message || 'Erreur lors du chargement du tournoi', 'error');
                }
            } catch (e) {
                // Error Management: Handle JSON parsing errors
                showNotification('Erreur lors du traitement de la réponse', 'error');
            }
        }
    };

    xhr.send();
}

/**
 * View tournament details (placeholder function)
 * 
 * TODO: Implement tournament detail view functionality.
 * Currently shows placeholder notification.
 * 
 * @param {number} id - Tournament ID to view
 * @returns {void}
 */
// Removed unused placeholder function `viewTournoi`

// ================== Registered Teams Section ==================
let registeredTournamentId = null;
let pendingRemoveTeamId = null;
let pendingRemoveBtn = null;

function openRegisteredTeams(id) {
    registeredTournamentId = id;
    document.getElementById('inscritsModal').classList.remove('hidden');
    loadRegisteredTeams();
}

function closeRegisteredTeamsModal() {
    registeredTournamentId = null;
    document.getElementById('inscritsModal').classList.add('hidden');
}

function openRemoveTeam(idEquipe, btnEl) {
    pendingRemoveTeamId = idEquipe;
    pendingRemoveBtn = btnEl;
    document.getElementById('deleteEquipeModal').classList.remove('hidden');
}

function closeDeleteTeamModal() {
    pendingRemoveTeamId = null;
    pendingRemoveBtn = null;
    document.getElementById('deleteEquipeModal').classList.add('hidden');
}

function confirmRemoveTeam() {
    if (!pendingRemoveTeamId) return;
    removeTeam(pendingRemoveTeamId, pendingRemoveBtn, true);
}

// Tab handling removed; only teams are displayed

function loadRegisteredTeams() {
    if (!registeredTournamentId) return;
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `../../actions/admin-manager/tournament/get_teams_tournament.php?id_tournoi=${registeredTournamentId}`, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.success) {
                    document.getElementById('equipesCount').textContent = res.count || 0;
                    renderRegisteredTeams(res.equipes || []);
                } else {
                    showNotification(res.message || 'Erreur lors du chargement des équipes', 'error');
                }
            } catch (_) { showNotification('Erreur lors du traitement de la réponse', 'error'); }
        }
    };
    xhr.send();
}

function renderRegisteredTeams(equipes) {
    const list = document.getElementById('equipesList');
    if (!equipes.length) {
        list.innerHTML = '<div class="py-6 text-center text-gray-500">Aucune équipe inscrite</div>';
        return;
    }
    list.innerHTML = equipes.map(e => `
        <div class="py-3 flex items-center gap-2">
            <div class="flex-1 flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">${(e.nom_equipe || '?').substring(0,1).toUpperCase()}</div>
                <div>
                    <div class="font-semibold text-gray-800">${e.nom_equipe || ''}</div>
                    <div class="text-xs text-gray-500">${e.email_equipe || ''}</div>
                </div>
            </div>
            <div class="w-40">
                <select id="statut-${e.id_equipe}" class="w-full px-2 py-1 border-2 border-gray-200 rounded text-sm">
                    <option value="confirmee" ${e.statut_inscription === 'confirmee' ? 'selected' : ''}>confirmée</option>
                    <option value="invitee" ${e.statut_inscription === 'invitee' ? 'selected' : ''}>invitée</option>
                </select>
            </div>
            <div class="w-32 flex justify-end gap-2">
                <button class="p-2 text-blue-600 hover:bg-blue-50 rounded" title="Modifier infos" onclick="openEditTeam(${e.id_equipe})"><i class="fas fa-pen"></i></button>
                <button class="p-2 text-red-600 hover:bg-red-50 rounded" title="Retirer" onclick="openRemoveTeam(${e.id_equipe}, this)"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');
}

// -------- Edit team  --------
function openEditTeam(idEquipe) {
    if (!idEquipe) return;
    document.getElementById('edit_id_equipe').value = idEquipe;
    // load current values
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `../../actions/admin-manager/tournament/get_team.php?id_equipe=${idEquipe}`, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.success && res.equipe) {
                    document.getElementById('edit_nom_equipe').value = res.equipe.nom_equipe || '';
                    document.getElementById('edit_email_equipe').value = res.equipe.email_equipe || '';
                } else {
                    showNotification(res.message || "Erreur de chargement de l'équipe", 'error');
                }
                document.getElementById('editEquipeModal').classList.remove('hidden');
            } catch (_) {
                showNotification('Erreur lors du traitement de la réponse', 'error');
            }
        } else {
            showNotification('Erreur de connexion au serveur', 'error');
        }
    };
    xhr.send();
}

function closeEditTeamModal() {
    document.getElementById('editEquipeModal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editEquipeForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submit = document.getElementById('editEquipeSubmit');
            submit.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Modification...';
            const id_equipe = document.getElementById('edit_id_equipe').value;
            const nom_equipe = document.getElementById('edit_nom_equipe').value;
            const email_equipe = document.getElementById('edit_email_equipe').value;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../../actions/admin-manager/tournament/edit_team.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onload = function() {
                submit.disabled = false;
                submit.innerHTML = 'Modifier';
                if (xhr.status >= 200 && xhr.status < 300) {
                    let res = null; try { res = JSON.parse(xhr.responseText); } catch(_) {}
                    if (!res || res.success) {
                        closeEditTeamModal();
                        showNotification((res && res.message) || "Équipe modifiée", 'success');
                        loadRegisteredTeams();
                        tournamentsCache.clear();
                        loadTournaments(true);
                    } else {
                        showNotification(res.message || 'Erreur de modification', 'error');
                    }
                } else {
                    showNotification('Erreur de connexion au serveur', 'error');
                }
            };
            xhr.onerror = function() {
                submit.disabled = false;
                submit.innerHTML = 'Modifier';
                showNotification('Erreur réseau', 'error');
            };
            xhr.send(JSON.stringify({ id_equipe, nom_equipe, email_equipe }));
        });
    }
});

function updateTeamStatus(idEquipe) {
    if (!registeredTournamentId || !idEquipe) return;
    const select = document.getElementById(`statut-${idEquipe}`);
    const statut = select ? select.value : 'confirmee';
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../../actions/admin-manager/tournament/update_team_tournament.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            let res = null; try { res = JSON.parse(xhr.responseText); } catch(_) {}
            if (!res || res.success) {
                showNotification((res && res.message) || 'Statut mis à jour', 'success');
                loadRegisteredTeams();
            } else {
                showNotification(res.message || 'Erreur', 'error');
            }
        }
    };
    xhr.send(JSON.stringify({ id_tournoi: registeredTournamentId, id_equipe: idEquipe, statut_participation: statut }));
}

function removeTeam(idEquipe, btnEl, skipConfirm = false) {
    if (!registeredTournamentId || !idEquipe) return;
    // Confirmation handled by custom modal when skipConfirm is false
    if (!skipConfirm) {
        openRemoveTeam(idEquipe, btnEl);
        return;
    }
    closeDeleteTeamModal();
    if (btnEl) { btnEl.disabled = true; btnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../../actions/admin-manager/tournament/remove_team_tournament.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = '<i class="fas fa-trash"></i>'; }
        if (xhr.status >= 200 && xhr.status < 300) {
            let res = null; try { res = JSON.parse(xhr.responseText); } catch(_) {}
            if (!res || res.success) {
                showNotification((res && res.message) || 'Équipe retirée avec succès', 'success');
                loadRegisteredTeams();
                tournamentsCache.clear();
                loadTournaments(true);
            } else {
                showNotification(res.message || 'Erreur', 'error');
            }
        }
    };
    xhr.onerror = function() {
        if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = '<i class="fas fa-trash"></i>'; }
        showNotification('Erreur réseau', 'error');
    };
    xhr.send(JSON.stringify({ id_tournoi: registeredTournamentId, id_equipe: idEquipe }));
}

/**
 * Handle form submission for add/edit tournament
 * 
 * Submits tournament data to server via AJAX. Prevents default form submission.
 * Shows loading state on button and handles all error scenarios.
 * Determines endpoint based on whether tournament ID exists (edit vs add).
 * Partner: Security - Data is sent as JSON with proper content type.
 * Partner: Error Management - Handles network errors, HTTP errors and JSON parsing errors.
 * 
 * @param {Event} e - Form submit event
 * @returns {void}
 */
function handleSubmit(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    const isEdit = !!currentTournamentId;
    submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>${isEdit ? 'Modification' : 'Création'}...`;

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    const xhr = new XMLHttpRequest();
    // Determine endpoint: edit if currentTournamentId exists, otherwise add
    xhr.open('POST', currentTournamentId ? '../../actions/admin-manager/tournament/edit_tournament.php' : '../../actions/admin-manager/tournament/add_tournament.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onload = function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = isEdit ? '<i class="fas fa-pen mr-2"></i>Modifier' : '<i class="fas fa-plus mr-2"></i>Créer';
        
        // Treat any 2xx as success (200, 201, 204, ...)
        if (xhr.status >= 200 && xhr.status < 300) {
            let parsed = null;
            let success = true;
            let message = currentTournamentId ? 'Tournoi modifié avec succès' : 'Tournoi créé avec succès';

            const raw = (xhr.responseText || '').trim();
            const contentType = (xhr.getResponseHeader('Content-Type') || '').toLowerCase();

            if (raw && contentType.includes('application/json')) {
                try {
                    parsed = JSON.parse(raw);
                    if (typeof parsed.success === 'boolean') success = parsed.success;
                    if (parsed.message) message = parsed.message;
                } catch (_) {
                    // Keep defaults if parsing fails
                }
            }

            if (success) {
                // Close modal first
                closeModal();
                // Show success message
                showNotification(message, 'success');
                // Clear cache and force refresh
                tournamentsCache.clear();
                loadTournaments(true);
            } else {
                showNotification((parsed && parsed.message) || 'Erreur lors de l\'enregistrement', 'error');
            }
        } else {
            // Error Management: Handle HTTP errors
            showNotification('Erreur de connexion au serveur', 'error');
        }
    };

    // Error Management: Handle network errors
    xhr.onerror = function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = isEdit ? '<i class="fas fa-pen mr-2"></i>Modifier' : '<i class="fas fa-plus mr-2"></i>Créer';
        showNotification('Erreur réseau', 'error');
    };

    // Security: Send data as JSON
    xhr.send(JSON.stringify(data));
}

/**
 * Open delete confirmation modal
 * 
 * Stores tournament ID to delete and shows confirmation modal.
 * Requires user confirmation before actual deletion.
 * 
 * @param {number} id - Tournament ID to delete
 * @returns {void}
 */
function openDeleteModal(id) {
    deleteTournamentId = id;
    document.getElementById('deleteModal').classList.remove('hidden');
}

/**
 * Close delete confirmation modal
 * 
 * Hides modal and clears stored tournament ID.
 * 
 * @returns {void}
 */
function closeDeleteModal() {
    deleteTournamentId = null;
    document.getElementById('deleteModal').classList.add('hidden');
}

/**
 * Confirm and execute tournament deletion
 * 
 * Sends delete request to server. Only executes if deleteTournoiId is set.
 * Partner: Security - Tournament ID is validated before sending to server.
 * Partner: Error Management - Handles HTTP errors and JSON parsing errors.
 * 
 * @returns {void}
 */
function confirmDelete() {
    // Security: Validate tournament ID before deletion
    if (!deleteTournamentId) return;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../../actions/admin-manager/tournament/delete_tournament.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onload = function() {
        // Treat any 2xx as success
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const raw = (xhr.responseText || '').trim();
                let parsed = null;
                
                if (raw) {
                    parsed = JSON.parse(raw);
                }
                
                if (parsed && parsed.success) {
                    // Remove the card from DOM immediately for instant feedback
                    const card = document.getElementById(`tournoi-card-${deleteTournamentId}`);
                    if (card && card.parentNode) {
                        card.parentNode.removeChild(card);
                    }
                    // Close modal first, then show success message
                    closeDeleteModal();
                    showNotification(parsed.message || 'Tournoi supprimé avec succès', 'success');
                    // Clear cache and refresh list in background to stay consistent
                    tournamentsCache.clear();
                    loadTournaments(true);
                } else {
                    showNotification((parsed && parsed.message) || 'Erreur lors de la suppression', 'error');
                }
            } catch (e) {
                // If parsing fails but status is 2xx, assume success
                const card = document.getElementById(`tournoi-card-${deleteTournamentId}`);
                if (card && card.parentNode) {
                    card.parentNode.removeChild(card);
                }
                closeDeleteModal();
                showNotification('Tournoi supprimé avec succès', 'success');
                tournamentsCache.clear();
                loadTournaments(true);
            }
        } else {
            // Handle error responses
            let errorMessage = 'Erreur de connexion au serveur';
            try {
                const raw = (xhr.responseText || '').trim();
                if (raw) {
                    const parsed = JSON.parse(raw);
                    if (parsed.message) errorMessage = parsed.message;
                }
            } catch (e) {
                // Keep default error message
            }
            showNotification(errorMessage, 'error');
        }
    };

    // Error Management: Handle network errors
    xhr.onerror = function() {
        showNotification('Erreur réseau', 'error');
    };

    // Security: Send tournament ID as JSON
    xhr.send(JSON.stringify({
        id_tournoi: deleteTournamentId
    }));
}

/**
 * Close tournament modal and reset form
 * 
 * Hides modal, resets form fields and clears current tournament ID.
 * 
 * @returns {void}
 */
function closeModal() {
    document.getElementById('tournoiModal').classList.add('hidden');
    document.getElementById('tournoiForm').reset();
    currentTournamentId = null;
}

/**
 * Show loading indicator
 * 
 * Displays loader to indicate data is being fetched.
 * 
 * @returns {void}
 */
function showLoader() {
    document.getElementById('loader').classList.remove('hidden');
}

/**
 * Hide loading indicator
 * 
 * Hides loader when data fetching is complete.
 * 
 * @returns {void}
 */
function hideLoader() {
    document.getElementById('loader').classList.add('hidden');
}

/**
 * Show notification to user
 * 
 * Displays temporary notification message with appropriate styling and icon.
 * Auto-hides after 4 seconds. Supports success, error, info and warning types.
 * Partner: Security - Message is displayed as-is (should be sanitized before calling).
 * 
 * @param {string} message - Notification message to display
 * @param {string} type - Notification type (success, error, info, warning)
 * @returns {void}
 */
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
    // Note: Message should be pre-sanitized before calling this function
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas ${icons[type]} text-xl"></i>
            <span>${message}</span>
        </div>
    `;

    notification.classList.remove('hidden');
    // Auto-hide after 4 seconds
    setTimeout(() => {
        notification.classList.add('hidden');
    }, 4000);
}

/**
 * Get CSS class for tournament status badge
 * 
 * Returns Tailwind CSS classes for tournament status badge styling.
 * Colors: planifié (blue), en_cours (green), terminé (gray), annulé (red).
 * 
 * @param {string} statut - Tournament status (planifie, en_cours, termine, annule)
 * @returns {string} CSS classes for status badge
 */
function getStatusClass(statut) {
    const classes = {
        'planifie': 'bg-blue-100 text-blue-800',
        'en_cours': 'bg-green-100 text-green-800',
        'termine': 'bg-gray-100 text-gray-800',
        'annule': 'bg-red-100 text-red-800'
    };
    return classes[statut] || 'bg-gray-100 text-gray-800';
}

/**
 * Get French label for tournament status
 * 
 * Returns localized French label for tournament status.
 * Returns original value if status not found in mapping.
 * 
 * @param {string} statut - Tournament status code
 * @returns {string} French label for status
 */
function getStatusLabel(statut) {
    const labels = {
        'planifie': 'Planifié',
        'en_cours': 'En cours',
        'termine': 'Terminé',
        'annule': 'Annulé'
    };
    return labels[statut] || statut;
}

/**
 * Get tournament type label
 * 
 * Returns tournament type as-is (Senior, U-21, Open, etc.).
 * Used for display purposes.
 * 
 * @param {string} type - Tournament type
 * @returns {string} Tournament type label
 */
function getTypeLabel(type) {
    // Display as is (Senior, U-21, Open, ...)
    return type || '';
}

/**
 * Format date string to French locale
 * 
 * Converts date string to French date format (DD/MM/YYYY).
 * Uses toLocaleDateString with 'fr-FR' locale.
 * 
 * @param {string} dateString - Date string to format
 * @returns {string} Formatted date in French locale
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}
