<?php
/**
 * Statistiques des réservations pour un utilisateur
 * Retourne les compteurs de réservations
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Ajustez le chemin selon votre structure de dossiers
require_once '../../config/database.php';

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_email'])) {
        throw new Exception('Utilisateur non connecté');
    }

    $email_joueur = $_SESSION['user_email'];

    // ========================================
    // PROCHAINES RÉSERVATIONS (>= aujourd'hui)
    // ========================================
    $sqlProchaines = "
        SELECT COUNT(*) AS prochaine_reservation 
        FROM reservation 
        WHERE id_joueur = :email 
        AND date_reservation >= CURDATE()
        AND statut IN ('confirmee', 'en_attente')
    ";
    $stmtProchaines = $pdo->prepare($sqlProchaines);
    $stmtProchaines->execute([':email' => $email_joueur]);
    $resultProchaines = $stmtProchaines->fetch(PDO::FETCH_ASSOC);

    // ========================================
    // RÉSERVATIONS CONFIRMÉES
    // ========================================
    $sqlConfirmees = "
        SELECT COUNT(*) AS reservation_confirmee 
        FROM reservation 
        WHERE id_joueur = :email 
        AND statut = 'confirmee'
        AND date_reservation >= CURDATE()
    ";
    $stmtConfirmees = $pdo->prepare($sqlConfirmees);
    $stmtConfirmees->execute([':email' => $email_joueur]);
    $resultConfirmees = $stmtConfirmees->fetch(PDO::FETCH_ASSOC);

    // ========================================
    // RÉSERVATIONS EN ATTENTE
    // ========================================
    $sqlEnAttente = "
        SELECT COUNT(*) AS reservation_en_attente 
        FROM reservation 
        WHERE id_joueur = :email 
        AND statut = 'en_attente'
        AND date_reservation >= CURDATE()
    ";
    $stmtEnAttente = $pdo->prepare($sqlEnAttente);
    $stmtEnAttente->execute([':email' => $email_joueur]);
    $resultEnAttente = $stmtEnAttente->fetch(PDO::FETCH_ASSOC);

    // ========================================
    // MATCHS JOUÉS (historique)
    // ========================================
    $sqlHistorique = "
        SELECT COUNT(*) AS matchs_joues 
        FROM reservation 
        WHERE id_joueur = :email 
        AND (statut = 'terminee' OR date_reservation < CURDATE())
    ";
    $stmtHistorique = $pdo->prepare($sqlHistorique);
    $stmtHistorique->execute([':email' => $email_joueur]);
    $resultHistorique = $stmtHistorique->fetch(PDO::FETCH_ASSOC);

    // ========================================
    // STOCKER EN SESSION
    // ========================================
    $_SESSION['stats_reservations'] = [
        'prochaine_reservation' => $resultProchaines['prochaine_reservation'] ?? 0,
        'reservation_confirmee' => $resultConfirmees['reservation_confirmee'] ?? 0,
        'reservation_en_attente' => $resultEnAttente['reservation_en_attente'] ?? 0,
        'matchs_joues' => $resultHistorique['matchs_joues'] ?? 0
    ];

    // ========================================
    // RÉPONSE JSON
    // ========================================
    echo json_encode([
        'success' => true,
        'message' => 'Statistiques chargées avec succès',
        'stats' => [
            'prochaine_reservation' => (int)($resultProchaines['prochaine_reservation'] ?? 0),
            'reservation_confirmee' => (int)($resultConfirmees['reservation_confirmee'] ?? 0),
            'reservation_en_attente' => (int)($resultEnAttente['reservation_en_attente'] ?? 0),
            'matchs_joues' => (int)($resultHistorique['matchs_joues'] ?? 0)
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données : ' . $e->getMessage(),
        'stats' => [
            'prochaine_reservation' => 0,
            'reservation_confirmee' => 0,
            'reservation_en_attente' => 0,
            'matchs_joues' => 0
        ]
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Erreur SQL stats réservations : " . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur : ' . $e->getMessage(),
        'stats' => [
            'prochaine_reservation' => 0,
            'reservation_confirmee' => 0,
            'reservation_en_attente' => 0,
            'matchs_joues' => 0
        ]
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Erreur chargement stats : " . $e->getMessage());
}
?>