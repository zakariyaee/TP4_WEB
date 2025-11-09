<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $email_joueur = $_SESSION['user_email'];
    
    // Récupérer les invitations d'équipes
    $stmt = $pdo->prepare("
        SELECT 
            dr.id_demande,
            dr.statut,
            dr.date_demande,
            m.id_message,
            m.contenu,
            m.date_message,
            e.nom_equipe,
            u.nom as expediteur_nom,
            u.prenom as expediteur_prenom,
            CONCAT(SUBSTRING(u.nom, 1, 1), SUBSTRING(u.prenom, 1, 1)) as expediteur_initiales,
            d.date_debut,
            d.position,
            d.niveau,
            DATE_FORMAT(d.date_debut, '%d/%m/%Y') as date_formatted,
            DATE_FORMAT(d.date_debut, '%H:%i') as heure_formatted,
            DATE_FORMAT(m.date_message, '%d/%m/%Y à %H:%i') as date_message_formatted,
            'equipe' as type,
            NULL as nom_tournoi
        FROM demande_rejoindre dr
        INNER JOIN message m ON dr.id_message = m.id_message
        INNER JOIN equipe e ON dr.id_equipe = e.id_equipe
        INNER JOIN utilisateur u ON m.email_expediteur = u.email
        LEFT JOIN disponibilite d ON m.email_destinataire = d.email_joueur 
            AND d.statut = 'actif' 
            AND d.date_debut >= NOW()
        WHERE dr.email_demandeur = :email
        ORDER BY m.date_message DESC
    ");
    $stmt->execute([':email' => $email_joueur]);
    $invitations_equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les invitations aux tournois
    $stmt = $pdo->prepare("
        SELECT 
            te.id_tournoi as id_demande,
            CASE 
                WHEN te.statut_participation = 'invitee' THEN 'en_attente'
                WHEN te.statut_participation = 'confirmee' THEN 'acceptee'
                WHEN te.statut_participation = 'refusee' THEN 'refusee'
            END as statut,
            t.date_debut as date_demande,
            0 as id_message,
            t.nom_t as nom_tournoi,
            CONCAT('Rejoignez notre équipe pour le tournoi ', t.nom_t, '. ', t.description) as contenu,
            t.date_debut,
            t.date_fin,
            DATE_FORMAT(t.date_debut, '%d/%m/%Y') as date_formatted,
            DATE_FORMAT(t.date_debut, '%H:%i') as heure_formatted,
            DATE_FORMAT(t.date_debut, '%d/%m/%Y à %H:%i') as date_message_formatted,
            e.nom_equipe,
            u.nom as expediteur_nom,
            u.prenom as expediteur_prenom,
            CONCAT(SUBSTRING(u.nom, 1, 1), SUBSTRING(u.prenom, 1, 1)) as expediteur_initiales,
            NULL as position,
            NULL as niveau,
            'tournoi' as type
        FROM tournoi_equipe te
        INNER JOIN tournoi t ON te.id_tournoi = t.id_tournoi
        INNER JOIN equipe e ON te.id_equipe = e.id_equipe
        INNER JOIN equipe_joueur ej ON e.id_equipe = ej.id_equipe
        INNER JOIN utilisateur u ON t.email_organisateur = u.email
        WHERE ej.id_joueur = :email
        ORDER BY t.date_debut DESC
    ");
    $stmt->execute([':email' => $email_joueur]);
    $invitations_tournois = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fusionner les deux types d'invitations
    $all_invitations = array_merge($invitations_equipes, $invitations_tournois);
    
    // Trier par date
    usort($all_invitations, function($a, $b) {
        return strtotime($b['date_demande']) - strtotime($a['date_demande']);
    });
    
    // Calculer les statistiques
    $stats = [
        'en_attente' => 0,
        'acceptees' => 0,
        'refusees' => 0,
        'tournois' => 0
    ];
    
    foreach ($all_invitations as $inv) {
        if ($inv['statut'] === 'en_attente') {
            $stats['en_attente']++;
        } elseif ($inv['statut'] === 'acceptee') {
            $stats['acceptees']++;
        } elseif ($inv['statut'] === 'refusee') {
            $stats['refusees']++;
        }
        
        if ($inv['type'] === 'tournoi' && $inv['statut'] === 'en_attente') {
            $stats['tournois']++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'invitations' => $all_invitations,
        'stats' => $stats,
        'total' => count($all_invitations)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
    ]);
}