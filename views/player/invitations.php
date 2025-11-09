<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

$email_joueur = $_SESSION['user_email'];

// Récupérer les statistiques des invitations
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN dr.statut = 'en_attente' THEN 1 END) as en_attente,
        COUNT(CASE WHEN dr.statut = 'acceptee' THEN 1 END) as acceptees,
        COUNT(CASE WHEN dr.statut = 'refusee' THEN 1 END) as refusees
    FROM demande_rejoindre dr
    WHERE dr.email_demandeur = :email
");
$stmt->execute([':email' => $email_joueur]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Compter les invitations aux tournois
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM tournoi_equipe te
    INNER JOIN equipe_joueur ej ON te.id_equipe = ej.id_equipe
    WHERE ej.id_joueur = :email AND te.statut_participation = 'invitee'
");
$stmt->execute([':email' => $email_joueur]);
$tournois = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Invitations - TerrainBook</title>
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

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-8 max-w-7xl">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Mes Invitations</h1>
            <p class="text-gray-600">Gérez les invitations reçues des équipes</p>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">En attente</p>
                        <p class="text-3xl font-bold text-orange-600" id="stat-attente"><?php echo $stats['en_attente'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Acceptées</p>
                        <p class="text-3xl font-bold text-emerald-600" id="stat-acceptees"><?php echo $stats['acceptees'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Refusées</p>
                        <p class="text-3xl font-bold text-red-600" id="stat-refusees"><?php echo $stats['refusees'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Tournois</p>
                        <p class="text-3xl font-bold text-purple-600" id="stat-tournois"><?php echo $tournois['total'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-trophy text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <div class="bg-white rounded-xl shadow-md mb-6">
            <div class="border-b border-gray-200 px-6">
                <div class="flex gap-4">
                    <button onclick="switchTab('nouvelles')" id="tab-nouvelles" class="tab-button px-6 py-4 font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 active relative">
                        <i class="fas fa-envelope mr-2"></i>Nouvelles invitations
                        <span class="notification-badge" id="badge-nouvelles">0</span>
                    </button>
                    <button onclick="switchTab('historique')" id="tab-historique" class="tab-button px-6 py-4 font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600">
                        <i class="fas fa-history mr-2"></i>Historique
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
        let currentTab = 'nouvelles';
        let allInvitations = [];

        // Configuration localStorage
        const STORAGE_KEY = 'terrainbook_invitations';
        const CACHE_DURATION = 30000; // 30 secondes

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            loadInvitations();
            
            // Écouter les changements dans d'autres onglets
            window.addEventListener('storage', handleStorageChange);
            
            // Synchroniser périodiquement avec le serveur
            setInterval(syncWithServer, 60000); // Vérifier toutes les minutes
        });

        // Gérer les changements de localStorage depuis d'autres onglets
        function handleStorageChange(e) {
            if (e.key === STORAGE_KEY) {
                console.log('Mise à jour détectée depuis un autre onglet');
                loadFromLocalStorage();
            }
        }

        // Charger les données depuis localStorage ou API
        function loadInvitations() {
            const cached = getFromLocalStorage();
            
            if (cached && !isCacheExpired(cached.timestamp)) {
                // Utiliser le cache
                console.log('Chargement depuis localStorage');
                allInvitations = cached.data.invitations;
                updateStats(cached.data.stats);
                renderInvitations();
            } else {
                // Charger depuis l'API
                console.log('Chargement depuis l\'API');
                fetchFromServer();
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
                console.log('Données sauvegardées dans localStorage');
            } catch (error) {
                console.error('Erreur sauvegarde localStorage:', error);
            }
        }

        // Vérifier si le cache est expiré
        function isCacheExpired(timestamp) {
            return (Date.now() - timestamp) > CACHE_DURATION;
        }

        // Charger depuis le serveur
        function fetchFromServer() {
            fetch('../../../actions/player/invitation/get_invitations.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allInvitations = data.invitations;
                        updateStats(data.stats);
                        saveToLocalStorage(data);
                        renderInvitations();
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement API:', error);
                    // En cas d'erreur, utiliser le cache même expiré
                    const cached = getFromLocalStorage();
                    if (cached) {
                        allInvitations = cached.data.invitations;
                        updateStats(cached.data.stats);
                        renderInvitations();
                    }
                });
        }

        // Synchroniser avec le serveur (en arrière-plan)
        function syncWithServer() {
            const cached = getFromLocalStorage();
            if (!cached || isCacheExpired(cached.timestamp)) {
                console.log('Synchronisation avec le serveur...');
                fetchFromServer();
            }
        }

        // Charger uniquement depuis localStorage
        function loadFromLocalStorage() {
            const cached = getFromLocalStorage();
            if (cached) {
                allInvitations = cached.data.invitations;
                updateStats(cached.data.stats);
                renderInvitations();
            }
        }

        // Invalider le cache (forcer le rechargement)
        function invalidateCache() {
            localStorage.removeItem(STORAGE_KEY);
            fetchFromServer();
        }

        // Changer d'onglet
        function switchTab(tab) {
            currentTab = tab;
            
            document.getElementById('tab-nouvelles').classList.remove('active');
            document.getElementById('tab-historique').classList.remove('active');
            document.getElementById(`tab-${tab}`).classList.add('active');
            
            renderInvitations();
        }

        // Mettre à jour les statistiques
        function updateStats(stats) {
            document.getElementById('stat-attente').textContent = stats.en_attente || 0;
            document.getElementById('stat-acceptees').textContent = stats.acceptees || 0;
            document.getElementById('stat-refusees').textContent = stats.refusees || 0;
            document.getElementById('stat-tournois').textContent = stats.tournois || 0;
            
            const badge = document.getElementById('badge-nouvelles');
            const count = stats.en_attente || 0;
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }

        // Afficher les invitations
        function renderInvitations() {
            const container = document.getElementById('invitations-list');
            
            let filteredInvitations = allInvitations.filter(inv => {
                if (currentTab === 'nouvelles') {
                    return inv.statut === 'en_attente';
                } else {
                    return inv.statut !== 'en_attente';
                }
            });

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

        // Créer le HTML pour une invitation
        function createInvitationHTML(inv) {
            const statusColors = {
                'en_attente': 'bg-orange-100 text-orange-700',
                'acceptee': 'bg-emerald-100 text-emerald-700',
                'refusee': 'bg-red-100 text-red-700'
            };

            const statusLabels = {
                'en_attente': 'En attente',
                'acceptee': 'Acceptée',
                'refusee': 'Refusée'
            };

            const isTournoi = inv.type === 'tournoi';

            return `
                <div class="bg-white rounded-xl shadow-md p-6 fade-in">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4 flex-1">
                            <div class="w-12 h-12 bg-gradient-to-br from-emerald-600 to-green-700 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-lg">${inv.expediteur_initiales}</span>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-bold text-gray-900">${inv.nom_equipe}</h3>
                                    ${isTournoi ? '<span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium"><i class="fas fa-trophy mr-1"></i>Tournoi</span>' : ''}
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${statusColors[inv.statut]}">${statusLabels[inv.statut]}</span>
                                </div>
                                
                                <p class="text-sm text-gray-600 mb-2">
                                    <i class="fas fa-user text-emerald-600 mr-2"></i>
                                    Par ${inv.expediteur_nom} ${inv.expediteur_prenom}
                                </p>

                                ${inv.date_debut ? `
                                    <p class="text-sm text-gray-600 mb-2">
                                        <i class="fas fa-calendar text-emerald-600 mr-2"></i>
                                        ${inv.date_formatted} à ${inv.heure_formatted}
                                    </p>
                                ` : ''}

                                ${inv.position ? `
                                    <p class="text-sm text-gray-600 mb-2">
                                        <i class="fas fa-running text-emerald-600 mr-2"></i>
                                        Position: ${inv.position} - Niveau: ${inv.niveau}
                                    </p>
                                ` : ''}

                                ${isTournoi ? `
                                    <p class="text-sm text-gray-600 mb-2">
                                        <i class="fas fa-trophy text-emerald-600 mr-2"></i>
                                        ${inv.nom_tournoi}
                                    </p>
                                ` : ''}

                                <p class="text-sm text-gray-700 mt-3 bg-gray-50 p-3 rounded-lg">
                                    ${inv.contenu}
                                </p>

                                <p class="text-xs text-gray-400 mt-2">
                                    <i class="fas fa-clock mr-1"></i>
                                    ${inv.date_message_formatted}
                                </p>
                            </div>
                        </div>

                        ${inv.statut === 'en_attente' ? `
                            <div class="flex items-center gap-3 ml-4">
                                <button onclick="accepterInvitation(${inv.id_demande}, ${inv.id_message})"
                                        class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium flex items-center gap-2">
                                    <i class="fas fa-check"></i>
                                    Accepter
                                </button>
                                <button onclick="refuserInvitation(${inv.id_demande})"
                                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium flex items-center gap-2">
                                    <i class="fas fa-times"></i>
                                    Refuser
                                </button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        // Accepter une invitation
        function accepterInvitation(idDemande, idMessage) {
            if (!confirm('Voulez-vous accepter cette invitation ?')) return;

            fetch('../../../actions/player/invitation/accepter_invitation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_demande: idDemande, id_message: idMessage })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Invitation acceptée avec succès');
                    invalidateCache();
                } else {
                    showNotification('error', data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('error', 'Erreur lors de l\'acceptation');
            });
        }

        // Refuser une invitation
        function refuserInvitation(idDemande) {
            if (!confirm('Voulez-vous refuser cette invitation ?')) return;

            fetch('../../../actions/player/invitation/refuser_invitation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_demande: idDemande })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Invitation refusée');
                    invalidateCache();
                } else {
                    showNotification('error', data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('error', 'Erreur lors du refus');
            });
        }

        // Afficher une notification
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
                </div>
            `;
            
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