<?php
/**
 * Newsletter & Promotions shared helpers.
 *
 * Provides schema bootstrapping and reusable queries for newsletter features.
 *
 * @package actions/admin-manager/newsletter
 */

/**
 * Ensure newsletter related tables exist.
 */
function ensureNewsletterSchema(PDO $pdo): void
{
    // Table optionnelle pour stocker des brouillons ou campagnes enrichies.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Newsletter_Campagne (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titre VARCHAR(255) NOT NULL,
            objet VARCHAR(255) NOT NULL,
            contenu TEXT NOT NULL,
            audience_label VARCHAR(100) DEFAULT 'Tous les abonnés',
            destinataires_count INT DEFAULT 0,
            open_rate DECIMAL(5,2) DEFAULT 0,
            statut ENUM('brouillon','envoyee','planifiee') DEFAULT 'brouillon',
            date_envoi DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

/**
 * Check if a table exists in current schema.
 */
function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = :tableName
    ");
    $stmt->execute([':tableName' => $tableName]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Check if a column exists on a table in current schema.
 */
function columnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :tableName
          AND COLUMN_NAME = :columnName
    ");
    $stmt->execute([
        ':tableName' => $tableName,
        ':columnName' => $columnName,
    ]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Synchronise active platform users into the subscriber list (idempotent).
 */
function syncSubscribersFromUsers(PDO $pdo): void
{
    // Plus nécessaire avec le schéma existant.
}

/**
 * Mark promotions as expired when past expiration date.
 */
function updateExpiredPromotions(PDO $pdo): void
{
    if (tableExists($pdo, 'Newsletter_Promotion')) {
        $sql = "
            UPDATE Newsletter_Promotion
            SET statut = 'expiree'
            WHERE statut = 'active'
              AND date_expiration IS NOT NULL
              AND date_expiration < CURDATE()
        ";
        $pdo->exec($sql);
    }

    if (tableExists($pdo, 'promotion')) {
        $sqlLegacy = "
            UPDATE promotion
            SET statut = 'expiree'
            WHERE statut = 'active'
              AND date_fin IS NOT NULL
              AND date_fin < CURDATE()
        ";
        $pdo->exec($sqlLegacy);
    }
}

/**
 * Fetch dashboard stats.
 */
function getNewsletterStats(PDO $pdo): array
{
    $stats = [
        'subscribers' => 0,
        'campaignsSent' => 0,
        'averageOpenRate' => 0,
        'activePromotions' => 0,
    ];

    if (tableExists($pdo, 'utilisateur')) {
        $stats['subscribers'] = (int) $pdo->query("
            SELECT COUNT(*) FROM utilisateur WHERE statut_compte = 'actif'
        ")->fetchColumn();
    }

    $campaignsCount = 0;
    if (tableExists($pdo, 'newsletter')) {
        $campaignsCount += (int) $pdo->query("SELECT COUNT(*) FROM newsletter")->fetchColumn();

        if (tableExists($pdo, 'newsletter_abonnement')) {
            $averageStmt = $pdo->query("
                SELECT AVG(rate) FROM (
                    SELECT CASE 
                        WHEN COUNT(*) = 0 THEN NULL
                        ELSE (SUM(CASE WHEN statut_lecture = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100
                    END AS rate
                    FROM newsletter n
                    LEFT JOIN newsletter_abonnement na ON na.id_news = n.id_news
                    GROUP BY n.id_news
                ) rates
            ");
            $avgLegacy = $averageStmt->fetchColumn();
            if ($avgLegacy !== null) {
                $stats['averageOpenRate'] = round((float) $avgLegacy, 2);
            }
        }
    }

    if ($stats['averageOpenRate'] === 0 && tableExists($pdo, 'Newsletter_Campagne')) {
        $avgExtra = $pdo->query("
            SELECT AVG(open_rate) FROM Newsletter_Campagne WHERE statut = 'envoyee'
        ")->fetchColumn();
        if ($avgExtra !== null) {
            $stats['averageOpenRate'] = round((float) $avgExtra, 2);
        }
    }
    $stats['campaignsSent'] = $campaignsCount;

    if (tableExists($pdo, 'promotion')) {
        $stats['activePromotions'] = (int) $pdo->query("
            SELECT COUNT(*) FROM promotion
            WHERE statut = 'active'
              AND (date_fin IS NULL OR date_fin >= CURDATE())
        ")->fetchColumn();
    }

    return $stats;
}

/**
 * Retrieve campaigns history.
 */
function getNewsletterCampaigns(PDO $pdo): array
{
    $campaigns = [];

    if (tableExists($pdo, 'newsletter')) {
        $legacyStmt = $pdo->query("
            SELECT n.id_news,
                   n.titre,
                   n.contenu,
                   n.email_proprietaire,
                   DATE_FORMAT(n.date_envoi, '%Y-%m-%d') AS date_envoi
            FROM newsletter n
            ORDER BY n.date_envoi DESC
            LIMIT 100
        ");
        $legacy = $legacyStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($legacy)) {
            foreach ($legacy as $row) {
                $destCount = null;
                $openRate = null;
                if (tableExists($pdo, 'newsletter_abonnement')) {
                    $countStmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) AS total,
                            SUM(CASE WHEN statut_lecture = 1 THEN 1 ELSE 0 END) AS lus
                        FROM newsletter_abonnement
                        WHERE id_news = :id_news
                    ");
                    $countStmt->execute([':id_news' => $row['id_news']]);
                    $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
                    if ($counts) {
                        $destCount = (int) $counts['total'];
                        if ($counts['total'] > 0) {
                            $openRate = round(($counts['lus'] / $counts['total']) * 100, 2);
                        }
                    }
                }

                $campaigns[] = [
                    'id' => (int) $row['id_news'],
                    'titre' => $row['titre'] ?? '',
                    'objet' => $row['titre'] ?? '',
                    'contenu' => $row['contenu'] ?? '',
                    'envoyeur' => $row['email_proprietaire'] ?? '',
                    'statut' => 'envoyee',
                    'destinataires_count' => $destCount,
                    'open_rate' => $openRate,
                    'date_envoi' => $row['date_envoi'],
                    'created_at' => $row['date_envoi'],
                ];
            }
        }
    }

    if (tableExists($pdo, 'Newsletter_Campagne')) {
        $hasAudience = columnExists($pdo, 'Newsletter_Campagne', 'audience_label');
        $hasDestColumn = columnExists($pdo, 'Newsletter_Campagne', 'destinataires');
        $hasDestCount = columnExists($pdo, 'Newsletter_Campagne', 'destinataires_count');
        $hasOpenRate = columnExists($pdo, 'Newsletter_Campagne', 'open_rate');
        $hasDateEnvoi = columnExists($pdo, 'Newsletter_Campagne', 'date_envoi');
        $hasCreatedAt = columnExists($pdo, 'Newsletter_Campagne', 'created_at');

        $selectParts = [
            'id',
            'titre',
            'objet',
            'statut',
            'contenu'
        ];
        if ($hasAudience) {
            $selectParts[] = 'audience_label';
        }
        if ($hasDestCount) {
            $selectParts[] = 'destinataires_count';
        }
        if ($hasDestColumn) {
            $selectParts[] = 'destinataires';
        }
        if ($hasOpenRate) {
            $selectParts[] = 'open_rate';
        }
        if ($hasDateEnvoi) {
            $selectParts[] = "DATE_FORMAT(date_envoi, '%Y-%m-%d') AS date_envoi";
        }
        if ($hasCreatedAt) {
            $selectParts[] = "DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at";
        }

        $selectClause = implode(', ', $selectParts);
        $orderClause = $hasCreatedAt ? 'created_at DESC' : 'id DESC';

        $stmt = $pdo->query("
            SELECT $selectClause
            FROM Newsletter_Campagne
            ORDER BY $orderClause
            LIMIT 100
        ");
        $drafts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($drafts as $row) {
            $destCount = $row['destinataires_count'] ?? null;
            if ($destCount === null && $hasDestColumn && !empty($row['destinataires'])) {
                $decoded = json_decode($row['destinataires'], true);
                if (is_array($decoded)) {
                    if (isset($decoded['liste']) && is_array($decoded['liste'])) {
                        $destCount = count($decoded['liste']);
                    } else {
                        $destCount = count($decoded);
                    }
                }
            }

            $campaigns[] = [
                'id' => (int) ($row['id'] ?? 0),
                'titre' => $row['titre'] ?? '',
                'objet' => $row['objet'] ?? '',
                'contenu' => $row['contenu'] ?? '',
                'statut' => $row['statut'] ?? 'brouillon',
                'audience_label' => $hasAudience ? ($row['audience_label'] ?? 'Tous les abonnés') : 'Tous les abonnés',
                'destinataires_count' => $destCount,
                'open_rate' => $hasOpenRate ? ($row['open_rate'] ?? null) : null,
                'date_envoi' => $row['date_envoi'] ?? null,
                'created_at' => $row['created_at'] ?? null,
            ];
        }
    }

    // Sort by date desc manually to mix both sources
    usort($campaigns, function ($a, $b) {
        return strcmp($b['date_envoi'] ?? $b['created_at'] ?? '', $a['date_envoi'] ?? $a['created_at'] ?? '');
    });

    return $campaigns;
}

/**
 * Retrieve promotions.
 */
function getNewsletterPromotions(PDO $pdo, int $page = 1, int $perPage = 5): array
{
    $page = max(1, $page);
    $perPage = max(1, min(50, $perPage));

    $useLegacyTable = false;
    if (tableExists($pdo, 'promotion')) {
        $tableName = 'promotion';
    } elseif (tableExists($pdo, 'Newsletter_Promotion')) {
        $tableName = 'Newsletter_Promotion';
        $useLegacyTable = true;
    } else {
        return [
            'items' => [],
            'pagination' => [
                'page' => 1,
                'perPage' => $perPage,
                'total' => 0,
                'totalPages' => 1,
                'hasPrev' => false,
                'hasNext' => false,
            ],
        ];
    }

    $total = (int) $pdo->query("SELECT COUNT(*) FROM {$tableName}")->fetchColumn();
    if ($total === 0) {
        return [
            'items' => [],
            'pagination' => [
                'page' => 1,
                'perPage' => $perPage,
                'total' => 0,
                'totalPages' => 1,
                'hasPrev' => false,
                'hasNext' => false,
            ],
        ];
    }

    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    if (!$useLegacyTable) {
        $hasDateDebut = columnExists($pdo, 'promotion', 'date_debut');
        $hasDateFin = columnExists($pdo, 'promotion', 'date_fin');
        $hasUsageMax = columnExists($pdo, 'promotion', 'utilisation_max');
        $hasUsage = columnExists($pdo, 'promotion', 'utilisation');

        $selectFields = [
            'id_promotion AS id',
            'code_promo AS code',
            'description AS description',
            'pourcentage_reduction AS reduction',
            'statut'
        ];
        if ($hasDateDebut) {
            $selectFields[] = 'date_debut';
        }
        if ($hasDateFin) {
            $selectFields[] = 'date_fin';
        }
        if ($hasUsageMax) {
            $selectFields[] = 'utilisation_max';
        }
        if ($hasUsage) {
            $selectFields[] = 'utilisation';
        }

        $selectClause = implode(', ', $selectFields);

        $orderParts = [];
        if ($hasDateFin) {
            $orderParts[] = "statut = 'active' DESC";
            $orderParts[] = 'date_fin ASC';
        } else {
            $orderParts[] = "statut = 'active' DESC";
        }
        $orderParts[] = 'id_promotion DESC';
        $orderClause = implode(', ', array_unique($orderParts));

        $sql = "
            SELECT {$selectClause}
            FROM promotion
            ORDER BY {$orderClause}
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = array_map(function ($promo) use ($hasDateDebut, $hasDateFin, $hasUsageMax, $hasUsage) {
            return [
                'id' => (int) $promo['id'],
                'code' => $promo['code'],
                'description' => $promo['description'],
                'reduction' => $promo['reduction'] !== null ? (float) $promo['reduction'] : null,
                'utilisation_max' => $hasUsageMax ? ($promo['utilisation_max'] ?? null) : null,
                'utilisation' => $hasUsage ? ($promo['utilisation'] ?? null) : null,
                'statut' => $promo['statut'],
                'date_expiration' => $hasDateFin ? ($promo['date_fin'] ?? null) : null,
                'created_at' => $hasDateDebut ? ($promo['date_debut'] ?? null) : null,
            ];
        }, $rows);
    } else {
        $hasCreatedAt = columnExists($pdo, 'Newsletter_Promotion', 'created_at');
        $selectClause = "
            id,
            code,
            description,
            reduction,
            utilisation_max,
            utilisation,
            date_expiration,
            statut" . ($hasCreatedAt ? ", created_at" : '');

        $orderClause = $hasCreatedAt ? 'created_at DESC' : 'id DESC';

        $sql = "
            SELECT {$selectClause}
            FROM Newsletter_Promotion
            ORDER BY {$orderClause}
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = array_map(function ($promo) use ($hasCreatedAt) {
            return [
                'id' => (int) $promo['id'],
                'code' => $promo['code'],
                'description' => $promo['description'],
                'reduction' => $promo['reduction'] !== null ? (float) $promo['reduction'] : null,
                'utilisation_max' => $promo['utilisation_max'] ?? null,
                'utilisation' => $promo['utilisation'] ?? null,
                'statut' => $promo['statut'],
                'date_expiration' => $promo['date_expiration'] ?? null,
                'created_at' => $hasCreatedAt ? ($promo['created_at'] ?? null) : null,
            ];
        }, $rows);
    }

    return [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'hasPrev' => $page > 1,
            'hasNext' => $page < $totalPages,
        ],
    ];
}

/**
 * Fetch active subscriber emails.
 */
function getActiveSubscriberEmails(PDO $pdo): array
{
    if (!tableExists($pdo, 'utilisateur')) {
        return [];
    }

    $stmt = $pdo->query("
        SELECT email FROM utilisateur WHERE statut_compte = 'actif'
    ");
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

/**
 * Retrieve audience emails based on UI selection.
 */
function getAudienceEmails(PDO $pdo, string $audienceLabel): array
{
    if (!tableExists($pdo, 'utilisateur')) {
        return [];
    }

    $audienceLabel = strtolower(trim($audienceLabel));
    switch ($audienceLabel) {
        case 'joueurs actifs':
            $sql = "SELECT email FROM utilisateur WHERE statut_compte = 'actif' AND role = 'joueur'";
            break;
        case 'responsables':
            $sql = "SELECT email FROM utilisateur WHERE statut_compte = 'actif' AND role = 'responsable'";
            break;
        default:
            $sql = "SELECT email FROM utilisateur WHERE statut_compte = 'actif'";
            break;
    }

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

/**
 * Sanitize plain text.
 */
function sanitizeText(?string $value): string
{
    $clean = $value ?? '';
    $clean = strip_tags($clean);
    return trim($clean);
}

/**
 * Sanitize numeric percentage.
 */
function sanitizePercentage($value): float
{
    $clean = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($clean === false) {
        return 0.0;
    }
    return max(0.0, min(100.0, round($clean, 2)));
}

