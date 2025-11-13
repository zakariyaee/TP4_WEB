<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

$playerName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes équipes - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .scrollbar-thin::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgba(16, 185, 129, 0.35);
            border-radius: 9999px;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 py-10 max-w-7xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-12">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-sm font-medium mb-3">
                    <i class="fas fa-users"></i>
                    Mes équipes
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Coordonnez votre collectif</h1>
                <p class="text-gray-600 mt-3 max-w-2xl">
                    Créez votre équipe, invitez vos coéquipiers et suivez vos participations aux tournois TerrainBook. Organisez-vous comme les pros.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button id="openJoinTeam"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-emerald-200 text-emerald-700 font-semibold hover:border-emerald-400 transition">
                    <i class="fas fa-user-plus"></i>
                    Rejoindre une équipe
                </button>
                <button id="openCreateTeam"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 text-white font-semibold shadow hover:bg-emerald-700 transition">
                    <i class="fas fa-plus"></i>
                    Créer une équipe
                </button>
            </div>
        </div>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-12">
            <div class="bg-white border border-emerald-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-emerald-600">Équipes</span>
                    <span class="w-9 h-9 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
                <div id="statTotalTeams" class="text-3xl font-bold text-gray-900">0</div>
                <p class="text-sm text-gray-500 mt-2">Total d'équipes</p>
            </div>
            <div class="bg-white border border-blue-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-blue-600">Capitanats</span>
                    <span class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="fas fa-crown"></i>
                    </span>
                </div>
                <div id="statCaptainTeams" class="text-3xl font-bold text-gray-900">0</div>
                <p class="text-sm text-gray-500 mt-2">Équipes dont vous êtes capitaine</p>
            </div>
            <div class="bg-white border border-amber-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-amber-600">Tournois engagés</span>
                    <span class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center text-amber-600">
                        <i class="fas fa-trophy"></i>
                    </span>
                </div>
                <div id="statTotalTournaments" class="text-3xl font-bold text-gray-900">0</div>
                <p class="text-sm text-gray-500 mt-2">Tournois disputés par vos équipes</p>
            </div>
            <div class="bg-white border border-purple-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-purple-600">À venir</span>
                    <span class="w-9 h-9 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                </div>
                <div id="statUpcomingTournaments" class="text-3xl font-bold text-gray-900">0</div>
                <p class="text-sm text-gray-500 mt-2">Tournois à venir sur votre agenda</p>
            </div>
        </section>

        <section class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Mes équipes (capitaine)</h2>
                <span id="captainTeamsCount" class="text-sm text-gray-500">0 équipe</span>
            </div>
            <div id="captainTeamsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>
            <div id="emptyCaptainState" class="hidden border border-dashed border-emerald-200 rounded-2xl bg-emerald-50/40 p-10 text-center text-emerald-700 mt-6">
                <i class="fas fa-lightbulb text-3xl mb-4"></i>
                <p class="font-semibold mb-2">Vous n'êtes capitaine d'aucune équipe</p>
                <p class="text-sm mb-6">Créez votre propre équipe pour inviter vos coéquipiers et piloter vos tournois.</p>
                <button id="emptyCaptainCreate" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
                    <i class="fas fa-plus"></i>
                    Créer une équipe
                </button>
            </div>
        </section>

        <section class="mb-16">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Équipes auxquelles je participe</h2>
                <span id="memberTeamsCount" class="text-sm text-gray-500">0 équipe</span>
            </div>
            <div id="memberTeamsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>
            <div id="emptyMemberState" class="hidden border border-dashed border-gray-200 rounded-2xl bg-white p-10 text-center text-gray-500">
                <i class="fas fa-users-slash text-3xl mb-4"></i>
                <p class="font-semibold mb-2">Vous n'avez rejoint aucune équipe pour l'instant</p>
                <p class="text-sm mb-6">Demandez à votre capitaine de vous partager le code d'équipe ou rejoignez une nouvelle formation.</p>
                <button id="emptyMemberJoin" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-emerald-200 text-emerald-700 font-semibold hover:border-emerald-400 transition">
                    <i class="fas fa-user-plus"></i>
                    Rejoindre une équipe
                </button>
            </div>
        </section>
    </main>

    <!-- Modals -->
    <div id="createTeamModal" class="fixed inset-0 hidden z-50 bg-black/40 backdrop-blur flex items-center justify-center px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden">
            <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Créer une nouvelle équipe</h2>
                    <p class="text-sm text-gray-500 mt-1">Donnez un nom à votre collectif et invitez vos partenaires</p>
                </div>
                <button class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-modal="createTeamModal">&times;</button>
            </div>
            <form id="createTeamForm" class="p-8 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'équipe *</label>
                    <input type="text" name="nom_equipe" required
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">E-mail de contact</label>
                    <input type="email" name="email_equipe" placeholder="exemple@terrainbook.com"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    <p class="text-xs text-gray-500 mt-1">Laissez vide pour utiliser votre adresse e-mail.</p>
                </div>
                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="button" class="px-5 py-3 rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-50 transition"
                            data-close-modal="createTeamModal">Annuler</button>
                    <button type="submit" id="createTeamSubmit"
                            class="px-5 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
                        Créer l'équipe
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="joinTeamModal" class="fixed inset-0 hidden z-50 bg-black/40 backdrop-blur flex items-center justify-center px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Rejoindre une équipe</h2>
                    <p class="text-sm text-gray-500 mt-1">Sélectionnez une équipe ouverte qui recherche encore des joueurs</p>
                </div>
                <button class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-modal="joinTeamModal">&times;</button>
            </div>
            <div class="p-6 border-b border-gray-100">
                <div class="relative max-w-lg">
                    <i class="fas fa-search text-gray-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
                    <input id="joinTeamSearch" type="text" placeholder="Rechercher une équipe par son nom..."
                           class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>
            </div>
            <div id="joinTeamsContainer" class="p-6 space-y-4 overflow-y-auto flex-1">
                <!-- Cartes équipes injectées via JS -->
            </div>
            <div id="joinTeamsEmpty" class="hidden px-8 py-10 text-center text-gray-500">
                <i class="fas fa-users-slash text-3xl mb-3"></i>
                <p class="font-semibold text-gray-700 mb-2">Aucune équipe disponible pour le moment</p>
                <p class="text-sm">Revenez plus tard ou créez votre propre collectif pour inviter vos amis.</p>
            </div>
        </div>
    </div>

    <div id="teamMembersModal" class="fixed inset-0 hidden z-50 bg-black/40 backdrop-blur flex items-center justify-center px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100">
                <div>
                    <h2 id="teamMembersTitle" class="text-2xl font-bold text-gray-900">Membres de l'équipe</h2>
                    <p id="teamMembersSubtitle" class="text-sm text-gray-500 mt-1"></p>
                </div>
                <button class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-modal="teamMembersModal">&times;</button>
            </div>
            <div class="p-8 overflow-y-auto scrollbar-thin flex-1">
                <div id="teamMembersList" class="space-y-4"></div>
            </div>
            <div class="px-8 py-4 border-t border-gray-100 flex items-center justify-end text-sm text-gray-500">
                <button id="leaveTeamButton" class="px-4 py-2 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">
                    Quitter l'équipe
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="teamsToast" class="hidden fixed top-6 right-6 px-6 py-4 rounded-2xl shadow-lg text-white z-50"></div>

    <script>
        window.PLAYER_TEAMS_DATA = {
            endpoints: {
                list: '../../actions/player/team/get_teams.php',
                create: '../../actions/player/team/create_team.php',
                join: '../../actions/player/team/join_team.php',
                search: '../../actions/player/team/search_teams.php',
                leave: '../../actions/player/team/leave_team.php',
                members: '../../actions/player/team/get_team_members.php',
                removeMember: '../../actions/player/team/remove_member.php'
            },
            playerName: <?php echo json_encode($playerName, JSON_UNESCAPED_UNICODE); ?>
        };
    </script>
    <script src="../../assets/js/player/teams.js"></script>
</body>

</html>

