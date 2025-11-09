<?php
/**
 * AJAX - Validation/Annulation de réservations
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once '../../../config/database.php'; // doit définir $pdo

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!is_array($input)) {
        throw new Exception('Données JSON invalides');
    }

    if (empty($input['id_reservation']) || !is_numeric($input['id_reservation'])) {
        throw new Exception('ID de réservation invalide');
    }

    if (empty($input['action']) || !in_array($input['action'], ['validate', 'cancel'])) {
        throw new Exception('Action invalide');
    }

    $id = (int)$input['id_reservation'];
    $action = $input['action'];

    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'responsable') {
        $responsable_id = $_SESSION['user_email'];
        
        if ($action === 'validate') {
            $stmt = $pdo->prepare("
                UPDATE reservation 
                SET statut = 'confirmee' 
                WHERE id_reservation = :id 
                AND statut = 'en_attente' 
                AND id_terrain IN (SELECT id_terrain FROM terrain WHERE id_responsable = :responsable_id)
            ");
            $stmt->execute([':id' => $id, ':responsable_id' => $responsable_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Réservation introuvable, déjà confirmée ou non autorisée");
            }

            $message = "Réservation #$id confirmée avec succès";

        } elseif ($action === 'cancel') {
            $stmt = $pdo->prepare("
                UPDATE reservation 
                SET statut = 'annulee' 
                WHERE id_reservation = :id 
                AND statut IN ('en_attente', 'confirmee') 
                AND id_terrain IN (SELECT id_terrain FROM terrain WHERE id_responsable = :responsable_id)
            ");
            $stmt->execute([':id' => $id, ':responsable_id' => $responsable_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Réservation introuvable, déjà annulée ou non autorisée");
            }

            $message = "Réservation #$id annulée avec succès";
        }
    } else {
        // Admin peut gérer toutes les réservations
        if ($action === 'validate') {
            $stmt = $pdo->prepare("
                UPDATE reservation 
                SET statut = 'confirmee' 
                WHERE id_reservation = :id 
                AND statut = 'en_attente'
            ");
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Réservation introuvable ou déjà confirmée");
            }

            $message = "Réservation #$id confirmée avec succès";

        } elseif ($action === 'cancel') {
            $stmt = $pdo->prepare("
                UPDATE reservation 
                SET statut = 'annulee' 
                WHERE id_reservation = :id 
                AND statut IN ('en_attente', 'confirmee')
            ");
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Réservation introuvable ou déjà annulée");
            }

            $message = "Réservation #$id annulée avec succès";
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'id_reservation' => $id,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    error_log("Erreur AJAX Validation : " . $e->getMessage());
}