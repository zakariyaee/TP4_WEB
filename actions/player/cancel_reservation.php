<?php
/**
 * Annulation d'une réservation par un utilisateur
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../../../config/database.php';

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_email'])) {
        throw new Exception('Utilisateur non connecté');
    }

    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['id_reservation'])) {
        throw new Exception('ID de réservation manquant');
    }

    $id_reservation = (int)$data['id_reservation'];
    $email_joueur = $_SESSION['user_email'];
    
    // ========================================
    // VÉRIFIER QUE LA RÉSERVATION APPARTIENT À L'UTILISATEUR
    // ========================================
    $sqlVerif = "
        SELECT id_reservation, statut, date_reservation, DATEDIFF(date_reservation, NOW()) AS jours_restants
        FROM reservation 
        WHERE id_reservation = :id 
        AND id_joueur = :email
    ";
    
    $stmtVerif = $pdo->prepare($sqlVerif);
    $stmtVerif->execute([
        ':id' => $id_reservation,
        ':email' => $email_joueur
    ]);
    
    $reservation = $stmtVerif->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        throw new Exception('Réservation introuvable ou vous n\'avez pas l\'autorisation');
    }

    // Vérifier que la réservation n'est pas déjà annulée ou terminée
    if ($reservation['statut'] === 'annulee') {
        throw new Exception('Cette réservation est déjà annulée');
    }

    if ($reservation['statut'] === 'terminee') {
        throw new Exception('Impossible d\'annuler une réservation terminée');
    }

    // Vérifier si la réservation est dans le passé
    if ($reservation['jours_restants'] < 0) {
        throw new Exception('Impossible d\'annuler une réservation passée');
    }

    // ========================================
    // ANNULER LA RÉSERVATION
    // ========================================
    $sqlUpdate = "
        UPDATE reservation 
        SET statut = 'annulee',
            date_modification = NOW()
        WHERE id_reservation = :id 
        AND id_joueur = :email
    ";
    
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':id' => $id_reservation,
        ':email' => $email_joueur
    ]);

    if ($stmtUpdate->rowCount() === 0) {
        throw new Exception('Échec de l\'annulation de la réservation');
    }

    // ========================================
    // RÉPONSE SUCCÈS
    // ========================================
    echo json_encode([
        'success' => true,
        'message' => 'Réservation annulée avec succès',
        'id_reservation' => $id_reservation,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données : ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Erreur SQL annulation réservation : " . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Erreur annulation réservation : " . $e->getMessage());
}
?>