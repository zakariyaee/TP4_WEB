<?php
require_once '../../config/database.php';
require_once '../../actions/admin-manager/reservation/validate_reservation.php';

// Récupérer les filtres depuis la session
$filterDate = $_SESSION['currentFilters']['date'] ?? '';
$filterStatus = $_SESSION['currentFilters']['status'] ?? '';
$searchQuery = $_SESSION['currentFilters']['search'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réservations - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-slate-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include_once '../../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main id="content" class="pl-64 transition-all duration-300">
            <div class="p-8">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-8">
                    <button id="toggleSidebar" class="p-2 rounded-lg bg-green-600 hover:bg-green-700 text-white shadow transition duration-300">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900">Gestion des Réservations</h2>
                        <p class="text-slate-600 text-sm">Consulter et gérer toutes les réservations</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">Total Réservations</p>
                        <h3 id="stat-total-reservations" class="text-3xl font-bold text-gray-900">0</h3>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">Confirmées</p>
                        <h3 id="stat-confirmed-reservations" class="text-3xl font-bold text-blue-600">0</h3>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">En Attente</p>
                        <h3 id="stat-pending-reservations" class="text-3xl font-bold text-yellow-600">0</h3>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">Revenus (Confirmées)</p>
                        <h3 id="stat-total-revenue" class="text-3xl font-bold text-green-600">0.00 DH</h3>
                    </div>
                </div>

                <!-- Filters & Search -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input
                                type="text"
                                id="searchInput"
                                value="<?= htmlspecialchars($searchQuery) ?>"
                                placeholder="Rechercher..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <input
                            type="date"
                            id="filterDate"
                            value="<?= htmlspecialchars($filterDate) ?>"
                            class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        <select
                            id="filterStatus"
                            class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Tous les statuts</option>
                            <option value="confirmee" <?= $filterStatus === 'confirmee' ? 'selected' : '' ?>>Confirmée</option>
                            <option value="en_attente" <?= $filterStatus === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="terminee" <?= $filterStatus === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                            <option value="annulee" <?= $filterStatus === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                        </select>
                        <button
                            onclick="resetFilters()"
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                            <i class="fas fa-redo mr-2"></i>Réinitialiser
                        </button>
                    </div>
                    
                    <!-- Résumé des résultats -->
                    <?php if (!empty($filterDate) || !empty($filterStatus) || !empty($searchQuery)): ?>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-filter mr-2"></i>
                                <strong><?= count($_SESSION['reservationDetail'] ?? []) ?></strong> réservation(s) trouvée(s) avec les filtres appliqués
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Joueur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Terrain</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Heure</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Durée</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Prix</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Extras</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($_SESSION['reservationDetail'])): ?>
                                    <tr>
                                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                                            <p class="text-lg font-medium">Aucune réservation trouvée</p>
                                            <?php if (!empty($filterDate) || !empty($filterStatus) || !empty($searchQuery)): ?>
                                                <p class="text-sm mt-2">Essayez de modifier vos filtres</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($_SESSION['reservationDetail'] as $reservation): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900">#<?= $reservation['id_reservation'] ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900"><?= htmlspecialchars($reservation['nom'] . ' ' . $reservation['prenom']) ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-600"><?= htmlspecialchars($reservation['nom_terrain']) ?></span>
                                                <br>
                                                <span class="text-xs text-gray-500"><?= htmlspecialchars($reservation['categorie_terrain']) ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900"><?= date('d/m/Y', strtotime($reservation['date_debut'])) ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900"><?= substr($reservation['heure_debut'], 0, 5) ?> - <?= substr($reservation['heure_fin'], 0, 5) ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900"><?= $reservation['duree'] ?>h</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm">
                                                    <div class="font-medium text-gray-900"><?= number_format($reservation['prix_total'], 2) ?> DH</div>
                                                    <div class="text-xs text-gray-500">
                                                        Terrain: <?= number_format($reservation['prix_terrain'], 2) ?> DH
                                                        <?php if ($reservation['prix_extras'] > 0): ?>
                                                            <br>Extras: <?= number_format($reservation['prix_extras'], 2) ?> DH
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if (!empty($reservation['extras'])): ?>
                                                    <?php foreach (explode(', ', $reservation['extras']) as $extra): ?>
                                                        <span class="inline-block px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs mr-1 mb-1">
                                                            <?= htmlspecialchars($extra) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-sm text-gray-500">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $statusClasses = [
                                                    'confirmee' => 'bg-blue-100 text-blue-700',
                                                    'en_attente' => 'bg-yellow-100 text-yellow-700',
                                                    'terminee' => 'bg-green-100 text-green-700',
                                                    'annulee' => 'bg-red-100 text-red-700'
                                                ];
                                                $statusLabels = [
                                                    'confirmee' => 'Confirmée',
                                                    'en_attente' => 'En attente',
                                                    'terminee' => 'Terminée',
                                                    'annulee' => 'Annulée'
                                                ];
                                                ?>
                                                <span class="px-3 py-1 <?= $statusClasses[$reservation['statut']] ?> rounded-full text-xs font-medium">
                                                    <?= $statusLabels[$reservation['statut']] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de confirmation -->
    <div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
            <div class="text-center mb-4">
                <div class="mx-auto w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Confirmer l'action</h3>
                <p class="text-gray-600" id="modalMessage">Êtes-vous sûr de vouloir effectuer cette action ?</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeModal()" class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition">
                    Annuler
                </button>
                <button id="confirmButton" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                    Confirmer
                </button>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="hidden fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50"></div>

    <script src="../../assets/js/reservation.js"></script>
</body>

</html>