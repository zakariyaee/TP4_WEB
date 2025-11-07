// Variables globales
let currentTerrainId = null;
let deleteTerrainId = null;
const dropZone = document.getElementById("dropZone");
const imageFile = document.getElementById("imageFile");
const uploadPreview = document.getElementById("uploadPreview");
const previewContainer = document.getElementById("previewContainer");
const previewImage = document.getElementById("previewImage");
const imageInput = document.getElementById("image");
const villeInput = document.getElementById("ville");
const filterVilleInput = document.getElementById("filterVille");
const modalResponsableSelect = document.getElementById("id_responsable");
const filterResponsableSelect = document.getElementById("filterResponsable");

let allResponsables = [];

function normalizeVille(value) {
  return (value || "")
    .toString()
    .trim()
    .toLocaleLowerCase("fr-FR");
}

// Écouter les changements dans d'autres onglets
window.addEventListener("storage", function (e) {
  if (e.key === "terrains_updated") {
    // Un autre onglet a modifié les terrains
    console.log("Mise à jour détectée depuis un autre onglet");
    loadTerrains();

    // Afficher une notification discrète
    showNotification("Les données ont été mises à jour", "success");

    // Nettoyer le flag après utilisation
    localStorage.removeItem("terrains_updated");
  }
});

// Notifier les autres onglets après chaque modification
function notifyOtherTabs() {
  localStorage.setItem("terrains_updated", Date.now().toString());
}

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
  setupEventListeners();
  fetchAllResponsables()
    .then(() => {
      handleVilleChange();
      handleFilterVilleChange();
    })
    .catch((error) => {
      console.error(error);
    });
});

// Configuration des écouteurs d'événements
function setupEventListeners() {
  // Recherche en temps réel
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("input", debounce(loadTerrains, 500));
  }

  // Filtres
  const filterCategorie = document.getElementById("filterCategorie");
  if (filterCategorie) {
    filterCategorie.addEventListener("change", loadTerrains);
  }

  const filterDisponibilite = document.getElementById("filterDisponibilite");
  if (filterDisponibilite) {
    filterDisponibilite.addEventListener("change", loadTerrains);
  }

  if (filterResponsableSelect) {
    filterResponsableSelect.addEventListener("change", loadTerrains);
  }

  if (filterVilleInput) {
    const handler = () => {
      handleFilterVilleChange();
      loadTerrains();
    };
    filterVilleInput.addEventListener("input", handler);
    filterVilleInput.addEventListener("change", handler);
  }

  if (villeInput) {
    const handler = () => handleVilleChange();
    villeInput.addEventListener("input", handler);
    villeInput.addEventListener("change", handler);
  }

  // Formulaire
  const terrainForm = document.getElementById("terrainForm");
  if (terrainForm) {
    terrainForm.addEventListener("submit", handleSubmit);
  }
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
  const categorie = document.getElementById("filterCategorie")?.value || "";
  const disponibilite =
    document.getElementById("filterDisponibilite")?.value || "";
  const responsable = filterResponsableSelect ? filterResponsableSelect.value : "";
  const ville = filterVilleInput ? filterVilleInput.value : "";

  showLoader();

  const xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    `../../actions/admin-manager/stade/get_stades.php?search=${encodeURIComponent(
      search
    )}&categorie=${encodeURIComponent(
      categorie
    )}&disponibilite=${encodeURIComponent(
      disponibilite
    )}&responsable=${encodeURIComponent(
      responsable
    )}&ville=${encodeURIComponent(ville)}`,
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
                        <i class="fas fa-city text-emerald-600 w-4"></i>
                        <span>${terrain.ville || "Non renseignée"}</span>
                    </div>
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

// Charger tous les responsables disponibles
function fetchAllResponsables() {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "../../actions/admin-manager/stade/get_managers.php", true);

    xhr.onload = function () {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);

          if (response.success) {
            allResponsables = Array.isArray(response.responsables)
              ? response.responsables
              : [];
            updateFilterResponsableOptions();
            filterFormResponsablesByVille(villeInput ? villeInput.value : "");
            resolve(allResponsables);
          } else {
            reject(response.message || "Erreur lors du chargement des responsables");
          }
        } catch (e) {
          reject("Erreur lors du traitement de la réponse des responsables");
        }
      } else {
        reject("Erreur de connexion au serveur lors du chargement des responsables");
      }
    };

    xhr.onerror = function () {
      reject("Erreur réseau lors du chargement des responsables");
    };

    xhr.send();
  });
}

function updateFilterResponsableOptions() {
  if (!filterResponsableSelect) return;

  const selectedVille = normalizeVille(filterVilleInput ? filterVilleInput.value : "");

  const responsables = !selectedVille
    ? allResponsables
    : allResponsables.filter((r) => normalizeVille(r.ville) === selectedVille);

  const currentValue = filterResponsableSelect.value;

  filterResponsableSelect.innerHTML =
    '<option value="">Tous</option>' +
    responsables
      .map((r) => `<option value="${r.email}">${r.nom} ${r.prenom}</option>`)
      .join("");

  const stillExists = responsables.some((r) => r.email === currentValue);
  filterResponsableSelect.value = stillExists ? currentValue : "";
}

function filterFormResponsablesByVille(selectedVille, selectedEmail = "") {
  if (!modalResponsableSelect) return;

  const normalizedVille = normalizeVille(selectedVille);
  const responsables = !normalizedVille
    ? allResponsables
    : allResponsables.filter((r) => normalizeVille(r.ville) === normalizedVille);

  modalResponsableSelect.innerHTML =
    '<option value="">Sélectionner un responsable...</option>';

  if (responsables.length === 0) {
    modalResponsableSelect.innerHTML +=
      '<option value="" disabled>Aucun responsable disponible pour cette ville</option>';
    modalResponsableSelect.value = "";
    return;
  }

  modalResponsableSelect.innerHTML += responsables
    .map((r) => `<option value="${r.email}">${r.nom} ${r.prenom}</option>`)
    .join("");

  if (selectedEmail) {
    modalResponsableSelect.value = selectedEmail;
  }
}

function handleVilleChange(selectedEmail = "") {
  if (!villeInput) return;
  if (allResponsables.length === 0) {
    fetchAllResponsables()
      .then(() => filterFormResponsablesByVille(villeInput.value, selectedEmail))
      .catch(() => filterFormResponsablesByVille(villeInput.value, selectedEmail));
    return;
  }
  filterFormResponsablesByVille(villeInput.value, selectedEmail);
}

function handleFilterVilleChange() {
  if (allResponsables.length === 0) {
    fetchAllResponsables()
      .then(updateFilterResponsableOptions)
      .catch(updateFilterResponsableOptions);
    return;
  }
  updateFilterResponsableOptions();
}

// Ouvrir le modal d'ajout
function openAddModal() {
  currentTerrainId = null;
  document.getElementById("modalTitle").textContent = "Ajouter un terrain";
  document.getElementById("terrainForm").reset();
  document.getElementById("terrainId").value = "";

  // Réinitialiser complètement l'affichage de l'image
  clearImage();

  if (villeInput) {
    villeInput.value = "";
    handleVilleChange();
  }

  if (modalResponsableSelect) {
    modalResponsableSelect.value = "";
  }

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
          if (villeInput) {
            villeInput.value = terrain.ville || "";
          }
          handleVilleChange(terrain.id_responsable || "");

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
          notifyOtherTabs(); // ← AJOUTEZ CETTE LIGNE
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
          notifyOtherTabs(); // ← AJOUTEZ CETTE LIGNE
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
