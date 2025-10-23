<?php
$page_title = "TerrainBook - Mes Réservations";
include 'header.php';
?>

    <!-- Contenu spécifique à la page des réservations -->
    <main class="max-w-7xl mx-auto px-4 md:px-8 py-20">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Mes Réservations</h1>
        
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Réservation 1 -->
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Mini Foot - Terrain A</h3>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">Confirmée</span>
                    </div>
                    <div class="space-y-2 text-gray-600">
                        <p><strong>Date:</strong> 15 Janvier 2024</p>
                        <p><strong>Heure:</strong> 18:00 - 19:00</p>
                        <p><strong>Prix:</strong> 35€</p>
                    </div>
                    <button class="w-full mt-4 bg-terrain-green text-white py-2 rounded-lg hover:bg-green-600 transition-colors">
                        Voir détails
                    </button>
                </div>

                <!-- Réservation 2 -->
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Terrain Moyen - Terrain B</h3>
                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-sm">En attente</span>
                    </div>
                    <div class="space-y-2 text-gray-600">
                        <p><strong>Date:</strong> 20 Janvier 2024</p>
                        <p><strong>Heure:</strong> 20:00 - 21:00</p>
                        <p><strong>Prix:</strong> 60€</p>
                    </div>
                    <button class="w-full mt-4 bg-terrain-green text-white py-2 rounded-lg hover:bg-green-600 transition-colors">
                        Voir détails
                    </button>
                </div>

                <!-- Réservation 3 -->
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Grand Terrain - Terrain C</h3>
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-sm">Annulée</span>
                    </div>
                    <div class="space-y-2 text-gray-600">
                        <p><strong>Date:</strong> 10 Janvier 2024</p>
                        <p><strong>Heure:</strong> 16:00 - 17:00</p>
                        <p><strong>Prix:</strong> 100€</p>
                    </div>
                    <button class="w-full mt-4 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        Voir détails
                    </button>
                </div>
            </div>
        </div>
    </main>

<?php include 'footer.php'; ?>
