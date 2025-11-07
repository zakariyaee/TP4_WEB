<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();
$pageTitle = "Gestion des Terrains";
$isAdmin = $_SESSION['user_role'] === 'admin';
$moroccanCities = [
    'Tétouan', 'Martil', 'Azla', 'Rabat', 'Casablanca', 'Marrakech',
    'Fès', 'Tanger', 'Agadir', 'Salé', 'Meknès', 'Oujda', 'Nador',
    'Kenitra', 'Chefchaouen', 'Larache', 'Safi', 'Essaouira', 'El Jadida',
    'Beni Mellal', 'Khouribga', 'Errachidia', 'Laâyoune'
];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="flex">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="flex-1 ml-64 p-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Gestion des Terrains</h1>
                        <p class="text-gray-600 mt-2">Gérez tous les terrains de football</p>
                    </div>
                    <?php if ($isAdmin): ?>
                    <button onclick="openAddModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg flex items-center gap-2 transition-colors shadow-lg">
                        <i class="fas fa-plus"></i>
                        <span>Ajouter un terrain</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                        <input type="text" id="searchInput" placeholder="Nom du terrain..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
                        <select id="filterCategorie" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Toutes</option>
                            <option value="Grand Terrain">Grand Terrain</option>
                            <option value="Terrain Moyen">Terrain Moyen</option>
                            <option value="Mini Foot">Mini Foot</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Disponibilité</label>
                        <select id="filterDisponibilite" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Tous</option>
                            <option value="disponible">Disponible</option>
                            <option value="indisponible">Indisponible</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                        <input type="text" id="filterVille" list="villeList" placeholder="Ville..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Responsable</label>
                        <select id="filterResponsable" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Tous</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Message de notification -->
            <div id="notification" class="hidden fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50"></div>

            <!-- Liste des terrains -->
            <div id="terrainsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Les terrains seront chargés ici via AJAX -->
            </div>

            <!-- Loader -->
            <div id="loader" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
            </div>
        </main>
    </div>

    <!-- Modal Ajouter/Modifier Terrain -->
    <div id="terrainModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h2 id="modalTitle" class="text-2xl font-bold text-gray-800">Ajouter un terrain</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <form id="terrainForm" class="p-6 space-y-4">
                <input type="hidden" id="terrainId" name="id_terrain">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom du terrain *</label>
                        <input type="text" id="nom_te" name="nom_te" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie *</label>
                        <select id="categorie" name="categorie" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Sélectionner...</option>
                            <option value="Grand Terrain">Grand Terrain</option>
                            <option value="Terrain Moyen">Terrain Moyen</option>
                            <option value="Mini Foot">Mini Foot</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                        <select id="type" name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Sélectionner...</option>
                            <option value="Gazon naturel">Gazon naturel</option>
                            <option value="Gazon synthétique">Gazon synthétique</option>
                            <option value="Terre battue">Terre battue</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Taille *</label>
                        <select id="taille" name="taille" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Sélectionner...</option>
                            <option value="105x68m">105x68m (Standard FIFA)</option>
                            <option value="100x60m">100x60m</option>
                            <option value="90x50m">90x50m</option>
                            <option value="70x40m">70x40m (5 vs 5)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prix par heure (DH) *</label>
                        <input type="number" id="prix_heure" name="prix_heure" step="0.01" min="0" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
                        <input type="text" id="ville" name="ville" list="villeList" placeholder="Sélectionner ou saisir une ville" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Disponibilité *</label>
                        <select id="disponibilite" name="disponibilite" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="disponible">Disponible</option>
                            <option value="indisponible">Indisponible</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Localisation *</label>
                    <textarea id="localisation" name="localisation" rows="2" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Responsable *</label>
                    <select id="id_responsable" name="id_responsable"  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Sélectionner un responsable...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Photo du terrain</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-emerald-500 transition-colors" id="dropZone">
                        <input type="file" id="imageFile" name="imageFile" accept="image/*" class="hidden">
                        <div id="uploadPreview">
                            <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-600">Cliquez pour importer une photo ou glissez-la ici</p>
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG (max 5MB)</p>
                        </div>
                        <div id="previewContainer" class="hidden mt-4">
                            <img id="previewImage" src="" alt="Aperçu" class="max-w-xs mx-auto rounded-lg">
                            <button type="button" onclick="clearImage()" class="mt-2 text-sm text-red-600 hover:text-red-700">
                                <i class="fas fa-trash mr-1"></i>Supprimer l'image
                            </button>
                        </div>
                    </div>
                    <input type="hidden" id="image" name="image">
                </div>

                <div class="flex gap-3 pt-4 border-t">
                    <button type="submit" id="submitBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                    <button type="button" onclick="closeModal()" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                <p class="text-sm text-gray-500 mb-6">Êtes-vous sûr de vouloir supprimer ce terrain ? Cette action est irréversible.</p>
                <div class="flex gap-3">
                    <button onclick="confirmDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Supprimer
                    </button>
                    <button onclick="closeDeleteModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium transition-colors">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <datalist id="villeList">
        <?php foreach ($moroccanCities as $city): ?>
            <option value="<?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
    </datalist>

    <script src="../../assets/js/stades.js"></script>
</body>

</html>