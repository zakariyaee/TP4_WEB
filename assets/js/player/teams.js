(() => {
    const config = window.PLAYER_TEAMS_DATA || {};
    const endpoints = config.endpoints || {};

    let teams = [];
    let joinableTeams = [];
    let filteredJoinableTeams = [];
    let currentMembersTeamId = null;

    const selectors = {
        statTotalTeams: document.getElementById('statTotalTeams'),
        statCaptainTeams: document.getElementById('statCaptainTeams'),
        statTotalTournaments: document.getElementById('statTotalTournaments'),
        statUpcomingTournaments: document.getElementById('statUpcomingTournaments'),
        captainContainer: document.getElementById('captainTeamsContainer'),
        memberContainer: document.getElementById('memberTeamsContainer'),
        captainCount: document.getElementById('captainTeamsCount'),
        memberCount: document.getElementById('memberTeamsCount'),
        emptyCaptainState: document.getElementById('emptyCaptainState'),
        emptyMemberState: document.getElementById('emptyMemberState'),
        toast: document.getElementById('teamsToast'),
        teamMembersList: document.getElementById('teamMembersList'),
        teamMembersTitle: document.getElementById('teamMembersTitle'),
        teamMembersSubtitle: document.getElementById('teamMembersSubtitle'),
        leaveTeamButton: document.getElementById('leaveTeamButton'),
        joinTeamsContainer: document.getElementById('joinTeamsContainer'),
        joinTeamsEmpty: document.getElementById('joinTeamsEmpty'),
        joinTeamSearch: document.getElementById('joinTeamSearch'),
    };

    const showToast = (message, type = 'info') => {
        const palette = {
            success: 'bg-emerald-600',
            error: 'bg-red-600',
            warning: 'bg-amber-500',
            info: 'bg-blue-600',
        };
        const toast = selectors.toast;
        if (!toast) {
            return;
        }
        toast.className = `fixed top-6 right-6 px-6 py-4 rounded-2xl shadow-lg text-white z-50 ${palette[type] || palette.info}`;
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas fa-circle-info text-xl"></i>
                <span class="font-medium">${message}</span>
            </div>
        `;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 3500);
    };

    const openModal = (id) => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
        }
    };

    const closeModal = (id) => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    const formatDate = (value) => {
        if (!value) return 'Date inconnue';
        try {
            return new Intl.DateTimeFormat('fr-FR', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
            }).format(new Date(value));
        } catch (_) {
            return value;
        }
    };

    const computeStats = () => {
        const totalTeams = teams.length;
        const captainTeams = teams.filter((team) => (team.role_equipe || '').toLowerCase() === 'capitaine').length;
        const totalTournaments = teams.reduce((acc, team) => acc + (Number(team.tournoi_count) || 0), 0);
        const upcomingTournaments = teams.reduce((acc, team) => acc + (Number(team.tournoi_avenir_count) || 0), 0);

        if (selectors.statTotalTeams) selectors.statTotalTeams.textContent = totalTeams;
        if (selectors.statCaptainTeams) selectors.statCaptainTeams.textContent = captainTeams;
        if (selectors.statTotalTournaments) selectors.statTotalTournaments.textContent = totalTournaments;
        if (selectors.statUpcomingTournaments) selectors.statUpcomingTournaments.textContent = upcomingTournaments;
    };

    const renderJoinableTeams = () => {
        if (!selectors.joinTeamsContainer || !selectors.joinTeamsEmpty) {
            return;
        }

        selectors.joinTeamsContainer.innerHTML = filteredJoinableTeams.map((team) => {
            const capacityLabel = `${team.membre_count} / ${team.max_members} joueurs`;
            const isOpen = team.is_open;
            return `
                <div class="border border-gray-100 rounded-2xl p-5 bg-white hover:shadow-md transition flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">${team.nom_equipe || 'Équipe'}</h3>
                        <p class="text-sm text-gray-500">${team.email_equipe || '—'}</p>
                        <div class="flex items-center gap-3 text-xs text-gray-400 mt-2">
                            <span><i class="fas fa-calendar-alt mr-1"></i>${team.date_creation ? formatDate(team.date_creation) : 'Création inconnue'}</span>
                            <span><i class="fas fa-users mr-1"></i>${capacityLabel}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="px-4 py-2 rounded-lg ${isOpen ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-gray-200 text-gray-500 cursor-not-allowed'} text-sm font-semibold transition"
                                ${isOpen ? `data-action="join-now" data-team="${team.id_equipe}"` : 'disabled'}>
                            ${isOpen ? 'Rejoindre' : 'Équipe complète'}
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        selectors.joinTeamsEmpty.classList.toggle('hidden', filteredJoinableTeams.length > 0);
        selectors.joinTeamsContainer.classList.toggle('hidden', filteredJoinableTeams.length === 0);
    };

    const filterJoinableTeams = (term = '') => {
        const normalized = term.toLowerCase().trim();
        filteredJoinableTeams = joinableTeams.filter((team) => {
            if (!team.is_open) return false;
            if (!normalized) return true;
            return (team.nom_equipe || '').toLowerCase().includes(normalized)
                || (team.email_equipe || '').toLowerCase().includes(normalized)
                || String(team.id_equipe).includes(normalized);
        });
        renderJoinableTeams();
    };

    const createTeamCard = (team) => {
        const role = (team.role_equipe || '').toLowerCase();
        const isCaptain = role === 'capitaine';
        const code = team.id_equipe;
        const members = Number(team.membre_count) || 0;
        const tournaments = Number(team.tournoi_count) || 0;
        const upcoming = Number(team.tournoi_avenir_count) || 0;
        const createdAt = team.date_creation ? formatDate(team.date_creation) : null;
        const joinedAt = team.date_adhesion ? formatDate(team.date_adhesion) : null;

        return `
            <article class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition flex flex-col">
                <div class="p-6 flex-1 flex flex-col gap-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold ${isCaptain ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'} mb-2">
                                ${isCaptain ? 'Capitaine' : 'Membre'}
                            </span>
                            <h3 class="text-xl font-bold text-gray-900">${team.nom_equipe || 'Équipe sans nom'}</h3>
                            <p class="text-sm text-gray-500 mt-1">${team.email_equipe || '—'}</p>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600 transition" data-action="open-members" data-team="${code}" title="Voir les membres">
                            <i class="fas fa-chevron-right text-lg"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-sm text-gray-600">
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <div class="text-emerald-600 font-bold text-lg">${members}</div>
                            <div class="text-xs">Membres</div>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <div class="text-emerald-600 font-bold text-lg">${tournaments}</div>
                            <div class="text-xs">Tournois disputés</div>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <div class="text-emerald-600 font-bold text-lg">${upcoming}</div>
                            <div class="text-xs">Tournois à venir</div>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 space-y-1">
                        ${createdAt ? `<div><span class="font-semibold text-gray-600">Créée</span> ${createdAt}</div>` : ''}
                        ${joinedAt ? `<div><span class="font-semibold text-gray-600">Rejoint</span> ${joinedAt}</div>` : ''}
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end">
                    <button class="px-4 py-2 rounded-lg border border-red-200 text-red-600 text-sm font-semibold hover:bg-red-50 transition"
                            data-action="leave-team" data-team="${code}">
                        Quitter
                    </button>
                </div>
            </article>
        `;
    };

    const renderTeams = () => {
        const captainTeams = teams.filter((team) => (team.role_equipe || '').toLowerCase() === 'capitaine');
        const memberTeams = teams.filter((team) => (team.role_equipe || '').toLowerCase() !== 'capitaine');

        if (selectors.captainContainer) {
            selectors.captainContainer.innerHTML = captainTeams.map(createTeamCard).join('') || '';
        }
        if (selectors.memberContainer) {
            selectors.memberContainer.innerHTML = memberTeams.map(createTeamCard).join('') || '';
        }
        if (selectors.captainCount) {
            selectors.captainCount.textContent = `${captainTeams.length} ${captainTeams.length > 1 ? 'équipes' : 'équipe'}`;
        }
        if (selectors.memberCount) {
            selectors.memberCount.textContent = `${memberTeams.length} ${memberTeams.length > 1 ? 'équipes' : 'équipe'}`;
        }

        if (selectors.emptyCaptainState) {
            selectors.emptyCaptainState.classList.toggle('hidden', captainTeams.length > 0);
        }
        if (selectors.emptyMemberState) {
            selectors.emptyMemberState.classList.toggle('hidden', memberTeams.length > 0);
        }
    };

    const loadTeams = () => {
        if (!endpoints.list) {
            showToast("Service indisponible", 'error');
            return;
        }
        fetch(endpoints.list)
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    teams = data.teams || [];
                    computeStats();
                    renderTeams();
                } else {
                    showToast(data.message || 'Impossible de récupérer vos équipes', 'error');
                }
            })
            .catch(() => showToast('Erreur de connexion au serveur', 'error'));
    };

    const openMembersModal = (teamId) => {
        if (!endpoints.members) return;
        fetch(`${endpoints.members}?id_equipe=${teamId}`)
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    showToast(data.message || 'Erreur lors du chargement des membres', 'error');
                    return;
                }

                currentMembersTeamId = teamId;

                const team = teams.find((item) => Number(item.id_equipe) === Number(teamId));
                if (team && selectors.teamMembersTitle) {
                    selectors.teamMembersTitle.textContent = team.nom_equipe || 'Équipe';
                }
            if (selectors.teamMembersSubtitle) {
                selectors.teamMembersSubtitle.textContent = `${Number(team?.membre_count) || data.members.length} membres`;
            }

                if (selectors.leaveTeamButton) {
                    selectors.leaveTeamButton.dataset.team = teamId;
                }

                if (selectors.teamMembersList) {
                    selectors.teamMembersList.innerHTML = data.members.map((member) => {
                        const role = (member.role_equipe || '').toLowerCase();
                        return `
                            <div class="flex items-center justify-between gap-4 border border-gray-100 rounded-2xl p-4 hover:border-emerald-200 transition">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-semibold uppercase">
                                        ${(member.prenom || member.nom || member.email || '?').substring(0, 2)}
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-800">${member.prenom || ''} ${member.nom || ''}</div>
                                        <div class="text-xs text-gray-500">${member.email || ''}</div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            ${role === 'capitaine' ? '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[11px] font-semibold mr-2">Capitaine</span>' : ''}
                                            Membre depuis ${member.date_adhesion ? formatDate(member.date_adhesion) : '—'}
                                        </div>
                                    </div>
                                </div>
                                ${role !== 'capitaine' && team && (team.role_equipe || '').toLowerCase() === 'capitaine'
                                    ? `<button class="text-sm text-red-600 hover:text-red-700 flex items-center gap-2"
                                               data-action="remove-member"
                                               data-team="${teamId}"
                                               data-email="${member.email}">
                                           <i class="fas fa-user-minus"></i> Retirer
                                       </button>`
                                    : ''}
                            </div>
                        `;
                    }).join('');
                }

                openModal('teamMembersModal');
            })
            .catch(() => showToast('Erreur réseau', 'error'));
    };

    const loadJoinableTeams = () => {
        if (!endpoints.search) {
            return;
        }

        if (selectors.joinTeamsContainer) {
            selectors.joinTeamsContainer.innerHTML = `
                <div class="flex justify-center py-10 text-gray-400">
                    <i class="fas fa-spinner fa-spin text-xl mr-2"></i>
                    <span class="text-sm">Chargement des équipes disponibles...</span>
                </div>
            `;
        }

        fetch(endpoints.search)
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    joinableTeams = data.teams || [];
                    filterJoinableTeams(selectors.joinTeamSearch ? selectors.joinTeamSearch.value : '');
                } else {
                    joinableTeams = [];
                    filteredJoinableTeams = [];
                    renderJoinableTeams();
                    showToast(data.message || 'Impossible de charger les équipes disponibles', 'error');
                }
            })
            .catch(() => {
                joinableTeams = [];
                filteredJoinableTeams = [];
                renderJoinableTeams();
                showToast('Erreur réseau', 'error');
            });
    };

    const handleCreateTeam = () => {
        const form = document.getElementById('createTeamForm');
        const submitBtn = document.getElementById('createTeamSubmit');
        if (!form || !submitBtn || !endpoints.create) return;

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            submitBtn.disabled = true;
            const originalLabel = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Création...';

            fetch(endpoints.create, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        showToast(data.message || "Équipe créée", 'success');
                        closeModal('createTeamModal');
                        form.reset();
                        loadTeams();
                    } else {
                        showToast(data.message || "Impossible de créer l'équipe", 'error');
                    }
                })
                .catch(() => showToast('Erreur de connexion au serveur', 'error'))
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalLabel;
                });
        });
    };

    const leaveTeam = (teamId) => {
        if (!endpoints.leave) return;
        fetch(endpoints.leave, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_equipe: teamId }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    showToast(data.message || "Vous avez quitté l'équipe", 'success');
                    closeModal('teamMembersModal');
                    loadTeams();
                } else {
                    showToast(data.message || "Impossible de quitter l'équipe", 'error');
                }
            })
            .catch(() => showToast('Erreur réseau', 'error'));
    };

    const removeMember = (teamId, email) => {
        if (!endpoints.removeMember) return;
        fetch(endpoints.removeMember, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_equipe: teamId, id_joueur: email }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    showToast(data.message || "Membre retiré", 'success');
                    openMembersModal(teamId);
                } else {
                    showToast(data.message || "Impossible de retirer ce membre", 'error');
                }
            })
            .catch(() => showToast('Erreur réseau', 'error'));
    };

    const joinTeam = (teamId) => {
        if (!endpoints.join) return;
        fetch(endpoints.join, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_equipe: Number(teamId) }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    showToast(data.message || "Vous avez rejoint l'équipe", 'success');
                    closeModal('joinTeamModal');
                    loadTeams();
                } else {
                    showToast(data.message || "Impossible de rejoindre cette équipe", 'error');
                }
            })
            .catch(() => showToast('Erreur réseau', 'error'));
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }
        const action = button.dataset.action;
        const teamId = button.dataset.team;

        switch (action) {
            case 'open-members':
                openMembersModal(teamId);
                break;
            case 'leave-team':
                leaveTeam(teamId);
                break;
            case 'remove-member':
                removeMember(teamId, button.dataset.email);
                break;
            case 'join-now':
                joinTeam(teamId);
                break;
            default:
                break;
        }
    });

    if (selectors.leaveTeamButton) {
        selectors.leaveTeamButton.addEventListener('click', () => {
            if (currentMembersTeamId) {
                leaveTeam(currentMembersTeamId);
            }
        });
    }

    document.querySelectorAll('[data-close-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
            closeModal(btn.getAttribute('data-close-modal'));
        });
    });

    const openCreateTeamButton = document.getElementById('openCreateTeam');
    if (openCreateTeamButton) {
        openCreateTeamButton.addEventListener('click', () => openModal('createTeamModal'));
    }
    const openJoinTeamButton = document.getElementById('openJoinTeam');
    if (openJoinTeamButton) {
        openJoinTeamButton.addEventListener('click', () => {
            openModal('joinTeamModal');
            if (selectors.joinTeamSearch) {
                selectors.joinTeamSearch.value = '';
            }
            loadJoinableTeams();
        });
    }
    const emptyCaptainCreate = document.getElementById('emptyCaptainCreate');
    if (emptyCaptainCreate) {
        emptyCaptainCreate.addEventListener('click', () => openModal('createTeamModal'));
    }
    const emptyMemberJoin = document.getElementById('emptyMemberJoin');
    if (emptyMemberJoin) {
        emptyMemberJoin.addEventListener('click', () => {
            openModal('joinTeamModal');
            if (selectors.joinTeamSearch) {
                selectors.joinTeamSearch.value = '';
            }
            loadJoinableTeams();
        });
    }

    document.querySelectorAll('#createTeamModal, #joinTeamModal, #teamMembersModal').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });

    handleCreateTeam();
    if (selectors.joinTeamSearch) {
        selectors.joinTeamSearch.addEventListener('input', (event) => {
            filterJoinableTeams(event.target.value);
        });
    }
    loadTeams();
})();

