<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

// Récupérer l'ID du terrain depuis l'URL
$id_terrain = $_GET['id_terrain'] ?? null;
$etape = $_GET['etape'] ?? '1';

// Récupérer les informations du terrain
$terrain = null;
if ($id_terrain) {
    $stmt = $pdo->prepare("
        SELECT t.*, CONCAT(u.nom, ' ', u.prenom) as responsable_nom
        FROM terrain t
        LEFT JOIN utilisateur u ON t.id_responsable = u.email
        WHERE t.id_terrain = :id
    ");
    $stmt->execute([':id' => $id_terrain]);
    $terrain = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$terrain) {
    header('Location: stades.php');
    exit;
}

// Récupérer les équipes du joueur (CORRECTION: vérifier la relation)
$stmt = $pdo->prepare("
    SELECT DISTINCT e.id_equipe, e.nom_equipe
    FROM equipe e
    INNER JOIN equipe_joueur ej ON e.id_equipe = ej.id_equipe
    WHERE ej.id_joueur = :email_joueur
    ORDER BY e.nom_equipe
");
$stmt->execute([':email_joueur' => $_SESSION['user_email']]);
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: vérifier si on trouve des équipes
error_log("Équipes trouvées pour " . $_SESSION['user_email'] . ": " . count($equipes));

// Récupérer les informations du joueur
$stmt = $pdo->prepare("SELECT nom, prenom, num_tele FROM utilisateur WHERE email = :email");
$stmt->execute([':email' => $_SESSION['user_email']]);
$joueur = $stmt->fetch(PDO::FETCH_ASSOC);

// Déterminer les options de nombre de joueurs selon la catégorie du terrain
$options_joueurs = [];
switch ($terrain['categorie']) {
    case 'Mini Foot':
        $options_joueurs = [5 => '5 vs 5', 6 => '6 vs 6'];
        break;
    case 'Terrain Moyen':
        $options_joueurs = [7 => '7 vs 7', 8 => '8 vs 8'];
        break;
    case 'Grand Terrain':
        $options_joueurs = [11 => '11 vs 11'];
        break;
    default:
        $options_joueurs = [5 => '5 vs 5', 7 => '7 vs 7', 11 => '11 vs 11'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver <?php echo htmlspecialchars($terrain['nom_te']); ?> - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        /* Style pour les objets sélectionnés */
        .objet-card {
            transition: all 0.2s ease;
        }
        
        .objet-card.selected {
            border-color: #10b981;
            background-color: #ecfdf5;
        }
        
        .objet-card input[type="checkbox"]:checked ~ label {
            color: #10b981;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-8 max-w-7xl">
        <!-- Titre et barre de progression -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-6">
                <a href="stades.php" class="text-gray-600 hover:text-emerald-600">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Réserver <?php echo htmlspecialchars($terrain['nom_te']); ?></h1>
            </div>
            <p class="text-gray-600 mb-6">Complétez votre réservation en 3 étapes</p>
            
            <!-- Barre de progression -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-2 flex-1">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full <?php echo $etape >= '1' ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-500'; ?>">
                        <?php if ($etape > '1'): ?>
                            <i class="fas fa-check text-sm"></i>
                        <?php else: ?>
                            <span class="text-sm font-semibold">1</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 h-1 <?php echo $etape >= '2' ? 'bg-emerald-600' : 'bg-gray-200'; ?>"></div>
                </div>
                <div class="flex-1 px-4">
                    <div class="text-sm font-medium <?php echo $etape >= '1' ? 'text-emerald-600' : 'text-gray-500'; ?>">Étape 1 Date & Heure</div>
                </div>
                
                <div class="flex items-center gap-2 flex-1">
                    <div class="flex-1 h-1 <?php echo $etape >= '2' ? 'bg-emerald-600' : 'bg-gray-200'; ?>"></div>
                    <div class="flex items-center justify-center w-10 h-10 rounded-full <?php echo $etape >= '2' ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-500'; ?>">
                        <?php if ($etape > '2'): ?>
                            <i class="fas fa-check text-sm"></i>
                        <?php else: ?>
                            <span class="text-sm font-semibold">2</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 h-1 <?php echo $etape >= '3' ? 'bg-emerald-600' : 'bg-gray-200'; ?>"></div>
                </div>
                <div class="flex-1 px-4">
                    <div class="text-sm font-medium <?php echo $etape >= '2' ? 'text-emerald-600' : 'text-gray-500'; ?>">Étape 2 Détails</div>
                </div>
                
                <div class="flex items-center gap-2 flex-1">
                    <div class="flex-1 h-1 <?php echo $etape >= '3' ? 'bg-emerald-600' : 'bg-gray-200'; ?>"></div>
                    <div class="flex items-center justify-center w-10 h-10 rounded-full <?php echo $etape >= '3' ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-500'; ?>">
                        <span class="text-sm font-semibold">3</span>
                    </div>
                </div>
                <div class="flex-1 px-4">
                    <div class="text-sm font-medium <?php echo $etape >= '3' ? 'text-emerald-600' : 'text-gray-500'; ?>">Étape 3 Confirmation</div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Contenu principal -->
            <div class="lg:col-span-2">
                <?php if ($etape === '1'): ?>
                    <!-- Étape 1: Date et Heure -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Sélectionnez la date et l'heure</h2>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date de réservation *</label>
                                <input type="date" id="date_reservation" 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <p class="text-xs text-gray-500 mt-1">Sélectionnez une date à partir d'aujourd'hui</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Créneau horaire *</label>
                                <select id="creneau_horaire" 
                                        onchange="updateCostSummary()"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                        disabled>
                                    <option value="">Choisir un créneau</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1" id="creneau_message">Veuillez d'abord sélectionner une date</p>
                            </div>
                        </div>
                        
                        <div class="mt-8 flex justify-end">
                            <button onclick="nextStep1()" 
                                    class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-semibold">
                                Suivant
                            </button>
                        </div>
                    </div>
                    
                <?php elseif ($etape === '2'): ?>
                    <!-- Étape 2: Détails -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Informations du match</h2>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom de votre équipe *</label>
                                <div class="flex gap-2">
                                    <select id="id_equipe" onchange="handleEquipeSelection()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">Sélectionner une équipe existante</option>
                                        <?php if (count($equipes) > 0): ?>
                                            <?php foreach ($equipes as $equipe): ?>
                                                <option value="<?php echo $equipe['id_equipe']; ?>"><?php echo htmlspecialchars($equipe['nom_equipe']); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <option value="nouvelle">+ Créer une nouvelle équipe</option>
                                    </select>
                                </div>
                                <input type="text" id="nouvelle_equipe" placeholder="Nom de la nouvelle équipe" 
                                       style="display: none;"
                                       class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <?php if (count($equipes) === 0): ?>
                                    <p class="text-xs text-amber-600 mt-1">
                                        <i class="fas fa-info-circle"></i> Vous n'avez pas encore d'équipe. Créez-en une nouvelle ci-dessus.
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Équipe adversaire (optionnel)</label>
                                <input type="text" id="nom_equipe_adverse" 
                                       placeholder="Nom de l'équipe adverse"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre de joueurs * 
                                    <span class="text-xs text-gray-500">(Adapté au type de terrain: <?php echo htmlspecialchars($terrain['categorie']); ?>)</span>
                                </label>
                                <select id="nombre_joueurs" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">Choisir</option>
                                    <?php foreach ($options_joueurs as $nb => $label): ?>
                                        <option value="<?php echo $nb; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                <input type="tel" id="telephone" 
                                       value="<?php echo htmlspecialchars($joueur['num_tele'] ?? ''); ?>"
                                       placeholder="0612345678"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes / Demandes spéciales</label>
                                <textarea id="notes" rows="4" 
                                          placeholder="Commentaires ou requêtes spécifiques..."
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">Options supplémentaires</h3>
                            <div id="objets_container" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <!-- Les objets seront chargés dynamiquement -->
                            </div>
                        </div>
                        
                        <div class="mt-8 flex justify-between">
                            <button onclick="prevStep()" 
                                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                                Précédent
                            </button>
                            <button onclick="nextStep2()" 
                                    class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-semibold">
                                Suivant
                            </button>
                        </div>
                    </div>
                    
                <?php elseif ($etape === '3'): ?>
                    <!-- Étape 3: Confirmation -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Confirmation de réservation</h2>
                        
                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between py-2 border-b">
                                <span class="text-gray-600">Date:</span>
                                <span class="font-semibold" id="conf_date">-</span>
                            </div>
                            <div class="flex justify-between py-2 border-b">
                                <span class="text-gray-600">Heure:</span>
                                <span class="font-semibold" id="conf_heure">-</span>
                            </div>
                            <div class="flex justify-between py-2 border-b">
                                <span class="text-gray-600">Votre équipe:</span>
                                <span class="font-semibold" id="conf_equipe">-</span>
                            </div>
                            <div class="flex justify-between py-2 border-b">
                                <span class="text-gray-600">Équipe adversaire:</span>
                                <span class="font-semibold" id="conf_equipe_adverse">-</span>
                            </div>
                            <div class="flex justify-between py-2 border-b">
                                <span class="text-gray-600">Nombre de joueurs:</span>
                                <span class="font-semibold" id="conf_nombre_joueurs">-</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">Services supplémentaires:</span>
                                <div class="text-right" id="conf_objets">-</div>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <p class="text-sm text-green-800">
                                    Vous pourrez modifier cette réservation jusqu'à 48h avant le début du match.
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex justify-between">
                            <button onclick="prevStep()" 
                                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                                Précédent
                            </button>
                            <button onclick="confirmReservation()" 
                                    id="btn_confirm"
                                    class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-semibold flex items-center gap-2">
                                <i class="fas fa-calendar-check"></i>
                                Confirmer la réservation
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Informations terrain -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
                    <div class="relative h-48 bg-gradient-to-br from-emerald-400 to-teal-600">
                        <?php if (!empty($terrain['image'])): ?>
                            <img src="../../assets/images/terrains/<?php echo htmlspecialchars($terrain['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($terrain['nom_te']); ?>" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="fas fa-futbol text-white text-6xl opacity-50"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($terrain['nom_te']); ?></h3>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-emerald-600 w-4"></i>
                                <span><?php echo htmlspecialchars($terrain['ville']); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-users text-emerald-600 w-4"></i>
                                <span><?php echo htmlspecialchars($terrain['categorie']); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-expand-arrows-alt text-emerald-600 w-4"></i>
                                <span><?php echo htmlspecialchars($terrain['taille']); ?> • <?php echo htmlspecialchars($terrain['type']); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-tag text-emerald-600 w-4"></i>
                                <span class="font-semibold text-emerald-600"><?php echo number_format($terrain['prix_heure'], 2); ?> DH/heure</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Résumé des coûts -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Résumé des coûts</h3>
                    <div id="cost_summary" class="space-y-2 text-sm">
                        <div class="flex justify-between py-1">
                            <span class="text-gray-600">Terrain</span>
                            <span class="font-semibold" id="cost_terrain">-</span>
                        </div>
                        <div id="cost_objets_container"></div>
                        <div class="border-t border-gray-200 pt-3 mt-3">
                            <div class="flex justify-between font-bold text-lg">
                                <span>Total</span>
                                <span class="text-emerald-600" id="cost_total">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const idTerrain = <?php echo $id_terrain; ?>;
        const prixHeure = <?php echo $terrain['prix_heure']; ?>;
        let creneauxData = [];
        let objetsData = [];
        let selectedObjets = [];
        let reservationData = {};
        const equipesList = <?php echo json_encode($equipes); ?>;

        console.log('Équipes disponibles:', equipesList);

        // Charger les objets au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadObjets();
            
            // Si on est à l'étape 1, écouter les changements de date
            if (document.getElementById('date_reservation')) {
                document.getElementById('date_reservation').addEventListener('change', loadCreneaux);
            }
            
            // Charger les données de réservation depuis le localStorage
            loadReservationData();
        });

        function handleEquipeSelection() {
            const select = document.getElementById('id_equipe');
            const nouvelleEquipeInput = document.getElementById('nouvelle_equipe');
            
            if (select.value === 'nouvelle') {
                nouvelleEquipeInput.style.display = 'block';
                nouvelleEquipeInput.focus();
            } else {
                nouvelleEquipeInput.style.display = 'none';
                nouvelleEquipeInput.value = '';
            }
        }

        function loadCreneaux() {
            const date = document.getElementById('date_reservation').value;
            if (!date) {
                document.getElementById('creneau_horaire').disabled = true;
                document.getElementById('creneau_message').textContent = 'Veuillez d\'abord sélectionner une date';
                return;
            }

            fetch(`../../actions/player/get_creneaux_disponibles.php?id_terrain=${idTerrain}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        creneauxData = data.creneaux;
                        const select = document.getElementById('creneau_horaire');
                        select.innerHTML = '<option value="">Choisir un créneau</option>';
                        
                        if (data.creneaux.length === 0) {
                            select.disabled = true;
                            document.getElementById('creneau_message').textContent = 'Aucun créneau disponible pour cette date';
                            document.getElementById('creneau_message').classList.add('text-red-500');
                        } else {
                            select.disabled = false;
                            document.getElementById('creneau_message').textContent = `${data.creneaux.length} créneau(x) disponible(s)`;
                            document.getElementById('creneau_message').classList.remove('text-red-500');
                            
                            data.creneaux.forEach(creneau => {
                                const option = document.createElement('option');
                                option.value = creneau.id_creneaux;
                                option.textContent = creneau.libelle;
                                select.appendChild(option);
                            });
                            
                            // Restaurer la sélection si elle existe
                            if (reservationData.creneau) {
                                select.value = reservationData.creneau;
                            }
                        }
                        updateCostSummary();
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des créneaux');
                });
        }

        function loadObjets() {
            fetch('../../actions/player/get_objets.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        objetsData = data.objets;
                        renderObjets();
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
        }

        function renderObjets() {
            const container = document.getElementById('objets_container');
            if (!container) return;
            
            container.innerHTML = '';
            objetsData.forEach(objet => {
                const div = document.createElement('div');
                div.className = 'objet-card flex flex-col items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all';
                div.id = `objet_card_${objet.id_object}`;
                
                const isSelected = selectedObjets.includes(parseInt(objet.id_object));
                if (isSelected) {
                    div.classList.add('selected');
                }
                
                div.innerHTML = `
                    <input type="checkbox" 
                           id="objet_${objet.id_object}" 
                           value="${objet.id_object}" 
                           class="hidden"
                           ${isSelected ? 'checked' : ''}>
                    <label for="objet_${objet.id_object}" class="cursor-pointer text-center w-full">
                        <div class="text-3xl mb-2">${getObjetIcon(objet.nom_objet)}</div>
                        <div class="text-sm font-medium text-gray-900">${objet.nom_objet}</div>
                        <div class="text-sm text-emerald-600 mt-1 font-semibold">+${objet.prix} DH</div>
                    </label>
                `;
                
                // Gérer le clic sur toute la carte
                div.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleObjet(parseInt(objet.id_object), parseFloat(objet.prix));
                });
                
                container.appendChild(div);
            });
        }

        function getObjetIcon(nom) {
            const icons = {
                'Ballon': '⚽',
                'Arbitre': '👔',
                'Chasubles': '👕',
                'Douche': '🚿',
                'Vestiaire': '🚪',
                'Éclairage nocturne': '💡',
                'Trousse premiers secours': '🏥',
                'Vidéo analyse': '📹'
            };
            return icons[nom] || '📦';
        }

        function toggleObjet(id, prix) {
            const card = document.getElementById(`objet_card_${id}`);
            const checkbox = document.getElementById(`objet_${id}`);
            const index = selectedObjets.indexOf(id);
            
            if (index === -1) {
                selectedObjets.push(id);
                card.classList.add('selected');
                checkbox.checked = true;
            } else {
                selectedObjets.splice(index, 1);
                card.classList.remove('selected');
                checkbox.checked = false;
            }
            
            console.log('Objets sélectionnés:', selectedObjets);
            updateCostSummary();
        }

        function updateCostSummary() {
            // Calculer les heures et prix du terrain
            let heures = 0;
            let prixTerrain = 0;
            
            const creneauSelect = document.getElementById('creneau_horaire');
            if (creneauSelect && creneauSelect.value) {
                const creneau = creneauxData.find(c => c.id_creneaux == creneauSelect.value);
                if (creneau) {
                    const [h1, m1] = creneau.heure_debut.split(':').map(Number);
                    const [h2, m2] = creneau.heure_fin.split(':').map(Number);
                    heures = (h2 - h1) + (m2 - m1) / 60;
                    prixTerrain = prixHeure * heures;
                }
            }
            
            // Calculer le prix des objets
            let prixObjets = 0;
            const objetsContainer = document.getElementById('cost_objets_container');
            if (objetsContainer) {
                objetsContainer.innerHTML = '';
                selectedObjets.forEach(objetId => {
                    const objet = objetsData.find(o => parseInt(o.id_object) === objetId);
                    if (objet) {
                        prixObjets += parseFloat(objet.prix);
                        const div = document.createElement('div');
                        div.className = 'flex justify-between py-1';
                        div.innerHTML = `
                            <span class="text-gray-600">${objet.nom_objet}</span>
                            <span class="font-semibold">+${parseFloat(objet.prix).toFixed(2)} DH</span>
                        `;
                        objetsContainer.appendChild(div);
                    }
                });
            }
            
            // Mettre à jour l'affichage
            const costTerrain = document.getElementById('cost_terrain');
            const costTotal = document.getElementById('cost_total');
            
            if (costTerrain) {
                if (heures > 0) {
                    costTerrain.textContent = `${prixTerrain.toFixed(2)} DH (${heures}h)`;
                } else {
                    costTerrain.textContent = '-';
                }
            }
            
            if (costTotal) {
                const total = prixTerrain + prixObjets;
                costTotal.textContent = total > 0 ? `${total.toFixed(2)} DH` : '-';
            }
        }

        function nextStep1() {
            const date = document.getElementById('date_reservation').value;
            const creneau = document.getElementById('creneau_horaire').value;
            
            if (!date || !creneau) {
                alert('Veuillez sélectionner une date et un créneau');
                return;
            }
            
            // Sauvegarder dans le localStorage
            reservationData.date = date;
            reservationData.creneau = creneau;
            saveReservationData();
            
            // Aller à l'étape 2
            window.location.href = `?id_terrain=${idTerrain}&etape=2`;
        }

        function nextStep2() {
            const idEquipe = document.getElementById('id_equipe').value;
            const nouvelleEquipe = document.getElementById('nouvelle_equipe').value;
            const nombreJoueurs = document.getElementById('nombre_joueurs').value;
            
            if (!idEquipe && !nouvelleEquipe) {
                alert('Veuillez sélectionner ou créer une équipe');
                return;
            }
            
            if (idEquipe === 'nouvelle' && !nouvelleEquipe.trim()) {
                alert('Veuillez entrer le nom de la nouvelle équipe');
                document.getElementById('nouvelle_equipe').focus();
                return;
            }
            
            if (!nombreJoueurs) {
                alert('Veuillez sélectionner le nombre de joueurs');
                return;
            }
            
            // Sauvegarder dans le localStorage
            reservationData.id_equipe = idEquipe;
            reservationData.nouvelle_equipe = nouvelleEquipe;
            reservationData.nom_equipe_adverse = document.getElementById('nom_equipe_adverse').value;
            reservationData.nombre_joueurs = nombreJoueurs;
            reservationData.telephone = document.getElementById('telephone').value;
            reservationData.notes = document.getElementById('notes').value;
            reservationData.objets = selectedObjets;
            saveReservationData();
            
            // Aller à l'étape 3
            window.location.href = `?id_terrain=${idTerrain}&etape=3`;
        }

        function prevStep() {
            const currentEtape = <?php echo $etape; ?>;
            if (currentEtape > 1) {
                window.location.href = `?id_terrain=${idTerrain}&etape=${currentEtape - 1}`;
            }
        }

        function loadReservationData() {
            // Charger depuis le localStorage
            const stored = localStorage.getItem('reservation_data');
            if (stored) {
                reservationData = JSON.parse(stored);
                
                // Vérifier que c'est bien pour le même terrain
                if (reservationData.id_terrain != idTerrain) {
                    // Réinitialiser si terrain différent
                    reservationData = { id_terrain: idTerrain };
                    localStorage.setItem('reservation_data', JSON.stringify(reservationData));
                    return;
                }
                
                // Remplir les champs selon l'étape
                if (document.getElementById('date_reservation') && reservationData.date) {
                    document.getElementById('date_reservation').value = reservationData.date;
                    loadCreneaux();
                }
                
                if (document.getElementById('id_equipe')) {
                    if (reservationData.id_equipe === 'nouvelle' && reservationData.nouvelle_equipe) {
                        document.getElementById('id_equipe').value = 'nouvelle';
                        document.getElementById('nouvelle_equipe').style.display = 'block';
                        document.getElementById('nouvelle_equipe').value = reservationData.nouvelle_equipe;
                    } else if (reservationData.id_equipe) {
                        document.getElementById('id_equipe').value = reservationData.id_equipe;
                    }
                }
                
                if (document.getElementById('nom_equipe_adverse') && reservationData.nom_equipe_adverse) {
                    document.getElementById('nom_equipe_adverse').value = reservationData.nom_equipe_adverse;
                }
                if (document.getElementById('nombre_joueurs') && reservationData.nombre_joueurs) {
                    document.getElementById('nombre_joueurs').value = reservationData.nombre_joueurs;
                }
                if (document.getElementById('telephone') && reservationData.telephone) {
                    document.getElementById('telephone').value = reservationData.telephone;
                }
                if (document.getElementById('notes') && reservationData.notes) {
                    document.getElementById('notes').value = reservationData.notes;
                }
                
                if (reservationData.objets && Array.isArray(reservationData.objets)) {
                    selectedObjets = reservationData.objets;
                }
            } else {
                // Initialiser avec l'ID du terrain
                reservationData = { id_terrain: idTerrain };
            }
            
            // Charger les données de confirmation
            if (<?php echo $etape; ?> === 3) {
                loadConfirmationData();
            }
            
            updateCostSummary();
        }

        function saveReservationData() {
            reservationData.id_terrain = idTerrain;
            localStorage.setItem('reservation_data', JSON.stringify(reservationData));
            console.log('Données sauvegardées:', reservationData);
        }

        function loadConfirmationData() {
            if (!reservationData.date || !reservationData.creneau) {
                alert('Données incomplètes. Retour à l\'étape 1.');
                window.location.href = `?id_terrain=${idTerrain}&etape=1`;
                return;
            }
            
            // Charger les créneaux pour obtenir les informations
            fetch(`../../actions/player/get_creneaux_disponibles.php?id_terrain=${idTerrain}&date=${reservationData.date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        creneauxData = data.creneaux;
                        const creneau = creneauxData.find(c => c.id_creneaux == reservationData.creneau);
                        
                        // Afficher la date
                        if (document.getElementById('conf_date')) {
                            const date = new Date(reservationData.date + 'T00:00:00');
                            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                            document.getElementById('conf_date').textContent = date.toLocaleDateString('fr-FR', options);
                        }
                        
                        // Afficher l'heure
                        if (document.getElementById('conf_heure') && creneau) {
                            document.getElementById('conf_heure').textContent = creneau.libelle;
                        }
                        
                        // Afficher l'équipe
                        if (document.getElementById('conf_equipe')) {
                            if (reservationData.id_equipe === 'nouvelle' && reservationData.nouvelle_equipe) {
                                document.getElementById('conf_equipe').textContent = reservationData.nouvelle_equipe + ' (nouvelle équipe)';
                            } else {
                                const equipe = equipesList.find(e => e.id_equipe == reservationData.id_equipe);
                                document.getElementById('conf_equipe').textContent = equipe ? equipe.nom_equipe : '-';
                            }
                        }
                        
                        // Afficher l'équipe adversaire
                        if (document.getElementById('conf_equipe_adverse')) {
                            document.getElementById('conf_equipe_adverse').textContent = reservationData.nom_equipe_adverse || 'Aucune';
                        }
                        
                        // Afficher le nombre de joueurs
                        if (document.getElementById('conf_nombre_joueurs')) {
                            document.getElementById('conf_nombre_joueurs').textContent = reservationData.nombre_joueurs + ' vs ' + reservationData.nombre_joueurs;
                        }
                        
                        // Charger les objets si pas encore chargés
                        if (objetsData.length === 0) {
                            return fetch('../../actions/player/get_objets.php')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        objetsData = data.objets;
                                        displayObjetsConfirmation();
                                        updateCostSummary();
                                    }
                                });
                        } else {
                            displayObjetsConfirmation();
                            updateCostSummary();
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données');
                });
        }

        function displayObjetsConfirmation() {
            const confObjets = document.getElementById('conf_objets');
            if (confObjets && selectedObjets.length > 0) {
                const objetsList = selectedObjets.map(objetId => {
                    const objet = objetsData.find(o => parseInt(o.id_object) === objetId);
                    return objet ? `<span class="inline-block px-2 py-1 bg-emerald-50 text-emerald-700 rounded text-xs mr-2 mb-2">${objet.nom_objet} (+${parseFloat(objet.prix).toFixed(2)} DH)</span>` : '';
                }).join('');
                confObjets.innerHTML = objetsList;
            } else if (confObjets) {
                confObjets.innerHTML = '<span class="text-gray-400">Aucun</span>';
            }
        }

        function confirmReservation() {
            if (!reservationData.date || !reservationData.creneau) {
                alert('Données de réservation incomplètes');
                return;
            }
            
            if (!reservationData.id_equipe && !reservationData.nouvelle_equipe) {
                alert('Veuillez sélectionner une équipe');
                return;
            }
            
            // Préparer les données
            const data = {
                id_terrain: idTerrain,
                id_creneau: reservationData.creneau,
                date_reservation: reservationData.date,
                id_equipe: reservationData.id_equipe !== 'nouvelle' ? reservationData.id_equipe : '',
                nouvelle_equipe: reservationData.id_equipe === 'nouvelle' ? reservationData.nouvelle_equipe : '',
                nombre_joueurs: reservationData.nombre_joueurs,
                nom_equipe_adverse: reservationData.nom_equipe_adverse || '',
                objets: selectedObjets,
                notes: reservationData.notes || ''
            };
            
            console.log('Envoi des données:', data);
            
            // Désactiver le bouton pour éviter les doubles clics
            const btn = document.getElementById('btn_confirm');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>En cours...';
            
            // Envoyer la requête
            fetch('../../actions/player/create_reservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                console.log('Résultat:', result);
                if (result.success) {
                    // Nettoyer le localStorage
                    localStorage.removeItem('reservation_data');
                    // Afficher un message de succès
                    alert('Réservation créée avec succès!\n\nTotal: ' + result.prix_total.toFixed(2) + ' DH\nRéférence: #' + result.id_reservation);
                    // Rediriger vers la page de mes réservations
                    window.location.href = 'mes-reservations.php';
                } else {
                    alert('Erreur: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirmer la réservation';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la création de la réservation');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirmer la réservation';
            });
        }
    </script>
</body>
</html>