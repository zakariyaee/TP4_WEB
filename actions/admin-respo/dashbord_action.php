<?php
$_SESSION['revenue_total']=0;
$requete="Select id_terrain from reservation where Month(date_reservation)=Month(CURRENT_DATE()) AND Year(date_reservation)=Year(CURRENT_DATE())";
$req=$pdo->prepare($requete);
$req->execute();
$ids_terrain=$req->fetchAll(PDO::FETCH_COLUMN);
foreach($ids_terrain as $id_terrain){
    $requete="Select prix_heure from terrain where id_terrain=:id_terrain";
    $req=$pdo->prepare($requete);
    $req->bindParam(':id_terrain',$id_terrain);
    $req->execute();
    $_SESSION['revenue_total']+=$req->fetchColumn();
}
$req1="select id_object ,quantite from reservation_objet where Month(Date_reservation_objet)=Month(CURRENT_DATE()) AND Year(Date_reservation_objet)=Year(CURRENT_DATE())";
$req=$pdo->prepare($req1);
$req->execute();
$reservations_objets=$req->fetchAll(PDO::FETCH_ASSOC);
foreach($reservations_objets as $reservation_objet){
    $requete="Select prix from objet where id_object=:id_objet";
    $req=$pdo->prepare($requete);
    $req->bindParam(':id_objet',$reservation_objet['id_objet']);
    $req->execute();
    $_SESSION['revenue_total']+=$req->fetchColumn()*$reservation_objet['quantite'];
}
$requete="Select count(*) from utilisateur where role='joueur'";
$req=$pdo->prepare($requete);
$req->execute();
$_SESSION['total_joueurs']=$req->fetchColumn();
$requete="Select count(*) from terrain where disponibilite='disponible'";
$req=$pdo->prepare($requete);
$req->execute();
$_SESSION['total_terrains']=$req->fetchColumn();
$requete="Select count(*) from reservation where statut='confirmee'";
$req=$pdo->prepare($requete);
$req->execute();
$_SESSION['total_reservations']=$req->fetchColumn();


// Préparation des données pour le graphique des réservations par jour de la semaine
$requteGraphe1="Select Count(*) as Lundi from reservation where Dayofweek(date_reservation)=2 AND Month(date_reservation)=Month(CURRENT_DATE()) AND Year(date_reservation)=Year(CURRENT_DATE())";
$req=$pdo->prepare($requteGraphe1);
$req->execute();
$_SESSION['graphe_jour']['Lundi']=$req->fetchColumn();
$requteGraphe2="Select Count(*) as Mardi from reservation where Dayofweek(date_reservation)=3 AND Month(date_reservation)=Month(CURRENT_DATE()) AND Year(date_reservation)=Year(CURRENT_DATE())";
$req=$pdo->prepare($requteGraphe2);
$req->execute();
$_SESSION['graphe_jour']['Mardi']=$req->fetchColumn();
$requteGraphe3="Select Count(*) as Mercredi from reservation where Dayofweek(date_reservation)=4 AND Month(date_reservation)=Month(CURRENT_DATE()) AND Year(date_reservation)=Year(CURRENT_DATE())";
$req=$pdo->prepare($requteGraphe3);
$req->execute();
$_SESSION['graphe_jour']['Mercredi']=$req->fetchColumn();
$requteGraphe4="Select Count(*) as Jeudi from reservation where Dayofweek(date_reservation)=5 AND Month(date_reservation)=Month(CURRENT_DATE()) AND Year(date_reservation)=Year(CURRENT_DATE())";
$req=$pdo->prepare($requteGraphe4);
$req->execute();
$_SESSION['graphe_jour']['Jeudi']=$req->fetchColumn();
$requteGraphe5="Select Count(*) as Vendredi from reservation where Dayofweek(date_reservation)=6 AND Month(date_reservation)=Month(CURRENT_DATE()) AND Year(date_reservation)=Year(CURRENT_DATE())";
$req=$pdo->prepare($requteGraphe5);
$req->execute();
$_SESSION['graphe_jour']['Vendredi']=$req->fetchColumn();
$requteGraphe6="Select Count(*) as Samedi from reservation where Dayofweek(date_reservation)=7 AND Month(date_reservation)=Month(CURRENT_DATE()) AND Year(date_reservation)=Year(CURRENT_DATE())";
$req=$pdo->prepare($requteGraphe6);
$req->execute();
$_SESSION['graphe_jour']['Samedi']=$req->fetchColumn();
$requteGraphe7="Select Count(*) as Dimanche from reservation where Dayofweek(date_reservation)=1 AND Month(date_reservation)=Month(CURRENT_DATE()) AND Year(date_reservation)=Year(CURRENT_DATE())";
$req=$pdo->prepare($requteGraphe7);
$req->execute();
$_SESSION['graphe_jour']['Dimanche']=$req->fetchColumn();

// diagrame eÉvolution des Revenus

$_SESSION['revenus_mensuels'] = array_fill(0, 6, 0); // Initialisation à 0 pour 6 mois

for ($i = 0; $i < 6; $i++) {

    // 🔹 Calcul du mois cible en partant d'aujourd'hui
    $mois_cible = date('m', strtotime("-$i month"));
    $annee_cible = date('Y', strtotime("-$i month"));

    // 🔹 Requête pour récupérer les id_terrain du mois ciblé
    $requeteRev1 = "
        SELECT id_terrain 
        FROM reservation 
        WHERE MONTH(date_reservation) = :mois 
          AND YEAR(date_reservation) = :annee
    ";
    $req = $pdo->prepare($requeteRev1);
    $req->execute([
        ':mois' => $mois_cible,
        ':annee' => $annee_cible
    ]);

    $ids_terrain = $req->fetchAll(PDO::FETCH_COLUMN);

    // 🔹 Pour chaque terrain réservé, récupérer le prix
    foreach ($ids_terrain as $id_terrain) {
        $requete = "SELECT prix_heure FROM terrain WHERE id_terrain = :id_terrain";
        $req2 = $pdo->prepare($requete);
        $req2->bindParam(':id_terrain', $id_terrain);
        $req2->execute();

        $prix = $req2->fetchColumn();
        $_SESSION['revenus_mensuels'][$i] += $prix;
    }
}
    
// diagrame circulair 
    $resq="Select count(*) as total_minifoot from terrain where taille='70x40m'";
    $req=$pdo->prepare($resq);
    $req->execute();
    $_SESSION['total_minifoot']=$req->fetchColumn();
    $resq="Select count(*) as total_moyenne from terrain where taille='90x50m'";
    $req=$pdo->prepare($resq);
    $req->execute();
    $_SESSION['total_moyenne']=$req->fetchColumn();
    $resq="Select count(*) as total_Grand from terrain where taille='105x68m'";
    $req=$pdo->prepare($resq);
    $req->execute();
    $_SESSION['total_Grand']=$req->fetchColumn();
    $total = $_SESSION['total_minifoot'] + $_SESSION['total_moyenne'] + $_SESSION['total_Grand'];
    $_SESSION['total_minifoot'] = ($_SESSION['total_minifoot'] / $total) * 100;
    $_SESSION['total_moyenne'] = ($_SESSION['total_moyenne'] / $total) * 100;
    $_SESSION['total_Grand'] = ($_SESSION['total_Grand'] / $total) * 100;






