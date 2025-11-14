<?php
/**
 * Reject tournament request
 * 
 * Rejects a tournament request with optional comment.
 * Only responsable of the terrain can reject requests for their terrains.
 *
 * @return void
 * @throws PDOException Database connection or query errors
 */

require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$idDemande = isset($data['id_demande']) ? (int) $data['id_demande'] : 0;
$commentaire = trim($data['commentaire'] ?? '');

if ($idDemande <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de demande invalide']);
    exit;
}

try {
    // Récupérer la demande avec vérification des permissions
    $sql = "SELECT d.*, tr.id_responsable 
            FROM demande_tournoi d
            LEFT JOIN terrain tr ON d.id_terrain = tr.id_terrain
            WHERE d.id_demande = :id";
    
    if ($_SESSION['user_role'] === 'responsable') {
        $sql .= " AND d.id_responsable = :user_email";
    }
    
    $stmt = $pdo->prepare($sql);
    $params = [':id' => $idDemande];
    if ($_SESSION['user_role'] === 'responsable') {
        $params[':user_email'] = $_SESSION['user_email'];
    }
    $stmt->execute($params);
    $demande = $stmt->fetch();
    
    if (!$demande) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Demande introuvable ou non autorisée']);
        exit;
    }
    
    // Vérifier que la demande est en attente
    if ($demande['statut'] !== 'en_attente') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cette demande a déjà été traitée']);
        exit;
    }
    
    // Mettre à jour le statut de la demande
    $updateSql = "UPDATE demande_tournoi 
                  SET statut = 'rejetee', 
                      date_reponse = NOW(),
                      commentaire_reponse = :commentaire,
                      id_responsable = :responsable
                  WHERE id_demande = :id";
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([
        ':id' => $idDemande,
        ':commentaire' => $commentaire !== '' ? $commentaire : null,
        ':responsable' => $_SESSION['user_email']
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Demande rejetée avec succès'
    ]);
    
} catch (PDOException $e) {
    error_log('Reject tournament request error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du rejet de la demande'
    ]);
}
