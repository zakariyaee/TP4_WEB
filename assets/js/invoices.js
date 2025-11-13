// ============================================
// 1. VARIABLES GLOBALES
// ============================================
let facturesData = [];
let filteredData = [];

// ============================================
// 2. CHARGEMENT INITIAL DES FACTURES
// ============================================
function loadFactures() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '../../actions/admin-manager/load_invoices.php', true);
    xhr.withCredentials = true;
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    facturesData = response.factures;
                    filteredData = [...facturesData];
                    
                    updateStats(response.stats);
                    displayFactures(filteredData);
                } else {
                    showNotification(response.message || 'Erreur de chargement', 'error');
                }
            } catch (e) {
                console.error('Erreur parsing:', e);
                showNotification('Erreur de traitement des données', 'error');
            }
        } else {
            showNotification('Erreur de connexion au serveur', 'error');
        }
    };
    
    xhr.onerror = function() {
        showNotification('Erreur réseau', 'error');
    };
    
    xhr.send();
}

// ============================================
// 3. MISE À JOUR DES STATISTIQUES
// ============================================
function updateStats(stats) {
    document.getElementById('stat-total').textContent = stats.total + ' €';
    document.getElementById('stat-payees').textContent = stats.payees + ' €';
    document.getElementById('stat-attente').textContent = stats.attente + ' €';
    document.getElementById('stat-retard').textContent = stats.retard;
}

// ============================================
// 4. AFFICHAGE DES FACTURES
// ============================================
function displayFactures(factures) {
    const tbody = document.getElementById('factures-tbody');
    
    if (!factures || factures.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>Aucune facture trouvée</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = factures.map(f => createFactureRow(f)).join('');
    attachEventListeners();
}

// ============================================
// 5. CRÉATION D'UNE LIGNE DE FACTURE
// ============================================
function createFactureRow(facture) {
    const statut = getStatutBadge(facture.statut_paiement);
    const date = new Date(facture.date_facture).toLocaleDateString('fr-FR');
    
    return `
        <tr class="transition hover:bg-slate-50" data-id="${facture.id_facture}">
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-file-invoice text-green-600"></i>
                    <span class="font-medium text-slate-800">${facture.numero_facture}</span>
                </div>
            </td>
            <td class="px-6 py-4 text-slate-700">${escapeHtml(facture.nom_client)}</td>
            <td class="px-6 py-4 text-slate-600 text-sm">${escapeHtml(facture.email_client)}</td>
            <td class="px-6 py-4 text-slate-700">${escapeHtml(facture.nom_terrain)}</td>
            <td class="px-6 py-4 text-slate-600">#${facture.id_reservation}</td>
            <td class="px-6 py-4 text-slate-600">${date}</td>
            <td class="px-6 py-4">
                <span class="font-semibold text-green-600">${parseFloat(facture.montant_total).toFixed(2)} €</span>
            </td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs font-medium ${statut.class}">
                    ${statut.label}
                </span>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
                    <button class="btn-view p-2 text-blue-600 hover:bg-blue-50 rounded transition" title="Voir">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-download p-2 text-green-600 hover:bg-green-50 rounded transition" title="Télécharger">
                        <i class="fas fa-file-download"></i>
                    </button>
                    <button class="btn-send p-2 text-orange-600 hover:bg-orange-50 rounded transition" title="Envoyer">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>  
            </td>
        </tr>
    `;
}

// ============================================
// 6. BADGE DE STATUT
// ============================================
function getStatutBadge(statut) {
    const badges = {
        'payee': { class: 'badge-payee', label: 'Payée' },
        'attente': { class: 'badge-attente', label: 'En attente' },
        'retard': { class: 'badge-retard', label: 'En retard' }
    };
    return badges[statut] || { class: 'bg-gray-100 text-gray-700', label: statut };
}

// ============================================
// 7. ATTACHER LES ÉVÉNEMENTS
// ============================================
function attachEventListeners() {
    // Boutons Voir
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const id = row.dataset.id;
            showFactureDetails(id);
        });
    });
    
    // Boutons Télécharger
    document.querySelectorAll('.btn-download').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const id = row.dataset.id;
            downloadFacture(id);
        });
    });
    
    // Boutons Envoyer
    document.querySelectorAll('.btn-send').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const id = row.dataset.id;
            sendFacture(id);
        });
    });
}

// ============================================
// 8. AFFICHER LES DÉTAILS
// ============================================
function showFactureDetails(id) {
    const facture = facturesData.find(f => f.id_facture == id);
    if (!facture) return;
    
    const modal = document.getElementById('modal-details');
    const content = document.getElementById('modal-content');
    
    content.innerHTML = `
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Numéro de facture</p>
                    <p class="font-semibold text-slate-800">${facture.numero_facture}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Date</p>
                    <p class="font-semibold text-slate-800">${new Date(facture.date_facture).toLocaleDateString('fr-FR')}</p>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h4 class="font-semibold text-slate-800 mb-3">Client</h4>
                <div class="space-y-2">
                    <p><span class="text-slate-500">Nom:</span> ${escapeHtml(facture.nom_client)}</p>
                    <p><span class="text-slate-500">Email:</span> ${escapeHtml(facture.email_client)}</p>
                    <p><span class="text-slate-500">Équipe:</span> ${escapeHtml(facture.nom_equipe)}</p>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h4 class="font-semibold text-slate-800 mb-3">Réservation</h4>
                <div class="space-y-2">
                    <p><span class="text-slate-500">Terrain:</span> ${escapeHtml(facture.nom_terrain)}</p>
                    <p><span class="text-slate-500">Date:</span> ${new Date(facture.date_reservation).toLocaleDateString('fr-FR')}</p>
                    <p><span class="text-slate-500">Statut:</span> ${facture.statut_reservation}</p>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h4 class="font-semibold text-slate-800 mb-3">Montants</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Terrain:</span>
                        <span class="font-medium">${parseFloat(facture.montant_terrain).toFixed(2)} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Objets:</span>
                        <span class="font-medium">${parseFloat(facture.montant_objets).toFixed(2)} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">TVA (${facture.tva}%):</span>
                        <span class="font-medium">${(parseFloat(facture.montant_total) * parseFloat(facture.tva) / (100 + parseFloat(facture.tva))).toFixed(2)} €</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="font-semibold text-slate-800">Total TTC:</span>
                        <span class="font-bold text-green-600 text-xl">${parseFloat(facture.montant_total).toFixed(2)} €</span>
                    </div>
                </div>
            </div>
            
            ${facture.notes ? `
                <div class="border-t pt-4">
                    <h4 class="font-semibold text-slate-800 mb-2">Notes</h4>
                    <p class="text-slate-600">${escapeHtml(facture.notes)}</p>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.classList.remove('hidden');
}

// ============================================
// 9. TÉLÉCHARGER UNE FACTURE
// ============================================
function downloadFacture(id) {
    showNotification('Génération du PDF en cours...', 'info');
    // Ouvrir dans un nouvel onglet pour télécharger
    window.open(`../../actions/admin-manager/generate_invoice.php?id=${id}`, '_blank');
}

// ============================================
// 10. ENVOYER UNE FACTURE PAR EMAIL
// ============================================
function sendFacture(id) {
    if (!confirm('Envoyer cette facture par email au client ?')) return;
    
    showNotification('Envoi en cours...', 'info');
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../../actions/admin-manager/send_invoice.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.withCredentials = true;
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            showNotification(xhr.responseText, 'success');
            loadFactures();
        } else {
            showNotification('Erreur lors de l\'envoi: ' + xhr.responseText, 'error');
        }
    };
    
    xhr.onerror = function() {
        showNotification('Erreur réseau', 'error');
    };
    
    xhr.send('id=' + id);
}

// ============================================
// 11. RECHERCHE ET FILTRES
// ============================================
document.getElementById('search-input').addEventListener('input', applyFilters);
document.getElementById('filter-statut').addEventListener('change', applyFilters);
document.getElementById('filter-paiement').addEventListener('change', applyFilters);

function applyFilters() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const statut = document.getElementById('filter-statut').value;
    const paiement = document.getElementById('filter-paiement').value;
    
    filteredData = facturesData.filter(f => {
        const matchSearch = !search || 
            f.numero_facture.toLowerCase().includes(search) ||
            f.nom_client.toLowerCase().includes(search) ||
            f.email_client.toLowerCase().includes(search);
        
        const matchStatut = !statut || f.statut === statut;
        const matchPaiement = !paiement || f.statut_paiement === paiement;
        
        return matchSearch && matchStatut && matchPaiement;
    });
    
    displayFactures(filteredData);
}

document.getElementById('btn-reset-filters').addEventListener('click', function() {
    document.getElementById('search-input').value = '';
    document.getElementById('filter-statut').value = '';
    document.getElementById('filter-paiement').value = '';
    applyFilters();
});

// ============================================
// 14. FERMETURE MODALE
// ============================================
document.querySelectorAll('.close-modal').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('modal-details').classList.add('hidden');
    });
});

document.getElementById('modal-details').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// ============================================
// 15. NOTIFICATIONS
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
    
    setTimeout(() => {
        notification.classList.add('hidden');
    }, 4000);
}

// ============================================
// 16. UTILITAIRES
// ============================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// 17. INITIALISATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('📊 Initialisation de la gestion des factures');
    loadFactures();
    
    // Actualisation automatique toutes les 60 secondes
    setInterval(loadFactures, 60000);
});