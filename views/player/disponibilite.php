<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

$email_joueur = $_SESSION['user_email'];

// Récupérer les statistiques du joueur connecté
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT DATE(date_debut)) as disponibilites_actives,
        COUNT(DISTINCT CASE WHEN date_debut >= NOW() THEN DATE(date_debut) END) as disponibilites_futures
    FROM disponibilite 
    WHERE email_joueur = :email AND statut = 'actif'
");
$stmt->execute([':email' => $email_joueur]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer toutes les équipes du joueur
$stmt = $pdo->prepare("
    SELECT DISTINCT e.id_equipe, e.nom_equipe
    FROM equipe e
    INNER JOIN equipe_joueur ej ON e.id_equipe = ej.id_equipe
    WHERE ej.id_joueur = :email_joueur
    ORDER BY e.nom_equipe
");
$stmt->execute([':email_joueur' => $email_joueur]);
$mes_equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les terrains
$stmt = $pdo->prepare("SELECT id_terrain, nom_te, ville, categorie FROM terrain WHERE disponibilite = 'disponible' ORDER BY ville, nom_te");
$stmt->execute();
$terrains = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilités des Joueurs - TerrainBook</title>
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

        .toggle-checkbox:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .toggle-checkbox:checked + .toggle-label {
            left: 1.5rem;
        }

        .tab-button.active {
            background-color: #10b981;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-8 max-w-7xl">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Disponibilités des Joueurs</h1>
            <p class="text-gray-600">Consultez les disponibilités et invitez des joueurs à rejoindre votre équipe</p>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Mes disponibilités</p>
                        <p class="text-3xl font-bold text-emerald-600"><?php echo $stats['disponibilites_actives'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-check text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Joueurs disponibles</p>
                        <p class="text-3xl font-bold text-blue-600" id="total-disponibilites">0</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Invitations envoyées</p>
                        <p class="text-3xl font-bold text-purple-600" id="invitations-count">0</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-paper-plane text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <div class="bg-white rounded-xl shadow-md mb-6">
            <div class="border-b border-gray-200 px-6">
                <div class="flex gap-4">
                    <button onclick="switchTab('tous')" id="tab-tous" class="tab-button px-6 py-4 font-medium text-gray-700  border-b-2 border-transparent hover:border-emerald-600 active">
                        <i class="fas fa-users mr-2"></i>Tous les joueurs
                    </button>
                    <button onclick="switchTab('mes')" id="tab-mes" class="tab-button px-6 py-4 font-medium text-gray-700  border-b-2 border-transparent hover:border-emerald-600">
                        <i class="fas fa-user mr-2"></i>Mes disponibilités
                    </button>
                </div>
            </div>

            <!-- Filtres -->
            <div class="p-6 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                        <select id="filter-position" onchange="applyFilters()" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Toutes</option>
                            <option value="Gardien">Gardien</option>
                            <option value="Défenseur">Défenseur</option>
                            <option value="Milieu">Milieu</option>
                            <option value="Attaquant">Attaquant</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
                        <select id="filter-niveau" onchange="applyFilters()" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Tous</option>
                            <option value="Débutant">Débutant</option>
                            <option value="Intermédiaire">Intermédiaire</option>
                            <option value="Avancé">Avancé</option>
                            <option value="Expert">Expert</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                        <select id="filter-ville" onchange="applyFilters()" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Toutes</option>
                            <option value="Tétouan">Tétouan</option>
                            <option value="Rabat">Rabat</option>
                            <option value="Casablanca">Casablanca</option>
                            <option value="Marrakech">Marrakech</option>
                            <option value="Fès">Fès</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date" id="filter-date" onchange="applyFilters()" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
            </div>
        </div>

        <!-- Bouton Ajouter (visible uniquement dans l'onglet "Mes disponibilités") -->
        <div id="btn-add-container" class="mb-6 hidden">
            <button onclick="openModal()" 
                    class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-semibold flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Ajouter ma disponibilité
            </button>
        </div>

        <!-- Liste des disponibilités -->
        <div class="bg-white rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900" id="list-title">Tous les joueurs disponibles</h2>
            </div>
            
            <div id="disponibilites-list" class="divide-y divide-gray-200">
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin text-4xl mb-3"></i>
                    <p>Chargement des disponibilités...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter/Modifier Disponibilité -->
    <div id="modal-disponibilite" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
                <h2 class="text-2xl font-bold text-gray-900" id="modal-title">Ajouter ma disponibilité</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="form-disponibilite" class="p-6 space-y-6">
                <input type="hidden" id="id_disponibilite" name="id_disponibilite">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                        <input type="date" id="date_debut" name="date_debut" required
                               min="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Heure début *</label>
                        <input type="time" id="heure_debut" name="heure_debut" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Heure fin *</label>
                    <input type="time" id="heure_fin" name="heure_fin" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                    <select id="position" name="position" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Sélectionner une position</option>
                        <option value="Gardien">Gardien</option>
                        <option value="Défenseur">Défenseur</option>
                        <option value="Milieu">Milieu</option>
                        <option value="Attaquant">Attaquant</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Niveau *</label>
                    <select id="niveau" name="niveau" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Sélectionner un niveau</option>
                        <option value="Débutant">Débutant</option>
                        <option value="Intermédiaire">Intermédiaire</option>
                        <option value="Avancé">Avancé</option>
                        <option value="Expert">Expert</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Terrain (optionnel)</label>
                    <select id="id_terrain" name="id_terrain"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Tous les terrains</option>
                        <?php foreach ($terrains as $terrain): ?>
                            <option value="<?php echo $terrain['id_terrain']; ?>">
                                <?php echo htmlspecialchars($terrain['nom_te']); ?> - <?php echo htmlspecialchars($terrain['ville']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rayon (km)</label>
                    <input type="number" id="rayon_km" name="rayon_km" min="1" max="100"
                           placeholder="Distance maximale depuis votre position"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description (optionnel)</label>
                    <textarea id="description" name="description" rows="3"
                              placeholder="Informations complémentaires..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeModal()"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Annuler
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Invitation -->
    <div id="modal-invitation" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">Inviter ce joueur</h2>
            </div>

            <form id="form-invitation" class="p-6 space-y-6">
                <input type="hidden" id="inv_id_disponibilite">
                <input type="hidden" id="inv_email_joueur">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sélectionnez votre équipe *</label>
                    <select id="inv_id_equipe" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Choisir une équipe</option>
                        <?php foreach ($mes_equipes as $equipe): ?>
                            <option value="<?php echo $equipe['id_equipe']; ?>">
                                <?php echo htmlspecialchars($equipe['nom_equipe']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message (optionnel)</label>
                    <textarea id="inv_message" rows="4"
                              placeholder="Ajoutez un message personnel..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeInvitationModal()"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Annuler
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Envoyer l'invitation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Variables globales
        const currentUserEmail = '<?php echo $email_joueur; ?>';
        const mesEquipes = <?php echo json_encode($mes_equipes); ?>;
    </script>
    <script src="../../assets/js/player/disponibilite.js"></script>
</body>
</html>