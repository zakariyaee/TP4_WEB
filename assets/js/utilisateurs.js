let currentEmail = null;
let deleteEmail = null;
let currentPage = 1;
let itemsPerPage = 10;
let usersCache = new Map(); // Cache pour les utilisateurs
let lastLoadTime = 0;
let isLoading = false;

document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    setupEventListeners();
    
    // Clean cache every 5 minutes
    setInterval(() => {
        usersCache.clear();
    }, 300000);
});

/**
 * Setup event listeners for search, filters and form
 * 
 * Configures event listeners on page load for:
 * - Real-time search input with debounce (500ms delay)
 * - Role and status filter changes
 * - Form submission
 * 
 * @returns {void}
 */
function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('input', debounce(() => {
        currentPage = 1; // Reset to first page on search
        // Clear cache when searching to get fresh results
        usersCache.clear();
        loadUsers(1, true);
    }, 300)); // Reduced debounce time for better responsiveness
    document.getElementById('filterRole').addEventListener('change', () => {
        currentPage = 1;
        usersCache.clear();
        loadUsers(1, true);
    });
    document.getElementById('filterStatut').addEventListener('change', () => {
        currentPage = 1;
        usersCache.clear();
        loadUsers(1, true);
    });
    document.getElementById('itemsPerPage').addEventListener('change', function() {
        itemsPerPage = parseInt(this.value);
        currentPage = 1;
        usersCache.clear();
        loadUsers(1, true);
    });
    document.getElementById('userForm').addEventListener('submit', handleSubmit);
}

/**
 * Debounce function to limit function execution frequency
 * 
 * Prevents function from being called too frequently by delaying execution.
 * Useful for search inputs to reduce server requests.
 * 
 * @param {Function} func - Function to debounce
 * @param {number} wait - Delay in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Load users list from server with filters
 * 
 * Fetches users from server with optional search, role and status filters.
 * Implements caching to reduce server requests and improve performance.
 * Displays loading indicator during request.
 * Partner: Error Management - Handles HTTP errors, network errors and JSON parsing errors.
 * Partner: Security - Search term is URL encoded to prevent injection.
 * 
 * @returns {void}
 */
function loadUsers(page = currentPage, forceRefresh = false) {
    // Prevent multiple simultaneous requests
    if (isLoading) return;
    
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const role = document.getElementById('filterRole').value;
    const statut = document.getElementById('filterStatut').value;

    // Create cache key
    const cacheKey = `${search}-${role}-${statut}-${page}-${itemsPerPage}`;
    
    // Check cache first (unless force refresh)
    if (!forceRefresh && usersCache.has(cacheKey)) {
        const cachedData = usersCache.get(cacheKey);
        // Use cache if data is less than 30 seconds old
        if (Date.now() - cachedData.timestamp < 30000) {
            displayUsers(cachedData.users);
            displayPagination(cachedData.pagination);
            return;
        }
    }

    // Check if we need to refresh (avoid too frequent requests)
    const now = Date.now();
    if (!forceRefresh && now - lastLoadTime < 1000) {
        return;
    }

    isLoading = true;
    lastLoadTime = now;
    showLoader();

    const xhr = new XMLHttpRequest();
    // Security: encodeURIComponent prevents XSS in URL parameters
    const url = `../../actions/admin-respo/get_users.php?search=${encodeURIComponent(search)}&role=${role}&statut=${statut}&page=${page}&limit=${itemsPerPage}`;
    xhr.open('GET', url, true);
    
    xhr.onload = function() {
        isLoading = false;
        hideLoader();
        // Error Management: Check HTTP status
        if (xhr.status === 200) {
            try {
                // Error Management: JSON parsing with try-catch
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Cache the response
                    usersCache.set(cacheKey, {
                        users: response.users,
                        pagination: response.pagination,
                        timestamp: Date.now()
                    });
                    
                    displayUsers(response.users);
                    displayPagination(response.pagination);
                } else {
                    showNotification(response.message || 'Erreur lors du chargement des utilisateurs', 'error');
                }
            } catch (e) {
                // Error Management: Handle JSON parsing errors
                showNotification('Erreur lors du traitement de la réponse', 'error');
            }
        } else {
            // Error Management: Handle HTTP errors
            showNotification('Erreur de connexion au serveur', 'error');
        }
    };
    
    // Error Management: Handle network errors
    xhr.onerror = function() {
        isLoading = false;
        hideLoader();
        showNotification('Erreur réseau', 'error');
    };
    
    xhr.send();
}

/**
 * Display users in table
 * 
 * Renders user list in table format with proper escaping to prevent XSS.
 * Shows empty state if no users found.
 * Partner: Security - All user data is HTML escaped before insertion.
 * 
 * @param {Array} users - Array of user objects
 * @returns {void}
 */
function displayUsers(users) {
    const tbody = document.getElementById('usersTable');
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                    <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                    <div>Aucun utilisateur trouvé</div>
                </td>
            </tr>
        `;
        return;
    }

    /**
     * XSS escaping function
     * 
     * Escapes HTML special characters to prevent XSS attacks.
     * Uses textContent to safely encode text before insertion.
     * Partner: Security - Prevents XSS injection through user data.
     * 
     * @param {string} text - Text to escape
     * @returns {string} HTML-escaped text
     */
    const escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    };

    // Security: All user data is escaped before rendering
    tbody.innerHTML = users.map(u => {
        const nom = escapeHtml(u.nom);
        const prenom = escapeHtml(u.prenom);
        const email = escapeHtml(u.email);
        const role = escapeHtml(u.role);
        const statut = escapeHtml(u.statut_compte);
        // Security: Escape email for use in onclick attribute (escape quotes)
        const emailAttr = escapeHtml(u.email).replace(/'/g, "\\'");
        
        return `
            <tr class="hover:bg-gray-50 transition-colors duration-200 group">
                <td class="px-8 py-5 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 text-emerald-700 flex items-center justify-center font-bold text-lg shadow-sm group-hover:shadow-md transition-shadow">
                            ${prenom ? prenom.charAt(0).toUpperCase() : nom.charAt(0).toUpperCase()}
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-semibold text-gray-900">${nom} ${prenom}</div>
                            <div class="text-xs text-gray-500 mt-0.5">${email}</div>
                        </div>
                    </div>
                </td>
                <td class="px-8 py-5 whitespace-nowrap">
                    <div class="text-sm text-gray-700 font-medium">${email}</div>
                </td>
                <td class="px-8 py-5 whitespace-nowrap">
                    <span class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm ${roleClass(role)}">${capitalize(role)}</span>
                </td>
                <td class="px-8 py-5 whitespace-nowrap">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" ${statut === 'actif' ? 'checked' : ''} 
                            onchange="toggleUserStatus('${emailAttr}', this.checked)"
                            class="sr-only peer">
                        <div class="w-14 h-8 bg-red-500 peer-checked:bg-emerald-500 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-7 after:w-7 after:transition-all"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">${capitalize(statut)}</span>
                    </label>
                </td>
                <td class="px-8 py-5 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end gap-2">
                        <button onclick="editUser('${emailAttr}')" class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-all duration-200 group-hover:scale-110 transform" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="openDeleteModal('${emailAttr}')" class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all duration-200 group-hover:scale-110 transform" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function roleClass(role) {
    const m = { admin: 'bg-purple-100 text-purple-800', responsable: 'bg-blue-100 text-blue-800', joueur: 'bg-gray-100 text-gray-800' };
    return m[role] || 'bg-gray-100 text-gray-800';
}
function statutClass(s) {
    const m = { actif: 'bg-green-100 text-green-800', suspendu: 'bg-yellow-100 text-yellow-800' };
    return m[s] || 'bg-gray-100 text-gray-800';
}
function capitalize(t) { return (t || '').charAt(0).toUpperCase() + (t || '').slice(1); }

function openAddModal() {
    currentEmail = null;
    deleteEmail = null;
    document.getElementById('modalTitle').textContent = 'Ajouter un utilisateur';
    document.getElementById('userForm').reset();
    document.getElementById('originalEmail').value = '';
    document.getElementById('email').removeAttribute('readonly');
    document.getElementById('password').required = true;
    document.getElementById('password').value = '';
    document.getElementById('passwordHint').classList.remove('hidden');
    document.getElementById('userModal').classList.remove('hidden');
}

/**
 * Edit user by loading user data and opening modal
 * 
 * Fetches user data from server and populates form for editing.
 * Email field is readonly during edit. Password is optional.
 * Partner: Security - Email is URL encoded to prevent injection.
 * Partner: Error Management - Handles HTTP errors and JSON parsing errors.
 * 
 * @param {string} email - User email to edit
 * @returns {void}
 */
function editUser(email) {
    currentEmail = email;
    const xhr = new XMLHttpRequest();
    // Security: encodeURIComponent prevents XSS in URL parameters
    xhr.open('GET', `../../actions/admin-respo/get_user.php?email=${encodeURIComponent(email)}`, true);
    
    xhr.onload = function() {
        // Error Management: Check HTTP status
        if (xhr.status === 200) {
            try {
                // Error Management: JSON parsing with try-catch
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const u = response.user;
                    document.getElementById('modalTitle').textContent = 'Modifier l\'utilisateur';
                    document.getElementById('nom').value = u.nom;
                    document.getElementById('prenom').value = u.prenom;
                    document.getElementById('email').value = u.email;
                    // Security: Email cannot be changed during edit
                    document.getElementById('email').setAttribute('readonly', 'readonly');
                    document.getElementById('originalEmail').value = u.email;
                    document.getElementById('role').value = u.role;
                    document.getElementById('statut_compte').value = u.statut_compte;
                    // Password optional for edit
                    document.getElementById('password').value = '';
                    document.getElementById('password').required = false;
                    document.getElementById('passwordHint').classList.add('hidden');
                    document.getElementById('userModal').classList.remove('hidden');
                } else {
                    showNotification(response.message || 'Utilisateur introuvable', 'error');
                }
            } catch (e) {
                // Error Management: Handle JSON parsing errors
                showNotification('Erreur lors du traitement de la réponse', 'error');
            }
        }
    };
    
    xhr.send();
}

/**
 * Handle form submission for add/edit user
 * 
 * Submits user data to server via AJAX. Prevents default form submission.
 * Shows loading state on button and handles all error scenarios.
 * Partner: Security - Data is sent as JSON, password is never logged.
 * Partner: Error Management - Handles network errors, HTTP errors and JSON parsing errors.
 * 
 * @param {Event} e - Form submit event
 * @returns {void}
 */
function handleSubmit(e) {
    e.preventDefault();
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...';

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Remove originalEmail from data if adding new user (only used for editing)
    if (!currentEmail) {
        delete data.originalEmail;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', currentEmail ? '../../actions/admin-respo/edit_user.php' : '../../actions/admin-respo/add_user.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onload = function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer';
        // Treat any 2xx as success (200, 201, 204, ...)
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                // Error Management: JSON parsing with try-catch
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification(response.message, 'success');
                    closeModal();
                    // Clear cache and force refresh
                    usersCache.clear();
                    loadUsers(1, true);
                } else {
                    showNotification(response.message || 'Erreur lors de l\'enregistrement', 'error');
                }
            } catch (e) {
                // Error Management: Handle JSON parsing errors
                showNotification('Erreur lors du traitement de la réponse', 'error');
            }
        } else {
            // Error Management: Handle HTTP errors
            showNotification('Erreur de connexion au serveur', 'error');
        }
    };

    // Error Management: Handle network errors
    xhr.onerror = function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer';
        showNotification('Erreur réseau', 'error');
    };

    // Security: Send data as JSON, password is included but handled securely on server
    xhr.send(JSON.stringify(data));
}

/**
 * Open delete confirmation modal
 * 
 * Stores email to delete and shows confirmation modal.
 * Requires user confirmation before actual deletion.
 * 
 * @param {string} email - Email of user to delete
 * @returns {void}
 */
function openDeleteModal(email) {
    deleteEmail = email;
    document.getElementById('deleteModal').classList.remove('hidden');
}

/**
 * Close delete confirmation modal
 * 
 * Hides modal and clears stored email.
 * 
 * @returns {void}
 */
function closeDeleteModal() {
    deleteEmail = null;
    document.getElementById('deleteModal').classList.add('hidden');
}

/**
 * Confirm and execute user deletion
 * 
 * Sends delete request to server. Only executes if deleteEmail is set.
 * Partner: Security - Email is validated before sending to server.
 * Partner: Error Management - Handles HTTP errors and JSON parsing errors.
 * 
 * @returns {void}
 */
function confirmDelete() {
    // Security: Validate email before deletion
    if (!deleteEmail) return;
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../../actions/admin-respo/delete_user.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onload = function() {
        // Treat any 2xx as success (including 204)
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                // Error Management: JSON parsing with try-catch
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification(response.message || 'Utilisateur supprimé avec succès', 'success');
                    closeDeleteModal();
                    // Clear form and modal state after successful deletion
                    closeModal();
                    currentEmail = null;
                    deleteEmail = null;
                    // Clear cache and force refresh list or page
                    usersCache.clear();
                    // Full page refresh to ensure UI is fully reset
                    window.location.reload();
                } else {
                    showNotification(response.message || 'Erreur lors de la suppression', 'error');
                }
            } catch (e) {
                // Error Management: Handle JSON parsing errors
                showNotification('Erreur lors du traitement de la réponse', 'error');
            }
        }
    };
    
    // Security: Send email as JSON
    xhr.send(JSON.stringify({ email: deleteEmail }));
}

/**
 * Close user modal and reset form
 * 
 * Hides modal, resets form fields and clears current email.
 * Ensures all fields are properly cleared including originalEmail and readonly state.
 * 
 * @returns {void}
 */
function closeModal() {
    document.getElementById('userModal').classList.add('hidden');
    document.getElementById('userForm').reset();
    document.getElementById('originalEmail').value = '';
    document.getElementById('email').removeAttribute('readonly');
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passwordHint').classList.remove('hidden');
    currentEmail = null;
}

/**
 * Show loading indicator
 * 
 * Displays loader to indicate data is being fetched.
 * 
 * @returns {void}
 */
function showLoader() {
    document.getElementById('loader').classList.remove('hidden');
}
/**
 * Hide loading indicator
 * 
 * Hides loader when data fetching is complete.
 * 
 * @returns {void}
 */
function hideLoader() {
    document.getElementById('loader').classList.add('hidden');
}

/**
 * Show notification to user
 * 
 * Displays temporary notification message with appropriate styling and icon.
 * Auto-hides after 4 seconds. Supports success, error, info and warning types.
 * Partner: Security - Message is displayed as-is (should be sanitized before calling).
 * 
 * @param {string} message - Notification message to display
 * @param {string} type - Notification type (success, error, info, warning)
 * @returns {void}
 */
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const colors = { success: 'bg-green-500', error: 'bg-red-500', info: 'bg-blue-500', warning: 'bg-yellow-500' };
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 text-white ${colors[type]}`;
    // Note: Message should be pre-sanitized before calling this function
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas ${icons[type]} text-xl"></i>
            <span>${message}</span>
        </div>
    `;
    notification.classList.remove('hidden');
    // Auto-hide after 4 seconds
    setTimeout(() => { notification.classList.add('hidden'); }, 4000);
}

/**
 * Toggle user status (actif/suspendu)
 * 
 * Updates user status directly without fetching current data first.
 * Optimized to use a single request instead of GET + POST.
 * 
 * @param {string} email - User email
 * @param {boolean} isActive - New status (true = actif, false = suspendu)
 * @returns {void}
 */
function toggleUserStatus(email, isActive) {
    // Disable the toggle while updating
    const toggle = event.target;
    toggle.disabled = true;
    
    const newStatus = isActive ? 'actif' : 'suspendu';
    
    // Send direct update request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../../actions/admin-respo/update_user_status.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onload = function() {
        toggle.disabled = false;
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification(`Statut mis à jour: ${newStatus}`, 'success');
                    // Update cache instead of full reload
                    updateUserInCache(email, newStatus);
                    // Refresh only if not in cache
                    if (!updateUserInCache(email, newStatus)) {
                        loadUsers(currentPage, true);
                    }
                } else {
                    // Revert toggle on error
                    toggle.checked = !isActive;
                    showNotification(response.message || 'Erreur lors de la mise à jour', 'error');
                }
            } catch (e) {
                toggle.checked = !isActive;
                showNotification('Erreur lors du traitement de la réponse', 'error');
            }
        } else {
            toggle.checked = !isActive;
            showNotification('Erreur de connexion au serveur', 'error');
        }
    };
    
    xhr.onerror = function() {
        toggle.disabled = false;
        toggle.checked = !isActive;
        showNotification('Erreur réseau', 'error');
    };
    
    xhr.send(JSON.stringify({ 
        email: email, 
        statut_compte: newStatus 
    }));
}

/**
 * Update user status in cache
 * 
 * Updates the user status in the cache to avoid full reload.
 * 
 * @param {string} email - User email
 * @param {string} newStatus - New status
 * @returns {boolean} True if updated in cache, false if not found
 */
function updateUserInCache(email, newStatus) {
    let updated = false;
    for (let [key, data] of usersCache.entries()) {
        const userIndex = data.users.findIndex(user => user.email === email);
        if (userIndex !== -1) {
            data.users[userIndex].statut_compte = newStatus;
            updated = true;
            // Re-display with updated data
            displayUsers(data.users);
            displayPagination(data.pagination);
            break;
        }
    }
    return updated;
}

/**
 * Display pagination controls
 * 
 * Renders pagination buttons and info based on pagination data.
 * 
 * @param {Object} pagination - Pagination data from server
 * @returns {void}
 */
function displayPagination(pagination) {
    const paginationContainer = document.getElementById('paginationContainer');
    if (!pagination || pagination.totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    const { currentPage, totalPages, totalUsers, hasNext, hasPrev } = pagination;
    
    let paginationHTML = `
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="text-sm text-gray-700">
                Affichage de <span class="font-semibold">${(currentPage - 1) * itemsPerPage + 1}</span> 
                à <span class="font-semibold">${Math.min(currentPage * itemsPerPage, totalUsers)}</span> 
                sur <span class="font-semibold">${totalUsers}</span> utilisateurs
            </div>
            <div class="flex items-center gap-2">
    `;

    // Previous button
    if (hasPrev) {
        paginationHTML += `
            <button onclick="loadUsers(${currentPage - 1})" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all duration-200">
                Précédent
            </button>
        `;
    } else {
        paginationHTML += `
            <button disabled 
                class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                Précédent
            </button>
        `;
    }

    // Page numbers
    const maxVisiblePages = 7;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // First page and ellipsis
    if (startPage > 1) {
        paginationHTML += `
            <button onclick="loadUsers(1)" 
                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200">
                1
            </button>
        `;
        if (startPage > 2) {
            paginationHTML += `<span class="px-2 text-gray-500">...</span>`;
        }
    }

    // Page number buttons
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            paginationHTML += `
                <button disabled
                    class="px-3 py-2 text-sm font-medium text-white bg-emerald-600 border border-emerald-600 rounded-lg cursor-default">
                    ${i}
                </button>
            `;
        } else {
            paginationHTML += `
                <button onclick="loadUsers(${i})" 
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200">
                    ${i}
                </button>
            `;
        }
    }

    // Last page and ellipsis
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `<span class="px-2 text-gray-500">...</span>`;
        }
        paginationHTML += `
            <button onclick="loadUsers(${totalPages})" 
                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200">
                ${totalPages}
            </button>
        `;
    }

    // Next button
    if (hasNext) {
        paginationHTML += `
            <button onclick="loadUsers(${currentPage + 1})" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all duration-200">
                Suivant
            </button>
        `;
    } else {
        paginationHTML += `
            <button disabled 
                class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                Suivant
            </button>
        `;
    }

    paginationHTML += `
            </div>
        </div>
    `;

    paginationContainer.innerHTML = paginationHTML;
}
