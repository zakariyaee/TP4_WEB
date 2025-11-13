<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

$playerEmail = $_SESSION['user_email'] ?? null;

/**
 * Helper to safely fetch query results.
 *
 * @param PDO $pdo
 * @param string $sql
 * @param array $params
 * @return array
 */
function fetchAllSafe(PDO $pdo, string $sql, array $params = []): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Player tournaments query error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Normalize a value to lower-case without accents.
 */
function normalizeToken(?string $value): string
{
    if ($value === null) {
        return '';
    }

    $lower = mb_strtolower($value, 'UTF-8');
    $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $lower);
    return $transliterated !== false ? $transliterated : $lower;
}

/**
 * Map status key to badge colors.
 */
function statusBadgeClasses(string $statusKey): string
{
    return match ($statusKey) {
        'ongoing' => 'bg-emerald-100 text-emerald-700',
        'completed' => 'bg-gray-100 text-gray-600',
        'cancelled' => 'bg-red-100 text-red-700',
        default => 'bg-blue-100 text-blue-700',
    };
}

/**
 * Format price label.
 */
function formatPriceLabel($price): string
{
    if ($price === null || $price === '') {
        return 'Gratuit';
    }
    $amount = number_format((float) $price, 0, ',', ' ');
    return $amount . ' DH';
}

/**
 * Format date range label.
 */
function formatDateRangeLabel(?DateTimeImmutable $start, ?DateTimeImmutable $end): string
{
    if (!$start && !$end) {
        return 'Dates à confirmer';
    }

    if ($start && $end) {
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return 'Le ' . $start->format('d/m/Y');
        }
        return 'Du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y');
    }

    $ref = $start ?? $end;
    return ($start ? 'À partir du ' : 'Jusqu’au ') . $ref->format('d/m/Y');
}

/**
 * Compute status key and label.
 */
function resolveStatus(?string $statut, ?DateTimeImmutable $start, ?DateTimeImmutable $end): array
{
    $now = new DateTimeImmutable('now');
    $statusKey = 'upcoming';
    $statusLabel = 'À venir';

    $normalized = normalizeToken($statut);

    if ($normalized === 'annule') {
        return ['cancelled', 'Annulé'];
    }

    if ($normalized === 'termine') {
        return ['completed', 'Terminé'];
    }

    if ($normalized === 'en_cours') {
        return ['ongoing', 'En cours'];
    }

    if ($start && $end) {
        if ($now < $start) {
            return ['upcoming', 'À venir'];
        }
        if ($now > $end) {
            return ['completed', 'Terminé'];
        }
        return ['ongoing', 'En cours'];
    }

    if ($start) {
        return $now >= $start ? ['ongoing', 'En cours'] : ['upcoming', 'À venir'];
    }

    if ($end) {
        return $now > $end ? ['completed', 'Terminé'] : ['ongoing', 'En cours'];
    }

    return [$statusKey, $statusLabel];
}

/**
 * Compute remaining days before start.
 */
function computeDaysUntil(?DateTimeImmutable $start): ?int
{
    if (!$start) {
        return null;
    }
    $now = new DateTimeImmutable('today');
    if ($start <= $now) {
        return 0;
    }
    return (int) $now->diff($start)->format('%a');
}

$playerTeams = [];
if ($playerEmail) {
    $playerTeams = fetchAllSafe(
        $pdo,
        "SELECT DISTINCT e.id_equipe, e.nom_equipe
         FROM equipe e
         INNER JOIN equipe_joueur ej ON ej.id_equipe = e.id_equipe
         WHERE ej.id_joueur = :email
         ORDER BY e.nom_equipe",
        [':email' => $playerEmail]
    );
}

$playerTeamIds = array_map(static fn($team) => (int) $team['id_equipe'], $playerTeams);

$registeredTournamentIds = [];
if (!empty($playerTeamIds)) {
    $placeholders = implode(',', array_fill(0, count($playerTeamIds), '?'));
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT id_tournoi FROM tournoi_equipe WHERE id_equipe IN ($placeholders)");
        $stmt->execute($playerTeamIds);
        $registeredTournamentIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        error_log('Player tournaments registrations error: ' . $e->getMessage());
        $registeredTournamentIds = [];
    }
}

$availableTerrains = fetchAllSafe(
    $pdo,
    "SELECT id_terrain, nom_te, ville, categorie
     FROM terrain
     ORDER BY nom_te ASC"
);

$tournamentsRaw = fetchAllSafe(
    $pdo,
    "SELECT 
        t.id_tournoi,
        t.nom_t,
        t.date_debut,
        t.date_fin,
        t.size,
        t.description,
        t.statut,
        t.prix_inscription,
        t.regles,
        t.email_organisateur,
        t.id_terrain,
        tr.nom_te AS terrain_nom,
        tr.ville,
        tr.localisation,
        tr.image AS terrain_image,
        COALESCE((
            SELECT COUNT(*) FROM tournoi_equipe te WHERE te.id_tournoi = t.id_tournoi
        ), 0) AS nb_inscrits
     FROM tournoi t
     LEFT JOIN terrain tr ON t.id_terrain = tr.id_terrain
     ORDER BY t.date_debut ASC, t.id_tournoi DESC"
);

$grouped = [
    'upcoming' => [],
    'ongoing' => [],
    'completed' => [],
    'cancelled' => []
];
$allTournaments = [];

foreach ($tournamentsRaw as $row) {
    $start = !empty($row['date_debut']) ? new DateTimeImmutable($row['date_debut']) : null;
    $end = !empty($row['date_fin']) ? new DateTimeImmutable($row['date_fin']) : null;
    [$statusKey, $statusLabel] = resolveStatus($row['statut'] ?? null, $start, $end);

    $maxTeams = isset($row['size']) ? max(0, (int) $row['size']) : 0;
    $registeredCount = isset($row['nb_inscrits']) ? (int) $row['nb_inscrits'] : 0;
    $remainingSlots = $maxTeams > 0 ? max(0, $maxTeams - $registeredCount) : null;
    $progressPercent = $maxTeams > 0 ? (int) round(min(100, ($registeredCount / $maxTeams) * 100)) : 0;
    $isRegistered = in_array((int) $row['id_tournoi'], $registeredTournamentIds, true);
    $city = $row['ville'] ?? '';
    $searchIndex = trim(mb_strtolower(($row['nom_t'] ?? '') . ' ' . ($row['terrain_nom'] ?? '') . ' ' . ($row['description'] ?? ''), 'UTF-8'));
    $daysUntil = computeDaysUntil($start);

    $enriched = [
        'id' => (int) $row['id_tournoi'],
        'name' => $row['nom_t'] ?? 'Tournoi sans nom',
        'statusKey' => $statusKey,
        'statusLabel' => $statusLabel,
        'start' => $start ? $start->format('Y-m-d') : null,
        'end' => $end ? $end->format('Y-m-d') : null,
        'dateRangeLabel' => formatDateRangeLabel($start, $end),
        'daysUntil' => $daysUntil,
        'maxTeams' => $maxTeams,
        'registeredTeams' => $registeredCount,
        'remainingSlots' => $remainingSlots,
        'progressPercent' => $progressPercent,
        'priceLabel' => formatPriceLabel($row['prix_inscription'] ?? null),
        'rawPrice' => $row['prix_inscription'] ?? null,
        'description' => $row['description'] ?? '',
        'rules' => $row['regles'] ?? '',
        'terrainName' => $row['terrain_nom'] ?? 'Terrain à confirmer',
        'terrainLocation' => $row['localisation'] ?? '',
        'terrainCity' => $city,
        'terrainImage' => $row['terrain_image'] ?? '',
        'terrainId' => $row['id_terrain'] ?? null,
        'organizer' => $row['email_organisateur'] ?? '',
        'isRegistered' => $isRegistered,
        'isFull' => $remainingSlots !== null ? $remainingSlots <= 0 : false,
        'searchIndex' => $searchIndex,
    ];

    $grouped[$statusKey][] = $enriched;
    $allTournaments[] = $enriched;
}

$counts = [
    'upcoming' => count($grouped['upcoming']),
    'ongoing' => count($grouped['ongoing']),
    'my' => count(array_unique($registeredTournamentIds)),
    'completed' => count($grouped['completed']),
];

$pageData = [
    'endpoints' => [
        'create' => '../../actions/player/tournament/add_tournament.php',
        'join' => '../../actions/player/tournament/join_tournament.php',
    ],
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
                    Créer un tournoi
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
                <div class="text-3xl font-bold text-gray-900"><?php echo $counts['upcoming']; ?></div>
                <p class="text-sm text-gray-500 mt-2">Tournois programmés</p>
            </div>
            <div class="bg-white border border-blue-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-blue-600">En cours</span>
                    <span class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="fas fa-stopwatch"></i>
                    </span>
                </div>
                <div class="text-3xl font-bold text-gray-900"><?php echo $counts['ongoing']; ?></div>
                <p class="text-sm text-gray-500 mt-2">Compétitions actives</p>
            </div>
            <div class="bg-white border border-amber-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-amber-600">Mes inscriptions</span>
                    <span class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center text-amber-600">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
                <div class="text-3xl font-bold text-gray-900"><?php echo $counts['my']; ?></div>
                <p class="text-sm text-gray-500 mt-2">Tournois avec vos équipes</p>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-600">Terminés</span>
                    <span class="w-9 h-9 rounded-full bg-gray-50 flex items-center justify-center text-gray-600">
                        <i class="fas fa-flag-checkered"></i>
                    </span>
                </div>
                <div class="text-3xl font-bold text-gray-900"><?php echo $counts['completed']; ?></div>
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
                    <h2 class="text-2xl font-bold text-gray-900">Créer un tournoi</h2>
                    <p class="text-sm text-gray-500 mt-1">Proposez votre propre compétition à la communauté</p>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Terrain</label>
                        <select name="id_terrain"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition appearance-none">
                            <option value="">À confirmer</option>
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
                        Valider le tournoi
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
        window.TOURNAMENT_PAGE_DATA = <?php echo json_encode([
            'playerHasTeams' => !empty($playerTeams),
            'endpoints' => $pageData['endpoints'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="../../assets/js/player/tournaments.js"></script>
</body>

</html>

