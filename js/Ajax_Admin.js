const StatEls = {
  totalReservations: document.getElementById('stat-total-reservations'),
  revenueTotal: document.getElementById('stat-revenue-total'),
  totalTerrains: document.getElementById('stat-total-terrains'),
  totalJoueurs: document.getElementById('stat-total-joueurs'),
};

function Ajax_Dashbord_Statistique() {
  const xhr = new XMLHttpRequest();
  const url = '/actions/admin-respo/dashbord_statistique.php';

  xhr.open('GET', url, true);
  xhr.withCredentials = true; // équivaut à fetch(..., { credentials: 'same-origin' })

  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) { // requête terminée
      if (xhr.status === 200) {
        try {
          const data = JSON.parse(xhr.responseText);

          // === Mise à jour des cartes ===
          if (StatEls.totalReservations && typeof data.total_reservations !== 'undefined') {
            StatEls.totalReservations.textContent = data.total_reservations;
          }
          if (StatEls.revenueTotal && typeof data.revenue_total !== 'undefined') {
            StatEls.revenueTotal.textContent = data.revenue_total + ' €';
          }
          if (StatEls.totalTerrains && typeof data.total_terrains !== 'undefined') {
            StatEls.totalTerrains.textContent = data.total_terrains;
          }
          if (StatEls.totalJoueurs && typeof data.total_joueurs !== 'undefined') {
            StatEls.totalJoueurs.textContent = data.total_joueurs;
          }

        } catch (e) {
          console.error('Erreur JSON :', e, xhr.responseText);
        }
      } else {
        console.error('Erreur HTTP :', xhr.status);
      }
    }
  };

  xhr.send();
}

// Rafraîchir quand localStorage change
window.addEventListener('storage', function(event) {
  if (event.key === 'update_statistiques') {
    Ajax_Dashbord_Statistique();
  }
});
