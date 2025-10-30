<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
        <main id="content" class="flex-1 ml-64 transition-all duration-300">
            <div class="p-8">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-8">
                    <button id="toggleSidebar" class="p-2 rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white shadow transition duration-300">
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
                        <h3 class="text-3xl font-bold text-gray-900">5</h3>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">Confirmées</p>
                        <h3 class="text-3xl font-bold text-blue-600">7</h3>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">En Attente</p>
                        <h3 class="text-3xl font-bold text-yellow-600">10</h3>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">Revenus (Payées)</p>
                        <h3 class="text-3xl font-bold text-green-600"><?=$_SESSION['revenue_total']?> DH</h3>
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
                                value=""
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($reservations)): ?>
                                    <tr>
                                        <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3"></i>
                                            <p>Aucune réservation trouvée</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reservations as $r): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900">4</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">7</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-600">8</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">5</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900"><?= substr($r['heure_debut'], 0, 5) ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">5</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900"> DH</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if (!empty($r['extras'])): ?>
                                                    <?php foreach (explode(', ', $r['extras']) as $extra): ?>
                                                        <span class="inline-block px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs mr-1">
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
                                                <span class="px-3 py-1 <?= $statusClasses[$r['statut']] ?> rounded-full text-xs font-medium">
                                                    <?= $statusLabels[$r['statut']] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex gap-2">
                                                    <?php if ($r['statut'] === 'en_attente'): ?>
                                                        <button onclick="validateReservation(<?= $r['id_reservation'] ?>)" 
                                                                class="text-green-600 hover:text-green-700" 
                                                                title="Valider">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($r['statut'] !== 'annulee'): ?>
                                                        <button onclick="cancelReservation(<?= $r['id_reservation'] ?>)" 
                                                                class="text-red-600 hover:text-red-700" 
                                                                title="Annuler">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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

    <script>
       // ============================================================
// SCRIPT AJAX POUR ACTIONS DYNAMIQUES UNIQUEMENT
// L'affichage initial se fait via PHP/HTML
// ============================================================

// ============================================================
// 1. ACTIONS SUR LES RÉSERVATIONS (AJAX)
// ============================================================
function validateReservation(id) {
    showModal('Voulez-vous confirmer cette réservation ?', () => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../../actions/admin-respo/validate_reservation.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showNotification('Réservation confirmée avec succès', 'success');
                        // Recharger la page pour afficher les nouvelles données
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(response.message || 'Erreur lors de la validation', 'error');
                    }
                } catch (e) {
                    showNotification('Erreur lors de la validation', 'error');
                }
            }
        };

        xhr.send(JSON.stringify({ id_reservation: id }));
    });
}

function cancelReservation(id) {
    showModal('Voulez-vous annuler cette réservation ?', () => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../../actions/admin-respo/cancel_reservation.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showNotification('Réservation annulée', 'success');
                        // Recharger la page pour afficher les nouvelles données
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(response.message || 'Erreur lors de l\'annulation', 'error');
                    }
                } catch (e) {
                    showNotification('Erreur lors de l\'annulation', 'error');
                }
            }
        };

        xhr.send(JSON.stringify({ id_reservation: id }));
    });
}

// ============================================================
// 2. FILTRES ET RECHERCHE (Rechargement de page)
// ============================================================
function applyFilters() {
    const searchQuery = document.getElementById('searchInput').value;
    const filterDate = document.getElementById('filterDate').value;
    const filterStatus = document.getElementById('filterStatus').value;

    // Construire l'URL avec les paramètres
    const params = new URLSearchParams();
    if (searchQuery) params.append('search', searchQuery);
    if (filterDate) params.append('date', filterDate);
    if (filterStatus) params.append('status', filterStatus);

    // Recharger la page avec les filtres
    window.location.href = '?' + params.toString();
}

function resetFilters() {
    // Recharger la page sans paramètres
    window.location.href = window.location.pathname;
}

// ============================================================
// 3. MODAL DE CONFIRMATION
// ============================================================
function showModal(message, onConfirm) {
    document.getElementById('modalMessage').textContent = message;
    document.getElementById('confirmModal').classList.remove('hidden');
    document.getElementById('confirmButton').onclick = () => {
        onConfirm();
        closeModal();
    };
}

function closeModal() {
    document.getElementById('confirmModal').classList.add('hidden');
}

// ============================================================
// 4. NOTIFICATIONS
// ============================================================
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 text-white ${colors[type]}`;
    notification.textContent = message;
    notification.classList.remove('hidden');
    
    setTimeout(() => notification.classList.add('hidden'), 3000);
}

// ============================================================
// 5. ÉVÉNEMENTS (Debounce pour la recherche)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    // Recherche avec debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => applyFilters(), 800);
    });

    // Filtrage par date
    document.getElementById('filterDate').addEventListener('change', applyFilters);

    // Filtrage par statut
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
});
    </script>
</body>

</html>