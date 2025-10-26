document.addEventListener('DOMContentLoaded', () => { 
  const sidebar = document.getElementById('sidebar'); 
  const toggleButton = document.getElementById('toggleSidebar'); 
  const content = document.getElementById('content');
  const recentActivity = document.getElementById('recentActivity');

  if (!sidebar || !toggleButton || !content || !recentActivity) return;

  // Transitions
  sidebar.classList.add('transition-all', 'duration-300', 'overflow-hidden');
  content.classList.add('transition-all', 'duration-300');
  recentActivity.classList.add('transition-all', 'duration-300');

  // Fonction toggle
  const setCollapsed = (collapsed) => {
  if (collapsed) {
    sidebar.classList.remove('w-64');
    sidebar.classList.add('w-0');
    content.classList.remove('pl-64');
    content.classList.add('pl-0');

    // 🟩 Activité récente en plein écran
    recentActivity.classList.remove('max-w-5xl', 'mx-auto');
    recentActivity.classList.add('w-full', 'mx-0');
  } else {
    sidebar.classList.remove('w-0');
    sidebar.classList.add('w-64');
    content.classList.remove('pl-0');
    content.classList.add('pl-64');

    // 🟦 Revenir à la largeur normale
    recentActivity.classList.remove('w-full', 'mx-0');
    recentActivity.classList.add('max-w-5xl', 'mx-auto');
  }
};

  // Bouton toggle
  toggleButton.addEventListener('click', () => {
    const isCollapsed = sidebar.classList.contains('w-0');
    setCollapsed(!isCollapsed);
  });

  // Sidebar cachée par défaut sur mobile
  if (window.innerWidth < 1024) setCollapsed(true);

  // Responsive
  window.addEventListener('resize', () => {
    if (window.innerWidth < 1024) setCollapsed(true);
    else setCollapsed(false);
  });
});
