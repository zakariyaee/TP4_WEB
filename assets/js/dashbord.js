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

             // EXPLICATION : Appel initial de la fonction AJAX.
      // Dès que le contenu de la page est chargé (DOMContentLoaded), on exécute une première fois la fonction
      // pour s'assurer que les données affichées sont les plus récentes, sans attendre le premier intervalle de 3 secondes.
      document.addEventListener('DOMContentLoaded', () => {
        if (typeof Ajax_Dashbord_Statistique === 'function') {
          Ajax_Dashbord_Statistique();
        }
      });