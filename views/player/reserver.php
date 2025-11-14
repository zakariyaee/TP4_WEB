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
// Récupérer les équipes du joueur (CORRECTION)
$stmt = $pdo->prepare("
    SELECT DISTINCT e.id_equipe, e.nom_equipe
    FROM equipe e
    INNER JOIN equipe_joueur ej ON e.id_equipe = ej.id_equipe
    WHERE ej.id_joueur = :email_joueur
    ORDER BY e.nom_equipe
");
$stmt->execute([':email_joueur' => $_SESSION['user_email']]);
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer TOUTES les équipes pour l'équipe adversaire
$stmt_all_equipes = $pdo->prepare("
    SELECT id_equipe, nom_equipe
    FROM equipe
    ORDER BY nom_equipe
");
$stmt_all_equipes->execute();
$toutes_equipes = $stmt_all_equipes->fetchAll(PDO::FETCH_ASSOC);


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

        * {
            font-family: 'Inter', sans-serif;
        }

        /* Style pour les objets sélectionnés */
        .objet-card {
            transition: all 0.2s ease;
        }

        .objet-card.selected {
            border-color: #10b981;
            background-color: #ecfdf5;
        }

        .objet-card input[type="checkbox"]:checked~label {
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Équipe adversaire (optionnel)
                                </label>
                                <select id="id_equipe_adverse"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">Aucune équipe adversaire</option>
                                    <option value="" disabled>──────────────</option>
                                    <?php foreach ($toutes_equipes as $equipe): ?>
                                        <option value="<?php echo $equipe['id_equipe']; ?>">
                                            <?php echo htmlspecialchars($equipe['nom_equipe']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle"></i>
                                    Sélectionnez une équipe si vous organisez un match contre une équipe spécifique
                                </p>
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

    <script src="../../assets/js/player/reservation.js"></script>
<script>
    // Initialiser la réservation avec les données PHP
    document.addEventListener('DOMContentLoaded', function() {
        initReservation(
            <?php echo $id_terrain; ?>,
            <?php echo $terrain['prix_heure']; ?>,
            <?php echo json_encode($equipes); ?>,
            <?php echo json_encode($toutes_equipes); ?>,
            <?php echo $etape; ?>
        );
    });
</script>
</body>
</html>