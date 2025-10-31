// Variables globales
let currentTerrainId = null;
let deleteTerrainId = null;
const dropZone = document.getElementById("dropZone");
const imageFile = document.getElementById("imageFile");
const uploadPreview = document.getElementById("uploadPreview");
const previewContainer = document.getElementById("previewContainer");
const previewImage = document.getElementById("previewImage");
const imageInput = document.getElementById("image");

// Clic sur la zone de dépôt
dropZone.addEventListener("click", () => imageFile.click());

// Drag and drop
dropZone.addEventListener("dragover", (e) => {
  e.preventDefault();
  dropZone.classList.add("border-emerald-500", "bg-emerald-50");
});

dropZone.addEventListener("dragleave", () => {
  dropZone.classList.remove("border-emerald-500", "bg-emerald-50");
});

dropZone.addEventListener("drop", (e) => {
  e.preventDefault();
  dropZone.classList.remove("border-emerald-500", "bg-emerald-50");

  const files = e.dataTransfer.files;
  if (files.length > 0) {
    handleFileSelect(files[0]);
  }
});

// Changement de fichier
imageFile.addEventListener("change", (e) => {
  if (e.target.files.length > 0) {
    handleFileSelect(e.target.files[0]);
  }
});

// Traiter le fichier sélectionné
function handleFileSelect(file) {
  // Validation du type
  if (!file.type.startsWith("image/")) {
    alert("Veuillez sélectionner une image valide");
    return;
  }

  // Validation de la taille (5MB)
  if (file.size > 5 * 1024 * 1024) {
    alert("La taille de l'image ne doit pas dépasser 5MB");
    return;
  }

  // Lire le fichier
  const reader = new FileReader();
  reader.onload = (e) => {
    const base64 = e.target.result;
    imageInput.value = base64;
    previewImage.src = base64;
    uploadPreview.classList.add("hidden");
    previewContainer.classList.remove("hidden");
  };
  reader.readAsDataURL(file);
}

// Effacer l'image
function clearImage() {
  imageInput.value = "";
  imageFile.value = "";
  previewImage.src = "";
  uploadPreview.classList.remove("hidden");
  previewContainer.classList.add("hidden");
}

// Initialisation au chargement de la page
document.addEventListener("DOMContentLoaded", function () {
  loadTerrains();
  loadResponsables();
  setupEventListeners();
});

// Configuration des écouteurs d'événements
function setupEventListeners() {
  // Recherche en temps réel
  document
    .getElementById("searchInput")
    .addEventListener("input", debounce(loadTerrains, 500));

  // Filtres
  document
    .getElementById("filterCategorie")
    .addEventListener("change", loadTerrains);
  document
    .getElementById("filterDisponibilite")
    .addEventListener("change", loadTerrains);
  document
    .getElementById("filterResponsable")
    .addEventListener("change", loadTerrains);

  // Formulaire
  document
    .getElementById("terrainForm")
    .addEventListener("submit", handleSubmit);
}

// Fonction debounce pour la recherche
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

// Charger tous les terrains
function loadTerrains() {
  const search = document.getElementById("searchInput").value;
  const categorie = document.getElementById("filterCategorie").value;
  const disponibilite = document.getElementById("filterDisponibilite").value;
  const responsable = document.getElementById("filterResponsable").value;

  showLoader();

  const xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    `../../actions/admin-manager/stade/get_stades.php?search=${encodeURIComponent(
      search
    )}&categorie=${categorie}&disponibilite=${disponibilite}&responsable=${responsable}`,
    true
  );

  xhr.onload = function () {
    hideLoader();

    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);

        if (response.success) {
          displayTerrains(response.terrains);
        } else {
          showNotification(
            response.message || "Erreur lors du chargement des terrains",
            "error"
          );
        }
      } catch (e) {
        showNotification("Erreur lors du traitement de la réponse", "error");
      }
    } else {
      showNotification("Erreur de connexion au serveur", "error");
    }
  };

  xhr.onerror = function () {
    hideLoader();
    showNotification("Erreur réseau", "error");
  };

  xhr.send();
}

// Afficher les terrains
function displayTerrains(terrains) {
  const container = document.getElementById("terrainsContainer");

  if (terrains.length === 0) {
    container.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-search text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">Aucun terrain trouvé</p>
            </div>
        `;
    return;
  }

  container.innerHTML = terrains
    .map(
      (terrain) => `
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
            <div class="relative h-48 bg-gradient-to-br from-emerald-400 to-teal-600">
                ${
                  terrain.image
                    ? `
                    <img src="../../assets/images/terrains/${terrain.image}" alt="${terrain.nom_te}" class="w-full h-full object-cover">
                `
                    : `
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-futbol text-white text-6xl opacity-50"></i>
                    </div>
                `
                }
                <div class="absolute top-3 right-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${getDisponibiliteClass(
                      terrain.disponibilite
                    )}">
                        ${getDisponibiliteLabel(terrain.disponibilite)}
                    </span>
                </div>
                <div class="absolute top-3 left-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white text-gray-800">
                        ${terrain.categorie}
                    </span>
                </div>
            </div>
            
            <div class="p-5">
                <h3 class="text-xl font-bold text-gray-800 mb-2">${
                  terrain.nom_te
                }</h3>
                
                <div class="space-y-2 mb-4 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-emerald-600 w-4"></i>
                        <span class="truncate">${terrain.localisation}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-layer-group text-emerald-600 w-4"></i>
                        <span>${terrain.type}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-expand-arrows-alt text-emerald-600 w-4"></i>
                        <span>${terrain.taille}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user-tie text-emerald-600 w-4"></i>
                        <span>${terrain.responsable_nom || "Non assigné"}</span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="text-2xl font-bold text-emerald-600">
                        ${
                          terrain.prix_heure
                        } DH<span class="text-sm text-gray-500 font-normal">/h</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editTerrain(${
                          terrain.id_terrain
                        })" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="openDeleteModal(${
                          terrain.id_terrain
                        })" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `
    )
    .join("");
}

// Charger les responsables
function loadResponsables() {
  const xhr = new XMLHttpRequest();
  xhr.open("GET", "../../actions/admin-manager/stade/get_managers.php", true);

  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);

        if (response.success) {
          populateResponsableSelects(response.responsables);
        }
      } catch (e) {
        console.error("Erreur lors du chargement des responsables");
      }
    }
  };

  xhr.send();
}

// Remplir les listes déroulantes des responsables
function populateResponsableSelects(responsables) {
  const modalSelect = document.getElementById("id_responsable");
  const filterSelect = document.getElementById("filterResponsable");

  // Modal select
  modalSelect.innerHTML =
    '<option value="">Sélectionner un responsable...</option>' +
    responsables
      .map((r) => `<option value="${r.email}">${r.nom} ${r.prenom}</option>`)
      .join("");

  // Filter select
  filterSelect.innerHTML =
    '<option value="">Tous</option>' +
    responsables
      .map((r) => `<option value="${r.email}">${r.nom} ${r.prenom}</option>`)
      .join("");
}

// Ouvrir le modal d'ajout
function openAddModal() {
  currentTerrainId = null;
  document.getElementById("modalTitle").textContent = "Ajouter un terrain";
  document.getElementById("terrainForm").reset();
  document.getElementById("terrainId").value = "";

  // Réinitialiser complètement l'affichage de l'image
  clearImage();

  document.getElementById("terrainModal").classList.remove("hidden");
}

// Modifier un terrain
function editTerrain(id) {
  currentTerrainId = id;

  const xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    `../../actions/admin-manager/stade/get_stade.php?id=${id}`,
    true
  );

  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);

        if (response.success) {
          const terrain = response.terrain;

          document.getElementById("modalTitle").textContent =
            "Modifier le terrain";
          document.getElementById("terrainId").value = terrain.id_terrain;
          document.getElementById("nom_te").value = terrain.nom_te;
          document.getElementById("categorie").value = terrain.categorie;
          document.getElementById("type").value = terrain.type;
          document.getElementById("taille").value = terrain.taille;
          document.getElementById("prix_heure").value = terrain.prix_heure;
          document.getElementById("disponibilite").value =
            terrain.disponibilite;
          document.getElementById("localisation").value = terrain.localisation;
          document.getElementById("id_responsable").value =
            terrain.id_responsable || "";

          // Gérer l'affichage de l'image existante
          if (terrain.image) {
            // Afficher l'image existante
            const imagePath = `../../assets/images/terrains/${terrain.image}`;
            document.getElementById("image").value = terrain.image;
            document.getElementById("previewImage").src = imagePath;
            document.getElementById("uploadPreview").classList.add("hidden");
            document
              .getElementById("previewContainer")
              .classList.remove("hidden");
          } else {
            // Pas d'image, réinitialiser l'affichage
            clearImage();
          }

          document.getElementById("terrainModal").classList.remove("hidden");
        } else {
          showNotification(
            response.message || "Erreur lors du chargement du terrain",
            "error"
          );
        }
      } catch (e) {
        showNotification("Erreur lors du traitement de la réponse", "error");
      }
    }
  };

  xhr.send();
}

// Gérer la soumission du formulaire
function handleSubmit(e) {
  e.preventDefault();

  const submitBtn = document.getElementById("submitBtn");
  submitBtn.disabled = true;
  submitBtn.innerHTML =
    '<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...';

  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData);

  const xhr = new XMLHttpRequest();
  xhr.open(
    "POST",
    currentTerrainId
      ? "../../actions/admin-manager/stade/edit_stade.php"
      : "../../actions/admin-manager/stade/add_stade.php",
    true
  );
  xhr.setRequestHeader("Content-Type", "application/json");

  xhr.onload = function () {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer';

    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);

        if (response.success) {
          showNotification(response.message, "success");
          closeModal();
          loadTerrains();
        } else {
          showNotification(
            response.message || "Erreur lors de l'enregistrement",
            "error"
          );
        }
      } catch (e) {
        showNotification("Erreur lors du traitement de la réponse", "error");
      }
    } else {
      showNotification("Erreur de connexion au serveur", "error");
    }
  };

  xhr.onerror = function () {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer';
    showNotification("Erreur réseau", "error");
  };

  xhr.send(JSON.stringify(data));
}

// Ouvrir le modal de suppression
function openDeleteModal(id) {
  deleteTerrainId = id;
  document.getElementById("deleteModal").classList.remove("hidden");
}

// Fermer le modal de suppression
function closeDeleteModal() {
  deleteTerrainId = null;
  document.getElementById("deleteModal").classList.add("hidden");
}

// Confirmer la suppression
function confirmDelete() {
  if (!deleteTerrainId) return;

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "../../actions/admin-manager/stade/delete_stade.php", true);
  xhr.setRequestHeader("Content-Type", "application/json");

  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);

        if (response.success) {
          showNotification(response.message, "success");
          closeDeleteModal();
          loadTerrains();
        } else {
          showNotification(
            response.message || "Erreur lors de la suppression",
            "error"
          );
        }
      } catch (e) {
        showNotification("Erreur lors du traitement de la réponse", "error");
      }
    }
  };

  xhr.send(
    JSON.stringify({
      id_terrain: deleteTerrainId,
    })
  );
}

// Fermer le modal principal
function closeModal() {
  document.getElementById("terrainModal").classList.add("hidden");
  document.getElementById("terrainForm").reset();
  currentTerrainId = null;
}

// Afficher/masquer le loader
function showLoader() {
  document.getElementById("loader").classList.remove("hidden");
}

function hideLoader() {
  document.getElementById("loader").classList.add("hidden");
}

// Afficher une notification
function showNotification(message, type = "info") {
  const notification = document.getElementById("notification");

  const colors = {
    success: "bg-green-500",
    error: "bg-red-500",
    info: "bg-blue-500",
    warning: "bg-yellow-500",
  };

  const icons = {
    success: "fa-check-circle",
    error: "fa-exclamation-circle",
    info: "fa-info-circle",
    warning: "fa-exclamation-triangle",
  };

  notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 text-white ${colors[type]}`;
  notification.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas ${icons[type]} text-xl"></i>
            <span>${message}</span>
        </div>
    `;

  notification.classList.remove("hidden");

  setTimeout(() => {
    notification.classList.add("hidden");
  }, 4000);
}

// Fonctions utilitaires
function getDisponibiliteClass(disponibilite) {
  const classes = {
    disponible: "bg-green-500 text-white",
    indisponible: "bg-red-500 text-white",
    maintenance: "bg-yellow-500 text-white",
  };
  return classes[disponibilite] || "bg-gray-500 text-white";
}

function getDisponibiliteLabel(disponibilite) {
  const labels = {
    disponible: "Disponible",
    indisponible: "Indisponible",
    maintenance: "Maintenance",
  };
  return labels[disponibilite] || disponibilite;
}
