<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

$email_joueur = $_SESSION['user_email'];

// Récupérer les réservations depuis la base de données
try {
    // Prochaines réservations (confirmées + en attente)
    $stmt = $pdo->prepare("
        SELECT 
            r.id_reservation,
            r.statut,
            r.date_reservation,
            DATE(r.date_reservation) AS date_reservation_only,
            TIME(r.date_reservation) AS heure_reservation,
            DATE_FORMAT(r.date_reservation, '%d/%m/%Y') AS date_formatted,
            DATE_FORMAT(r.date_reservation, '%H:%i') AS heure_formatted,
            r.date_creation,
            
            -- Informations terrain
            t.id_terrain,
            t.nom_te AS nom_terrain,
            t.categorie AS categorie_terrain,
            t.localisation,
            t.prix_heure,
            
            -- Informations créneau
            cr.heure_debut,
            cr.heure_fin,
            TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin) AS duree_heures,
            
            -- Calcul du prix terrain
            (t.prix_heure * TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin)) AS prix_terrain,
            
            -- Informations équipe du joueur
            e1.id_equipe AS id_equipe_joueur,
            e1.nom_equipe AS nom_equipe_joueur,
            
            -- Informations équipe adverse
            e2.id_equipe AS id_equipe_adverse,
            e2.nom_equipe AS nom_equipe_adverse,
            
            -- Prix total
            (
                (t.prix_heure * TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin)) +
                COALESCE((
                    SELECT SUM(o.prix * ro.quantite)
                    FROM reservation_objet ro
                    INNER JOIN objet o ON ro.id_object = o.id_object
                    WHERE ro.id_reservation = r.id_reservation
                ), 0)
            ) AS prix_total,
            
            -- Calcul des jours restants pour modification
            DATEDIFF(r.date_reservation, NOW()) AS jours_restants
            
        FROM reservation r
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        INNER JOIN creneau cr ON r.id_creneau = cr.id_creneaux
        INNER JOIN equipe e1 ON r.id_equipe = e1.id_equipe
        LEFT JOIN equipe e2 ON r.id_equipe_adverse = e2.id_equipe
        
        WHERE r.id_joueur = :email
        AND r.statut IN ('confirmee', 'en_attente')
        AND DATE(r.date_reservation) >= CURDATE()
        
        ORDER BY r.date_reservation ASC
    ");
    $stmt->execute([':email' => $email_joueur]);
    $prochaines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Historique (terminées + annulées + passées)
    $stmt = $pdo->prepare("
        SELECT 
            r.id_reservation,
            r.statut,
            r.date_reservation,
            DATE(r.date_reservation) AS date_reservation_only,
            TIME(r.date_reservation) AS heure_reservation,
            DATE_FORMAT(r.date_reservation, '%d/%m/%Y') AS date_formatted,
            DATE_FORMAT(r.date_reservation, '%H:%i') AS heure_formatted,
            r.date_creation,
            
            -- Informations terrain
            t.id_terrain,
            t.nom_te AS nom_terrain,
            t.categorie AS categorie_terrain,
            t.localisation,
            t.prix_heure,
            
            -- Informations créneau
            cr.heure_debut,
            cr.heure_fin,
            TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin) AS duree_heures,
            
            -- Calcul du prix terrain
            (t.prix_heure * TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin)) AS prix_terrain,
            
            -- Informations équipe du joueur
            e1.id_equipe AS id_equipe_joueur,
            e1.nom_equipe AS nom_equipe_joueur,
            
            -- Informations équipe adverse
            e2.id_equipe AS id_equipe_adverse,
            e2.nom_equipe AS nom_equipe_adverse,
            
            -- Prix total
            (
                (t.prix_heure * TIMESTAMPDIFF(HOUR, cr.heure_debut, cr.heure_fin)) +
                COALESCE((
                    SELECT SUM(o.prix * ro.quantite)
                    FROM reservation_objet ro
                    INNER JOIN objet o ON ro.id_object = o.id_object
                    WHERE ro.id_reservation = r.id_reservation
                ), 0)
            ) AS prix_total
            
        FROM reservation r
        INNER JOIN terrain t ON r.id_terrain = t.id_terrain
        INNER JOIN creneau cr ON r.id_creneau = cr.id_creneaux
        INNER JOIN equipe e1 ON r.id_equipe = e1.id_equipe
        LEFT JOIN equipe e2 ON r.id_equipe_adverse = e2.id_equipe
        
        WHERE r.id_joueur = :email
        AND (r.statut IN ('terminee', 'annulee') OR DATE(r.date_reservation) < CURDATE())
        
        ORDER BY r.date_reservation DESC
    ");
    $stmt->execute([':email' => $email_joueur]);
    $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $prochaines = [];
    $historique = [];
    error_log("Erreur chargement réservations: " . $e->getMessage());
}

// Statistiques
$nb_prochaines = count($prochaines);
$nb_confirmees = count(array_filter($prochaines, fn($r) => $r['statut'] === 'confirmee'));
$nb_en_attente = count(array_filter($prochaines, fn($r) => $r['statut'] === 'en_attente'));
$nb_historiques = count($historique);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tab-button.active {
            background-color: #f3f4f6;
            border-bottom: 2px solid #10b981;
            color: #10b981;
        }
        #editModal.show {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

#editModal .bg-white {
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-8 max-w-7xl">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Mes Réservations</h1>
            <p class="text-gray-600">Gérez vos réservations passées et à venir</p>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Prochaines réservations</p>
                        <p class="text-3xl font-bold text-emerald-600" id="totalReservations"><?= $nb_prochaines ?></p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-check text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Confirmées</p>
                        <p class="text-3xl font-bold text-blue-600" id="ConfirmedReservations"><?= $nb_confirmees ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Annule</p>
                        <p class="text-3xl font-bold text-orange-600" id="pendingReservations"><?= $nb_en_attente ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <div class="bg-white rounded-xl shadow-md mb-6">
            <div class="border-b border-gray-200 px-6">
                <div class="flex gap-4">
                    <button onclick="switchTab('prochaines')" id="tab-prochaines" class="tab-button px-6 py-4 font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 active">
                        <i class="fas fa-calendar-alt mr-2"></i>Prochaines réservations
                </button>
                    <button onclick="switchTab('historique')" id="tab-historique" class="tab-button px-6 py-4 font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600">
                        <i class="fas fa-history mr-2"></i>Historique
                </button>
                </div>
            </div>
        </div>

        <!-- Section : Prochaines réservations -->
        <div id="section-prochaines" class="space-y-4">
            <?php if (!empty($prochaines)) : ?>
                <?php foreach ($prochaines as $r) : ?>
                    <div class="bg-white rounded-xl shadow-md p-6 fade-in">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($r['nom_terrain']) ?></h3>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= $r['statut'] === 'confirmee' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' ?>">
                                        <?= $r['statut'] === 'confirmee' ? 'Confirmée' : 'En attente' ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-4 mb-4 text-gray-600">
                            <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar text-emerald-600"></i>
                                        <span><?= $r['date_formatted'] ?? date('d/m/Y', strtotime($r['date_reservation'])) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                        <i class="fas fa-clock text-emerald-600"></i>
                                <span><?= substr($r['heure_debut'], 0, 5) ?> - <?= substr($r['heure_fin'], 0, 5) ?></span>
                            </div>
                                    <?php if (!empty($r['localisation'])) : ?>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-map-marker-alt text-emerald-600"></i>
                                        <span><?= htmlspecialchars($r['localisation']) ?></span>
                                    </div>
                                    <?php endif; ?>
                        </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <span class="text-gray-600 text-sm">Votre équipe:</span>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($r['nom_equipe_joueur'] ?? '-') ?></p>
                            </div>
                                    <div>
                                        <span class="text-gray-600 text-sm">Équipe adverse:</span>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($r['nom_equipe_adverse'] ?? '—') ?></p>
                            </div>
                                    <div>
                                        <span class="text-gray-600 text-sm">Prix total:</span>
                                        <p class="font-bold text-emerald-600 text-lg"><?= number_format($r['prix_total'], 2, '.', ' ') ?> DH</p>
                            </div>
                        </div>

                                <?php if (isset($r['jours_restants']) && $r['jours_restants'] > 0) : ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                                <p class="text-green-700 text-sm">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Modification possible (<?= $r['jours_restants'] ?> jours restants)
                                </p>
                            </div>
                        <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex gap-3 mt-4">
                            <?php if (isset($r['jours_restants']) && $r['jours_restants'] > 0 && $r['statut'] === 'confirmee') : ?>
                                <button onclick="editReservation(<?= $r['id_reservation'] ?>)" class="flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition font-medium flex items-center justify-center gap-2">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <?php endif; ?>
                            <?php if ($r['statut'] !== 'annulee' && (isset($r['jours_restants']) && $r['jours_restants'] > 0 || !isset($r['jours_restants']))) : ?>
                                <button onclick="cancelReservation(<?= $r['id_reservation'] ?>)" class="flex-1 bg-white border border-red-300 text-red-600 px-4 py-2 rounded-lg hover:bg-red-50 transition font-medium flex items-center justify-center gap-2">
                                    <i class="fas fa-times"></i> Annuler
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="bg-white rounded-xl shadow-md p-12 text-center">
                    <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucune réservation</h3>
                    <p class="text-gray-600 mb-6">Vous n'avez pas encore de réservations à venir</p>
                    <a href="reserver.php" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition font-medium">
                        <i class="fas fa-plus"></i>
                        Réserver un terrain
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section : Historique -->
        <div id="section-historique" class="hidden space-y-4">
            <?php if (!empty($historique)) : ?>
                <?php foreach ($historique as $r) : ?>
                    <div class="bg-white rounded-xl shadow-md p-6 fade-in">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($r['nom_terrain']) ?></h3>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= $r['statut'] === 'terminee' ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-700' ?>">
                                        <?= $r['statut'] === 'terminee' ? 'Terminée' : ($r['statut'] === 'annulee' ? 'Annulée' : 'Passée') ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-4 mb-4 text-gray-600">
                            <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar text-gray-500"></i>
                                        <span><?= $r['date_formatted'] ?? date('d/m/Y', strtotime($r['date_reservation'])) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                        <i class="fas fa-clock text-gray-500"></i>
                                <span><?= substr($r['heure_debut'], 0, 5) ?> - <?= substr($r['heure_fin'], 0, 5) ?></span>
                            </div>
                                    <?php if (!empty($r['localisation'])) : ?>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-map-marker-alt text-gray-500"></i>
                                        <span><?= htmlspecialchars($r['localisation']) ?></span>
                                    </div>
                                    <?php endif; ?>
                        </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <span class="text-gray-600 text-sm">Votre équipe:</span>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($r['nom_equipe_joueur'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 text-sm">Équipe adverse:</span>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($r['nom_equipe_adverse'] ?? '—') ?></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 text-sm">Prix total:</span>
                                        <p class="font-bold text-gray-600"><?= number_format($r['prix_total'], 2, '.', ' ') ?> DH</p>
                            </div>
                            </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="bg-white rounded-xl shadow-md p-12 text-center">
                    <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucun historique</h3>
                    <p class="text-gray-600">Aucun match joué ou annulé pour le moment</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

            <!-- Form popup pour modifier une réservation -->
             <!-- À ajouter dans votre fichier mes_reservations.php, avant la fermeture du </body> -->

        <!-- Modal de modification de réservation -->
        <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- En-tête du modal -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-edit text-emerald-600 mr-2"></i>
                        Modifier la réservation
                    </h2>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <!-- Contenu du modal -->
                <div id="editModalContent" class="p-6">
                    <!-- Loader initial -->
                    <div class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
                    </div>
                </div>

                <!-- Pied du modal -->
                <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex gap-3">
                    <button onclick="closeEditModal()" class="flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-3 rounded-lg hover:bg-gray-50 transition font-medium">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                    <button onclick="saveReservation()" id="saveBtn" class="flex-1 bg-emerald-600 text-white px-4 py-3 rounded-lg hover:bg-emerald-700 transition font-medium">
                        <i class="fas fa-check mr-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </div>
        </div>
    <script src="/assets/js/user_Reservation.js"></script>
</body>
</html>
