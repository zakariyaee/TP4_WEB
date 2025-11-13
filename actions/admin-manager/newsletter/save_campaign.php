<?php
/**
 * Save newsletter campaign as draft.
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

    $titre = sanitizeText($_POST['titre'] ?? '');
    $objet = sanitizeText($_POST['objet'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $audienceLabel = sanitizeText($_POST['audience_label'] ?? 'Tous les abonnés');

    if ($titre === '' || $objet === '' || $contenu === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Merci de renseigner au moins le titre, l\'objet et le contenu.']);
        exit;
    }

    $destinataires = getAudienceEmails($pdo, $audienceLabel);
    $destCount = count($destinataires);

    if (!tableExists($pdo, 'Newsletter_Campagne')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Table des brouillons indisponible.']);
        exit;
    }

    $hasAudience = columnExists($pdo, 'Newsletter_Campagne', 'audience_label');
    $hasDestColumn = columnExists($pdo, 'Newsletter_Campagne', 'destinataires');
    $hasDestCount = columnExists($pdo, 'Newsletter_Campagne', 'destinataires_count');

    if ($hasAudience && $hasDestCount) {
        $stmt = $pdo->prepare("
            INSERT INTO Newsletter_Campagne (titre, objet, contenu, audience_label, destinataires_count, statut)
            VALUES (:titre, :objet, :contenu, :audience_label, :dest_count, 'brouillon')
        ");
        $stmt->execute([
            ':titre' => $titre,
            ':objet' => $objet,
            ':contenu' => $contenu,
            ':audience_label' => $audienceLabel,
            ':dest_count' => $destCount,
        ]);
    } elseif ($hasDestColumn) {
        $stmt = $pdo->prepare("
            INSERT INTO Newsletter_Campagne (titre, objet, contenu, destinataires, destinataires_count, statut)
            VALUES (:titre, :objet, :contenu, :destinataires, :dest_count, 'brouillon')
        ");
        $stmt->execute([
            ':titre' => $titre,
            ':objet' => $objet,
            ':contenu' => $contenu,
            ':destinataires' => json_encode([
                'audience' => $audienceLabel,
                'liste' => $destinataires,
            ]),
            ':dest_count' => $destCount,
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO Newsletter_Campagne (titre, objet, contenu, statut)
            VALUES (:titre, :objet, :contenu, 'brouillon')
        ");
        $stmt->execute([
            ':titre' => $titre,
            ':objet' => $objet,
            ':contenu' => $contenu,
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Brouillon enregistré.',
        'campaign' => [
            'id' => (int) $pdo->lastInsertId(),
            'titre' => $titre,
            'objet' => $objet,
            'statut' => 'brouillon',
            'destinataires_count' => $destCount,
        ],
    ]);
} catch (PDOException $e) {
    error_log('Erreur newsletter/save_campaign: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du brouillon.']);
} catch (Throwable $e) {
    error_log('Erreur inattendue newsletter/save_campaign: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur inattendue lors de l\'enregistrement du brouillon.']);
}

