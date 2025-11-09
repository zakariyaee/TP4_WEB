<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkJoueur();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation des champs requis
    $required = ['id_terrain', 'id_creneau', 'date_reservation', 'nombre_joueurs'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
            exit;
        }
    }
    
    // Vérifier qu'on a soit un id_equipe soit une nouvelle_equipe
    if (empty($data['id_equipe']) && empty($data['nouvelle_equipe'])) {
        echo json_encode(['success' => false, 'message' => 'Vous devez sélectionner ou créer une équipe']);
        exit;
    }
    
    // Vérifier que le terrain existe et est disponible
    $stmt = $pdo->prepare("SELECT disponibilite, prix_heure FROM terrain WHERE id_terrain = :id");
    $stmt->execute([':id' => $data['id_terrain']]);
    $terrain = $stmt->fetch();
    
    if (!$terrain) {
        echo json_encode(['success' => false, 'message' => 'Terrain introuvable']);
        exit;
    }
    
    if ($terrain['disponibilite'] !== 'disponible') {
        echo json_encode(['success' => false, 'message' => 'Ce terrain n\'est pas disponible']);
        exit;
    }
    
    // Vérifier que le créneau est disponible pour cette date
    $date = $data['date_reservation'];
    $timestamp = strtotime($date);
    $jours = [
        'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'
    ];
    $jour_semaine = $jours[date('l', $timestamp)] ?? '';
    
    $stmt = $pdo->prepare("
        SELECT c.id_creneaux, c.heure_debut, c.heure_fin
        FROM creneau c
        WHERE c.id_creneaux = :id_creneau
        AND c.id_terrain = :id_terrain
        AND c.jour_semaine = :jour_semaine
        AND c.disponibilite = 1
    ");
    $stmt->execute([
        ':id_creneau' => $data['id_creneau'],
        ':id_terrain' => $data['id_terrain'],
        ':jour_semaine' => $jour_semaine
    ]);
    $creneau = $stmt->fetch();
    
    if (!$creneau) {
        echo json_encode(['success' => false, 'message' => 'Créneau non disponible pour cette date']);
        exit;
    }
    
    // Vérifier qu'il n'y a pas déjà une réservation pour ce créneau et cette date
    $stmt = $pdo->prepare("
        SELECT id_reservation 
        FROM reservation 
        WHERE id_creneau = :id_creneau
        AND DATE(date_reservation) = :date
        AND statut IN ('confirmee')
    ");
    $stmt->execute([
        ':id_creneau' => $data['id_creneau'],
        ':date' => $date
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce créneau est déjà réservé pour cette date']);
        exit;
    }
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    try {
        // Gérer la création d'une nouvelle équipe si nécessaire
        $id_equipe_finale = $data['id_equipe'];
        
        if (empty($data['id_equipe']) && !empty($data['nouvelle_equipe'])) {
            // Créer une nouvelle équipe
            $stmt_new_equipe = $pdo->prepare("
                INSERT INTO equipe (nom_equipe, email_equipe, date_creation)
                VALUES (:nom_equipe, :email_equipe, NOW())
            ");
            $stmt_new_equipe->execute([
                ':nom_equipe' => trim($data['nouvelle_equipe']),
                ':email_equipe' => $_SESSION['user_email']
            ]);
            
            $id_equipe_finale = $pdo->lastInsertId();
            
            // Ajouter le joueur comme capitaine de cette nouvelle équipe
            $stmt_add_member = $pdo->prepare("
                INSERT INTO equipe_joueur (id_joueur, id_equipe, role_equipe, date_adhesion)
                VALUES (:id_joueur, :id_equipe, 'capitaine', NOW())
            ");
            $stmt_add_member->execute([
                ':id_joueur' => $_SESSION['user_email'],
                ':id_equipe' => $id_equipe_finale
            ]);
        }
        
        // Calculer le prix total
        $heure_debut_parts = explode(':', $creneau['heure_debut']);
        $heure_fin_parts = explode(':', $creneau['heure_fin']);
        $debut_minutes = ($heure_debut_parts[0] * 60) + ($heure_debut_parts[1] ?? 0);
        $fin_minutes = ($heure_fin_parts[0] * 60) + ($heure_fin_parts[1] ?? 0);
        $heures = ($fin_minutes - $debut_minutes) / 60;
        $prix_terrain = $terrain['prix_heure'] * $heures;
        
        // Calculer le prix des objets
        $prix_objets = 0;
        $objets_reserves = [];
        if (!empty($data['objets']) && is_array($data['objets'])) {
            foreach ($data['objets'] as $objet_id) {
                $stmt = $pdo->prepare("SELECT prix FROM objet WHERE id_object = :id AND disponibilite = 1");
                $stmt->execute([':id' => $objet_id]);
                $objet = $stmt->fetch();
                if ($objet) {
                    $prix_objets += floatval($objet['prix']);
                    $objets_reserves[] = $objet_id;
                }
            }
        }
        
        // Prix total
        $prix_total = $prix_terrain + $prix_objets;
        
        // Créer la réservation
        $datetime_reservation = $date . ' ' . substr($creneau['heure_debut'], 0, 5) . ':00';
        
        // Gérer l'équipe adverse (MODIFIÉ)
        $id_equipe_adverse = null;
        if (!empty($data['id_equipe_adverse'])) {
            $id_equipe_adverse = intval($data['id_equipe_adverse']);
            
            // Vérifier que l'équipe existe
            $stmt_check = $pdo->prepare("SELECT id_equipe FROM equipe WHERE id_equipe = :id");
            $stmt_check->execute([':id' => $id_equipe_adverse]);
            if (!$stmt_check->fetch()) {
                $id_equipe_adverse = null;
            }
        }
        
        // Insérer la réservation avec statut 'confirmee' au lieu de 'en_attente'
        $stmt = $pdo->prepare("
            INSERT INTO reservation (
                id_equipe, id_equipe_adverse, statut_equipe_adverse,
                statut, id_joueur, date_reservation, id_terrain, id_creneau
            ) VALUES (
                :id_equipe, :id_equipe_adverse, :statut_equipe_adverse,
                'confirmee', :id_joueur, :date_reservation, :id_terrain, :id_creneau
            )
        ");
        
        $stmt->execute([
            ':id_equipe' => $id_equipe_finale,
            ':id_equipe_adverse' => $id_equipe_adverse,
            ':statut_equipe_adverse' => $id_equipe_adverse ? 'en_attente' : 'en_attente',
            ':id_joueur' => $_SESSION['user_email'],
            ':date_reservation' => $datetime_reservation,
            ':id_terrain' => $data['id_terrain'],
            ':id_creneau' => $data['id_creneau']
        ]);
        
        $id_reservation = $pdo->lastInsertId();
        
        // Ajouter les objets réservés
        if (!empty($objets_reserves)) {
            $stmt = $pdo->prepare("
                INSERT INTO reservation_objet (id_reservation, id_object, quantite)
                VALUES (:id_reservation, :id_object, 1)
            ");
            
            foreach ($objets_reserves as $objet_id) {
                $stmt->execute([
                    ':id_reservation' => $id_reservation,
                    ':id_object' => $objet_id
                ]);
            }
        }
        
        // Valider la transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Réservation créée avec succès',
            'id_reservation' => $id_reservation,
            'prix_terrain' => $prix_terrain,
            'prix_objets' => $prix_objets,
            'prix_total' => $prix_total
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Erreur create_reservation: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création de la réservation: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur create_reservation: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création de la réservation: ' . $e->getMessage()
    ]);
}