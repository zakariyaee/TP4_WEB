<?php
$normalizedCity = htmlspecialchars(normalizeToken($tournoi['terrainCity']), ENT_QUOTES, 'UTF-8');
$searchIndex = htmlspecialchars($tournoi['searchIndex'], ENT_QUOTES, 'UTF-8');
$imageUrl = '';
if (!empty($tournoi['terrainImage'])) {
    $imageUrl = '../../assets/images/terrains/' . rawurlencode($tournoi['terrainImage']);
}
$remaining = $tournoi['remainingSlots'];
$remainingLabel = null;
if ($remaining !== null) {
    $remainingLabel = $remaining . ' place' . ($remaining > 1 ? 's' : '') . ' restantes';
}
$maxTeamsLabel = $tournoi['maxTeams'] ? $tournoi['maxTeams'] : null;
$daysUntilLabel = null;
if ($tournoi['daysUntil'] !== null && $tournoi['statusKey'] === 'upcoming') {
    $daysUntilLabel = $tournoi['daysUntil'] === 0 ? 'Aujourd’hui' : 'J-' . $tournoi['daysUntil'];
}
$hasDescription = !empty($tournoi['description']);
$terrainSubtitleParts = [$tournoi['terrainName']];
if (!empty($tournoi['terrainCity'])) {
    $terrainSubtitleParts[] = $tournoi['terrainCity'];
}
$terrainSubtitle = implode(' · ', array_filter($terrainSubtitleParts));
?>
<article
    class="tournoi-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition duration-300 flex flex-col"
    data-status="<?php echo htmlspecialchars($tournoi['statusKey'], ENT_QUOTES, 'UTF-8'); ?>"
    data-search="<?php echo $searchIndex; ?>"
    data-tournament-id="<?php echo (int) $tournoi['id']; ?>"
>
    <div class="relative h-48">
        <?php if ($imageUrl): ?>
            <img src="<?php echo $imageUrl; ?>" alt="Terrain du tournoi"
                 class="w-full h-full object-cover">
        <?php else: ?>
            <div class="w-full h-full bg-gradient-to-br from-emerald-500 via-green-600 to-slate-700 flex items-center justify-center">
                <i class="fas fa-futbol text-white text-6xl opacity-80"></i>
            </div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

        <div class="absolute top-4 right-4 flex flex-wrap gap-2 justify-end">
            <span class="px-3 py-1 rounded-full text-xs font-semibold backdrop-blur bg-white/90 <?php echo statusBadgeClasses($tournoi['statusKey']); ?>">
                <?php echo htmlspecialchars($tournoi['statusLabel'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <?php if ($tournoi['isRegistered']): ?>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500 text-white shadow">
                    <i class="fas fa-check mr-1"></i> Inscrit
                </span>
            <?php endif; ?>
            <?php if ($daysUntilLabel): ?>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 shadow">
                    <?php echo htmlspecialchars($daysUntilLabel, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="absolute bottom-4 left-4 right-4 text-white">
            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($tournoi['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="text-sm text-white/80 mt-1"><?php echo htmlspecialchars($terrainSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>

    <div class="p-5 flex-1 flex flex-col">
        <div class="space-y-2 text-sm text-gray-600 mb-5">
            <div class="flex items-center gap-2">
                <i class="fas fa-calendar text-emerald-600"></i>
                <span><?php echo htmlspecialchars($tournoi['dateRangeLabel'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php if (!empty($tournoi['terrainLocation'])): ?>
                <div class="flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-emerald-600"></i>
                    <span class="truncate"><?php echo htmlspecialchars($tournoi['terrainLocation'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>
            <div class="flex items-center gap-2">
                <i class="fas fa-users text-emerald-600"></i>
                <span>
                    <?php echo (int) $tournoi['registeredTeams']; ?>
                    <?php if ($maxTeamsLabel): ?>
                        / <?php echo (int) $maxTeamsLabel; ?>
                    <?php endif; ?>
                    équipes inscrites
                </span>
            </div>
        </div>

        <?php if ($hasDescription): ?>
            <p class="text-sm text-gray-600 mb-5">
                <?php echo htmlspecialchars(mb_strimwidth($tournoi['description'], 0, 220, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <?php if ($tournoi['maxTeams']): ?>
            <div class="mb-6">
                <div class="flex items-center justify-between text-xs font-semibold text-gray-500 mb-1">
                    <span>Progression</span>
                    <span><?php echo $tournoi['progressPercent']; ?>%</span>
                </div>
                <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-green-600"
                         style="width: <?php echo $tournoi['progressPercent']; ?>%;"></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-auto flex items-center justify-between">
            <div>
                <div class="text-xl font-bold text-emerald-600">
                    <?php echo htmlspecialchars($tournoi['priceLabel'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php if ($remainingLabel): ?>
                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($remainingLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        class="details-button px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition text-sm font-semibold"
                        data-tournament-id="<?php echo (int) $tournoi['id']; ?>">
                    Détails
                </button>
                <?php if ($tournoi['statusKey'] === 'cancelled'): ?>
                    <span class="px-4 py-2 rounded-xl bg-red-100 text-red-600 text-sm font-semibold">Annulé</span>
                <?php elseif ($tournoi['statusKey'] === 'completed'): ?>
                    <span class="px-4 py-2 rounded-xl bg-gray-100 text-gray-600 text-sm font-semibold">Terminé</span>
                <?php elseif ($tournoi['isRegistered']): ?>
                    <span class="px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-semibold">
                        <i class="fas fa-check mr-1"></i> Inscrit
                    </span>
                <?php elseif ($tournoi['isFull']): ?>
                    <span class="px-4 py-2 rounded-xl bg-gray-100 text-gray-500 text-sm font-semibold">Complet</span>
                <?php else: ?>
                    <button type="button"
                            class="join-button px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition"
                            data-tournament-id="<?php echo (int) $tournoi['id']; ?>">
                        S'inscrire
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</article>

