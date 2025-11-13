(() => {
    const pageData = window.TOURNAMENT_PAGE_DATA || {};
    const tournaments = window.TOURNAMENT_DETAILS || [];

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
            createSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Création...';

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
                    return { ok: response.ok, data };
                })
                .then(({ ok, data }) => {
                    if (ok && (data.success ?? true)) {
                        showToast(data.message || 'Tournoi créé avec succès', 'success');
                        closeModal('createTournamentModal');
                        setTimeout(() => window.location.reload(), 900);
                    } else {
                        showToast(data.message || 'Impossible de créer le tournoi', 'error');
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
                        setTimeout(() => window.location.reload(), 900);
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

    // Initial rendering
    setActiveTab(activeSectionId, document.querySelector(`.tournoi-tab[data-target="${activeSectionId}"]`));
})();

