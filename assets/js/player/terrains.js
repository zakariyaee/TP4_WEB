// ============================================
// GESTION DES TERRAINS - AJAX
// ============================================

let currentCategory = '';
let searchTimeout;

// ============================================
// 1. CHARGEMENT DES TERRAINS
// ============================================
function loadTerrains() {
    const search = document.getElementById('searchInput').value;
    const ville = document.getElementById('filterVille').value;
    const disponibilite = document.getElementById('filterDisponibilite').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (ville) params.append('ville', ville);
    if (disponibilite) params.append('disponibilite', disponibilite);
    if (currentCategory) params.append('categorie', currentCategory);
    
    const url = '../../actions/player/load_terrains.php?' + params.toString();
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        displayTerrains(response.terrains);
                        console.log('✅ Terrains chargés:', response.total);
                    } else {
                        showNotification('Erreur lors du chargement', 'error');
                    }
                } catch (e) {
                    console.error('❌ Erreur parsing:', e);
                    showNotification('Erreur de traitement des données', 'error');
                }
            } else {
                showNotification('Erreur de connexion', 'error');
            }
        }
    };
    
    xhr.onerror = function() {
        showNotification('Erreur réseau', 'error');
    };
    
    xhr.send();
}

// ============================================
// 2. AFFICHAGE DES TERRAINS
// ============================================
function displayTerrains(terrainsParCategorie) {
    const container = document.getElementById('terrains-container');
    
    if (!container) return;
    
    let html = '';
    let totalVisible = 0;
    
    const categoryTitles = {
        'Mini Foot': 'Mini Foot',
        'Terrain Moyen': 'Terrains Moyens',
        'Grand Terrain': 'Grands Terrains'
    };
    
    const categoryColors = {
        'Mini Foot': 'bg-blue-100 text-blue-800',
        'Terrain Moyen': 'bg-green-100 text-green-800',
        'Grand Terrain': 'bg-purple-100 text-purple-800'
    };
    
    // Parcourir chaque catégorie
    for (const [categorie, terrains] of Object.entries(terrainsParCategorie)) {
        if (terrains.length === 0) continue;
        
        totalVisible += terrains.length;
        
        html += `
            <div class="mb-12 category-section" data-category="${escapeHtml(categorie)}">
                <div class="flex items-center gap-4 mb-6">
                    <h2 class="text-3xl font-bold text-gray-900">${categoryTitles[categorie]}</h2>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold ${categoryColors[categorie]}">
                        ${terrains.length} terrain${terrains.length > 1 ? 's' : ''}
                    </span>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    ${terrains.map(terrain => buildTerrainCard(terrain)).join('')}
                </div>
            </div>
        `;
    }
    
    // Afficher le résultat
    if (totalVisible === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-search text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">Aucun terrain trouvé</p>
                <p class="text-gray-400 text-sm mt-2">Essayez de modifier vos filtres</p>
            </div>
        `;
    } else {
        container.innerHTML = html;
    }
}

// ============================================
// 3. CONSTRUCTION D'UNE CARTE TERRAIN
// ============================================
function buildTerrainCard(terrain) {
    const isConnected = document.body.dataset.userRole === 'joueur';
    
    const disponibiliteClasses = {
        'disponible': 'bg-green-100 text-green-800',
        'indisponible': 'bg-red-100 text-red-800',
        'maintenance': 'bg-yellow-100 text-yellow-800'
    };
    
    const disponibiliteLabels = {
        'disponible': 'Disponible',
        'indisponible': 'Indisponible',
        'maintenance': 'En maintenance'
    };
    
    const badgeClass = disponibiliteClasses[terrain.disponibilite] || 'bg-gray-100 text-gray-800';
    const badgeLabel = disponibiliteLabels[terrain.disponibilite] || 'Inconnu';
    
    let actionButton = '';
    if (terrain.disponibilite === 'disponible') {
        if (isConnected) {
            actionButton = `
                <a href="reserver.php?id_terrain=${terrain.id_terrain}" 
                   class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-semibold">
                    Réserver
                </a>
            `;
        } else {
            actionButton = `
                <a href="../auth/login.php?redirect=reserver&id_terrain=${terrain.id_terrain}" 
                   class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-semibold">
                    Réserver
                </a>
            `;
        }
    } else {
        actionButton = `
            <span class="px-4 py-2 bg-gray-200 text-gray-500 rounded-lg text-sm font-semibold cursor-not-allowed">
                ${terrain.disponibilite === 'maintenance' ? 'En maintenance' : 'Indisponible'}
            </span>
        `;
    }
    
    return `
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow ${terrain.disponibilite !== 'disponible' ? 'opacity-75' : ''}">
            <div class="relative h-48 bg-gradient-to-br from-emerald-400 to-teal-600">
                ${terrain.image ? 
                    `<img src="../../assets/images/terrains/${escapeHtml(terrain.image)}" 
                         alt="${escapeHtml(terrain.nom_te)}" 
                         class="w-full h-full object-cover">` :
                    `<div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-futbol text-white text-6xl opacity-50"></i>
                    </div>`
                }
                <div class="absolute top-3 right-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/95 backdrop-blur-sm ${badgeClass}">
                        ${badgeLabel}
                    </span>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-3">${escapeHtml(terrain.nom_te)}</h3>
                <div class="space-y-2 mb-4 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-city text-emerald-600 w-4"></i>
                        <span>${escapeHtml(terrain.ville)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-emerald-600 w-4"></i>
                        <span class="truncate">${escapeHtml(terrain.localisation)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-layer-group text-emerald-600 w-4"></i>
                        <span>${escapeHtml(terrain.type)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-expand-arrows-alt text-emerald-600 w-4"></i>
                        <span>${escapeHtml(terrain.taille)}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="text-2xl font-bold ${terrain.disponibilite === 'disponible' ? 'text-emerald-600' : 'text-gray-400'}">
                        ${parseFloat(terrain.prix_heure).toFixed(0)} DH
                        <span class="text-sm text-gray-500 font-normal">/heure</span>
                    </div>
                    ${actionButton}
                </div>
            </div>
        </div>
    `;
}

// ============================================
// 4. FILTRES
// ============================================
function filterByCategory(category) {
    currentCategory = category;
    loadTerrains();
    
    // Mettre à jour les boutons
    document.querySelectorAll('button[onclick^="filterByCategory"]').forEach(btn => {
        btn.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600');
        btn.classList.add('border-gray-300');
    });
    
    event.target.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
    event.target.classList.remove('border-gray-300');
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterVille').value = '';
    document.getElementById('filterDisponibilite').value = '';
    currentCategory = '';
    
    // Réinitialiser les boutons
    document.querySelectorAll('button[onclick^="filterByCategory"]').forEach(btn => {
        btn.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600');
        btn.classList.add('border-gray-300');
    });
    document.getElementById('btn-all').classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
    document.getElementById('btn-all').classList.remove('border-gray-300');
    
    loadTerrains();
}

// ============================================
// 5. NOTIFICATIONS
// ============================================
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg z-50`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 3000);
}

// ============================================
// 6. UTILITAIRES
// ============================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// 7. INITIALISATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏟️ Initialisation de la page terrains');
    
    // Charger les terrains initialement
    loadTerrains();
    
    // Recherche avec debounce
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadTerrains(), 500);
    });
    
    // Filtres
    document.getElementById('filterVille').addEventListener('change', loadTerrains);
    document.getElementById('filterDisponibilite').addEventListener('change', loadTerrains);
    
    // Activer le bouton "Tous" par défaut
    const urlParams = new URLSearchParams(window.location.search);
    const categoryParam = urlParams.get('categorie');
    
    if (categoryParam) {
        currentCategory = categoryParam;
        const buttonMap = {
            'Mini Foot': 'btn-mini',
            'Terrain Moyen': 'btn-moyen',
            'Grand Terrain': 'btn-grand'
        };
        if (buttonMap[categoryParam]) {
            document.getElementById(buttonMap[categoryParam]).click();
        }
    } else {
        document.getElementById('btn-all').classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
        document.getElementById('btn-all').classList.remove('border-gray-300');
    }
});
window.addEventListener('storage', function(event) {
    if (event.key === 'terrains_updated_admin'|| event.key==='terrains_delete' ) {
        console.log('🔄 Mise à jour depuis un autre onglet');
        loadTerrains();

    }
});
// reserve wsfe ila mkhdmx local storage
document.addEventListener('DOMContentLoaded', function() {
    console.log('9adya dyal terrain admin w utlisateurs ');
    loadTerrains();
    setInterval(() => {
        loadTerrains();
    }, 4000);
});
