  <?php
  require_once '../../config/database.php';
  require_once '../../check_auth.php';
  require_once '../../actions/admin-respo/dashbord_action.php';

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
    <title>Dashboard Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  </head>
  <body class="bg-slate-50">
    <div class="min-h-screen">
      <!-- Sidebar -->
    <?php include_once '../../includes/sidebar.php'; ?>

      <!-- Content wrapper -->
      <main id="content" class="pl-64 transition-all duration-300">
        <div class="p-8">
          <!-- Header avec bouton toggle -->
          <div class="flex items-center gap-4 mb-8">
            <button id="toggleSidebar" class="p-2 rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white shadow transition duration-300">
              <i class="fas fa-bars text-lg"></i>
            </button>
            <div>
              <h2 class="text-2xl font-bold text-slate-900">Tableau de bord</h2>
              <p class="text-slate-600 text-sm">Vue d'ensemble de votre plateforme</p>
            </div>
          </div>

          <!-- Stats cards -->
          <div id="Statistiques" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200 hover:-translate-y-1 hover:shadow-lg transition-transform duration-300">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-slate-500 text-sm font-medium mb-2">Total Réservations</p>
                  <h3 id="stat-total-reservations" class="text-4xl font-bold text-slate-800"><?=$_SESSION['total_reservations']?></h3>
                  <p class="text-green-600 text-xs mt-2"><i class="fas fa-arrow-up"></i> +12.5% vs mois dernier</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                  <i class="fas fa-calendar text-blue-600 text-xl"></i>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200 hover:-translate-y-1 hover:shadow-lg transition-transform duration-300">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-slate-500 text-sm font-medium mb-2">Revenue mensuels</p>
                  <h3 id="stat-revenue-total" class="text-4xl font-bold text-slate-800"><?=$_SESSION['revenue_total']?> €</h3>
                  <p class="text-green-600 text-xs mt-2"><i class="fas fa-arrow-up"></i> +8.2% vs mois dernier</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                  <i class="fas fa-euro-sign text-purple-600 text-xl"></i>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200 hover:-translate-y-1 hover:shadow-lg transition-transform duration-300">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-slate-500 text-sm font-medium mb-2">Terrains Actifs</p>
                  <h3 id="stat-total-terrains" class="text-4xl font-bold text-slate-800"><?=$_SESSION['total_terrains']?></h3>
                  <p class="text-green-600 text-xs mt-2"><i class="fas fa-arrow-up"></i> +2 vs mois dernier</p>
                </div>
                <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center">
                  <i class="fas fa-map-location-dot text-orange-600 text-xl"></i>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200 hover:-translate-y-1 hover:shadow-lg transition-transform duration-300">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-slate-500 text-sm font-medium mb-2">Utilisateurs Actifs</p>
                  <h3 id="stat-total-joueurs" class="text-4xl font-bold text-slate-800"><?=$_SESSION['total_joueurs']?></h3>
                  <p class="text-red-600 text-xs mt-2"><i class="fas fa-arrow-down"></i> -3.1% vs mois dernier</p>
                </div>
                <div class="w-12 h-12 bg-teal-50 rounded-lg flex items-center justify-center">
                  <i class="fas fa-user-friends text-teal-600 text-xl"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Charts row -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Bar chart -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
              <h2 class="text-lg font-bold text-slate-800 mb-1">Réservations Hebdomadaires</h2>
              <p class="text-slate-500 text-sm mb-4">Nombre total de réservations par jour.</p>
              <div class="h-64">
                <canvas id="chartReservations"></canvas>
              </div>
            </div>

            <!-- Line chart -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
              <h2 class="text-lg font-bold text-slate-800 mb-1">Évolution des Revenus</h2>
              <p class="text-slate-500 text-sm mb-4">Suivi mensuel du chiffre d'affaires.</p>
              <div class="h-64">
                <canvas id="chartRevenus"></canvas>
              </div>
            </div>
          </div>

          <!-- Activité Récente + Type de Terrain (côte à côte) -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Activité Récente -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200">
              <div class="p-6 border-b border-slate-200">
                <h2 class="text-xl font-bold text-slate-800 mb-1">Activité Récente</h2>
                <p class="text-slate-500 text-sm">Dernières actions sur la plateforme.</p>
              </div>
              <div class="p-4">
                <ul class="divide-y divide-slate-100">
                  <li class="py-4 hover:bg-slate-50 transition">
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-semibold bg-green-500">MA</div>
                        <div>
                          <div class="text-slate-800 font-medium">Mohamed Ali</div>
                          <div class="text-slate-500 text-sm">a réservé <span class="font-medium text-slate-700">Terrain A - Mini Foot</span></div>
                        </div>
                      </div>
                      <div class="text-sm text-slate-400 whitespace-nowrap">il y a 5 min</div>
                    </div>
                  </li>
                  <li class="py-4 hover:bg-slate-50 transition">
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-semibold bg-red-500">FZ</div>
                        <div>
                          <div class="text-slate-800 font-medium">Fatima Zahra</div>
                          <div class="text-slate-500 text-sm">a annulé <span class="font-medium text-slate-700">Terrain B - Grand</span></div>
                        </div>
                      </div>
                      <div class="text-sm text-slate-400 whitespace-nowrap">il y a 15 min</div>
                    </div>
                  </li>
                  <li class="py-4 hover:bg-slate-50 transition">
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-semibold bg-blue-500">YB</div>
                        <div>
                          <div class="text-slate-800 font-medium">Youssef Ben</div>
                          <div class="text-slate-500 text-sm">a payé <span class="font-medium text-slate-700">Facture #1234</span></div>
                        </div>
                      </div>
                      <div class="text-sm text-slate-400 whitespace-nowrap">il y a 30 min</div>
                    </div>
                  </li>
                  <li class="py-4 hover:bg-slate-50 transition">
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-semibold bg-purple-500">SM</div>
                        <div>
                          <div class="text-slate-800 font-medium">Sarah Mansouri</div>
                          <div class="text-slate-500 text-sm">s'est inscrit à <span class="font-medium text-slate-700">Tournoi Champions</span></div>
                        </div>
                      </div>
                      <div class="text-sm text-slate-400 whitespace-nowrap">il y a 1h</div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Type de Terrain (Pie Chart) -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
              <h2 class="text-lg font-bold text-slate-800 mb-1">Types de Terrains</h2>
              <p class="text-slate-500 text-sm mb-4">Répartition des types de terrains.</p>
              <div class="h-64 flex items-center justify-center">
                <canvas id="chartTypeTerrain"></canvas>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      // Bar: Réservations Hebdomadaires
      const ctxBar = document.getElementById('chartReservations').getContext('2d');
      window.chartReservations = new Chart(ctxBar, {
        type: 'bar',
        data: {
          labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
          datasets: [{
            label: 'Réservations',
            data: [<?= $_SESSION['graphe_jour']['Lundi']?>, <?= $_SESSION['graphe_jour']['Mardi']?>, <?= $_SESSION['graphe_jour']['Mercredi']?>,
             <?= $_SESSION['graphe_jour']['Jeudi']?>, <?= $_SESSION['graphe_jour']['Vendredi']?>, <?= $_SESSION['graphe_jour']['Samedi']?>, 
             <?= $_SESSION['graphe_jour']['Dimanche']?>],
            backgroundColor: '#10b981',
            borderRadius: 8,
            barThickness: 30,
          }],
        },
        options: {
          maintainAspectRatio: false,
          responsive: true,
          animation: { duration: 900, easing: 'easeOutQuart' },
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { color: '#475569' }, grid: { color: '#f1f5f9' } },
            x: { ticks: { color: '#475569' }, grid: { display: false } },
          },
        },
      });

      // Line: Évolution des Revenus
      const ctxLine = document.getElementById('chartRevenus').getContext('2d');
      window.chartRevenus = new Chart(ctxLine, {
        type: 'line',
        data: {
          labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
          datasets: [{
            label: 'Revenus (€)',
            data: [<?=  $_SESSION['revenus_mensuels'][0]?>, <?=  $_SESSION['revenus_mensuels'][1]?>,
             <?=  $_SESSION['revenus_mensuels'][2]?>, <?=  $_SESSION['revenus_mensuels'][3]?>,
              <?=  $_SESSION['revenus_mensuels'][4]?>, <?=  $_SESSION['revenus_mensuels'][5]?>],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: '#10b981',
          }],
        },
        options: {
          maintainAspectRatio: false,
          responsive: true,
          animation: { duration: 900, easing: 'easeOutQuart' },
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { color: '#475569' }, grid: { color: '#f1f5f9' } },
            x: { ticks: { color: '#475569' }, grid: { display: false } },
          },
        },
      });

      // Pie: Type de Terrain
      const ctxPie = document.getElementById('chartTypeTerrain').getContext('2d');
      window.chartTypeTerrain = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
          labels: ['Terrain Moyen <?=$_SESSION['total_moyenne']?>%', 'Mini Foot <?=$_SESSION['total_minifoot']?>%', 'Grand Terrain <?=$_SESSION['total_Grand']?>%'],
          datasets: [{
            data: [<?=$_SESSION['total_moyenne']?>,<?=$_SESSION['total_minifoot']?> , <?=$_SESSION['total_Grand']?>],
            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
            hoverOffset: 15,
            borderWidth: 0,
          }],
        },
        options: {
          maintainAspectRatio: false,
          responsive: true,
          animation: { duration: 900, easing: 'easeOutQuart' },
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: '#475569',
                font: { size: 12 },
                padding: 15,
              }
            }
          },
        },
      });

                // Toggle Sidebar
              document.addEventListener('DOMContentLoaded', () => {
              const sidebar = document.getElementById('sidebar');
              const toggleButton = document.getElementById('toggleSidebar');
              const content = document.getElementById('content');
              
              // État initial basé sur la largeur de l'écran
              let isSidebarOpen = window.innerWidth >= 1024;
              
              // Fonction optimisée pour basculer la sidebar
              const toggleSidebar = () => {
                isSidebarOpen = !isSidebarOpen;
                updateSidebarState();
              };
              
              // Fonction unique pour mettre à jour l'état de la sidebar
              const updateSidebarState = () => {
                // Utilisation de requestAnimationFrame pour des animations fluides
                requestAnimationFrame(() => {
                  if (isSidebarOpen) {
                    sidebar.classList.remove('w-0', 'opacity-0', '-translate-x-full');
                    sidebar.classList.add('w-64', 'opacity-100', 'translate-x-0');
                    content.classList.remove('pl-0');
                    content.classList.add('pl-64');
                  } else {
                    sidebar.classList.remove('w-64', 'opacity-100', 'translate-x-0');
                    sidebar.classList.add('w-0', 'opacity-0', '-translate-x-full');
                    content.classList.remove('pl-64');
                    content.classList.add('pl-0');
                  }
                });
              };
              
              // Gestionnaire d'événement avec debouncing
              if (toggleButton) {
                toggleButton.addEventListener('click', toggleSidebar);
              }
              
              // Gestion responsive avec debouncing
              let resizeTimeout;
              const handleResize = () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                  const shouldBeOpen = window.innerWidth >= 1024;
                  
                  // Éviter les mises à jour inutiles
                  if (isSidebarOpen !== shouldBeOpen) {
                    isSidebarOpen = shouldBeOpen;
                    updateSidebarState();
                  }
                }, 100); // Debounce de 100ms
              };
              
              window.addEventListener('resize', handleResize);
              
              // Initialisation
              updateSidebarState();
              
              // Fermer la sidebar en cliquant à l'extérieur (sur mobile)
              document.addEventListener('click', (e) => {
                if (window.innerWidth < 1024 && 
                    isSidebarOpen && 
                    !sidebar.contains(e.target) && 
                    e.target !== toggleButton) {
                  isSidebarOpen = false;
                  updateSidebarState();
                }
              });
              
              // Gestion des touches clavier (Escape pour fermer)
              document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && isSidebarOpen && window.innerWidth < 1024) {
                  isSidebarOpen = false;
                  updateSidebarState();
                }
              });
            });
    </script>
    <script src="/js/Ajax_Admin.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        if (typeof Ajax_Dashbord_Statistique === 'function') {
          Ajax_Dashbord_Statistique();
        }
      });
    </script>
  </body>
  </html>