<?php
require_once '../../config/database.php';
$prochaines = $_SESSION['reservations_prochaines'];
$historique = $_SESSION['reservations_historique'];
// Statistiques rapides
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
    <title>Mes Réservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Mes Réservations</h1>
            <p class="text-gray-600">Gérez vos réservations passées et à venir</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition">
                <div class="text-4xl font-bold text-teal-600 mb-2" id="totalReservations"><?= $nb_prochaines ?></div>
                <div class="text-gray-600 text-sm">Prochaines réservations</div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition">
             <div class="text-4xl font-bold text-blue-600 mb-2" id="ConfirmedReservations"><?= $nb_confirmees ?></div>
                <div class="text-gray-600 text-sm">Confirmées</div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition">
            <div class="text-4xl font-bold text-orange-600 mb-2" id="canceledReservations"><?= $nb_en_attente ?></div>
                <div class="text-gray-600 text-sm">En attente</div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition">
                <div class="text-4xl font-bold text-purple-600 mb-2"><?= $nb_historiques ?></div>
                <div class="text-gray-600 text-sm">Matchs joués</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
            <div class="flex border-b border-gray-200">
                <button id="tab-prochaines" class="flex-1 px-6 py-4 text-gray-800 font-medium border-b-2 border-teal-600 bg-white hover:bg-gray-50 transition">
                    Prochaines réservations
                </button>
                <button id="tab-historique" class="flex-1 px-6 py-4 text-gray-600 font-medium hover:bg-gray-50 transition">
                    Historique
                </button>
            </div>
        </div>

        <!-- Section : Prochaines réservations -->
        <div id="section-prochaines" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php if (!empty($prochaines)) : ?>
                <?php foreach ($prochaines as $r) : ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($r['nom_terrain']) ?></h3>
                            <span class="px-3 py-1 rounded-full text-sm font-medium 
                                <?= $r['statut'] === 'confirmee' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' ?>">
                                <?= ucfirst($r['statut']) ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-4 mb-4 text-gray-600">
                            <div class="flex items-center gap-2">
                                <i class="far fa-calendar"></i>
                                <span><?= date('d/m/Y', strtotime($r['date_reservation'])) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="far fa-clock"></i>
                                <span><?= substr($r['heure_debut'], 0, 5) ?> - <?= substr($r['heure_fin'], 0, 5) ?></span>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Votre équipe:</span>
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($r['nom_equipe_joueur'] ?? '-') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Équipe adverse:</span>
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($r['nom_equipe_adverse'] ?? '—') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Prix total:</span>
                                <span class="font-bold text-teal-600"><?= number_format($r['prix_total'], 2, '.', ' ') ?> DH</span>
                            </div>
                        </div>

                        <?php if ($r['jours_restants'] > 0) : ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                                <p class="text-green-700 text-sm">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Modification possible (<?= $r['jours_restants'] ?> jours restants)
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="flex gap-3">
                            <button class="flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition font-medium flex items-center justify-center gap-2">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <button class="flex-1 bg-white border border-red-300 text-red-600 px-4 py-2 rounded-lg hover:bg-red-50 transition font-medium flex items-center justify-center gap-2">
                                <i class="fas fa-trash-alt"></i> Annuler
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="col-span-full bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucune réservation</h3>
                    <p class="text-gray-600 mb-6">Vous n'avez pas encore de réservations à venir</p>
                    <a href="/views/terrains.php" class="bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-700 transition font-medium">
                        Réserver un terrain
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section : Historique -->
        <div id="section-historique" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php if (!empty($historique)) : ?>
                <?php foreach ($historique as $r) : ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($r['nom_terrain']) ?></h3>
                            <span class="px-3 py-1 rounded-full text-sm font-medium 
                                <?= $r['statut'] === 'terminee' ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-700' ?>">
                                <?= ucfirst($r['statut']) ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-4 mb-4 text-gray-600">
                            <div class="flex items-center gap-2">
                                <i class="far fa-calendar"></i>
                                <span><?= date('d/m/Y', strtotime($r['date_reservation'])) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="far fa-clock"></i>
                                <span><?= substr($r['heure_debut'], 0, 5) ?> - <?= substr($r['heure_fin'], 0, 5) ?></span>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Votre équipe:</span>
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($r['nom_equipe_joueur']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Équipe adverse:</span>
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($r['nom_equipe_adverse'] ?? '—') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Prix total:</span>
                                <span class="font-bold text-teal-600"><?= number_format($r['prix_total'], 2, ',', ' ') ?> €</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="col-span-full bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <i class="fas fa-calendar-times text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucun historique</h3>
                    <p class="text-gray-600">Aucun match joué ou annulé pour le moment</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Simple toggle entre prochaines réservations et historique
        const tabProchaines = document.getElementById('tab-prochaines');
        const tabHistorique = document.getElementById('tab-historique');
        const sectionProchaines = document.getElementById('section-prochaines');
        const sectionHistorique = document.getElementById('section-historique');

        tabProchaines.addEventListener('click', () => {
            tabProchaines.classList.add('border-teal-600', 'text-gray-800');
            tabHistorique.classList.remove('border-teal-600', 'text-gray-800');
            sectionProchaines.classList.remove('hidden');
            sectionHistorique.classList.add('hidden');
        });

        tabHistorique.addEventListener('click', () => {
            tabHistorique.classList.add('border-teal-600', 'text-gray-800');
            tabProchaines.classList.remove('border-teal-600', 'text-gray-800');
            sectionProchaines.classList.add('hidden');
            sectionHistorique.classList.remove('hidden');
        });
    </script>
    <script src="/assets/js/user_Reservation.js">
</script>

</body>
</html>