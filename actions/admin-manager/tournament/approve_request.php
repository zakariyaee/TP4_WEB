<?php
/**
 * Approve tournament request
 * 
 * Approves a tournament request and creates the actual tournament.
 * Only responsable of the terrain can approve requests for their terrains.
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

if ($idDemande <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de demande invalide']);
    exit;
}

try {
    $pdo->beginTransaction();
    
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
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Demande introuvable ou non autorisée']);
        exit;
    }
    
    // Vérifier que la demande est en attente
    if ($demande['statut'] !== 'en_attente') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cette demande a déjà été traitée']);
        exit;
    }
    
    // Calculer le statut du tournoi en fonction des dates
    $now = new DateTimeImmutable('now');
    $dateDebut = new DateTimeImmutable($demande['date_debut']);
    $dateFin = new DateTimeImmutable($demande['date_fin']);
    
    if ($now < $dateDebut) {
        $statutTournoi = 'planifie';
    } elseif ($now > $dateFin) {
        $statutTournoi = 'termine';
    } else {
        $statutTournoi = 'en_cours';
    }
    
    // Créer le tournoi à partir de la demande
    $insertSql = "INSERT INTO tournoi (
                    nom_t, date_debut, date_fin, size, description, statut, 
                    id_terrain, prix_inscription, regles";
    
    // Ajouter email_organisateur si la colonne existe
    $columnsWithOrganisateur = $insertSql . ", email_organisateur) VALUES (
                    :nom, :date_debut, :date_fin, :size, :description, :statut,
                    :id_terrain, :prix, :regles, :organisateur)";
    
    $columnsWithoutOrganisateur = $insertSql . ") VALUES (
                    :nom, :date_debut, :date_fin, :size, :description, :statut,
                    :id_terrain, :prix, :regles)";
    
    $baseParams = [
        ':nom' => $demande['nom_t'],
        ':date_debut' => $demande['date_debut'],
        ':date_fin' => $demande['date_fin'],
        ':size' => $demande['size'],
        ':description' => $demande['description'],
        ':statut' => $statutTournoi,
        ':id_terrain' => $demande['id_terrain'],
        ':prix' => $demande['prix_inscription'],
        ':regles' => $demande['regles']
    ];
    
    $inserted = false;
    $variants = [
        ['sql' => $columnsWithOrganisateur, 'params' => $baseParams + [':organisateur' => $demande['email_organisateur']]],
        ['sql' => $columnsWithoutOrganisateur, 'params' => $baseParams]
    ];
    
    foreach ($variants as $variant) {
        try {
            $stmt = $pdo->prepare($variant['sql']);
            $stmt->execute($variant['params']);
            $inserted = true;
            break;
        } catch (PDOException $e) {
            if ($e->getCode() === '42S22' || stripos($e->getMessage(), 'Unknown column') !== false) {
                continue;
            }
            throw $e;
        }
    }
    
    if (!$inserted) {
        throw new PDOException('Impossible de créer le tournoi (structure de table incompatible)');
    }
    
    // Mettre à jour le statut de la demande
    $updateSql = "UPDATE demande_tournoi 
                  SET statut = 'approuvee', 
                      date_reponse = NOW(),
                      id_responsable = :responsable
                  WHERE id_demande = :id";
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([
        ':id' => $idDemande,
        ':responsable' => $_SESSION['user_email']
    ]);
    
    $pdo->commit();
    
    // Note: localStorage update will be triggered from JavaScript
    // This ensures all tabs are notified of the new tournament
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Demande approuvée et tournoi créé avec succès'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Approve tournament request error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'approbation de la demande'
    ]);
}
