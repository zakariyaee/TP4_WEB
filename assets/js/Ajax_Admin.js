// Récupération des éléments DOM pour afficher les statistiques
const StatEls = {
  totalReservations: document.getElementById('stat-total-reservations'),
  revenueTotal: document.getElementById('stat-revenue-total'),
  totalTerrains: document.getElementById('stat-total-terrains'),
  totalJoueurs: document.getElementById('stat-total-joueurs'),
};


function Ajax_Dashbord_Statistique() {
  const xhr = new XMLHttpRequest();
  const url = '../../actions/admin-respo/dashbord_statistique.php';
  xhr.open('GET', url, true);
  xhr.withCredentials = true; // Important pour envoyer les cookies de session
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) { // Requête terminée
      if (xhr.status === 200) { // Succès
        try {
          const data = JSON.parse(xhr.responseText);
          
          if (data.success) {
            // Mise à jour des statistiques de base
            if (StatEls.totalReservations && typeof data.total_reservations !== 'undefined') {
              StatEls.totalReservations.textContent = data.total_reservations;
            }
            
            if (StatEls.revenueTotal && typeof data.revenue_total !== 'undefined') {
              StatEls.revenueTotal.textContent = data.revenue_total + ' €';
            }
            
            // CORRECTION : Mise à jour du nombre de terrains actifs
            if (StatEls.totalTerrains && typeof data.total_terrains !== 'undefined') {
              StatEls.totalTerrains.textContent = data.total_terrains;
            }
            
            if (StatEls.totalJoueurs && typeof data.total_joueurs !== 'undefined') {
              StatEls.totalJoueurs.textContent = data.total_joueurs;
            }
            if (typeof data.total_moyenne !== 'undefined' &&
              typeof data.total_minifoot !== 'undefined' &&
              typeof data.total_Grand !== 'undefined') {
            initChartTypeTerrain(data);
}
            
            console.log('✅ Statistiques mises à jour avec succès');
          } else {
            console.error('❌ Erreur dans la réponse:', data.message);
          }
        } catch (e) {
          console.error('❌ Erreur de parsing JSON :', e, xhr.responseText);
        }
      } else {
        console.error('❌ Erreur HTTP :', xhr.status, xhr.statusText);
      }
    }
  };

  xhr.onerror = function() {
    console.error('❌ Erreur réseau lors de la requête AJAX');
  };

  xhr.send();
}

/**
 * Fonction pour mettre à jour les graphiques Chart.js si nécessaire
 */
// === Graphique Doughnut des types de terrains ===
let chartTypeTerrain = null;

  function initChartTypeTerrain(data) {
    const ctxPie = document.getElementById('chartTypeTerrain').getContext('2d');
    $total=data.total_moyenne + data.total_minifoot + data.total_Grand;
    // Données à afficher
    const labels = [
      `Terrain Moyen ${(data.total_moyenne/$total)*100 || 0}%`,
      `Mini Foot ${(data.total_minifoot/$total)*100 || 0}%`,
      `Grand Terrain ${(data.total_Grand/$total)*100 || 0}%`
    ];

    const values = [
      data.total_moyenne || 0,
      data.total_minifoot || 0,
      data.total_Grand || 0
    ];

    // Si le graphique existe déjà → on le met à jour
    if (chartTypeTerrain) {
      chartTypeTerrain.data.labels = labels;
      chartTypeTerrain.data.datasets[0].data = values;
      chartTypeTerrain.update();
      return;
    }

    // Sinon, on crée le graphique pour la première fois
    chartTypeTerrain = new Chart(ctxPie, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
          hoverOffset: 15,
          borderWidth: 0
        }]
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
              padding: 15
            }
          },
          tooltip: {
            callbacks: {
              label: (tooltipItem) => `${tooltipItem.label}: ${tooltipItem.raw}%`
            }
          }
        }
      }
    });
  }


window.addEventListener('storage', function(event) {
  if (event.key === 'update_statistiques') {
    console.log('🔄 Mise à jour déclenchée depuis un autre onglet');
    Ajax_Dashbord_Statistique();
  }
});

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
  console.log('📊 Initialisation du système de statistiques AJAX');
  // Première mise à jour immédiate
  Ajax_Dashbord_Statistique();
})
setInterval(Ajax_Dashbord_Statistique, 3000);