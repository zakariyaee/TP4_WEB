(() => {
    const pageData = window.TOURNAMENT_PAGE_DATA || {};
    const defaultGrouped = {
        upcoming: [],
        ongoing: [],
        completed: [],
        cancelled: []
    };
    const defaultCounts = {
        upcoming: 0,
        ongoing: 0,
        my: 0,
        completed: 0
    };
    const refreshEndpoint = pageData.refreshEndpoint || null;
    let dataVersion = pageData.dataVersion || null;
    let groupedData = { ...defaultGrouped, ...(window.TOURNAMENT_GROUPED || {}) };
    let counts = { ...defaultCounts, ...(window.TOURNAMENT_COUNTS || {}) };
    let tournaments = window.TOURNAMENT_DETAILS || [];
    let isRefreshing = false;
    
    // Sync configuration
    const SYNC_ENABLED = typeof window.SyncManager !== 'undefined';

    const searchInput = document.getElementById('searchTournament');
    const tabs = document.querySelectorAll('.tournoi-tab');
    let activeSectionId = document.querySelector('.tournoi-tab.active')?.dataset.target || 'section-upcoming';
    let toastTimer = null;

    const normalize = (value) => {
        if (!value) {
            return '';
        }
        return value
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    };

    const escapeHtml = (value) => {
        if (value === undefined || value === null) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    };

    const statusBadgeClassFor = (statusKey) => {
        switch (statusKey) {
            case 'ongoing':
                return 'bg-emerald-100 text-emerald-700';
            case 'completed':
                return 'bg-gray-100 text-gray-600';
            case 'cancelled':
                return 'bg-red-100 text-red-700';
            default:
                return 'bg-blue-100 text-blue-700';
        }
    };

    const truncate = (text, max = 220) => {
        if (!text) {
            return '';
        }
        const trimmed = String(text).trim();
        return trimmed.length > max ? `${trimmed.slice(0, max - 1)}…` : trimmed;
    };

    const buildTournamentCard = (tournament) => {
        const id = Number(tournament.id) || 0;
        const statusKey = tournament.statusKey || 'upcoming';
        const statusLabel = tournament.statusLabel || '';
        const searchValue = tournament.searchIndex || '';
        const terrainName = tournament.terrainName || 'Terrain à confirmer';
        const terrainCity = tournament.terrainCity || '';
        const terrainSubtitle = [terrainName, terrainCity].filter(Boolean).join(' · ');
        const terrainLocation = tournament.terrainLocation || '';
        const imagePath = tournament.terrainImage
            ? `../../assets/images/terrains/${encodeURIComponent(tournament.terrainImage)}`
            : null;
        const remainingSlots = typeof tournament.remainingSlots === 'number' ? tournament.remainingSlots : null;
        const remainingLabel = remainingSlots !== null
            ? `${remainingSlots} place${remainingSlots > 1 ? 's' : ''} restantes`
            : null;
        const maxTeamsLabel = tournament.maxTeams ? `${tournament.maxTeams}` : null;
        const daysUntil = typeof tournament.daysUntil === 'number' ? tournament.daysUntil : null;
        let daysUntilLabel = null;
        if (daysUntil !== null && statusKey === 'upcoming') {
            daysUntilLabel = daysUntil === 0 ? 'Aujourd’hui' : `J-${daysUntil}`;
        }
        const priceLabel = tournament.priceLabel || 'Gratuit';
        const descriptionExcerpt = truncate(tournament.description, 220);
        const hasDescription = Boolean(descriptionExcerpt);
        const progressPercent = Number(tournament.progressPercent) || 0;
        const showProgress = Number(tournament.maxTeams) > 0;
        const isRegistered = Boolean(tournament.isRegistered);
        const isFull = Boolean(tournament.isFull);

        return `
            <article
                class="tournoi-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition duration-300 flex flex-col"
                data-status="${escapeHtml(statusKey)}"
                data-search="${escapeHtml(searchValue)}"
                data-tournament-id="${id}"
            >
                <div class="relative h-48">
                    ${imagePath
                        ? `<img src="${imagePath}" alt="Terrain du tournoi" class="w-full h-full object-cover">`
                        : `<div class="w-full h-full bg-gradient-to-br from-emerald-500 via-green-600 to-slate-700 flex items-center justify-center">
                                <i class="fas fa-futbol text-white text-6xl opacity-80"></i>
                            </div>`}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

                    <div class="absolute top-4 right-4 flex flex-wrap gap-2 justify-end">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold backdrop-blur bg-white/90 ${statusBadgeClassFor(statusKey)}">
                            ${escapeHtml(statusLabel)}
                        </span>
                        ${isRegistered ? `<span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500 text-white shadow">
                            <i class="fas fa-check mr-1"></i> Inscrit
                        </span>` : ''}
                        ${daysUntilLabel ? `<span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 shadow">
                            ${escapeHtml(daysUntilLabel)}
                        </span>` : ''}
                    </div>

                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <h3 class="text-lg font-semibold">${escapeHtml(tournament.name || 'Tournoi sans nom')}</h3>
        <p class="text-sm text-white/80 mt-1">${escapeHtml(terrainSubtitle)}</p>
                    </div>
                </div>

                <div class="p-5 flex-1 flex flex-col">
                    <div class="space-y-2 text-sm text-gray-600 mb-5">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar text-emerald-600"></i>
                            <span>${escapeHtml(tournament.dateRangeLabel || '')}</span>
                        </div>
                        ${terrainLocation ? `
                        <div class="flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-emerald-600"></i>
                            <span class="truncate">${escapeHtml(terrainLocation)}</span>
                        </div>` : ''}
                        <div class="flex items-center gap-2">
                            <i class="fas fa-users text-emerald-600"></i>
                            <span>
                                ${Number(tournament.registeredTeams) || 0}
                                ${maxTeamsLabel ? `/ ${escapeHtml(maxTeamsLabel)}` : ''}
                                équipes inscrites
                            </span>
                        </div>
                    </div>

                    ${hasDescription ? `
                    <p class="text-sm text-gray-600 mb-5">
                        ${escapeHtml(descriptionExcerpt)}
                    </p>` : ''}

                    ${showProgress ? `
                    <div class="mb-6">
                        <div class="flex items-center justify-between text-xs font-semibold text-gray-500 mb-1">
                            <span>Progression</span>
                            <span>${progressPercent}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-500 to-green-600" style="width: ${progressPercent}%;"></div>
                        </div>
                    </div>` : ''}

                    <div class="mt-auto flex items-center justify-between">
                        <div>
                            <div class="text-xl font-bold text-emerald-600">
                                ${escapeHtml(priceLabel)}
                            </div>
                            ${remainingLabel ? `<p class="text-xs text-gray-500 mt-1">${escapeHtml(remainingLabel)}</p>` : ''}
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    class="details-button px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition text-sm font-semibold"
                                    data-tournament-id="${id}">
                                Détails
                            </button>
                            ${statusKey === 'cancelled' ? `
                                <span class="px-4 py-2 rounded-xl bg-red-100 text-red-600 text-sm font-semibold">Annulé</span>
                            ` : statusKey === 'completed' ? `
                                <span class="px-4 py-2 rounded-xl bg-gray-100 text-gray-600 text-sm font-semibold">Terminé</span>
                            ` : isRegistered ? `
                                <span class="px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-semibold">
                                    <i class="fas fa-check mr-1"></i> Inscrit
                                </span>
                            ` : isFull ? `
                                <span class="px-4 py-2 rounded-xl bg-gray-100 text-gray-500 text-sm font-semibold">Complet</span>
                            ` : `
                                <button type="button"
                                        class="join-button px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition"
                                        data-tournament-id="${id}">
                                    S'inscrire
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            </article>
        `;
    };

    const flattenTournaments = (data) => {
        return ['upcoming', 'ongoing', 'completed', 'cancelled'].reduce((acc, key) => {
            const items = Array.isArray(data[key]) ? data[key] : [];
            return acc.concat(items);
        }, []);
    };

    const debounce = (fn, delay = 250) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn(...args), delay);
        };
    };

    const showToast = (message, type = 'info') => {
        const toast = document.getElementById('tournamentToast');
        if (!toast) {
            return;
        }
        const palette = {
            success: 'bg-emerald-600',
            error: 'bg-red-600',
            warning: 'bg-amber-500',
            info: 'bg-blue-600'
        };
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-triangle-exclamation',
            warning: 'fa-circle-info',
            info: 'fa-circle-info'
        };
        toast.className = `fixed top-6 right-6 px-6 py-4 rounded-2xl shadow-lg text-white z-50 ${palette[type] || palette.info}`;
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas ${icons[type] || icons.info} text-xl"></i>
                <span class="font-medium">${message}</span>
            </div>
        `;
        toast.classList.remove('hidden');
        if (toastTimer) {
            clearTimeout(toastTimer);
        }
        toastTimer = setTimeout(() => {
            toast.classList.add('hidden');
        }, 3800);
    };

    const setActiveTab = (targetId, tabEl) => {
        if (!targetId) {
            return;
        }

        tabs.forEach((tab) => {
            tab.classList.remove('active', 'border-b-2', 'border-emerald-500', 'text-emerald-600', 'font-semibold');
            tab.classList.add('text-gray-600');
        });
        if (tabEl) {
            tabEl.classList.add('active', 'border-b-2', 'border-emerald-500', 'text-emerald-600', 'font-semibold');
            tabEl.classList.remove('text-gray-600');
        }

        document.querySelectorAll('.tournoi-section').forEach((section) => {
            section.classList.add('hidden');
        });
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
            targetSection.classList.remove('hidden');
        }

        activeSectionId = targetId;
        applyFilters();
    };

    const applyFilters = () => {
        const activeSection = document.getElementById(activeSectionId);
        if (!activeSection) {
            return;
        }

        const cardsWrapper = activeSection.querySelector('[data-cards-wrapper]');
        const emptyState = activeSection.querySelector('[data-empty-state]');
        const cards = activeSection.querySelectorAll('.tournoi-card');

        const searchValue = normalize(searchInput?.value || '');

        let visibleCount = 0;

        cards.forEach((card) => {
            const cardSearch = normalize(card.dataset.search || '');

            const matchesSearch = !searchValue || cardSearch.includes(searchValue);

            const visible = matchesSearch;
            card.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        if (cardsWrapper) {
            cardsWrapper.classList.toggle('hidden', visibleCount === 0);
        }
        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
        }
    };

    const updateCountsDisplay = (nextCounts = {}) => {
        counts = { ...counts, ...nextCounts };
        ['upcoming', 'ongoing', 'my', 'completed'].forEach((key) => {
            const el = document.querySelector(`[data-count="${key}"]`);
            if (el) {
                el.textContent = counts[key] ?? 0;
            }
        });
    };

    const renderTournamentSections = (data) => {
        const merged = { ...defaultGrouped, ...data };
        ['upcoming', 'ongoing', 'completed'].forEach((status) => {
            const section = document.getElementById(`section-${status}`);
            if (!section) {
                return;
            }
            const wrapper = section.querySelector('[data-cards-wrapper]');
            const emptyState = section.querySelector('[data-empty-state]');
            const list = Array.isArray(merged[status]) ? merged[status] : [];

            if (wrapper) {
                if (list.length === 0) {
                    wrapper.classList.add('hidden');
                    wrapper.innerHTML = '';
                } else {
                    wrapper.classList.remove('hidden');
                    
                    // Smart update: only update changed cards
                    const existingCards = wrapper.querySelectorAll('[data-tournament-id]');
                    const existingIds = new Set();
                    existingCards.forEach(card => existingIds.add(parseInt(card.dataset.tournamentId)));
                    
                    const newIds = new Set(list.map(t => t.id));
                    
                    // Remove deleted tournaments
                    existingIds.forEach(id => {
                        if (!newIds.has(id)) {
                            const card = wrapper.querySelector(`[data-tournament-id="${id}"]`);
                            if (card) card.remove();
                        }
                    });
                    
                    // Update or add tournaments
                    list.forEach((tournament, index) => {
                        const existingCard = wrapper.querySelector(`[data-tournament-id="${tournament.id}"]`);
                        const newCardHtml = buildTournamentCard(tournament);
                        
                        if (existingCard) {
                            // Update existing card only if data changed
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = newCardHtml;
                            const newCard = tempDiv.firstElementChild;
                            
                            // Check if content actually changed before replacing
                            if (existingCard.outerHTML !== newCard.outerHTML) {
                                existingCard.replaceWith(newCard);
                            }
                        } else {
                            // Add new card at correct position
                            if (index === 0 || wrapper.children.length === 0) {
                                wrapper.insertAdjacentHTML('afterbegin', newCardHtml);
                            } else if (index >= wrapper.children.length) {
                                wrapper.insertAdjacentHTML('beforeend', newCardHtml);
                            } else {
                                const referenceCard = wrapper.children[index];
                                referenceCard.insertAdjacentHTML('beforebegin', newCardHtml);
                            }
                        }
                    });
                }
            }
            if (emptyState) {
                emptyState.classList.toggle('hidden', list.length > 0);
            }
        });
        groupedData = merged;
        tournaments = flattenTournaments(groupedData);
        window.TOURNAMENT_DETAILS = tournaments;
        applyFilters();
    };

    const syncJoinButtonState = () => {
        const joinSubmit = document.getElementById('joinTournamentSubmit');
        if (!joinSubmit) {
            return;
        }
        const canJoin = !!pageData.playerHasTeams;
        joinSubmit.disabled = !canJoin;
        joinSubmit.classList.toggle('opacity-50', !canJoin);
        joinSubmit.classList.toggle('cursor-not-allowed', !canJoin);
    };

    const fetchLatestTournaments = async (force = false) => {
        if (!refreshEndpoint) {
            return;
        }
        if (isRefreshing) {
            if (force) {
                setTimeout(() => fetchLatestTournaments(true), 250);
            }
            return;
        }
        isRefreshing = true;
        try {
            const response = await fetch(refreshEndpoint, {
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) {
                return;
            }
            const payload = await response.json();
            if (!payload.success) {
                return;
            }
            const incomingVersion = payload.dataVersion || null;
            if (!force && dataVersion && incomingVersion && incomingVersion === dataVersion) {
                return;
            }
            dataVersion = incomingVersion;
            pageData.dataVersion = dataVersion;

            const incomingGrouped = payload.grouped || {};
            const incomingCounts = payload.counts || {};
            renderTournamentSections({ ...defaultGrouped, ...incomingGrouped });
            updateCountsDisplay(incomingCounts);

            if (typeof payload.playerHasTeams === 'boolean') {
                pageData.playerHasTeams = payload.playerHasTeams;
                syncJoinButtonState();
            }
            
            // Store last update time
            window.lastTournamentsLoadTime = Date.now();
        } catch (error) {
            console.warn('Impossible de rafraîchir les tournois', error);
        } finally {
            isRefreshing = false;
        }
    };

    /**
     * Setup universal sync system using SyncManager
     */
    const setupUniversalSync = () => {
        if (!SYNC_ENABLED) {
            console.warn('[Tournaments] SyncManager not available, falling back to basic refresh');
            fetchLatestTournaments();
            return;
        }

        // Register tournaments channel
        window.SyncManager.register('tournaments', (data) => {
            console.log('[Tournaments] Sync update received:', data);
            fetchLatestTournaments(true);
        }, {
            pollInterval: 1000, // Poll every 1 second for real-time sync
            storageKey: 'sync_tournaments_update',
            checkEndpoint: refreshEndpoint
        });

        // Register requests channel (player creates requests)
        window.SyncManager.register('tournament_requests', (data) => {
            console.log('[Tournaments] Request update received:', data);
            fetchLatestTournaments(true);
        }, {
            pollInterval: 1000, // Poll every 1 second
            storageKey: 'sync_tournament_requests_update'
        });

        console.log('[Tournaments] Universal sync enabled');
    };

    /**
     * Trigger tournaments update (notify all tabs/browsers)
     */
    const triggerTournamentsUpdate = () => {
        if (SYNC_ENABLED) {
            window.SyncManager.notify('tournaments', {
                source: 'player_action',
                action: 'update'
            });
        }
        // Always refresh locally
        fetchLatestTournaments(true);
    };

    /**
     * Trigger request update (notify all tabs/browsers)
     */
    const triggerRequestUpdate = () => {
        if (SYNC_ENABLED) {
            window.SyncManager.notify('tournament_requests', {
                source: 'player_action',
                action: 'create_request'
            });
        }
    };

    // Initialize universal sync
    setupUniversalSync();
    fetchLatestTournaments();

    tournaments = flattenTournaments(groupedData);
    window.TOURNAMENT_DETAILS = tournaments;

    const openModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
        }
    };

    const closeModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    const fillJoinSummary = (tournamentId) => {
        const summary = document.getElementById('joinTournamentSummary');
        if (!summary) {
            return;
        }
        const tournament = tournaments.find((item) => Number(item.id) === Number(tournamentId));
        if (!tournament) {
            summary.innerHTML = '';
            return;
        }
        const parts = [];
        parts.push(`
            <div class="flex items-center gap-2">
                <i class="fas fa-trophy text-emerald-600"></i>
                <span class="font-semibold text-gray-800">${tournament.name}</span>
            </div>
        `);
        parts.push(`
            <div class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-calendar text-emerald-600"></i>
                <span>${tournament.dateRangeLabel}</span>
            </div>
        `);
        if (tournament.priceLabel) {
            parts.push(`
                <div class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-tags text-emerald-600"></i>
                    <span>${tournament.priceLabel}</span>
                </div>
            `);
        }
        if (typeof tournament.remainingSlots === 'number') {
            const label = tournament.remainingSlots > 1 ? `${tournament.remainingSlots} places restantes` : `${tournament.remainingSlots} place restante`;
            parts.push(`
                <div class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-users text-emerald-600"></i>
                    <span>${label}</span>
                </div>
            `);
        }
        summary.innerHTML = parts.join('');
    };

    const fillDetailsModal = (tournamentId) => {
        const detailsContent = document.getElementById('detailsContent');
        const detailsTitle = document.getElementById('detailsTitle');
        const detailsSubtitle = document.getElementById('detailsSubtitle');
        if (!detailsContent || !detailsTitle) {
            return;
        }
        const tournament = tournaments.find((item) => Number(item.id) === Number(tournamentId));
        if (!tournament) {
            detailsTitle.textContent = 'Tournoi introuvable';
            detailsContent.innerHTML = '<p class="text-gray-600 text-sm">Impossible de récupérer les informations de ce tournoi.</p>';
            return;
        }

        detailsTitle.textContent = tournament.name;
        if (detailsSubtitle) {
            detailsSubtitle.textContent = `${tournament.dateRangeLabel} · ${tournament.terrainName}`;
        }

        const rows = [];
        rows.push(`
            <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Tarif</div>
                <div class="text-sm font-semibold text-gray-800">${tournament.priceLabel}</div>
            </div>
        `);
        rows.push(`
            <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-3 text-gray-700 text-sm mb-3">
                    <i class="fas fa-map-marker-alt text-emerald-600"></i>
                    <span>${tournament.terrainName}${tournament.terrainCity ? ' · ' + tournament.terrainCity : ''}</span>
                </div>
                ${tournament.terrainLocation ? `<p class="text-gray-500 text-sm">${tournament.terrainLocation}</p>` : ''}
            </div>
        `);
        if (tournament.description) {
            rows.push(`
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                    <p class="text-gray-600 leading-relaxed text-sm">${tournament.description}</p>
                </div>
            `);
        }
        if (tournament.rules) {
            rows.push(`
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Règlement & Récompenses</h3>
                    <p class="text-gray-600 leading-relaxed text-sm whitespace-pre-line">${tournament.rules}</p>
                </div>
            `);
        }
        rows.push(`
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center">
                    <div class="text-xs text-gray-500 uppercase font-semibold mb-1">Équipes</div>
                    <div class="text-lg font-bold text-gray-800">${tournament.registeredTeams}${tournament.maxTeams ? ' / ' + tournament.maxTeams : ''}</div>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center">
                    <div class="text-xs text-gray-500 uppercase font-semibold mb-1">Statut</div>
                    <div class="text-lg font-bold text-emerald-600">${tournament.statusLabel}</div>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center">
                    <div class="text-xs text-gray-500 uppercase font-semibold mb-1">Places restantes</div>
                    <div class="text-lg font-bold text-gray-800">
                        ${typeof tournament.remainingSlots === 'number' ? tournament.remainingSlots : 'Illimité'}
                    </div>
                </div>
            </div>
        `);

        detailsContent.innerHTML = rows.join('');
    };

    // Tab events
    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.target;
            if (!target || target === activeSectionId) {
                return;
            }
            setActiveTab(target, tab);
        });
    });

    // Filters
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => applyFilters(), 200));
    }

    // Create tournament modal
    const createTrigger = document.getElementById('openCreateModal');
    if (createTrigger) {
        createTrigger.addEventListener('click', () => {
            const form = document.getElementById('createTournamentForm');
            if (form) {
                form.reset();
            }
            openModal('createTournamentModal');
        });
    }

    const createForm = document.getElementById('createTournamentForm');
    const createSubmit = document.getElementById('createTournamentSubmit');
    if (createForm && createSubmit) {
        createForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!pageData.endpoints || !pageData.endpoints.create) {
                showToast("Impossible de créer le tournoi pour le moment.", 'warning');
                return;
            }

            const formData = new FormData(createForm);
            const payload = Object.fromEntries(formData.entries());

            createSubmit.disabled = true;
            const originalLabel = createSubmit.innerHTML;
            createSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Envoi de la demande...';

            fetch(pageData.endpoints.create, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(async (response) => {
                    let data = {};
                    try {
                        data = await response.json();
                    } catch (_) {
                        // ignore parse errors
                    }
                    return { ok: response.ok, status: response.status, data };
                })
                .then(({ ok, status, data }) => {
                    if ((ok || status === 201) && (data.success ?? true)) {
                        showToast(data.message || 'Demande de tournoi envoyée avec succès', 'success');
                        
                        // Notify all tabs/browsers about the new request
                        triggerRequestUpdate();
                        
                        // Also trigger tournaments update (in case request is auto-approved)
                        triggerTournamentsUpdate();
                        
                        // Reset form and close modal
                        createForm.reset();
                        closeModal('createTournamentModal');
                    } else {
                        showToast(data.message || 'Impossible d\'envoyer la demande', 'error');
                    }
                })
                .catch(() => {
                    showToast('Erreur de connexion au serveur', 'error');
                })
                .finally(() => {
                    createSubmit.disabled = false;
                    createSubmit.innerHTML = originalLabel;
                });
        });
    }

    // Join tournament modal
    const joinForm = document.getElementById('joinTournamentForm');
    const joinSubmit = document.getElementById('joinTournamentSubmit');
    const joinTournamentIdInput = document.getElementById('join_tournament_id');
    const joinTeamSelect = document.getElementById('join_team_select');

    if (joinForm && joinSubmit && joinTournamentIdInput) {
        joinForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!pageData.playerHasTeams) {
                showToast('Créez une équipe avant de vous inscrire.', 'warning');
                return;
            }

            if (!joinTeamSelect || !joinTeamSelect.value) {
                showToast('Veuillez sélectionner une équipe.', 'warning');
                return;
            }

            if (!pageData.endpoints || !pageData.endpoints.join) {
                showToast("Service d'inscription indisponible.", 'warning');
                return;
            }

            const payload = {
                id_tournoi: joinTournamentIdInput.value,
                id_equipe: joinTeamSelect.value
            };

            joinSubmit.disabled = true;
            const originalLabel = joinSubmit.innerHTML;
            joinSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Inscription...';

            fetch(pageData.endpoints.join, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(async (response) => {
                    let data = {};
                    try {
                        data = await response.json();
                    } catch (_) {
                        // ignore parse errors
                    }
                    return { ok: response.ok, data };
                })
                .then(({ ok, data }) => {
                    if (ok && (data.success ?? true)) {
                        showToast(data.message || 'Inscription confirmée', 'success');
                        closeModal('joinTournamentModal');
                        
                        // Notify all tabs/browsers about the tournament update
                        triggerTournamentsUpdate();
                    } else {
                        showToast(data.message || 'Impossible de vous inscrire', 'error');
                    }
                })
                .catch(() => {
                    showToast('Erreur de connexion au serveur', 'error');
                })
                .finally(() => {
                    joinSubmit.disabled = false;
                    joinSubmit.innerHTML = originalLabel;
                });
        });
    }

    // Detail & join buttons (delegation)
    document.addEventListener('click', (event) => {
        const joinButton = event.target.closest('.join-button');
        if (joinButton && joinTournamentIdInput) {
            const tournamentId = joinButton.dataset.tournamentId;
            joinTournamentIdInput.value = tournamentId || '';
            if (joinTeamSelect) {
                joinTeamSelect.selectedIndex = 0;
            }
            fillJoinSummary(tournamentId);
            openModal('joinTournamentModal');
        }

        const detailsButton = event.target.closest('.details-button');
        if (detailsButton) {
            const tournamentId = detailsButton.dataset.tournamentId;
            fillDetailsModal(tournamentId);
            openModal('detailsModal');
        }
    });

    // Close modal buttons
    document.querySelectorAll('[data-close-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-close-modal');
            closeModal(modalId);
        });
    });

    // Close modal on outside click
    document.querySelectorAll('#createTournamentModal, #joinTournamentModal, #detailsModal').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });

    // Close modal on escape
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            ['createTournamentModal', 'joinTournamentModal', 'detailsModal'].forEach((id) => closeModal(id));
        }
    });

    // Initial rendering & live updates
    setActiveTab(activeSectionId, document.querySelector(`.tournoi-tab[data-target="${activeSectionId}"]`));
    renderTournamentSections(groupedData);
    updateCountsDisplay(counts);
    syncJoinButtonState();
    
    // localStorage-based refresh is already initialized above
})();

