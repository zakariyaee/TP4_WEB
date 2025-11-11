<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id_demande'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de demande manquant'
        ]);
        exit;
    }
    
    $email_joueur = $_SESSION['user_email'];
    
    // Vérifier que l'invitation appartient bien au joueur
    $stmt = $pdo->prepare("
        SELECT dr.*, m.email_destinataire, m.email_expediteur, e.nom_equipe
        FROM demande_rejoindre dr
        INNER JOIN message m ON dr.id_message = m.id_message
        INNER JOIN equipe e ON dr.id_equipe = e.id_equipe
        WHERE dr.id_demande = :id_demande 
        AND m.email_destinataire = :email
        AND dr.statut = 'en_attente'
    ");
    $stmt->execute([
        ':id_demande' => $data['id_demande'],
        ':email' => $email_joueur
    ]);
    
    $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invitation) {
        echo json_encode([
            'success' => false,
            'message' => 'Invitation non trouvée ou déjà traitée'
        ]);
        exit;
    }
    
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    try {
        // Mettre à jour le statut de la demande
        $stmt = $pdo->prepare("
            UPDATE demande_rejoindre 
            SET statut = 'refusee' 
            WHERE id_demande = :id_demande
        ");
        $stmt->execute([':id_demande' => $data['id_demande']]);
        
        // Envoyer une notification à l'expéditeur (celui qui a envoyé l'invitation)
        $stmt = $pdo->prepare("
            INSERT INTO message (contenu, email_expediteur, email_destinataire, type_message) 
            VALUES (:contenu, :expediteur, :destinataire, 'notification')
        ");
        
        $stmt->execute([
            ':contenu' => "Votre invitation à rejoindre l'équipe '{$invitation['nom_equipe']}' a été refusée.",
            ':expediteur' => $email_joueur,
            ':destinataire' => $invitation['email_expediteur']
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Invitation refusée'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du refus: ' . $e->getMessage()
    ]);
}