<?php
/**
 * Create a new promotion for newsletters.
 *
 * @package actions/admin-manager/newsletter
 */

require_once '../../../config/database.php';
require_once '../../../check_auth.php';
require_once __DIR__ . '/newsletter_service.php';

checkAdminOrRespo();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

try {
    ensureNewsletterSchema($pdo);

    $code = strtoupper(trim($_POST['code'] ?? ''));
    $description = sanitizeText($_POST['description'] ?? '');
    $reduction = sanitizePercentage($_POST['reduction'] ?? 0);
    $utilisationMaxRaw = $_POST['utilisation_max'] ?? 0;
    $utilisationMax = is_numeric($utilisationMaxRaw) ? (int) $utilisationMaxRaw : 0;
    $dateExpirationInput = trim($_POST['date_expiration'] ?? '');

    if ($code === '' || $reduction <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Merci de renseigner au moins un code et une réduction.']);
        exit;
    }

    if ($utilisationMax < 0) {
        $utilisationMax = 0;
    }

    $dateExpirationSql = null;
    if ($dateExpirationInput !== '') {
        $acceptedFormats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        $validDate = null;
        foreach ($acceptedFormats as $format) {
            $dateValidation = DateTime::createFromFormat($format, $dateExpirationInput);
            if ($dateValidation && $dateValidation->format($format) === $dateExpirationInput) {
                $validDate = $dateValidation;
                break;
            }
        }

        if (!$validDate) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Format de date invalide. Utilisez AAAA-MM-JJ ou JJ/MM/AAAA.']);
            exit;
        }

        $dateExpirationSql = $validDate->format('Y-m-d');
    }

    if (tableExists($pdo, 'promotion')) {
        $hasUsage = columnExists($pdo, 'promotion', 'utilisation_max');
        $hasUsageCurrent = columnExists($pdo, 'promotion', 'utilisation');
        $hasDateDebut = columnExists($pdo, 'promotion', 'date_debut');
        $hasDateFin = columnExists($pdo, 'promotion', 'date_fin');

        $fields = ['code_promo', 'description', 'pourcentage_reduction', 'statut'];
        $values = [':code', ':description', ':reduction', "'active'"];

        if ($hasDateDebut) {
            $fields[] = 'date_debut';
            $values[] = ':date_debut';
        }
        if ($hasDateFin) {
            $fields[] = 'date_fin';
            $values[] = ':date_fin';
        }
        if ($hasUsage) {
            $fields[] = 'utilisation_max';
            $values[] = ':utilisation_max';
        }
        if ($hasUsageCurrent) {
            $fields[] = 'utilisation';
            $values[] = '0';
        }

        $sql = sprintf(
            "INSERT INTO promotion (%s) VALUES (%s)",
            implode(', ', $fields),
            implode(', ', $values)
        );

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':code', $code);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':reduction', $reduction);
        if ($hasDateDebut) {
            $stmt->bindValue(':date_debut', date('Y-m-d'));
        }
        if (strpos($sql, ':date_fin') !== false) {
            $stmt->bindValue(':date_fin', $dateExpirationSql);
        }
        if ($hasUsage) {
            $stmt->bindValue(':utilisation_max', $utilisationMax);
        }
        if (!$hasUsage && !$hasUsageCurrent && $utilisationMaxRaw !== null && $utilisationMaxRaw !== '') {
            // Table without usage columns -> fallback table may require autre structure; ignoring utilisation_max.
        }

        $stmt->execute();

        $newId = (int) $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Promotion créée.',
            'promotion' => [
                'id' => $newId,
                'code' => $code,
                'description' => $description,
                'reduction' => $reduction,
            'utilisation_max' => $hasUsage ? $utilisationMax : null,
            'utilisation' => $hasUsageCurrent ? 0 : null,
            'date_expiration' => $hasDateFin ? $dateExpirationSql : null,
            'statut' => 'active',
            ],
        ]);
    } elseif (tableExists($pdo, 'Newsletter_Promotion')) {
        $stmt = $pdo->prepare("
            INSERT INTO Newsletter_Promotion (code, description, reduction, utilisation_max, date_expiration, statut)
            VALUES (:code, :description, :reduction, :utilisation_max, :date_expiration, 'active')
        ");

        $stmt->execute([
            ':code' => $code,
            ':description' => $description,
            ':reduction' => $reduction,
            ':utilisation_max' => $utilisationMax,
            ':date_expiration' => $dateExpirationSql,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Promotion créée.',
            'promotion' => [
                'id' => (int) $pdo->lastInsertId(),
                'code' => $code,
                'description' => $description,
                'reduction' => $reduction,
                'utilisation_max' => $utilisationMax,
                'utilisation' => 0,
                'date_expiration' => $dateExpirationSql,
                'statut' => 'active',
            ],
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Aucune table promotion disponible en base.']);
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') { // duplicate code
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Ce code promo existe déjà.']);
        exit;
    }

    error_log('Erreur newsletter/create_promotion: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création de la promotion: ' . $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log('Erreur inattendue newsletter/create_promotion: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur inattendue lors de la création de la promotion: ' . $e->getMessage()
    ]);
}

