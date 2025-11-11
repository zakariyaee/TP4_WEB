<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $email_joueur = $_SESSION['user_email'];
    
    // Récupérer les invitations ENVOYÉES par le joueur connecté
    $stmt = $pdo->prepare("
        SELECT 
            dr.id_demande,
            dr.statut,
            dr.date_demande,
            m.id_message,
            m.contenu,
            m.date_message,
            e.nom_equipe,
            u_dest.nom as destinataire_nom,
            u_dest.prenom as destinataire_prenom,
            CONCAT(SUBSTRING(u_dest.nom, 1, 1), SUBSTRING(u_dest.prenom, 1, 1)) as destinataire_initiales,
            d.date_debut,
            d.position,
            d.niveau,
            DATE_FORMAT(d.date_debut, '%d/%m/%Y') as date_formatted,
            DATE_FORMAT(d.date_debut, '%H:%i') as heure_formatted,
            DATE_FORMAT(m.date_message, '%d/%m/%Y à %H:%i') as date_message_formatted
        FROM demande_rejoindre dr
        INNER JOIN message m ON dr.id_message = m.id_message
        INNER JOIN equipe e ON dr.id_equipe = e.id_equipe
        INNER JOIN utilisateur u_dest ON m.email_destinataire = u_dest.email
        LEFT JOIN disponibilite d ON dr.email_demandeur = d.email_joueur 
            AND d.statut = 'actif' 
            AND d.date_debut >= NOW()
        WHERE m.email_expediteur = :email
        ORDER BY m.date_message DESC
    ");
    $stmt->execute([':email' => $email_joueur]);
    $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer les statistiques
    $stats = [
        'en_attente' => 0,
        'acceptees' => 0,
        'refusees' => 0
    ];
    
    foreach ($invitations as $inv) {
        if ($inv['statut'] === 'en_attente') {
            $stats['en_attente']++;
        } elseif ($inv['statut'] === 'acceptee') {
            $stats['acceptees']++;
        } elseif ($inv['statut'] === 'refusee') {
            $stats['refusees']++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'invitations' => $invitations,
        'stats' => $stats,
        'total' => count($invitations)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
    ]);
}