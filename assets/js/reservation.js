function validateReservation(id) {
    showModal('Voulez-vous confirmer cette réservation ?', () => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../../actions/admin-respo/reservation/validate_reservation.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function() {
            if (xhr.status === 200 ) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showNotification('Réservation confirmée avec succès', 'success');
                        // Recharger la page pour afficher les nouvelles données
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(response.message || 'Erreur lors de la validation', 'error');
                    }
                } catch (e) {
                    showNotification('Erreur lors de la validation', 'error');
                }
            }
        };

        xhr.send(JSON.stringify({ id_reservation: id }));
    });
}

function cancelReservation(id) {
    showModal('Voulez-vous annuler cette réservation ?', () => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../../actions/admin-respo/cancel_reservation.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showNotification('Réservation annulée', 'success');
                        // Recharger la page pour afficher les nouvelles données
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(response.message || 'Erreur lors de l\'annulation', 'error');
                    }
                } catch (e) {
                    showNotification('Erreur lors de l\'annulation', 'error');
                }
            }
        };

        xhr.send(JSON.stringify({ id_reservation: id }));
    });
}

// 2. FILTRES ET RECHERCHE (Rechargement de page)
function applyFilters() {
    const searchQuery = document.getElementById('searchInput').value;
    const filterDate = document.getElementById('filterDate').value;
    const filterStatus = document.getElementById('filterStatus').value;

    // Construire l'URL avec les paramètres
    const params = new URLSearchParams();
    if (searchQuery) params.append('search', searchQuery);
    if (filterDate) params.append('date', filterDate);
    if (filterStatus) params.append('status', filterStatus);

    // Recharger la page avec les filtres
    window.location.href = '?' + params.toString();
}

function resetFilters() {
    // Recharger la page sans paramètres
    window.location.href = window.location.pathname;
}

// 3. MODAL DE CONFIRMATION
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

// 4. NOTIFICATIONS
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


// 5. ÉVÉNEMENTS (Debounce pour la recherche)
      document.addEventListener('DOMContentLoaded', () => {
          // Recherche avec debounce
          let searchTimeout;
          document.getElementById('searchInput').addEventListener('input', function() {
              clearTimeout(searchTimeout);
              searchTimeout = setTimeout(() => applyFilters(), 800);
          });

          // Filtrage par date
          document.getElementById('filterDate').addEventListener('change', applyFilters);

          // Filtrage par statut
          document.getElementById('filterStatus').addEventListener('change', applyFilters);
      });

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