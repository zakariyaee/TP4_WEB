<?php
/**
 * Send newsletter campaign immediately.
 *
 * Stores the campaign as sent and (optionally) dispatches real emails.
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
        echo json_encode([
            'success' => false,
            'message' => 'Merci de renseigner au moins le titre, l\'objet et le contenu.'
        ]);
        exit;
    }

    $destinataires = getAudienceEmails($pdo, $audienceLabel);
    $destCount = count($destinataires);

    if ($destCount === 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Aucun abonné actif pour envoyer la newsletter.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    $ownerEmail = $_SESSION['user_email'] ?? null;
    if ($ownerEmail === null) {
        throw new RuntimeException("Impossible de déterminer l'expéditeur de la newsletter.");
    }

    // Insérer dans la table historique existante
    $newsletterStmt = $pdo->prepare("
        INSERT INTO newsletter (contenu, email_proprietaire, titre)
        VALUES (:contenu, :email_proprietaire, :titre)
    ");
    $newsletterStmt->execute([
        ':contenu' => $contenu,
        ':email_proprietaire' => $ownerEmail,
        ':titre' => $titre !== '' ? $titre : $objet,
    ]);

    $newsletterId = (int) $pdo->lastInsertId();

    // Journaliser les destinataires dans newsletter_abonnement si disponible
    if (tableExists($pdo, 'newsletter_abonnement')) {
        $abonnementStmt = $pdo->prepare("
            INSERT INTO newsletter_abonnement (id_news, email_joueur, statut_lecture)
            VALUES (:id_news, :email_joueur, 0)
        ");
        foreach ($destinataires as $email) {
            $abonnementStmt->execute([
                ':id_news' => $newsletterId,
                ':email_joueur' => $email,
            ]);
        }
    }

    // Optionnel : enregistrer aussi dans la table complémentaire si elle existe
    if (tableExists($pdo, 'Newsletter_Campagne')) {
        $hasAudience = columnExists($pdo, 'Newsletter_Campagne', 'audience_label');
        $hasDestColumn = columnExists($pdo, 'Newsletter_Campagne', 'destinataires');
        $hasDestCount = columnExists($pdo, 'Newsletter_Campagne', 'destinataires_count');
        $hasDateEnvoi = columnExists($pdo, 'Newsletter_Campagne', 'date_envoi');
        $hasOpenRate = columnExists($pdo, 'Newsletter_Campagne', 'open_rate');

        if ($hasAudience && $hasDestCount && $hasDateEnvoi && $hasOpenRate) {
            $campagneStmt = $pdo->prepare("
                INSERT INTO Newsletter_Campagne (titre, objet, contenu, audience_label, destinataires_count, statut, date_envoi, open_rate)
                VALUES (:titre, :objet, :contenu, :audience_label, :destinataires_count, 'envoyee', NOW(), 0)
            ");
            $campagneStmt->execute([
                ':titre' => $titre,
                ':objet' => $objet,
                ':contenu' => $contenu,
                ':audience_label' => $audienceLabel,
                ':destinataires_count' => $destCount,
            ]);
        } elseif ($hasDestColumn) {
            $campagneStmt = $pdo->prepare("
                INSERT INTO Newsletter_Campagne (titre, objet, contenu, destinataires, destinataires_count, statut)
                VALUES (:titre, :objet, :contenu, :destinataires, :destinataires_count, 'envoyee')
            ");
            $campagneStmt->execute([
                ':titre' => $titre,
                ':objet' => $objet,
                ':contenu' => $contenu,
                ':destinataires' => json_encode([
                    'audience' => $audienceLabel,
                    'liste' => $destinataires,
                ]),
                ':destinataires_count' => $destCount,
            ]);
        } else {
            $campagneStmt = $pdo->prepare("
                INSERT INTO Newsletter_Campagne (titre, objet, contenu, statut)
                VALUES (:titre, :objet, :contenu, 'envoyee')
            ");
            $campagneStmt->execute([
                ':titre' => $titre,
                ':objet' => $objet,
                ':contenu' => $contenu,
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Newsletter envoyée. (Simulation d\'envoi réalisée).',
        'newsletterId' => $newsletterId,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erreur newsletter/send_newsletter: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi de la newsletter.'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erreur inattendue newsletter/send_newsletter: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur inattendue lors de l\'envoi de la newsletter.'
    ]);
}

