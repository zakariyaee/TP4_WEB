<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';
require_once '../../includes/player/tournament_helpers.php';

checkJoueur();

$playerEmail = $_SESSION['user_email'] ?? null;

$tournamentData = getPlayerTournamentData($pdo, $playerEmail);

$playerTeams = $tournamentData['playerTeams'];
$availableTerrains = $tournamentData['availableTerrains'];
$grouped = $tournamentData['grouped'];
$allTournaments = $tournamentData['allTournaments'];
$counts = $tournamentData['counts'];
$dataVersion = $tournamentData['dataVersion'];

$pageData = [
    'playerHasTeams' => !empty($playerTeams),
    'endpoints' => [
        'create' => '../../actions/player/tournament/add_tournament.php',
        'join' => '../../actions/player/tournament/join_tournament.php',
    ],
    'refreshEndpoint' => '../../actions/player/tournament/get_tournaments.php',
    'pollIntervalMs' => 1000,
    'dataVersion' => $dataVersion,
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournois - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .backdrop-blur {
            backdrop-filter: blur(12px);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 py-10 max-w-7xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-10">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-sm font-medium mb-3">
                    <i class="fas fa-trophy"></i>
                    Tournois & compétitions
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Participez aux meilleurs tournois</h1>
                <p class="text-gray-600 mt-3 max-w-xl">
                    Retrouvez des tournois adaptés à votre niveau, inscrivez votre équipe ou créez votre propre événement pour défier les meilleures formations.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button id="openCreateModal"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 text-white font-semibold shadow hover:bg-emerald-700 transition-colors">
                    <i class="fas fa-plus"></i>
                    Demander un tournoi
                </button>
            </div>
        </div>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-10">
            <div class="bg-white border border-emerald-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-emerald-600">À venir</span>
                    <span class="w-9 h-9 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <i class="fas fa-calendar-day"></i>
                    </span>
                </div>
                <div class="text-3xl font-bold text-gray-900" data-count="upcoming"><?php echo $counts['upcoming']; ?></div>
                <p class="text-sm text-gray-500 mt-2">Tournois programmés</p>
            </div>
            <div class="bg-white border border-blue-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-blue-600">En cours</span>
                    <span class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="fas fa-stopwatch"></i>
                    </span>
                </div>
                <div class="text-3xl font-bold text-gray-900" data-count="ongoing"><?php echo $counts['ongoing']; ?></div>
                <p class="text-sm text-gray-500 mt-2">Compétitions actives</p>
            </div>
            <div class="bg-white border border-amber-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-amber-600">Mes inscriptions</span>
                    <span class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center text-amber-600">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
                <div class="text-3xl font-bold text-gray-900" data-count="my"><?php echo $counts['my']; ?></div>
                <p class="text-sm text-gray-500 mt-2">Tournois avec vos équipes</p>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-600">Terminés</span>
                    <span class="w-9 h-9 rounded-full bg-gray-50 flex items-center justify-center text-gray-600">
                        <i class="fas fa-flag-checkered"></i>
                    </span>
                </div>
                <div class="text-3xl font-bold text-gray-900" data-count="completed"><?php echo $counts['completed']; ?></div>
                <p class="text-sm text-gray-500 mt-2">Tournois clôturés</p>
            </div>
        </section>

        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 mb-10">
            <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                <div>
                    <label for="searchTournament" class="block text-sm font-medium text-gray-600 mb-2">Rechercher un tournoi</label>
                    <div class="relative">
                        <i class="fas fa-search text-gray-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
                        <input id="searchTournament" type="text"
                               placeholder="Nom du tournoi, ville, terrain..."
                               class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-100 px-6">
                <div class="flex">
                    <button class="tournoi-tab active border-b-2 border-emerald-500 text-emerald-600 px-5 py-4 font-semibold"
                            data-target="section-upcoming">
                        Prochains tournois
                    </button>
                    <button class="tournoi-tab px-5 py-4 text-gray-600 hover:text-emerald-600 transition"
                            data-target="section-ongoing">
                        En cours
                    </button>
                    <button class="tournoi-tab px-5 py-4 text-gray-600 hover:text-emerald-600 transition"
                            data-target="section-completed">
                        Terminés
                    </button>
                </div>
            </div>

            <div class="p-6">
                <section id="section-upcoming" class="tournoi-section">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 <?php echo empty($grouped['upcoming']) ? 'hidden' : ''; ?>" data-cards-wrapper>
                        <?php foreach ($grouped['upcoming'] as $tournoi): ?>
                            <?php include __DIR__ . '/partials/tournament-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state text-center py-16 text-gray-500 <?php echo empty($grouped['upcoming']) ? '' : 'hidden'; ?>" data-empty-state>
                        <i class="fas fa-trophy text-5xl text-gray-300 mb-4"></i>
                        <p>Aucun tournoi à venir pour le moment.</p>
                    </div>
                </section>

                <section id="section-ongoing" class="tournoi-section hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 <?php echo empty($grouped['ongoing']) ? 'hidden' : ''; ?>" data-cards-wrapper>
                        <?php foreach ($grouped['ongoing'] as $tournoi): ?>
                            <?php include __DIR__ . '/partials/tournament-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state text-center py-16 text-gray-500 <?php echo empty($grouped['ongoing']) ? '' : 'hidden'; ?>" data-empty-state>
                        <i class="fas fa-running text-5xl text-gray-300 mb-4"></i>
                        <p>Aucun tournoi en cours actuellement.</p>
                    </div>
                </section>

                <section id="section-completed" class="tournoi-section hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 <?php echo empty($grouped['completed']) ? 'hidden' : ''; ?>" data-cards-wrapper>
                        <?php foreach ($grouped['completed'] as $tournoi): ?>
                            <?php include __DIR__ . '/partials/tournament-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state text-center py-16 text-gray-500 <?php echo empty($grouped['completed']) ? '' : 'hidden'; ?>" data-empty-state>
                        <i class="fas fa-flag-checkered text-5xl text-gray-300 mb-4"></i>
                        <p>Pas encore de tournoi terminé.</p>
                    </div>
                </section>
            </div>
        </section>
    </main>

    <!-- Create Tournament Modal -->
    <div id="createTournamentModal" class="fixed inset-0 hidden z-50 bg-black/40 backdrop-blur flex items-center justify-center px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Créer une demande de tournoi</h2>
                    <p class="text-sm text-gray-500 mt-1">Envoyez une demande au responsable du terrain pour créer un tournoi</p>
                </div>
                <button class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-modal="createTournamentModal">&times;</button>
            </div>
            <form id="createTournamentForm" class="p-8 space-y-5 overflow-y-auto flex-1 min-h-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom du tournoi *</label>
                        <input type="text" name="nom_tournoi" required
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date de début *</label>
                        <input type="date" name="date_debut" min="<?php echo (new DateTime())->format('Y-m-d'); ?>" required
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin *</label>
                        <input type="date" name="date_fin" min="<?php echo (new DateTime())->format('Y-m-d'); ?>" required
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre d’équipes *</label>
                        <input type="number" name="size" min="2" max="64" value="12" required
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prix d’inscription (DH)</label>
                        <input type="number" name="prix_inscription" min="0" step="50"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Terrain *</label>
                        <select name="id_terrain" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition appearance-none">
                            <option value="">Sélectionner un terrain...</option>
                            <?php foreach ($availableTerrains as $terrain): ?>
                                <option value="<?php echo (int) $terrain['id_terrain']; ?>">
                                    <?php
                                    $labelParts = [$terrain['nom_te'] ?? 'Terrain'];
                                    if (!empty($terrain['ville'])) {
                                        $labelParts[] = $terrain['ville'];
                                    }
                                    if (!empty($terrain['categorie'])) {
                                        $labelParts[] = $terrain['categorie'];
                                    }
                                    echo htmlspecialchars(implode(' · ', $labelParts), ENT_QUOTES, 'UTF-8');
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="4" placeholder="Présentez le format, les règles principales, les récompenses..."
                                  class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Règlement ou récompenses</label>
                        <textarea name="regles" rows="4" placeholder="Indiquez ici les règles spécifiques, récompenses, etc."
                                  class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" class="px-5 py-3 rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-50"
                            data-close-modal="createTournamentModal">
                        Annuler
                    </button>
                    <button type="submit" id="createTournamentSubmit"
                            class="px-5 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
                        Envoyer la demande
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Join Tournament Modal -->
    <div id="joinTournamentModal" class="fixed inset-0 hidden z-50 bg-black/40 backdrop-blur flex items-center justify-center px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden">
            <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Inscrire mon équipe</h2>
                    <p class="text-sm text-gray-500 mt-1">Sélectionnez l’équipe que vous souhaitez engager sur ce tournoi</p>
                </div>
                <button class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-modal="joinTournamentModal">&times;</button>
            </div>
            <form id="joinTournamentForm" class="p-8 space-y-5">
                <input type="hidden" name="id_tournoi" id="join_tournament_id">
                <?php if (!empty($playerTeams)): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Choisir mon équipe *</label>
                        <select name="id_equipe" id="join_team_select" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition appearance-none">
                            <option value="">Sélectionnez votre équipe</option>
                            <?php foreach ($playerTeams as $team): ?>
                                <option value="<?php echo (int) $team['id_equipe']; ?>">
                                    <?php echo htmlspecialchars($team['nom_equipe'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-amber-700">
                        <i class="fas fa-circle-info mr-2"></i>
                        Vous n’avez pas encore créé d’équipe. Rendez-vous dans la section <a href="teams.php" class="underline font-semibold">Mes équipes</a> pour en ajouter une avant de vous inscrire.
                    </div>
                <?php endif; ?>

                <div class="space-y-3 text-sm text-gray-600" id="joinTournamentSummary"></div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" class="px-5 py-3 rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-50"
                            data-close-modal="joinTournamentModal">
                        Annuler
                    </button>
                    <button type="submit" id="joinTournamentSubmit"
                            class="px-5 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition <?php echo empty($playerTeams) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                            <?php echo empty($playerTeams) ? 'disabled' : ''; ?>>
                        Confirmer mon inscription
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="fixed inset-0 hidden z-50 bg-black/40 backdrop-blur flex items-center justify-center px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100">
                <div>
                    <h2 id="detailsTitle" class="text-2xl font-bold text-gray-900">Détails du tournoi</h2>
                    <p id="detailsSubtitle" class="text-sm text-gray-500 mt-1"></p>
                </div>
                <button class="text-gray-400 hover:text-gray-600 text-2xl leading-none" data-close-modal="detailsModal">&times;</button>
            </div>
            <div id="detailsContent" class="p-8 space-y-6 overflow-y-auto"></div>
        </div>
    </div>

    <!-- Toast -->
    <div id="tournamentToast" class="hidden fixed top-6 right-6 px-6 py-4 rounded-2xl shadow-lg text-white z-50"></div>

    <script>
        window.TOURNAMENT_DETAILS = <?php echo json_encode($allTournaments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.TOURNAMENT_GROUPED = <?php echo json_encode($grouped, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.TOURNAMENT_COUNTS = <?php echo json_encode($counts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.TOURNAMENT_PAGE_DATA = <?php echo json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <!-- Universal Sync Manager - Load BEFORE other scripts -->
    <script src="../../assets/js/sync-manager.js"></script>
    <script src="../../assets/js/player/tournaments.js"></script>
</body>

</html>

