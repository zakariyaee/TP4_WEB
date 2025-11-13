(() => {
    const config = window.PLAYER_INVITATIONS_DATA || {};
    const endpoints = config.endpoints || {};

    const selectors = {
        toast: document.getElementById('invitationsToast'),
        statPending: document.getElementById('statPendingInvites'),
        statAccepted: document.getElementById('statAcceptedInvites'),
        statTotal: document.getElementById('statTotalInvites'),
        statCaptainPending: document.getElementById('statCaptainPending'),
        pendingContainer: document.getElementById('pendingInvitesContainer'),
        pendingCount: document.getElementById('pendingInvitesCount'),
        emptyPending: document.getElementById('emptyPendingInvites'),
        historyContainer: document.getElementById('historyInvitesContainer'),
        historyCount: document.getElementById('historyInvitesCount'),
        emptyHistory: document.getElementById('emptyHistoryInvites'),
    };

    const showToast = (message, type = 'info') => {
        const palette = {
            success: 'bg-emerald-600',
            error: 'bg-red-600',
            warning: 'bg-amber-500',
            info: 'bg-blue-600',
        };
        const toast = selectors.toast;
        if (!toast) return;

        toast.className = `fixed top-6 right-6 px-6 py-4 rounded-2xl shadow-lg text-white z-50 ${palette[type] || palette.info}`;
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas fa-circle-info text-xl"></i>
                <span class="font-medium">${message}</span>
            </div>
        `;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3600);
    };

    const formatDateRange = (start, end) => {
        const formatDate = (value) => {
            if (!value) return null;
            try {
                return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date(value));
            } catch (_) {
                return value;
            }
        };
        const startLabel = formatDate(start);
        const endLabel = formatDate(end);

        if (startLabel && endLabel) {
            return startLabel === endLabel ? `Le ${startLabel}` : `Du ${startLabel} au ${endLabel}`;
        }
        if (startLabel) return `À partir du ${startLabel}`;
        if (endLabel) return `Jusqu'au ${endLabel}`;
        return 'Dates à confirmer';
    };

    const statusLabel = (status) => {
        switch ((status || '').toLowerCase()) {
            case 'confirmee': return { label: 'Confirmée', classes: 'bg-emerald-100 text-emerald-700' };
            case 'invitee': return { label: 'En attente', classes: 'bg-blue-100 text-blue-700' };
            case 'refusee': return { label: 'Refusée', classes: 'bg-red-100 text-red-700' };
            default: return { label: status || 'En attente', classes: 'bg-gray-100 text-gray-600' };
        }
    };

    const renderPendingInvite = (invite) => {
        const isCaptain = (invite.role_equipe || '').toLowerCase() === 'capitaine';
        const dateRange = formatDateRange(invite.date_debut, invite.date_fin);

        return `
            <article class="bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden">
                <div class="grid md:grid-cols-5">
                    <div class="md:col-span-3 p-6 border-b md:border-b-0 md:border-r border-gray-100">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                                ${invite.nom_equipe || 'Équipe'}
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                ${invite.role_equipe || ''}
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">${invite.nom_tournoi || 'Tournoi'}</h3>
                        <p class="text-sm text-gray-500 mb-4">${invite.description ? invite.description.slice(0, 140) + (invite.description.length > 140 ? '…' : '') : 'Pas de description fournie.'}</p>
                        <div class="grid sm:grid-cols-2 gap-3 text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-calendar text-emerald-600"></i>
                                <span>${dateRange}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-emerald-600"></i>
                                <span>${invite.terrain_nom || 'Terrain à préciser'}${invite.ville ? ` · ${invite.ville}` : ''}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-coins text-emerald-600"></i>
                                <span>${invite.prix_inscription !== null ? `${invite.prix_inscription} DH` : 'Tarif à confirmer'}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-flag text-emerald-600"></i>
                                <span>${statusLabel(invite.statut_tournoi).label}</span>
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-2 p-6 flex flex-col justify-between bg-gray-50">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-semibold text-gray-600">Réponse requise</span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">En attente</span>
                        </div>
                        <div class="space-y-3">
                            ${isCaptain
                                ? `<button class="w-full px-4 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition"
                                           data-action="respond"
                                           data-decision="accept"
                                           data-team="${invite.id_equipe}"
                                           data-tournament="${invite.id_tournoi}">
                                        <i class="fas fa-check mr-2"></i>Accepter l'invitation
                                   </button>
                                   <button class="w-full px-4 py-3 rounded-xl border border-red-200 text-red-600 font-semibold hover:bg-red-50 transition"
                                           data-action="respond"
                                           data-decision="decline"
                                           data-team="${invite.id_equipe}"
                                           data-tournament="${invite.id_tournoi}">
                                        <i class="fas fa-times mr-2"></i>Décliner
                                   </button>`
                                : `<div class="px-4 py-3 rounded-xl border border-dashed border-gray-300 text-center text-sm text-gray-500">
                                        Seul le capitaine peut répondre à cette invitation.
                                   </div>`}
                        </div>
                    </div>
                </div>
            </article>
        `;
    };

    const renderHistoryInvite = (invite) => {
        const status = statusLabel(invite.statut_participation);
        const dateRange = formatDateRange(invite.date_debut, invite.date_fin);
        return `
            <article class="bg-white border border-gray-100 rounded-2xl p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                            ${invite.nom_equipe || 'Équipe'}
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${status.classes}">
                            ${status.label}
                        </span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">${invite.nom_tournoi || 'Tournoi'}</h3>
                    <p class="text-sm text-gray-500">${dateRange}</p>
                </div>
                <div class="text-sm text-gray-400">
                    ${invite.ville ? `<span class="mr-4"><i class="fas fa-map-marker-alt mr-2"></i>${invite.ville}</span>` : ''}
                    <span><i class="fas fa-user-tag mr-2"></i>${invite.role_equipe || ''}</span>
                </div>
            </article>
        `;
    };

    const updateStats = (pendingInvites, historyInvites) => {
        const captainPending = pendingInvites.filter((invite) => (invite.role_equipe || '').toLowerCase() === 'capitaine').length;
        const acceptedCount = historyInvites.filter((invite) => (invite.statut_participation || '').toLowerCase() === 'confirmee').length;

        if (selectors.statPending) selectors.statPending.textContent = pendingInvites.length;
        if (selectors.statAccepted) selectors.statAccepted.textContent = acceptedCount;
        if (selectors.statTotal) selectors.statTotal.textContent = pendingInvites.length + historyInvites.length;
        if (selectors.statCaptainPending) selectors.statCaptainPending.textContent = captainPending;

        if (selectors.pendingCount) selectors.pendingCount.textContent = `${pendingInvites.length} ${pendingInvites.length > 1 ? 'invitations' : 'invitation'}`;
        if (selectors.historyCount) selectors.historyCount.textContent = `${historyInvites.length} ${historyInvites.length > 1 ? 'éléments' : 'élément'}`;
    };

    const renderInvitations = (invitations) => {
        const pending = invitations.filter((invite) => (invite.statut_participation || 'invitee').toLowerCase() === 'invitee');
        const history = invitations.filter((invite) => (invite.statut_participation || '').toLowerCase() !== 'invitee');

        if (selectors.pendingContainer) {
            selectors.pendingContainer.innerHTML = pending.map(renderPendingInvite).join('');
        }
        if (selectors.historyContainer) {
            selectors.historyContainer.innerHTML = history.map(renderHistoryInvite).join('');
        }

        if (selectors.emptyPending) {
            selectors.emptyPending.classList.toggle('hidden', pending.length > 0);
        }
        if (selectors.emptyHistory) {
            selectors.emptyHistory.classList.toggle('hidden', history.length > 0);
        }

        updateStats(pending, history);
    };

    const fetchInvitations = () => {
        if (!endpoints.fetch) {
            showToast('Service indisponible', 'error');
            return;
        }

        fetch(endpoints.fetch)
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    renderInvitations(data.invitations || []);
                } else {
                    showToast(data.message || 'Impossible de récupérer les invitations', 'error');
                }
            })
            .catch(() => showToast('Erreur de connexion au serveur', 'error'));
    };

    const respondInvitation = (teamId, tournamentId, decision) => {
        if (!endpoints.respond) return;
        fetch(endpoints.respond, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_equipe: Number(teamId),
                id_tournoi: Number(tournamentId),
                decision,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    showToast(data.message || 'Réponse enregistrée', 'success');
                    fetchInvitations();
                } else {
                    showToast(data.message || 'Impossible de traiter votre réponse', 'error');
                }
            })
            .catch(() => showToast('Erreur réseau', 'error'));
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action="respond"]');
        if (!button) return;
        const teamId = button.dataset.team;
        const tournamentId = button.dataset.tournament;
        const decision = button.dataset.decision;
        respondInvitation(teamId, tournamentId, decision);
    });

    fetchInvitations();
})();

