<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Admin</title>
  <script src="https://cdn.tailwindcss.com" onerror="console.warn('Tailwind CSS failed to load')"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-+" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-slate-50">
  <div class="min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white shadow-md fixed top-0 left-0 h-full z-30">
      <div class="p-4 flex flex-col h-full">
        <a href="../index.php" class="flex items-center gap-3 mb-8 px-2">
          <img src="../assets/images/logo.png" alt="Logo UniEvents" class="w-9 h-9" />
          <div>
          <h1 class="font-bold text-lg text-slate-900">Terrain Book</h1>
          <p class="text-slate-600 mt-1"> Admin Global</p>
            </div>
        </a>
        <nav class="space-y-1 flex-1">
          <a href="admin_dashboard.php" class="flex items-center gap-3 px-4 py-2.5 bg-indigo-50 text-indigo-600 rounded-lg font-semibold transition-all duration-200 hover:scale-105">
            <i class="fas fa-th-large w-5 text-center"></i>
            <span>Dashboard</span>
          </a>
          <a href="events.php" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-100 hover:translate-x-1 rounded-lg font-medium transition-all duration-200">
            <i class="fas fa-calendar-check w-5 text-center"></i>
            <span>Utilisateurs</span>
          </a>
          <a href="clubs.php" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-100 hover:translate-x-1 rounded-lg font-medium transition-all duration-200">
            <i class="fas fa-users w-5 text-center"></i>
            <span>Terrains</span>
          </a>
          <a href="createClub.php" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-100 hover:translate-x-1 rounded-lg font-medium transition-all duration-200">
            <i class="fas fa-plus-circle w-5 text-center"></i>
            <span>Reservations</span>
          </a>
          <a href="" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-100 hover:translate-x-1 rounded-lg font-medium transition-all duration-200">
            <i class="fas fa-ban w-5 text-center"></i>
            <span>Tournois</span>
          </a>
          <a href="" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-100 hover:translate-x-1 rounded-lg font-medium transition-all duration-200">
            <i class="fas fa-ban w-5 text-center"></i>
            <span>Factures</span>
          </a>
          <a href="" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-100 hover:translate-x-1 rounded-lg font-medium transition-all duration-200">
            <i class="fas fa-ban w-5 text-center"></i>
            <span>NewsLater</span>
          </a>

        </nav>
        <div class="mt-auto">
          <a href="../index.php" class="flex items-center gap-3 px-4 py-2.5 text-slate-700 hover:bg-slate-100 rounded-lg font-medium transition-all duration-200 hover:translate-x-1">
            <i class="fas fa-sign-out-alt w-5 text-center"></i>
            <span>Déconnexion</span>
          </a>
        </div>
      </div>
    </aside>

    <!-- Content wrapper offset by fixed sidebar -->
    <main id="content" class="pl-64">
                <div class="p-8 max-w-7xl mx-auto">
            <!-- Titre avec bouton aligné -->
            <div class="flex items-center gap-4 mb-8"> 
                <!-- Bouton menu -->
                <button id="toggleSidebar" 
                        class="p-2 rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white shadow transition duration-300">
                <i class="fas fa-bars text-lg"></i>
                </button>
                <!-- Bloc titres -->
                <div>
                <h2 class="text-2xl font-bold text-slate-900">Tableau de bord</h2>
                <p class="text-slate-600 text-sm">Vue d’ensemble de votre plateforme</p>
                </div>
            </div>
            

        <!-- Stats cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200 hover:-translate-y-1 hover:shadow-lg transition-transform duration-300">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-slate-500 text-sm font-medium mb-2">Total Réservations</p>
                <h3 class="text-4xl font-bold text-slate-800">7</h3>
              </div>
              <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar text-blue-600 text-xl"></i>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200 hover:-translate-y-1 hover:shadow-lg transition-transform duration-300">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-slate-500 text-sm font-medium mb-2">Revenus Mensuels</p>
                <h3 class="text-4xl font-bold text-slate-800">80$</h3>
              </div>
              <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-layer-group text-purple-600 text-xl"></i>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-200 hover:-translate-y-1 hover:shadow-lg transition-transform duration-300">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-slate-500 text-sm font-medium mb-2">Terrains Actifs</p>
                <h3 class="text-4xl font-bold text-slate-800">7</h3>
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
                <h3 class="text-4xl font-bold text-slate-800">7</h3>
              </div>
              <div class="w-12 h-12 bg-teal-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-friends text-teal-600 text-xl"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Bar chart -->
          <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 h-80">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Réservations Hebdomadaires</h2>
            <p class="text-slate-500 text-sm mb-3">Nombre total de réservations par jour.</p>
            <canvas id="chartReservations" class="w-full h-full"></canvas>
          </div>

          <!-- Line chart -->
          <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 h-80">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Évolution des Revenus</h2>
            <p class="text-slate-500 text-sm mb-3">Suivi mensuel du chiffre d'affaires.</p>
            <canvas id="chartRevenus" class="w-full h-full"></canvas>
          </div>
        </div>
      </div>
    




    <!-- 🟦 Activité Récente -->
 <!-- Card globale -->
    <div id="recentActivity" class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8 max-w-5xl mx-auto transition-all duration-300">
    <!-- Header -->
    <div class="p-6 border-b border-slate-200">
        <h2 id="recent-activity" class="text-xl font-bold text-slate-800 mb-1">Activité Récente</h2>
        <p class="text-slate-500 text-sm">Voici les dernières actions effectuées sur la plateforme.</p>
    </div>

  <!-- Liste d'activités -->
  <div class="p-4">
    <ul class="divide-y divide-slate-100">
      <!-- Item 1 -->
      <li class="py-4 hover:bg-slate-50 transition">
        <div class="flex items-start justify-between gap-4">
          <!-- left: avatar + text -->
          <div class="flex items-start gap-3">
            <!-- avatar (initiales ou icone) -->
            <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-semibold bg-green-500">
              MA
            </div>
            <!-- content -->
            <div>
              <div class="text-slate-800 font-medium">Mohamed Ali</div>
              <div class="text-slate-500 text-sm">a réservé <span class="font-medium text-slate-700">Terrain A - Mini Foot</span></div>
            </div>
          </div>

          <!-- right: timestamp -->
          <div class="text-sm text-slate-400 whitespace-nowrap">il y a 5 min</div>
        </div>
      </li>

      <!-- Item 2 -->
      <li class="py-4 hover:bg-slate-50 transition">
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-semibold bg-red-500">
              FB
            </div>
            <div>
              <div class="text-slate-800 font-medium">Fatima Zahra</div>
              <div class="text-slate-500 text-sm">a annulé <span class="font-medium text-slate-700">Terrain B - Grand</span></div>
            </div>
          </div>
          <div class="text-sm text-slate-400 whitespace-nowrap">il y a 15 min</div>
        </div>
      </li>
    </ul>
  </div>
   </div>
    </div>
    </main>
  </div>
  <!-- Charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Bar: Réservations Hebdomadaires
    const ctxBar = document.getElementById('chartReservations').getContext('2d');
    new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
        datasets: [{
          label: 'Réservations',
          data: [45, 55, 35, 65, 80, 95, 85],
          backgroundColor: '#10b981',
          borderRadius: 8,
          barThickness: 30,
        }],
      },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        animation: { duration: 900, easing: 'easeOutQuart' },
        plugins: { legend: { display: false }, title: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { color: '#475569' }, grid: { color: '#f1f5f9' } },
          x: { ticks: { color: '#475569' }, grid: { display: false } },
        },
      },
    });

    // Line: Évolution des Revenus
    const ctxLine = document.getElementById('chartRevenus').getContext('2d');
    new Chart(ctxLine, {
      type: 'line',
      data: {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
        datasets: [{
          label: 'Revenus (DH)',
          data: [32000, 34000, 37000, 35000, 42000, 45000],
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.2)',
          tension: 0.3,
          fill: true,
          pointRadius: 5,
          pointBackgroundColor: '#10b981',
        }],
      },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        animation: { duration: 900, easing: 'easeOutQuart' },
        plugins: { legend: { display: false }, title: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { color: '#475569' }, grid: { color: '#f1f5f9' } },
          x: { ticks: { color: '#475569' }, grid: { display: false } },
        },
      },
    });
  </script>
<script src="dashbord.js"></script>
</body>
</html>