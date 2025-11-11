<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

// Vérifier le rôle (admin ou responsable uniquement)
if (!in_array($_SESSION['user_role'], ['admin', 'responsable'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestion des Factures</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    .badge-payee { background: #d1fae5; color: #065f46; }
    .badge-attente { background: #fef3c7; color: #92400e; }
    .badge-retard { background: #fee2e2; color: #991b1b; }
    .table-hover tbody tr:hover { background: #f9fafb; }
  </style>
</head>
<body class="bg-slate-50">
  <div class="min-h-screen">
    <!-- Sidebar -->
    <?php include_once '../../includes/sidebar.php'; ?>

    <!-- Content wrapper -->
    <main id="content" class="pl-64 transition-all duration-300">
      <div class="p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
          <div class="flex items-center gap-4">
            <button id="toggleSidebar" class="p-2 rounded-lg bg-green-600 hover:bg-green-700 text-white shadow transition">
              <i class="fas fa-bars text-lg"></i>
            </button>
            <div>
              <h2 class="text-2xl font-bold text-slate-900">Gestion des Factures</h2>
              <p class="text-slate-600 text-sm">Consulter et envoyer les factures</p>
            </div>
          </div>
          
          <div class="flex gap-3">
            <button id="btn-send-reminders" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg shadow transition flex items-center gap-2">
              <i class="fas fa-bell"></i>
              <span>Relancer impayés</span>
            </button>
            <button id="btn-generate-all" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow transition flex items-center gap-2">
              <i class="fas fa-file-pdf"></i>
              <span>Générer toutes</span>
            </button>
          </div>
        </div>

        <!-- Stats cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-slate-500 text-sm font-medium mb-2">Montant Total</p>
                <h3 id="stat-total" class="text-3xl font-bold text-slate-800">1485 €</h3>
              </div>
              <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-euro-sign text-blue-600 text-xl"></i>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-slate-500 text-sm font-medium mb-2">Payées</p>
                <h3 id="stat-payees" class="text-3xl font-bold text-green-600">550 €</h3>
              </div>
              <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-slate-500 text-sm font-medium mb-2">En Attente</p>
                <h3 id="stat-attente" class="text-3xl font-bold text-orange-600">575 €</h3>
              </div>
              <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-orange-600 text-xl"></i>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-slate-500 text-sm font-medium mb-2">En Retard</p>
                <h3 id="stat-retard" class="text-3xl font-bold text-red-600">1</h3>
              </div>
              <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
          <div class="flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-[300px]">
              <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input 
                  type="text" 
                  id="search-input" 
                  placeholder="Rechercher une facture..." 
                  class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                />
              </div>
            </div>

            <select id="filter-statut" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
              <option value="">Tous les statuts</option>
              <option value="generee">Générée</option>
              <option value="envoyee">Envoyée</option>
              <option value="annulee">Annulée</option>
            </select>

            <select id="filter-paiement" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
              <option value="">Tous les paiements</option>
              <option value="payee">Payée</option>
              <option value="attente">En attente</option>
              <option value="retard">En retard</option>
            </select>

            <button id="btn-reset-filters" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition">
              <i class="fas fa-redo mr-2"></i>Réinitialiser
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full table-hover">
              <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">N° Facture</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Client</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Email</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Terrain</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Réservation</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Date</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Montant</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Statut</th>
                  <th class="px-6 py-4 text-center text-sm font-semibold text-slate-700">Actions</th>
                </tr>
              </thead>
              <tbody id="factures-tbody" class="divide-y divide-slate-100">
                <!-- Les factures seront chargées ici via AJAX -->
                <tr>
                  <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                    <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                    <p>Chargement des factures...</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Modal Détails Facture -->
  <div id="modal-details" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-xl font-bold text-slate-900">Détails de la facture</h3>
        <button class="close-modal text-slate-400 hover:text-slate-600 transition">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      <div id="modal-content" class="p-6">
        <!-- Contenu chargé dynamiquement -->
      </div>
    </div>
  </div>

  <!-- Notification Toast -->
  <div id="notification" class="hidden fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 text-white"></div>

  <script src="/assets/js/dashbord.js"></script>
  <script src="/assets/js/invoices.js"></script>
</body>
</html>