<?php
/**
 * Shared helper functions for player tournament listings.
 *
 * Provides reusable logic for fetching and formatting tournament data
 * across the player view and AJAX endpoints.
 */

declare(strict_types=1);

if (!function_exists('fetchAllSafe')) {
    /**
     * Execute a prepared query and return all rows while logging failures.
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
}

if (!function_exists('normalizeToken')) {
    /**
     * Normalize a value to lowercase ASCII for search/indexing.
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
}

if (!function_exists('statusBadgeClasses')) {
    /**
     * Map tournament status to Tailwind badge classes.
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
}

if (!function_exists('formatPriceLabel')) {
    /**
     * Convert numeric price to human readable label.
     */
    function formatPriceLabel($price): string
    {
        if ($price === null || $price === '') {
            return 'Gratuit';
        }
        $amount = number_format((float) $price, 0, ',', ' ');
        return $amount . ' DH';
    }
}

if (!function_exists('formatDateRangeLabel')) {
    /**
     * Build a localized date label for tournament schedule.
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
}

if (!function_exists('resolveStatus')) {
    /**
     * Determine status key/label based on statut column and dates.
     *
     * @return array{0:string,1:string}
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
}

if (!function_exists('computeDaysUntil')) {
    /**
     * Calculate remaining days until the tournament start date.
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
}

if (!function_exists('getPlayerTournamentData')) {
    /**
     * Build full dataset required by the player tournaments page/API.
     *
     * @return array{
     *     playerTeams: array<int, array>,
     *     availableTerrains: array<int, array>,
     *     grouped: array<string, array<int, array>>,
     *     allTournaments: array<int, array>,
     *     counts: array<string, int>,
     *     dataVersion: string|null
     * }
     */
    function getPlayerTournamentData(PDO $pdo, ?string $playerEmail): array
    {
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

        $playerTeamIds = array_map(static fn($team) => (int) ($team['id_equipe'] ?? 0), $playerTeams);

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
                    SELECT COUNT(*)
                    FROM tournoi_equipe te
                    WHERE te.id_tournoi = t.id_tournoi
                ), 0) AS nb_inscrits
             FROM tournoi t
             LEFT JOIN terrain tr ON t.id_terrain = tr.id_terrain
             ORDER BY t.id_tournoi DESC"
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
            $tournamentId = (int) ($row['id_tournoi'] ?? 0);
            $isRegistered = in_array($tournamentId, $registeredTournamentIds, true);
            $city = $row['ville'] ?? '';
            $searchIndex = trim(mb_strtolower(($row['nom_t'] ?? '') . ' ' . ($row['terrain_nom'] ?? '') . ' ' . ($row['description'] ?? ''), 'UTF-8'));
            $daysUntil = computeDaysUntil($start);

            $enriched = [
                'id' => $tournamentId,
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

        foreach ($grouped as &$list) {
            usort($list, static fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));
        }
        unset($list);

        usort($allTournaments, static fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

        $counts = [
            'upcoming' => count($grouped['upcoming']),
            'ongoing' => count($grouped['ongoing']),
            'my' => count(array_unique($registeredTournamentIds)),
            'completed' => count($grouped['completed']),
        ];

        $dataVersion = null;
        $encoded = json_encode($allTournaments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded !== false) {
            $dataVersion = sha1($encoded);
        }

        return [
            'playerTeams' => $playerTeams,
            'availableTerrains' => $availableTerrains,
            'grouped' => $grouped,
            'allTournaments' => $allTournaments,
            'counts' => $counts,
            'dataVersion' => $dataVersion,
        ];
    }
}

