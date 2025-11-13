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

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .status-updated {
            animation: pulse 0.5s ease-in-out;
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

        .sync-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 1000;
        }

        .sync-indicator.active {
            display: flex;
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

    <!-- Indicateur de synchronisation -->
    <div id="sync-indicator" class="sync-indicator">
        <i class="fas fa-sync-alt fa-spin text-emerald-600"></i>
        <span class="text-sm text-gray-700">Synchronisation...</span>
    </div>

    <script>
        const currentUserEmail = '<?php echo $email_joueur; ?>';
        let currentTab = 'toutes';
        let allInvitations = [];
        let syncInterval = null;
        let lastServerTimestamp = 0;

        // Configuration localStorage
        const STORAGE_KEY = 'terrainbook_sent_invitations';
        const SYNC_SIGNAL_KEY = 'terrainbook_sync_signal';
        const CACHE_DURATION = 5000; // 5 secondes
        const SYNC_INTERVAL = 3000; // Vérifier toutes les 3 secondes

        document.addEventListener('DOMContentLoaded', function() {
            loadInvitations();
            // Écouter les changements dans localStorage (pour synchronisation multi-onglets)
            window.addEventListener('storage', handleStorageChange);
            // Polling régulier pour vérifier les mises à jour
            startSyncInterval();
        });

        // Gestion des changements de localStorage (entre onglets)
        function handleStorageChange(e) {
            if (e.key === STORAGE_KEY) {
                console.log('Changement détecté dans localStorage');
                loadFromLocalStorage();
            } else if (e.key === SYNC_SIGNAL_KEY) {
                console.log('Signal de synchronisation reçu');
                fetchFromServer(true);
            }
        }

        // Démarrer l'intervalle de synchronisation
        function startSyncInterval() {
            syncInterval = setInterval(() => {
                checkForUpdates();
            }, SYNC_INTERVAL);
        }

        // Vérifier s'il y a des mises à jour
        function checkForUpdates() {
            const cached = getFromLocalStorage();
            if (!cached || isCacheExpired(cached.timestamp)) {
                fetchFromServer(false);
            }
        }

        // Charger les invitations
        function loadInvitations() {
            const cached = getFromLocalStorage();
            if (cached && !isCacheExpired(cached.timestamp)) {
                allInvitations = cached.data.invitations;
                lastServerTimestamp = cached.data.timestamp || 0;
                updateStats(cached.data.stats);
                renderInvitations();
            } else {
                fetchFromServer(true);
            }
        }

        // Récupérer depuis localStorage
        function getFromLocalStorage() {
            try {
                const data = localStorage.getItem(STORAGE_KEY);
                return data ? JSON.parse(data) : null;
            } catch (error) {
                console.error('Erreur lecture localStorage:', error);
                return null;
            }
        }

        // Sauvegarder dans localStorage
        function saveToLocalStorage(data) {
            try {
                const cacheData = { 
                    data: data, 
                    timestamp: Date.now()
                };
                localStorage.setItem(STORAGE_KEY, JSON.stringify(cacheData));
                // Notifier les autres onglets
                localStorage.setItem(SYNC_SIGNAL_KEY, Date.now().toString());
            } catch (error) {
                console.error('❌ Erreur sauvegarde localStorage:', error);
            }
        }

        // Vérifier si le cache a expiré
        function isCacheExpired(timestamp) {
            return (Date.now() - timestamp) > CACHE_DURATION;
        }

        // Récupérer depuis le serveur
        function fetchFromServer(showIndicator = false) {
            if (showIndicator) {
                showSyncIndicator();
            }

            fetch('../../../actions/player/invitation/get_sent_invitations.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Vérifier s'il y a eu des changements
                        const hasChanges = detectChanges(data.invitations);
                        
                        allInvitations = data.invitations;
                        lastServerTimestamp = data.timestamp || 0;
                        saveToLocalStorage(data);
                        updateStats(data.stats);
                        renderInvitations(hasChanges);

                        if (hasChanges) {
                            console.log('Nouvelles données détectées et affichées');
                        }
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
                })
                .finally(() => {
                    hideSyncIndicator();
                });
        }

        // Détecter les changements entre les anciennes et nouvelles données
        function detectChanges(newInvitations) {
            if (allInvitations.length !== newInvitations.length) {
                return true;
            }

            for (let i = 0; i < newInvitations.length; i++) {
                const oldInv = allInvitations.find(inv => inv.id_demande === newInvitations[i].id_demande);
                if (!oldInv || oldInv.statut !== newInvitations[i].statut) {
                    return true;
                }
            }

            return false;
        }

        // Charger depuis localStorage
        function loadFromLocalStorage() {
            const cached = getFromLocalStorage();
            if (cached) {
                const hasChanges = detectChanges(cached.data.invitations);
                allInvitations = cached.data.invitations;
                lastServerTimestamp = cached.data.timestamp || 0;
                updateStats(cached.data.stats);
                renderInvitations(hasChanges);
            }
        }

        // Invalider le cache
        function invalidateCache() {
            localStorage.removeItem(STORAGE_KEY);
            fetchFromServer(true);
        }

        // Changer d'onglet
        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`tab-${tab}`).classList.add('active');
            renderInvitations();
        }

        // Mettre à jour les statistiques
        function updateStats(stats) {
            const elements = {
                'en_attente': document.getElementById('stat-en-attente'),
                'acceptees': document.getElementById('stat-acceptees'),
                'refusees': document.getElementById('stat-refusees')
            };

            for (let key in elements) {
                const newValue = stats[key] || 0;
                const element = elements[key];
                if (element.textContent !== newValue.toString()) {
                    element.textContent = newValue;
                    element.classList.add('status-updated');
                    setTimeout(() => element.classList.remove('status-updated'), 500);
                }
            }
        }

        // Afficher les invitations
        function renderInvitations(highlightChanges = false) {
            const container = document.getElementById('invitations-list');
            let filteredInvitations = allInvitations;

            if (currentTab !== 'toutes') {
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

            container.innerHTML = filteredInvitations.map(inv => 
                createInvitationHTML(inv, highlightChanges)
            ).join('');
        }

        // Créer le HTML d'une invitation
        function createInvitationHTML(inv, highlight = false) {
            let statut = inv.statut || 'en_attente';
            if (statut === 'acceptees') statut = 'acceptee';
            if (statut === 'refusees') statut = 'refusee';
            if (!['en_attente', 'acceptee', 'refusee'].includes(statut)) {
                statut = 'en_attente';
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

            const highlightClass = highlight ? 'status-updated' : '';

            return `
                <div class="bg-white rounded-xl shadow-md p-6 fade-in ${highlightClass}">
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

        // Afficher l'indicateur de synchronisation
        function showSyncIndicator() {
            document.getElementById('sync-indicator').classList.add('active');
        }

        // Masquer l'indicateur de synchronisation
        function hideSyncIndicator() {
            setTimeout(() => {
                document.getElementById('sync-indicator').classList.remove('active');
            }, 500);
        }

        // Afficher une notification
        function showNotification(type, message) {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };

            const icons = {
                success: 'check-circle',
                error: 'exclamation-circle',
                info: 'info-circle'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 fade-in`;
            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <i class="fas fa-${icons[type]}"></i>
                    <span>${message}</span>
                </div>`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-10px)';
                notification.style.transition = 'all 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Nettoyer l'intervalle quand on quitte la page
        window.addEventListener('beforeunload', () => {
            if (syncInterval) {
                clearInterval(syncInterval);
            }
        });
    </script>
</body>
</html>