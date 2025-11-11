<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    if (empty($data['id_disponibilite']) || empty($data['email_destinataire']) || empty($data['id_equipe'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Données manquantes'
        ]);
        exit;
    }
    
    // Vérifier que l'équipe appartient bien au joueur qui envoie l'invitation
    $stmt = $pdo->prepare("
        SELECT 1 FROM equipe_joueur 
        WHERE id_equipe = :id_equipe AND id_joueur = :email_expediteur
    ");
    $stmt->execute([
        ':id_equipe' => $data['id_equipe'],
        ':email_expediteur' => $_SESSION['user_email']
    ]);
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Cette équipe ne vous appartient pas'
        ]);
        exit;
    }
    
    // Récupérer les informations de la disponibilité
    // Vérifier que la disponibilité existe, est active et appartient au bon utilisateur
    $stmt = $pdo->prepare("
        SELECT d.*, u.nom, u.prenom
        FROM disponibilite d
        JOIN utilisateur u ON d.email_joueur = u.email
        WHERE d.id_disponibilite = :id
          AND d.statut = 'actif'
          AND d.date_debut >= NOW()
    ");
    $stmt->execute([':id' => $data['id_disponibilite']]);
    $disponibilite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$disponibilite) {
        echo json_encode([
            'success' => false,
            'message' => 'Disponibilité introuvable ou non disponible (peut-être désactivée ou expirée)'
        ]);
        exit;
    }
    
    // Vérifier que l'utilisateur n'essaie pas de s'envoyer une invitation à lui-même
    if ($disponibilite['email_joueur'] === $data['email_destinataire']) {
        echo json_encode([
            'success' => false,
            'message' => 'Vous ne pouvez pas vous envoyer une invitation à vous-même'
        ]);
        exit;
    }
    
    // Récupérer le nom de l'équipe
    $stmt = $pdo->prepare("SELECT nom_equipe FROM equipe WHERE id_equipe = :id");
    $stmt->execute([':id' => $data['id_equipe']]);
    $equipe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Créer le message d'invitation
    $contenu = isset($data['message']) && !empty($data['message']) 
        ? $data['message'] 
        : "Bonjour, je vous invite à rejoindre mon équipe '{$equipe['nom_equipe']}' pour un match le " . 
          date('d/m/Y à H:i', strtotime($disponibilite['date_debut']));
    
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    try {
        // 1. Insérer le message
        $stmt = $pdo->prepare("
            INSERT INTO message (contenu, email_expediteur, email_destinataire, type_message) 
            VALUES (:contenu, :expediteur, :destinataire, 'invitation')
        ");
        
        $stmt->execute([
            ':contenu' => $contenu,
            ':expediteur' => $_SESSION['user_email'],
            ':destinataire' => $data['email_destinataire']
        ]);
        
        $id_message = $pdo->lastInsertId();
        
        // 2. Créer la demande de rejoindre (IMPORTANT !)
        $stmt = $pdo->prepare("
            INSERT INTO demande_rejoindre (statut, id_message, id_equipe, email_demandeur, date_demande) 
            VALUES ('en_attente', :id_message, :id_equipe, :email_demandeur, NOW())
        ");
        
        $stmt->execute([
            ':id_message' => $id_message,
            ':id_equipe' => $data['id_equipe'],
            ':email_demandeur' => $data['email_destinataire']  // C'est le destinataire qui devient le demandeur
        ]);
        
        // Valider la transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Invitation envoyée avec succès',
            'id_demande' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage()
    ]);
}