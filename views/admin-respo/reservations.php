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
          <!-- Header avec bouton toggle -->
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
                        <h3 id="total-reservations" class="text-3xl font-bold text-gray-900"><?=$_SESSION['nombre_reservations']?></h3>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">Confirmées</p>
                        <h3 id="confirmed-reservations" class="text-3xl font-bold text-blue-600"><?=$_SESSION['nombre_confirm_reservations']?>€</h3>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">En Attente</p>
                        <h3 id="pending-reservations" class="text-3xl font-bold text-yellow-600"><?$_SESSION['nombre_attente_reservations']?>€</h3>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
                        <p class="text-gray-600 text-sm mb-1">Revenus (Payées)</p>
                        <h3 id="revenue-total" class="text-3xl font-bold text-green-600"><?= $_SESSION['revenue_total-resrvation']?> €</h3>
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
                                placeholder="Rechercher..." 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                            >
                        </div>
                        <input 
                            type="date" 
                            id="filterDate" 
                            class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                        >
                        <select 
                            id="filterStatus" 
                            class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="confirmee">Confirmée</option>
                            <option value="en_attente">En attente</option>
                            <option value="payee">Payée</option>
                            <option value="annulee">Annulée</option>
                        </select>
                        <button 
                            onclick="resetFilters()" 
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium"
                        >
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
                            <tbody id="reservationsTableBody" class="divide-y divide-gray-200">
                                <!-- Les données seront chargées ici via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        Affichage de <span id="showing-from">1</span> à <span id="showing-to">5</span> sur <span id="total-entries">5</span> entrées
                    </p>
                    <div class="flex gap-2">
                        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-4 py-2 bg-teal-600 text-white rounded-lg">1</button>
                        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">2</button>
                        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Help Button -->
    <button class="fixed bottom-6 right-6 w-12 h-12 bg-gray-900 hover:bg-gray-800 text-white rounded-full shadow-lg flex items-center justify-center transition">
        <i class="fas fa-question"></i>
    </button>

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
        // Données de démonstration
        const reservationsData = [
            {
                id: 1,
                joueur: 'Mohamed Ali',
                terrain: 'Terrain A - Centre',
                date: '25/10/2025',
                heure: '16:00',
                duree: '2h',
                prix: '300 €',
                extras: ['Ballon', 'Arbitre'],
                statut: 'confirmee'
            },
            {
                id: 2,
                joueur: 'Fatima Zahra',
                terrain: 'Terrain B - Nord',
                date: '26/10/2025',
                heure: '18:00',
                duree: '1h',
                prix: '250 €',
                extras: [],
                statut: 'payee'
            },
            {
                id: 3,
                joueur: 'Youssef Ben',
                terrain: 'Terrain A - Centre',
                date: '27/10/2025',
                heure: '20:00',
                duree: '1.5h',
                prix: '225 €',
                extras: ['Ballon'],
                statut: 'en_attente'
            },
            {
                id: 4,
                joueur: 'Sarah Mansouri',
                terrain: 'Terrain D - Est',
                date: '28/10/2025',
                heure: '14:00',
                duree: '2h',
                prix: '360 €',
                extras: ['Arbitre', 'Maillots'],
                statut: 'confirmee'
            },
            {
                id: 5,
                joueur: 'Ahmed Tazi',
                terrain: 'Terrain C - Sud',
                date: '29/10/2025',
                heure: '19:00',
                duree: '1h',
                prix: '350 €',
                extras: [],
                statut: 'annulee'
            }
        ];

        let currentReservations = [...reservationsData];

        // Fonction pour obtenir la classe de badge de statut
        function getStatusBadge(statut) {
            const badges = {
                'confirmee': '<span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Confirmée</span>',
                'en_attente': '<span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">En attente</span>',
                'payee': '<span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Payée</span>',
                'annulee': '<span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Annulée</span>'
            };
            return badges[statut] || statut;
        }

        // Fonction pour afficher les extras
        function displayExtras(extras) {
            if (extras.length === 0) return '-';
            return extras.map(extra => 
                `<span class="inline-block px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs mr-1">${extra}</span>`
            ).join('');
        }

        // Fonction pour charger les réservations
        function loadReservations() {
            const tbody = document.getElementById('reservationsTableBody');
            
            if (currentReservations.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>Aucune réservation trouvée</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = currentReservations.map(reservation => `
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900">#${reservation.id}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">${reservation.joueur}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-600">${reservation.terrain}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">${reservation.date}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">${reservation.heure}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">${reservation.duree}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900">${reservation.prix}</span>
                    </td>
                    <td class="px-6 py-4">
                        ${displayExtras(reservation.extras)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${getStatusBadge(reservation.statut)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex gap-2">
                            ${reservation.statut === 'en_attente' ? 
                                '<button onclick="validateReservation(' + reservation.id + ')" class="text-green-600 hover:text-green-700" title="Valider"><i class="fas fa-check"></i></button>' : ''}
                            <button onclick="cancelReservation(' + reservation.id + ')" class="text-red-600 hover:text-red-700" title="Annuler"><i class="fas fa-times"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');

            updateStats();
        }

        

        // Fonction de recherche
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            currentReservations = reservationsData.filter(r => 
                r.joueur.toLowerCase().includes(search) ||
                r.terrain.toLowerCase().includes(search)
            );
            loadReservations();
        });

        // Fonction de filtrage par statut
        document.getElementById('filterStatus').addEventListener('change', function(e) {
            const status = e.target.value;
            if (status === '') {
                currentReservations = [...reservationsData];
            } else {
                currentReservations = reservationsData.filter(r => r.statut === status);
            }
            loadReservations();
        });

        // Réinitialiser les filtres
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterDate').value = '';
            document.getElementById('filterStatus').value = '';
            currentReservations = [...reservationsData];
            loadReservations();
        }

        

        // Annuler une réservation
        function cancelReservation(id) {
            showModal('Voulez-vous annuler cette réservation ?', () => {
                const index = currentReservations.findIndex(r => r.id === id);
                if (index !== -1) {
                    currentReservations[index].statut = 'annulee';
                    loadReservations();
                    showNotification('Réservation annulée', 'error');
                }
            });
        }

        // Afficher le modal
        function showModal(message, onConfirm) {
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('confirmModal').classList.remove('hidden');
            document.getElementById('confirmButton').onclick = () => {
                onConfirm();
                closeModal();
            };
        }

        // Fermer le modal
        function closeModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        // Afficher une notification
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
            
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 3000);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            loadReservations();
        });
    </script>
</body>
</html>