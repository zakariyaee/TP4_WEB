<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

$email_joueur = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitations Envoyées - TerrainBook</title>
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

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-8 max-w-7xl">
        <!-- En-tête -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <a href="invitations.php" class="text-emerald-600 hover:text-emerald-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Invitations Envoyées</h1>
            </div>
            <p class="text-gray-600">Suivez le statut de vos invitations envoyées aux autres joueurs</p>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">En attente</p>
                        <p class="text-3xl font-bold text-orange-600" id="stat-en-attente">0</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Acceptées</p>
                        <p class="text-3xl font-bold text-emerald-600" id="stat-acceptees">0</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Refusées</p>
                        <p class="text-3xl font-bold text-red-600" id="stat-refusees">0</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <div class="bg-white rounded-xl shadow-md mb-6">
            <div class="border-b border-gray-200 px-6">
                <div class="flex gap-4">
                    <button onclick="switchTab('toutes')" id="tab-toutes" class="tab-button px-6 py-4 font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 active">
                        <i class="fas fa-list mr-2"></i>Toutes
                    </button>
                    <button onclick="switchTab('en_attente')" id="tab-en_attente" class="tab-button px-6 py-4 font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600">
                        <i class="fas fa-clock mr-2"></i>En attente
                    </button>
                    <button onclick="switchTab('acceptees')" id="tab-acceptees" class="tab-button px-6 py-4 font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600">
                        <i class="fas fa-check-circle mr-2"></i>Acceptées
                    </button>
                    <button onclick="switchTab('refusees')" id="tab-refusees" class="tab-button px-6 py-4 font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600">
                        <i class="fas fa-times-circle mr-2"></i>Refusées
                    </button>
                </div>
            </div>
        </div>

        <!-- Liste des invitations -->
        <div id="invitations-list" class="space-y-4">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-3"></i>
                <p class="text-gray-500">Chargement des invitations...</p>
            </div>
        </div>
    </div>

    <script>
        const currentUserEmail = '<?php echo $email_joueur; ?>';
        let currentTab = 'toutes';
        let allInvitations = [];

        // Configuration localStorage
        const STORAGE_KEY = 'terrainbook_sent_invitations';
        const CACHE_DURATION = 30000; // 30 secondes

        document.addEventListener('DOMContentLoaded', function() {
            loadInvitations();
            window.addEventListener('storage', handleStorageChange);
            setInterval(syncWithServer, 60000); 
            
            // Écouter aussi les changements du signal de synchronisation
            window.addEventListener('storage', function(e) {
                if (e.key === 'sync_invitations') {
                    console.log('Signal de synchronisation reçu');
                    invalidateCache();
                }
            });
        });

        function handleStorageChange(e) {
            if (e.key === STORAGE_KEY) {
                loadFromLocalStorage();
            }
        }

        function loadInvitations() {
            const cached = getFromLocalStorage();
            if (cached && !isCacheExpired(cached.timestamp)) {
                allInvitations = cached.data.invitations;
                updateStats(cached.data.stats);
                renderInvitations();
            } else {
                fetchFromServer();
            }
        }

        function getFromLocalStorage() {
            try {
                const data = localStorage.getItem(STORAGE_KEY);
                return data ? JSON.parse(data) : null;
            } catch (error) {
                console.error('Erreur lecture localStorage:', error);
                return null;
            }
        }

        function saveToLocalStorage(data) {
            try {
                const cacheData = { data: data, timestamp: Date.now() };
                localStorage.setItem(STORAGE_KEY, JSON.stringify(cacheData));
            } catch (error) {
                console.error('Erreur sauvegarde localStorage:', error);
            }
        }

        function isCacheExpired(timestamp) {
            return (Date.now() - timestamp) > CACHE_DURATION;
        }

        function fetchFromServer() {
            fetch('../../../actions/player/invitation/get_sent_invitations.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allInvitations = data.invitations;
                        saveToLocalStorage(data);
                        updateStats(data.stats);
                        renderInvitations();
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement API:', error);
                    const cached = getFromLocalStorage();
                    if (cached) {
                        allInvitations = cached.data.invitations;
                        updateStats(cached.data.stats);
                        renderInvitations();
                    }
                });
        }

        function syncWithServer() {
            const cached = getFromLocalStorage();
            if (!cached || isCacheExpired(cached.timestamp)) {
                fetchFromServer();
            }
        }

        function loadFromLocalStorage() {
            const cached = getFromLocalStorage();
            if (cached) {
                allInvitations = cached.data.invitations;
                updateStats(cached.data.stats);
                renderInvitations();
            }
        }

        function invalidateCache() {
            localStorage.removeItem(STORAGE_KEY);
            fetchFromServer();
        }

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`tab-${tab}`).classList.add('active');
            renderInvitations();
        }

        function updateStats(stats) {
            document.getElementById('stat-en-attente').textContent = stats.en_attente || 0;
            document.getElementById('stat-acceptees').textContent = stats.acceptees || 0;
            document.getElementById('stat-refusees').textContent = stats.refusees || 0;
        }

        function renderInvitations() {
            const container = document.getElementById('invitations-list');
            let filteredInvitations = allInvitations;

            if (currentTab !== 'toutes') {
                // Mapper les onglets aux statuts de la base de données
                const statutMap = {
                    'en_attente': 'en_attente',
                    'acceptees': 'acceptee',
                    'refusees': 'refusee'
                };
                const statut = statutMap[currentTab] || currentTab;
                filteredInvitations = allInvitations.filter(inv => inv.statut === statut);
            }

            if (filteredInvitations.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-xl shadow-md p-8 text-center">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">Aucune invitation pour le moment</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = filteredInvitations.map(inv => createInvitationHTML(inv)).join('');
        }

        function createInvitationHTML(inv) {
            // Normaliser le statut (s'assurer qu'il correspond aux valeurs de la base de données)
            let statut = inv.statut || 'en_attente';
            if (statut === 'acceptees') statut = 'acceptee';
            if (statut === 'refusees') statut = 'refusee';
            if (!['en_attente', 'acceptee', 'refusee'].includes(statut)) {
                statut = 'en_attente'; // Valeur par défaut
            }
            
            const statusColors = {
                'en_attente': 'bg-orange-100 text-orange-700',
                'acceptee': 'bg-emerald-100 text-emerald-700',
                'refusee': 'bg-red-100 text-red-700'
            };

            const statusIcons = {
                'en_attente': 'fa-clock',
                'acceptee': 'fa-check-circle',
                'refusee': 'fa-times-circle'
            };

            const statusLabels = {
                'en_attente': 'En attente de réponse',
                'acceptee': 'Acceptée',
                'refusee': 'Refusée'
            };

            return `
                <div class="bg-white rounded-xl shadow-md p-6 fade-in">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4 flex-1">
                            <div class="w-12 h-12 bg-gradient-to-br from-emerald-600 to-green-700 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-lg">${inv.destinataire_initiales}</span>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-bold text-gray-900">${inv.nom_equipe}</h3>
                                    <span class="status-badge px-3 py-1 rounded-full text-sm font-medium ${statusColors[statut]}">
                                        <i class="fas ${statusIcons[statut]}"></i>
                                        ${statusLabels[statut]}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-2">
                                    <i class="fas fa-user text-emerald-600 mr-2"></i>
                                    Envoyée à ${inv.destinataire_nom} ${inv.destinataire_prenom}
                                </p>
                                ${inv.date_debut ? `
                                    <p class="text-sm text-gray-600 mb-2">
                                        <i class="fas fa-calendar text-emerald-600 mr-2"></i>
                                        ${inv.date_formatted} à ${inv.heure_formatted}
                                    </p>` : ''}
                                ${inv.position ? `
                                    <p class="text-sm text-gray-600 mb-2">
                                        <i class="fas fa-running text-emerald-600 mr-2"></i>
                                        Position: ${inv.position} - Niveau: ${inv.niveau}
                                    </p>` : ''}
                                <p class="text-sm text-gray-700 mt-3 bg-gray-50 p-3 rounded-lg">${inv.contenu}</p>
                                <p class="text-xs text-gray-400 mt-2">
                                    <i class="fas fa-clock mr-1"></i>Envoyée le ${inv.date_message_formatted}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function showNotification(type, message) {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 fade-in`;
            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-10px)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>