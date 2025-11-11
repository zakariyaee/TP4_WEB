<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $email_joueur = $_SESSION['user_email'];
    
    // Récupérer les invitations ENVOYÉES par le joueur connecté
    // La disponibilité doit être liée à l'expéditeur (celui qui envoie l'invitation)
    // On utilise des sous-requêtes pour récupérer la disponibilité la plus proche de chaque message
    $stmt = $pdo->prepare("
        SELECT 
            dr.id_demande,
            dr.statut,
            dr.date_demande,
            m.id_message,
            m.contenu,
            m.date_message,
            e.nom_equipe,
            e.id_equipe,
            u_dest.nom as destinataire_nom,
            u_dest.prenom as destinataire_prenom,
            u_dest.email as destinataire_email,
            CONCAT(SUBSTRING(u_dest.nom, 1, 1), SUBSTRING(u_dest.prenom, 1, 1)) as destinataire_initiales,
            (SELECT d_closest.date_debut
             FROM disponibilite d_closest
             WHERE d_closest.email_joueur = m.email_expediteur
               AND d_closest.statut = 'actif'
               AND d_closest.date_debut BETWEEN DATE_SUB(m.date_message, INTERVAL 30 DAY) 
                                            AND DATE_ADD(m.date_message, INTERVAL 30 DAY)
             ORDER BY ABS(TIMESTAMPDIFF(HOUR, d_closest.date_debut, m.date_message)) ASC
             LIMIT 1) as date_debut,
            (SELECT d_closest.position
             FROM disponibilite d_closest
             WHERE d_closest.email_joueur = m.email_expediteur
               AND d_closest.statut = 'actif'
               AND d_closest.date_debut BETWEEN DATE_SUB(m.date_message, INTERVAL 30 DAY) 
                                            AND DATE_ADD(m.date_message, INTERVAL 30 DAY)
             ORDER BY ABS(TIMESTAMPDIFF(HOUR, d_closest.date_debut, m.date_message)) ASC
             LIMIT 1) as position,
            (SELECT d_closest.niveau
             FROM disponibilite d_closest
             WHERE d_closest.email_joueur = m.email_expediteur
               AND d_closest.statut = 'actif'
               AND d_closest.date_debut BETWEEN DATE_SUB(m.date_message, INTERVAL 30 DAY) 
                                            AND DATE_ADD(m.date_message, INTERVAL 30 DAY)
             ORDER BY ABS(TIMESTAMPDIFF(HOUR, d_closest.date_debut, m.date_message)) ASC
             LIMIT 1) as niveau,
            DATE_FORMAT((SELECT d_closest.date_debut
                         FROM disponibilite d_closest
                         WHERE d_closest.email_joueur = m.email_expediteur
                           AND d_closest.statut = 'actif'
                           AND d_closest.date_debut BETWEEN DATE_SUB(m.date_message, INTERVAL 30 DAY) 
                                                        AND DATE_ADD(m.date_message, INTERVAL 30 DAY)
                         ORDER BY ABS(TIMESTAMPDIFF(HOUR, d_closest.date_debut, m.date_message)) ASC
                         LIMIT 1), '%d/%m/%Y') as date_formatted,
            DATE_FORMAT((SELECT d_closest.date_debut
                         FROM disponibilite d_closest
                         WHERE d_closest.email_joueur = m.email_expediteur
                           AND d_closest.statut = 'actif'
                           AND d_closest.date_debut BETWEEN DATE_SUB(m.date_message, INTERVAL 30 DAY) 
                                                        AND DATE_ADD(m.date_message, INTERVAL 30 DAY)
                         ORDER BY ABS(TIMESTAMPDIFF(HOUR, d_closest.date_debut, m.date_message)) ASC
                         LIMIT 1), '%H:%i') as heure_formatted,
            DATE_FORMAT(m.date_message, '%d/%m/%Y à %H:%i') as date_message_formatted
        FROM demande_rejoindre dr
        INNER JOIN message m ON dr.id_message = m.id_message
        INNER JOIN equipe e ON dr.id_equipe = e.id_equipe
        INNER JOIN utilisateur u_dest ON m.email_destinataire = u_dest.email
        WHERE m.email_expediteur = :email
        ORDER BY m.date_message DESC, dr.date_demande DESC
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