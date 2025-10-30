<?php
require_once '../../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Requête pour compter le nombre total de réservations
$requete_Count_reservations = "SELECT COUNT(*) AS count FROM reservation";
$requete_Count_reservations = $pdo->prepare($requete_Count_reservations);
$requete_Count_reservations->execute();
$nombre_reservations = $requete_Count_reservations->fetch(PDO::FETCH_ASSOC)['count'];
$_SESSION['nombre_reservations'] = $nombre_reservations;

//Requete pour nbre confirmr une réservation
$requete_confirm_reservations = "SELECT COUNT(*) AS count FROM reservation WHERE statut = 'confirmee'";
$requete_confirm_reservations = $pdo->prepare($requete_confirm_reservations);
$requete_confirm_reservations->execute();
$nombre_confirm_reservations = $requete_confirm_reservations->fetch(PDO::FETCH_ASSOC)['count'];
$_SESSION['nombre_confirm_reservations'] = $nombre_confirm_reservations;
// Requete pour nbre attente d'une réservationune 
$requete_attente_reservations = "SELECT COUNT(*) AS count FROM reservation WHERE statut = 'en_attente'";
$requete_attente_reservations = $pdo->prepare($requete_attente_reservations);
$requete_attente_reservations->execute();
$nombre_attente_reservations = $requete_attente_reservations->fetch(PDO::FETCH_ASSOC)['count'];
$_SESSION['nombre_attente_reservations'] = $nombre_attente_reservations;

// revenues total du reervation 
$_SESSION['revenue_total_reservation']=0;
$requete="Select id_terrain from reservation where Month(date_reservation)=Month(CURRENT_DATE()) AND Year(date_reservation)=Year(CURRENT_DATE())";
$req=$pdo->prepare($requete);
$req->execute();
$ids_terrain=$req->fetchAll(PDO::FETCH_COLUMN);
foreach($ids_terrain as $id_terrain){
    $requete="Select prix_heure from terrain where id_terrain=:id_terrain";
    $req=$pdo->prepare($requete);
    $req->bindParam(':id_terrain',$id_terrain);
    $req->execute();
    $_SESSION['revenue_total_reservation']+=$req->fetchColumn();
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
    $_SESSION['revenue_total-resrvation']+=$req->fetchColumn()*$reservation_objet['quantite'];
}

?>