let currentCreneauId = null;
let deleteCreneauId = null;
let userRole = null;

// Écouter les changements dans d'autres onglets
window.addEventListener("storage", function (e) {
  if (e.key === "slots_updated") {
    // Un autre onglet a modifié les terrains
    console.log("Mise à jour des créneaux détectée depuis un autre onglet");
    loadCreneaux();

    // Afficher une notification discrète
    showNotification("Les créneaux ont été mis à jour", "success");

    // Nettoyer le flag après utilisation
    localStorage.removeItem("slots_updated");
  }
});

// Notifier les autres onglets après chaque modification
function notifyOtherTabs() {
  localStorage.setItem("slots_updated", Date.now().toString());
}

// Initialisation
document.addEventListener("DOMContentLoaded", function () {
  loadTerrains();
  loadCreneaux();
  setupEventListeners();
});

// Configuration des écouteurs d'événements
function setupEventListeners() {
  const filterTerrain = document.getElementById("filterTerrain");
  const filterTerrainId = document.getElementById("filterTerrainId");
  const filterTerrainList = document.getElementById("filterTerrainList");

  if (filterTerrain) {
    filterTerrain.addEventListener("input", (e) => {
      const inputValue = e.target.value;
      const option = Array.from(filterTerrainList.options).find(
        (opt) => opt.value === inputValue
      );
      if (option && option.dataset.id) {
        if (filterTerrainId) {
          filterTerrainId.value = option.dataset.id;
        }
      } else if (!inputValue) {
        if (filterTerrainId) filterTerrainId.value = "";
      }
      loadCreneaux();
    });
    filterTerrain.addEventListener("blur", (e) => {
      const inputValue = e.target.value;
      const option = Array.from(filterTerrainList.options).find(
        (opt) => opt.value === inputValue
      );
      if (!option && inputValue) {
        e.target.value = "";
        if (filterTerrainId) filterTerrainId.value = "";
        loadCreneaux();
      }
    });
  }

  document
    .getElementById("filterJour")
    .addEventListener("change", loadCreneaux);
  document
    .getElementById("filterDisponibilite")
    .addEventListener("change", loadCreneaux);

  // Gérer la sélection du terrain dans le formulaire
  const terrainInput = document.getElementById("id_terrain_input");
  const terrainHidden = document.getElementById("id_terrain");
  const terrainList = document.getElementById("terrainList");

  if (terrainInput && terrainList) {
    terrainInput.addEventListener("input", (e) => {
      const inputValue = e.target.value;
      const option = Array.from(terrainList.options).find(
        (opt) => opt.value === inputValue
      );
      if (option && option.dataset.id) {
        if (terrainHidden) {
          terrainHidden.value = option.dataset.id;
        }
      } else if (!inputValue) {
        if (terrainHidden) terrainHidden.value = "";
      }
    });
    terrainInput.addEventListener("blur", (e) => {
      const inputValue = e.target.value;
      const option = Array.from(terrainList.options).find(
        (opt) => opt.value === inputValue
      );
      if (!option && inputValue) {
        e.target.value = "";
        if (terrainHidden) terrainHidden.value = "";
      }
    });
  }
  document
    .getElementById("filterPeriode")
    .addEventListener("change", function () {
      const customContainer1 = document.getElementById("customDatesContainer");
      const customContainer2 = document.getElementById("customDatesContainer2");

      if (this.value === "custom") {
        customContainer1.classList.remove("hidden");
        customContainer2.classList.remove("hidden");
      } else {
        customContainer1.classList.add("hidden");
        customContainer2.classList.add("hidden");
        loadCreneaux();
      }
    });

  document
    .getElementById("filterDateDebut")
    .addEventListener("change", loadCreneaux);
  document
    .getElementById("filterDateFin")
    .addEventListener("change", loadCreneaux);
  document
    .getElementById("creneauForm")
    .addEventListener("submit", handleSubmit);
}

// Charger les terrains
function loadTerrains() {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "../../actions/admin-manager/stade/get_stades.php", true);

    xhr.onload = function () {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (response.success) {
            populateTerrainSelects(response.terrains);
            resolve(response.terrains);
          } else {
            reject(
              response.message || "Erreur lors du chargement des terrains"
            );
          }
        } catch (e) {
          console.error("Erreur lors du chargement des terrains");
          reject(e);
        }
      } else {
        reject("Erreur de connexion");
      }
    };

    xhr.onerror = function () {
      reject("Erreur réseau");
    };

    xhr.send();
  });
}

// Remplir les datalist de terrain
function populateTerrainSelects(terrains) {
  const modalList = document.getElementById("terrainList");
  const filterList = document.getElementById("filterTerrainList");

  if (modalList) {
    modalList.innerHTML = terrains
      .map(
        (t) =>
          `<option value="${t.nom_te} - ${t.categorie} (${t.ville})" data-id="${t.id_terrain}">`
      )
      .join("");
  }

  if (filterList) {
    filterList.innerHTML = terrains
      .map(
        (t) =>
          `<option value="${t.nom_te} - ${t.ville}" data-id="${t.id_terrain}">`
      )
      .join("");
  }
}

// Charger les créneaux
function loadCreneaux() {
  const filterTerrainId = document.getElementById("filterTerrainId");
  const terrain = filterTerrainId ? filterTerrainId.value : "";
  const jour = document.getElementById("filterJour").value;
  const disponibilite = document.getElementById("filterDisponibilite").value;

  // NOUVEAU : Gérer les dates selon la période sélectionnée
  const periode = document.getElementById("filterPeriode").value;
  let dateDebut, dateFin;

  switch (periode) {
    case "current_week":
      dateDebut = getMonday(new Date());
      dateFin = new Date(dateDebut);
      dateFin.setDate(dateFin.getDate() + 6);
      break;
    case "next_week":
      dateDebut = getMonday(new Date());
      dateDebut.setDate(dateDebut.getDate() + 7);
      dateFin = new Date(dateDebut);
      dateFin.setDate(dateFin.getDate() + 6);
      break;
    case "current_month":
      const now = new Date();
      dateDebut = new Date(now.getFullYear(), now.getMonth(), 1);
      dateFin = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      break;
    case "custom":
      dateDebut = document.getElementById("filterDateDebut").value;
      dateFin = document.getElementById("filterDateFin").value;
      if (!dateDebut || !dateFin) return; // Attendre que les deux dates soient remplies
      break;
  }

  // Convertir en format YYYY-MM-DD si ce sont des objets Date
  if (dateDebut instanceof Date) {
    dateDebut = dateDebut.toISOString().split("T")[0];
  }
  if (dateFin instanceof Date) {
    dateFin = dateFin.toISOString().split("T")[0];
  }

  showLoader();

  const xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    `../../actions/admin-manager/slot/get_slots.php?terrain=${terrain}&jour=${encodeURIComponent(
      jour
    )}&disponibilite=${disponibilite}&date_debut=${dateDebut}&date_fin=${dateFin}`,
    true
  );

  xhr.onload = function () {
    hideLoader();

    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.success) {
          userRole = response.user_role;
          displayCreneaux(response.creneaux);
        } else {
          showNotification(
            response.message || "Erreur lors du chargement des créneaux",
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

// Fonction helper pour obtenir le lundi de la semaine
function getMonday(date) {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Ajuster si dimanche
  return new Date(d.setDate(diff));
}

// Afficher les créneaux groupés par terrain
function displayCreneaux(creneaux) {
  const container = document.getElementById("creneauxContainer");

  if (creneaux.length === 0) {
    container.innerHTML = `
            <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-calendar-times text-gray-400 text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Aucun créneau trouvé</h3>
                <p class="text-gray-500">Modifiez vos critères de recherche ou ajoutez de nouveaux créneaux</p>
            </div>
        `;
    return;
  }

  // Grouper par terrain
  const terrainGroups = {};
  creneaux.forEach((creneau) => {
    if (!terrainGroups[creneau.id_terrain]) {
      terrainGroups[creneau.id_terrain] = {
        nom: creneau.nom_terrain,
        info: creneau.terrain_info,
        prix: creneau.prix_heure,
        creneaux: [],
      };
    }
    terrainGroups[creneau.id_terrain].creneaux.push(creneau);
  });

  container.innerHTML = Object.values(terrainGroups)
    .map((terrain) => {
      // Grouper par jour
      const jourGroups = {};
      terrain.creneaux.forEach((creneau) => {
        if (!jourGroups[creneau.jour_semaine]) {
          jourGroups[creneau.jour_semaine] = [];
        }
        jourGroups[creneau.jour_semaine].push(creneau);
      });

      const jours = [
        "Lundi",
        "Mardi",
        "Mercredi",
        "Jeudi",
        "Vendredi",
        "Samedi",
        "Dimanche",
      ];
      const totalDisponibles = terrain.creneaux.filter(
        (c) => c.disponibilite
      ).length;
      const totalReserves = terrain.creneaux.filter(
        (c) => !c.disponibilite
      ).length;

      // Vérifier s'il y a des créneaux de nuit pour ce terrain
      const hasNuitCreneaux = terrain.creneaux.some((c) => {
        const heure = parseInt(c.heure_debut.split(":")[0]);
        return heure >= 0 && heure < 6;
      });

      return `
            <div class="terrain-card">
                <!-- Header Terrain -->
                <div class="terrain-header">
                    <div class="flex-1">
                        <h2>${terrain.nom}</h2>
                        <div class="text-sm opacity-90 mt-1">
                            <i class="fas fa-map-marker-alt mr-1"></i>${
                              terrain.info.localisation
                            }
                            <span class="mx-2">•</span>
                            <i class="fas fa-user-tie mr-1"></i>${
                              terrain.info.responsable
                            }
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 bg-white/20 px-3 py-1 rounded-lg">
                            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                            <span><strong>${totalDisponibles}</strong> disponibles</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white/20 px-3 py-1 rounded-lg">
                            <div class="w-2 h-2 bg-red-400 rounded-full"></div>
                            <span><strong>${totalReserves}</strong> réservés</span>
                        </div>
                    </div>
                </div>

                <!-- Planning Grid -->
                <div class="planning-grid">
                    <!-- Header vide pour colonne horaire -->
                    <div class="planning-header">HORAIRES</div>
                    
                    <!-- Headers jours -->
                    ${jours
                      .map(
                        (jour) => `
                        <div class="planning-header">
                            <div class="text-lg font-bold">${jour}</div>
                            <div class="text-xs mt-1 opacity-80">${
                              (jourGroups[jour] || []).length
                            } créneaux</div>
                        </div>
                    `
                      )
                      .join("")}

                    ${
                      hasNuitCreneaux
                        ? `
                        <!-- Time slot: Nuit (00:00 - 06:00) -->
                        <div class="time-label">
                            <div class="text-center">
                                <i class="fas fa-star text-purple-400"></i>
                                <div class="text-sm">NUIT</div>
                                <div class="text-xs opacity-75">00:00 - 06:00</div>
                            </div>
                        </div>
                        ${jours
                          .map((jour) => {
                            const creneauxNuit = (
                              jourGroups[jour] || []
                            ).filter((c) => {
                              const heure = parseInt(
                                c.heure_debut.split(":")[0]
                              );
                              return heure >= 0 && heure < 6;
                            });
                            return renderCreneauxCell(creneauxNuit);
                          })
                          .join("")}
                    `
                        : ""
                    }

                    <!-- Time slot: Matin (06:00 - 12:00) -->
                    <div class="time-label">
                        <div class="text-center">
                            <i class="fas fa-sun text-yellow-400"></i>
                            <div class="text-sm">MATIN</div>
                            <div class="text-xs opacity-75">06:00 - 12:00</div>
                        </div>
                    </div>
                    ${jours
                      .map((jour) => {
                        const creneauxMatin = (jourGroups[jour] || []).filter(
                          (c) => {
                            const heure = parseInt(c.heure_debut.split(":")[0]);
                            return heure >= 6 && heure < 12;
                          }
                        );
                        return renderCreneauxCell(creneauxMatin);
                      })
                      .join("")}

                    <!-- Time slot: Après-midi (12:00 - 18:00) -->
                    <div class="time-label">
                        <div class="text-center">
                            <i class="fas fa-cloud-sun text-orange-400"></i>
                            <div class="text-sm">APRÈS-MIDI</div>
                            <div class="text-xs opacity-75">12:00 - 18:00</div>
                        </div>
                    </div>
                    ${jours
                      .map((jour) => {
                        const creneauxApresMidi = (
                          jourGroups[jour] || []
                        ).filter((c) => {
                          const heure = parseInt(c.heure_debut.split(":")[0]);
                          return heure >= 12 && heure < 18;
                        });
                        return renderCreneauxCell(creneauxApresMidi);
                      })
                      .join("")}

                    <!-- Time slot: Soir (18:00 - 00:00) -->
                    <div class="time-label">
                        <div class="text-center">
                            <i class="fas fa-moon text-indigo-400"></i>
                            <div class="text-sm">SOIR</div>
                            <div class="text-xs opacity-75">18:00 - 00:00</div>
                        </div>
                    </div>
                    ${jours
                      .map((jour) => {
                        const creneauxSoir = (jourGroups[jour] || []).filter(
                          (c) => {
                            const heure = parseInt(c.heure_debut.split(":")[0]);
                            return heure >= 18 || heure === 0;
                          }
                        );
                        return renderCreneauxCell(creneauxSoir);
                      })
                      .join("")}
                </div>
            </div>
        `;
    })
    .join("");
}

// Fonction helper pour rendre une cellule de créneaux
// Dans renderCreneauxCell(), ajouter la date de réservation
function renderCreneauxCell(creneaux) {
  if (creneaux.length === 0) {
    return `
            <div class="planning-cell creneau-cell">
                <div class="empty-state">
                    <i class="fas fa-moon"></i><br>Aucun
                </div>
            </div>
        `;
  }

  return `
        <div class="planning-cell creneau-cell">
            ${creneaux
              .map(
                (c) => `
                <div class="creneau-item ${
                  c.disponibilite ? "available" : "reserved"
                }">
                    <div class="creneau-time">
                        <i class="fas fa-clock mr-1"></i>${c.heure_debut} - ${
                  c.heure_fin
                }
                    </div>
                    ${
                      !c.disponibilite && c.reservation_info
                        ? `
                        <div class="reservation-info">
                            <div class="text-xs font-bold mb-1">
                                <i class="fas fa-calendar mr-1"></i>${formatDate(
                                  c.reservation_info.date_reservation_only
                                )}
                            </div>
                            <div><i class="fas fa-users mr-1"></i>${
                              c.reservation_info.equipe_nom
                            }</div>
                            ${
                              c.reservation_info.equipe_adverse
                                ? `<div><i class="fas fa-shield-alt mr-1"></i>vs ${c.reservation_info.equipe_adverse}</div>`
                                : ""
                            }
                        </div>
                    `
                        : ""
                    }
                    <div class="flex gap-1 mt-2">
                        <button onclick="editCreneau(${
                          c.id_creneaux
                        })" class="action-btn bg-green-800 text-white hover:bg-green-900">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button onclick="openDeleteModal(${
                          c.id_creneaux
                        })" class="action-btn bg-red-600 text-white hover:bg-red-700">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            `
              )
              .join("")}
        </div>
    `;
}

// Fonction helper pour formater la date
function formatDate(dateStr) {
  const date = new Date(dateStr + "T00:00:00");
  const options = { day: "2-digit", month: "2-digit" };
  return date.toLocaleDateString("fr-FR", options);
}

// Ouvrir le modal d'ajout
function openAddModal() {
  currentCreneauId = null;
  document.getElementById("modalTitle").textContent = "Ajouter un créneau";
  document.getElementById("creneauForm").reset();
  document.getElementById("creneauId").value = "";
  const terrainInput = document.getElementById("id_terrain_input");
  const terrainHidden = document.getElementById("id_terrain");
  if (terrainInput) terrainInput.value = "";
  if (terrainHidden) terrainHidden.value = "";
  document.getElementById("creneauModal").classList.remove("hidden");
}

// Modifier un créneau
function editCreneau(id) {
  currentCreneauId = id;

  const xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    `../../actions/admin-manager/slot/get_slot.php?id=${id}`,
    true
  );

  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.success) {
          const creneau = response.creneau;
          document.getElementById("modalTitle").textContent =
            "Modifier le créneau";
          document.getElementById("creneauId").value = creneau.id_creneaux;

          // Trouver le terrain dans la liste et afficher son texte
          const terrainHidden = document.getElementById("id_terrain");
          const terrainInput = document.getElementById("id_terrain_input");
          const terrainList = document.getElementById("terrainList");
          if (terrainHidden) terrainHidden.value = creneau.id_terrain;

          // Charger les terrains si nécessaire pour trouver le nom
          if (terrainList && terrainList.options.length > 0) {
            const terrainOption = Array.from(terrainList.options).find(
              (opt) => opt.dataset.id === creneau.id_terrain.toString()
            );
            if (terrainOption && terrainInput) {
              terrainInput.value = terrainOption.value;
            }
          } else {
            // Recharger les terrains pour avoir la liste
            loadTerrains().then(() => {
              const terrainOption = Array.from(terrainList.options).find(
                (opt) => opt.dataset.id === creneau.id_terrain.toString()
              );
              if (terrainOption && terrainInput) {
                terrainInput.value = terrainOption.value;
              }
            });
          }

          document.getElementById("jour_semaine").value = creneau.jour_semaine;
          document.getElementById("heure_debut").value = creneau.heure_debut;
          document.getElementById("heure_fin").value = creneau.heure_fin;
          document.getElementById("creneauModal").classList.remove("hidden");
        } else {
          showNotification(
            response.message || "Erreur lors du chargement du créneau",
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
    currentCreneauId
      ? "../../actions/admin-manager/slot/edit_slot.php"
      : "../../actions/admin-manager/slot/add_slot.php",
    true
  );
  xhr.setRequestHeader("Content-Type", "application/json");

  xhr.onload = function () {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i>Enregistrer';

    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.success) {
          showNotification(response.message, "success");
          closeModal();
          loadCreneaux();
          notifyOtherTabs();
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
    submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i>Enregistrer';
    showNotification("Erreur réseau", "error");
  };

  xhr.send(JSON.stringify(data));
}

// Ouvrir le modal de suppression
function openDeleteModal(id) {
  deleteCreneauId = id;
  document.getElementById("deleteModal").classList.remove("hidden");
}

// Fermer le modal de suppression
function closeDeleteModal() {
  deleteCreneauId = null;
  document.getElementById("deleteModal").classList.add("hidden");
}

// Confirmer la suppression
function confirmDelete() {
  if (!deleteCreneauId) return;

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "../../actions/admin-manager/slot/delete_slot.php", true);
  xhr.setRequestHeader("Content-Type", "application/json");

  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.success) {
          showNotification(response.message, "success");
          closeDeleteModal();
          loadCreneaux();
          notifyOtherTabs();
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
      id_creneaux: deleteCreneauId,
    })
  );
}

// Fermer le modal principal
function closeModal() {
  document.getElementById("creneauModal").classList.add("hidden");
  document.getElementById("creneauForm").reset();
  currentCreneauId = null;
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
