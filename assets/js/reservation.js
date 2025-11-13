// ============================================
// 1. VARIABLES GLOBALES
// ============================================
const ReservationStatsEls = {
  totalReservations: document.getElementById('stat-total-reservations'),
  confirmedReservations: document.getElementById('stat-confirmed-reservations'),
  pendingReservations: document.getElementById('stat-pending-reservations'),
  totalRevenue: document.getElementById('stat-total-revenue'),
  tableBody: document.querySelector('tbody')
};

let searchTimeout;
let refreshInterval;


// ============================================
// 2. CHARGEMENT DES RÉSERVATIONS
// ============================================
function loadReservations() {
  const xhr = new XMLHttpRequest();
  
  // Construction de l'URL avec les paramètres de filtrage
  const searchQuery = document.getElementById('searchInput').value;
  const filterDate = document.getElementById('filterDate').value;
  const filterStatus = document.getElementById('filterStatus').value;
  
  const params = new URLSearchParams();
  if (searchQuery) params.append('search', searchQuery);
  if (filterDate) params.append('date', filterDate);
  if (filterStatus) params.append('status', filterStatus);
  
  const url = '../../actions/admin-manager/reservation/ajax_load_reservations.php?' + params.toString();
  
  xhr.open('GET', url, true);
  xhr.withCredentials = true;
  
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          
          if (response.success) {
            updateReservationStats(response.stats);
            updateReservationTable(response.reservations);
            console.log('✅ Réservations chargées avec succès');
          } else {
            console.error('❌ Erreur dans la réponse:', response.message);
            showNotification(response.message || 'Erreur lors du chargement', 'error');
          }
        } catch (e) {
          console.error('❌ Erreur de parsing JSON:', e, xhr.responseText);
          showNotification('Erreur lors du traitement des données', 'error');
        }
      } else {
        console.error('❌ Erreur HTTP:', xhr.status, xhr.statusText);
        showNotification('Erreur de connexion au serveur', 'error');
      }
    }
  };
  
  xhr.onerror = function() {
    console.error('❌ Erreur réseau lors de la requête AJAX');
    showNotification('Erreur réseau', 'error');
  };
  
  xhr.send();
}


// ============================================
// 3. MISE À JOUR DES STATISTIQUES
// ============================================
function updateReservationStats(stats) {
  if (ReservationStatsEls.totalReservations && typeof stats.total !== 'undefined') {
    ReservationStatsEls.totalReservations.textContent = stats.total;
  }
  
  if (ReservationStatsEls.confirmedReservations && typeof stats.confirmed !== 'undefined') {
    ReservationStatsEls.confirmedReservations.textContent = stats.confirmed;
  }
  
  if (ReservationStatsEls.pendingReservations && typeof stats.pending !== 'undefined') {
    ReservationStatsEls.pendingReservations.textContent = stats.pending;
  }
  
  if (ReservationStatsEls.totalRevenue && typeof stats.revenue !== 'undefined') {
    ReservationStatsEls.totalRevenue.textContent = stats.revenue + ' DH';
  }
}


// ============================================
// 4. MISE À JOUR DU TABLEAU
// ============================================
function updateReservationTable(reservations) {
  const tbody = ReservationStatsEls.tableBody;
  
  if (!tbody) {
    console.error('❌ Élément tbody introuvable');
    return;
  }
  
  // Si aucune réservation
  if (!reservations || reservations.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
          <i class="fas fa-inbox text-4xl mb-3 block"></i>
          <p class="text-lg font-medium">Aucune réservation trouvée</p>
          <p class="text-sm mt-2">Essayez de modifier vos filtres</p>
        </td>
      </tr>
    `;
    return;
  }
  
  // Construction du HTML pour chaque réservation
  let html = '';
  reservations.forEach(reservation => {
    html += buildReservationRow(reservation);
  });
  
  tbody.innerHTML = html;
}


// ============================================
// 5. CONSTRUCTION D'UNE LIGNE
// ============================================
function buildReservationRow(reservation) {
  const statusClasses = {
    'confirmee': 'bg-blue-100 text-blue-700',
    'en_attente': 'bg-yellow-100 text-yellow-700',
    'terminee': 'bg-green-100 text-green-700',
    'annulee': 'bg-red-100 text-red-700'
  };
  
  const statusLabels = {
    'confirmee': 'Confirmée',
    'en_attente': 'En attente',
    'terminee': 'Terminée',
    'annulee': 'Annulée'
  };
  
  // Formatage de la date
  const dateObj = new Date(reservation.date_debut);
  const dateFormatted = dateObj.toLocaleDateString('fr-FR');
  
  // Gestion des extras
  let extrasHtml = '-';
  if (reservation.extras) {
    const extrasArray = reservation.extras.split(', ');
    extrasHtml = extrasArray.map(extra => 
      `<span class="inline-block px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs mr-1 mb-1">
        ${escapeHtml(extra)}
      </span>`
    ).join('');
  }
  
  // Détail des prix
  let priceDetail = `Terrain: ${parseFloat(reservation.prix_terrain).toFixed(2)} DH`;
  if (parseFloat(reservation.prix_extras) > 0) {
    priceDetail += `<br>Extras: ${parseFloat(reservation.prix_extras).toFixed(2)} DH`;
  }
  
  return `
    <tr class="hover:bg-gray-50 transition">
      <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm font-medium text-gray-900">#${reservation.id_reservation}</span>
      </td>
      <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm text-gray-900">${escapeHtml(reservation.nom + ' ' + reservation.prenom)}</span>
      </td>
      <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm text-gray-600">${escapeHtml(reservation.nom_terrain)}</span>
        <br>
        <span class="text-xs text-gray-500">${escapeHtml(reservation.categorie_terrain)}</span>
      </td>
      <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm text-gray-900">${dateFormatted}</span>
      </td>
      <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm text-gray-900">${reservation.heure_debut.substring(0, 5)} - ${reservation.heure_fin.substring(0, 5)}</span>
      </td>
      <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm text-gray-900">${reservation.duree}h</span>
      </td>
      <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm">
          <div class="font-medium text-gray-900">${parseFloat(reservation.prix_total).toFixed(2)} DH</div>
          <div class="text-xs text-gray-500">${priceDetail}</div>
        </div>
      </td>
      <td class="px-6 py-4">
        ${extrasHtml}
      </td>
      <td class="px-6 py-4 whitespace-nowrap">
        <span class="px-3 py-1 ${statusClasses[reservation.statut]} rounded-full text-xs font-medium">
          ${statusLabels[reservation.statut]}
        </span>
      </td>
    </tr>
  `;
}


// ============================================
// 6. FILTRES ET RECHERCHE
// ============================================
function applyFilters() {
  loadReservations();
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('filterDate').value = '';
  document.getElementById('filterStatus').value = '';
  loadReservations();
}


// ============================================
// 7. MODAL DE CONFIRMATION
// ============================================
function showModal(message, onConfirm) {
  document.getElementById('modalMessage').textContent = message;
  document.getElementById('confirmModal').classList.remove('hidden');
  document.getElementById('confirmButton').onclick = () => {
    onConfirm();
    closeModal();
  };
}

function closeModal() {
  document.getElementById('confirmModal').classList.add('hidden');
}


// ============================================
// 8. NOTIFICATIONS
// ============================================
function showNotification(message, type = 'info') {
  const notification = document.getElementById('notification');
  const colors = {
    success: 'bg-green-500',
    error: 'bg-red-500',
    info: 'bg-blue-500'
  };
  
  notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 text-white ${colors[type]}`;
  notification.textContent = message;
  notification.classList.remove('hidden');
  
  setTimeout(() => notification.classList.add('hidden'), 3000);
}


// ============================================
// 9. UTILITAIRES
// ============================================
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}


// ============================================
// 10. GESTION SIDEBAR
// ============================================
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const toggleButton = document.getElementById('toggleSidebar');
  const content = document.getElementById('content');
  
  let isSidebarOpen = window.innerWidth >= 1024;
  
  const toggleSidebar = () => {
    isSidebarOpen = !isSidebarOpen;
    updateSidebarState();
  };
  
  const updateSidebarState = () => {
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
  
  if (toggleButton) {
    toggleButton.addEventListener('click', toggleSidebar);
  }
  
  let resizeTimeout;
  const handleResize = () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      const shouldBeOpen = window.innerWidth >= 1024;
      if (isSidebarOpen !== shouldBeOpen) {
        isSidebarOpen = shouldBeOpen;
        updateSidebarState();
      }
    }, 100);
  };
  
  window.addEventListener('resize', handleResize);
  updateSidebarState();
  
  document.addEventListener('click', (e) => {
    if (window.innerWidth < 1024 && 
        isSidebarOpen && 
        !sidebar.contains(e.target) && 
        e.target !== toggleButton) {
      isSidebarOpen = false;
      updateSidebarState();
    }
  });
  
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isSidebarOpen && window.innerWidth < 1024) {
      isSidebarOpen = false;
      updateSidebarState();
    }
  });
});


// ============================================
// 11. INITIALISATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
  console.log('📋 Initialisation du système de réservations');
  
  // Chargement initial des réservations
  loadReservations();
  
  // Recherche avec debounce (800ms)
  document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 800);
  });
  
  // Filtrage par date
  document.getElementById('filterDate').addEventListener('change', applyFilters);
  
  // Filtrage par statut
  document.getElementById('filterStatus').addEventListener('change', applyFilters);
});


// ============================================
// 12. SYNCHRONISATION MULTI-ONGLETS
// ============================================
window.addEventListener('storage', function(event) {
  if (event.key === 'update_reservations') {
    console.log('🔄 Mise à jour déclenchée depuis un autre onglet');
    loadReservations();
  }
});