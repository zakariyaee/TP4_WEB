<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $email_joueur = $_SESSION['user_email'];
    
    // Compter les invitations d'équipes en attente
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM demande_rejoindre dr
        INNER JOIN message m ON dr.id_message = m.id_message
        WHERE m.email_destinataire = :email
        AND dr.statut = 'en_attente'
    ");
    $stmt->execute([':email' => $email_joueur]);
    $equipes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Compter les invitations aux tournois en attente
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM tournoi_equipe te
        INNER JOIN equipe_joueur ej ON te.id_equipe = ej.id_equipe
        WHERE ej.id_joueur = :email
        AND te.statut_participation = 'invitee'
    ");
    $stmt->execute([':email' => $email_joueur]);
    $tournois = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = ($equipes['count'] ?? 0) + ($tournois['count'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'count' => $total
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
        'count' => 0
    ]);
}