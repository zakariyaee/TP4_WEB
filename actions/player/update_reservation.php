<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../check_auth.php';

checkJoueur();

try {
    $email = $_SESSION['user_email'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id_reservation'])) {
        throw new Exception('Données invalides');
    }
    
    // VÉRIFICATION IMPORTANTE : Vérifier qu'il reste plus de 2 jours (48h)
    $stmtCheck = $pdo->prepare("
        SELECT 
            id_reservation,
            DATEDIFF(date_reservation, NOW()) AS jours_restants,
            statut
        FROM reservation
        WHERE id_reservation = :id 
        AND id_joueur = :email
    ");
    
    $stmtCheck->execute([
        ':id' => $input['id_reservation'],
        ':email' => $email
    ]);
    
    $reservation = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        throw new Exception('Réservation introuvable');
    }
    
    if ($reservation['statut'] !== 'confirmee') {
        throw new Exception('Cette réservation ne peut pas être modifiée (statut: ' . $reservation['statut'] . ')');
    }
    
    // BLOCAGE SI MOINS DE 48H (jours_restants <= 2)
    if ($reservation['jours_restants'] <= 2) {
        throw new Exception('Modification impossible : il reste moins de 48 heures avant la réservation (' . $reservation['jours_restants'] . ' jour(s) restant(s))');
    }
    
    // Procéder à la mise à jour
    $stmt = $pdo->prepare("
        UPDATE reservation 
        SET date_reservation = :date, 
            id_creneau = :creneau,
            id_equipe = :equipe, 
            id_equipe_adverse = :adverse
        WHERE id_reservation = :id 
        AND id_joueur = :email
    ");
    
    $result = $stmt->execute([
        ':date' => $input['date_reservation'],
        ':creneau' => $input['id_creneau'],
        ':equipe' => $input['id_equipe'],
        ':adverse' => $input['id_equipe_adverse'] ?: null,
        ':id' => $input['id_reservation'],
        ':email' => $email
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Réservation modifiée avec succès'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Échec de la mise à jour');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("Erreur update_reservation : " . $e->getMessage());
}
?>