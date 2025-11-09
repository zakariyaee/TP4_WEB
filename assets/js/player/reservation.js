// Variables globales
let idTerrain;
let prixHeure;
let creneauxData = [];
let objetsData = [];
let selectedObjets = [];
let reservationData = {};
let equipesList = [];
let toutesEquipesList = [];
let currentEtape;

// Initialisation
function initReservation(terrainId, prix, equipes, toutesEquipes, etape) {
    idTerrain = terrainId;
    prixHeure = prix;
    equipesList = equipes;
    toutesEquipesList = toutesEquipes;
    currentEtape = etape;

    console.log('Équipes disponibles pour le joueur:', equipesList);
    console.log('Toutes les équipes (adversaires):', toutesEquipesList);

    // Charger les objets au chargement de la page
    loadObjets();

    // Si on est à l'étape 1, écouter les changements de date
    if (document.getElementById('date_reservation')) {
        document.getElementById('date_reservation').addEventListener('change', loadCreneaux);
    }

    // Charger les données de réservation depuis le localStorage
    loadReservationData();
}

function handleEquipeSelection() {
    const select = document.getElementById('id_equipe');
    const nouvelleEquipeInput = document.getElementById('nouvelle_equipe');

    if (select.value === 'nouvelle') {
        nouvelleEquipeInput.style.display = 'block';
        nouvelleEquipeInput.focus();
    } else {
        nouvelleEquipeInput.style.display = 'none';
        nouvelleEquipeInput.value = '';
    }
}

function loadCreneaux() {
    const date = document.getElementById('date_reservation').value;
    if (!date) {
        document.getElementById('creneau_horaire').disabled = true;
        document.getElementById('creneau_message').textContent = 'Veuillez d\'abord sélectionner une date';
        return;
    }

    fetch(`../../../actions/player/reservation/get_creneaux_disponibles.php?id_terrain=${idTerrain}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                creneauxData = data.creneaux;
                const select = document.getElementById('creneau_horaire');
                select.innerHTML = '<option value="">Choisir un créneau</option>';

                if (data.creneaux.length === 0) {
                    select.disabled = true;
                    document.getElementById('creneau_message').textContent = 'Aucun créneau disponible pour cette date';
                    document.getElementById('creneau_message').classList.add('text-red-500');
                } else {
                    select.disabled = false;

                    // Compter les créneaux disponibles
                    const disponibles = data.creneaux.filter(c => !c.est_reserve).length;
                    document.getElementById('creneau_message').textContent = `${disponibles} créneau(x) disponible(s) sur ${data.creneaux.length}`;
                    document.getElementById('creneau_message').classList.remove('text-red-500');

                    data.creneaux.forEach(creneau => {
                        const option = document.createElement('option');
                        option.value = creneau.id_creneaux;
                        option.textContent = creneau.libelle;

                        // Désactiver si réservé
                        if (creneau.est_reserve) {
                            option.disabled = true;
                            option.style.color = '#999';
                            option.style.fontStyle = 'italic';
                        }

                        select.appendChild(option);
                    });

                    // Restaurer la sélection si elle existe
                    if (reservationData.creneau) {
                        select.value = reservationData.creneau;
                    }
                }
                updateCostSummary();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des créneaux');
        });
}

function loadObjets() {
    fetch('../../../actions/player/reservation/get_objets.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                objetsData = data.objets;
                renderObjets();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
}

function renderObjets() {
    const container = document.getElementById('objets_container');
    if (!container) return;

    container.innerHTML = '';
    objetsData.forEach(objet => {
        const div = document.createElement('div');
        div.className = 'objet-card flex flex-col items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all';
        div.id = `objet_card_${objet.id_object}`;

        const isSelected = selectedObjets.includes(parseInt(objet.id_object));
        if (isSelected) {
            div.classList.add('selected');
        }

        div.innerHTML = `
            <input type="checkbox" 
                   id="objet_${objet.id_object}" 
                   value="${objet.id_object}" 
                   class="hidden"
                   ${isSelected ? 'checked' : ''}>
            <label for="objet_${objet.id_object}" class="cursor-pointer text-center w-full">
                <div class="text-3xl mb-2">${getObjetIcon(objet.nom_objet)}</div>
                <div class="text-sm font-medium text-gray-900">${objet.nom_objet}</div>
                <div class="text-sm text-emerald-600 mt-1 font-semibold">+${objet.prix} DH</div>
            </label>
        `;

        // Gérer le clic sur toute la carte
        div.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleObjet(parseInt(objet.id_object), parseFloat(objet.prix));
        });

        container.appendChild(div);
    });
}

function getObjetIcon(nom) {
    const icons = {
        'Ballon': '⚽',
        'Arbitre': '👔',
        'Chasubles': '👕',
        'Douche': '🚿',
        'Vestiaire': '🚪',
        'Éclairage nocturne': '💡',
        'Trousse premiers secours': '🏥',
        'Vidéo analyse': '📹'
    };
    return icons[nom] || '📦';
}

function toggleObjet(id, prix) {
    const card = document.getElementById(`objet_card_${id}`);
    const checkbox = document.getElementById(`objet_${id}`);
    const index = selectedObjets.indexOf(id);

    if (index === -1) {
        selectedObjets.push(id);
        card.classList.add('selected');
        checkbox.checked = true;
    } else {
        selectedObjets.splice(index, 1);
        card.classList.remove('selected');
        checkbox.checked = false;
    }

    console.log('Objets sélectionnés:', selectedObjets);
    updateCostSummary();
}

function updateCostSummary() {
    // Calculer les heures et prix du terrain
    let heures = 0;
    let prixTerrain = 0;

    // Essayer d'abord avec le select, puis avec les données sauvegardées
    const creneauSelect = document.getElementById('creneau_horaire');
    let creneauId = null;

    if (creneauSelect && creneauSelect.value) {
        creneauId = creneauSelect.value;
    } else if (reservationData.creneau) {
        creneauId = reservationData.creneau;
    }

    if (creneauId && creneauxData.length > 0) {
        const creneau = creneauxData.find(c => c.id_creneaux == creneauId);
        if (creneau) {
            const [h1, m1] = creneau.heure_debut.split(':').map(Number);
            const [h2, m2] = creneau.heure_fin.split(':').map(Number);
            heures = (h2 - h1) + (m2 - m1) / 60;
            prixTerrain = prixHeure * heures;
        }
    }

    // Calculer le prix des objets
    let prixObjets = 0;
    const objetsContainer = document.getElementById('cost_objets_container');

    if (objetsContainer) {
        objetsContainer.innerHTML = '';

        // Ajouter le prix du terrain comme premier élément
        if (heures > 0) {
            const divTerrain = document.createElement('div');
            divTerrain.className = 'flex justify-between py-1';
            divTerrain.innerHTML = `
                <span class="text-gray-600">Location terrain (${heures}h)</span>
                <span class="font-semibold">${prixTerrain.toFixed(2)} DH</span>
            `;
            objetsContainer.appendChild(divTerrain);
        }

        // Ajouter les objets sélectionnés
        selectedObjets.forEach(objetId => {
            const objet = objetsData.find(o => parseInt(o.id_object) === objetId);
            if (objet) {
                prixObjets += parseFloat(objet.prix);
                const div = document.createElement('div');
                div.className = 'flex justify-between py-1';
                div.innerHTML = `
                    <span class="text-gray-600">${objet.nom_objet}</span>
                    <span class="font-semibold">+${parseFloat(objet.prix).toFixed(2)} DH</span>
                `;
                objetsContainer.appendChild(div);
            }
        });
    }

    // Mettre à jour l'affichage
    const costTerrain = document.getElementById('cost_terrain');
    const costTotal = document.getElementById('cost_total');

    if (costTerrain) {
        if (heures > 0) {
            costTerrain.textContent = `${prixTerrain.toFixed(2)} DH (${heures}h)`;
        } else {
            costTerrain.textContent = '-';
        }
    }

    if (costTotal) {
        const total = prixTerrain + prixObjets;
        costTotal.textContent = total > 0 ? `${total.toFixed(2)} DH` : '-';
    }
}

function nextStep1() {
    const date = document.getElementById('date_reservation').value;
    const creneau = document.getElementById('creneau_horaire').value;

    if (!date || !creneau) {
        alert('Veuillez sélectionner une date et un créneau');
        return;
    }

    // Sauvegarder dans le localStorage
    reservationData.date = date;
    reservationData.creneau = creneau;
    saveReservationData();

    // Aller à l'étape 2
    window.location.href = `?id_terrain=${idTerrain}&etape=2`;
}

function nextStep2() {
    const idEquipe = document.getElementById('id_equipe').value;
    const nouvelleEquipe = document.getElementById('nouvelle_equipe').value;
    const nombreJoueurs = document.getElementById('nombre_joueurs').value;

    if (!idEquipe && !nouvelleEquipe) {
        alert('Veuillez sélectionner ou créer une équipe');
        return;
    }

    if (idEquipe === 'nouvelle' && !nouvelleEquipe.trim()) {
        alert('Veuillez entrer le nom de la nouvelle équipe');
        document.getElementById('nouvelle_equipe').focus();
        return;
    }

    if (!nombreJoueurs) {
        alert('Veuillez sélectionner le nombre de joueurs');
        return;
    }

    // Sauvegarder dans le localStorage
    reservationData.id_equipe = idEquipe;
    reservationData.nouvelle_equipe = nouvelleEquipe;
    reservationData.id_equipe_adverse = document.getElementById('id_equipe_adverse').value;
    reservationData.nombre_joueurs = nombreJoueurs;
    reservationData.telephone = document.getElementById('telephone').value;
    reservationData.notes = document.getElementById('notes').value;
    reservationData.objets = selectedObjets;
    saveReservationData();

    // Aller à l'étape 3
    window.location.href = `?id_terrain=${idTerrain}&etape=3`;
}

function prevStep() {
    if (currentEtape > 1) {
        window.location.href = `?id_terrain=${idTerrain}&etape=${currentEtape - 1}`;
    }
}

function loadReservationData() {
    // Charger depuis le localStorage
    const stored = localStorage.getItem('reservation_data');
    if (stored) {
        reservationData = JSON.parse(stored);

        // Vérifier que c'est bien pour le même terrain
        if (reservationData.id_terrain != idTerrain) {
            // Réinitialiser si terrain différent
            reservationData = {
                id_terrain: idTerrain
            };
            localStorage.setItem('reservation_data', JSON.stringify(reservationData));
            return;
        }

        // Remplir les champs selon l'étape
        if (document.getElementById('date_reservation') && reservationData.date) {
            document.getElementById('date_reservation').value = reservationData.date;
            loadCreneaux();
        }

        // Charger les créneaux pour l'étape 2 et 3
        if (reservationData.date && reservationData.creneau && currentEtape >= 2) {
            fetch(`../../../actions/player/reservation/get_creneaux_disponibles.php?id_terrain=${idTerrain}&date=${reservationData.date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        creneauxData = data.creneaux;
                        updateCostSummary();
                    }
                })
                .catch(error => console.error('Erreur:', error));
        }

        if (document.getElementById('id_equipe')) {
            if (reservationData.id_equipe === 'nouvelle' && reservationData.nouvelle_equipe) {
                document.getElementById('id_equipe').value = 'nouvelle';
                document.getElementById('nouvelle_equipe').style.display = 'block';
                document.getElementById('nouvelle_equipe').value = reservationData.nouvelle_equipe;
            } else if (reservationData.id_equipe) {
                document.getElementById('id_equipe').value = reservationData.id_equipe;
            }
        }

        // Restaurer l'équipe adversaire
        if (document.getElementById('id_equipe_adverse') && reservationData.id_equipe_adverse) {
            document.getElementById('id_equipe_adverse').value = reservationData.id_equipe_adverse;
        }

        if (document.getElementById('nombre_joueurs') && reservationData.nombre_joueurs) {
            document.getElementById('nombre_joueurs').value = reservationData.nombre_joueurs;
        }
        if (document.getElementById('telephone') && reservationData.telephone) {
            document.getElementById('telephone').value = reservationData.telephone;
        }
        if (document.getElementById('notes') && reservationData.notes) {
            document.getElementById('notes').value = reservationData.notes;
        }

        if (reservationData.objets && Array.isArray(reservationData.objets)) {
            selectedObjets = reservationData.objets;
        }
    } else {
        // Initialiser avec l'ID du terrain
        reservationData = {
            id_terrain: idTerrain
        };
    }

    // Charger les données de confirmation
    if (currentEtape === 3) {
        loadConfirmationData();
    }
}

function saveReservationData() {
    reservationData.id_terrain = idTerrain;
    localStorage.setItem('reservation_data', JSON.stringify(reservationData));
    console.log('Données sauvegardées:', reservationData);
}

function loadConfirmationData() {
    if (!reservationData.date || !reservationData.creneau) {
        alert('Données incomplètes. Retour à l\'étape 1.');
        window.location.href = `?id_terrain=${idTerrain}&etape=1`;
        return;
    }

    // Charger les créneaux pour obtenir les informations
    fetch(`../../../actions/player/reservation/get_creneaux_disponibles.php?id_terrain=${idTerrain}&date=${reservationData.date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                creneauxData = data.creneaux;
                const creneau = creneauxData.find(c => c.id_creneaux == reservationData.creneau);

                // Afficher la date
                if (document.getElementById('conf_date')) {
                    const date = new Date(reservationData.date + 'T00:00:00');
                    const options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                    document.getElementById('conf_date').textContent = date.toLocaleDateString('fr-FR', options);
                }

                // Afficher l'heure
                if (document.getElementById('conf_heure') && creneau) {
                    document.getElementById('conf_heure').textContent = creneau.libelle;
                }

                // Afficher l'équipe
                if (document.getElementById('conf_equipe')) {
                    if (reservationData.id_equipe === 'nouvelle' && reservationData.nouvelle_equipe) {
                        document.getElementById('conf_equipe').textContent = reservationData.nouvelle_equipe + ' (nouvelle équipe)';
                    } else {
                        const equipe = equipesList.find(e => e.id_equipe == reservationData.id_equipe);
                        document.getElementById('conf_equipe').textContent = equipe ? equipe.nom_equipe : '-';
                    }
                }

                // Afficher l'équipe adversaire
                if (document.getElementById('conf_equipe_adverse')) {
                    if (reservationData.id_equipe_adverse) {
                        const equipeAdverse = toutesEquipesList.find(e => e.id_equipe == reservationData.id_equipe_adverse);
                        document.getElementById('conf_equipe_adverse').textContent = equipeAdverse ? equipeAdverse.nom_equipe : 'Aucune';
                    } else {
                        document.getElementById('conf_equipe_adverse').textContent = 'Aucune';
                    }
                }

                // Afficher le nombre de joueurs
                if (document.getElementById('conf_nombre_joueurs')) {
                    document.getElementById('conf_nombre_joueurs').textContent = reservationData.nombre_joueurs + ' vs ' + reservationData.nombre_joueurs;
                }

                // Charger les objets si pas encore chargés
                if (objetsData.length === 0) {
                    return fetch('../../../actions/player/reservation/get_objets.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                objetsData = data.objets;
                                displayObjetsConfirmation();
                                updateCostSummary();
                            }
                        });
                } else {
                    displayObjetsConfirmation();
                    updateCostSummary();
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des données');
        });
}

function displayObjetsConfirmation() {
    const confObjets = document.getElementById('conf_objets');
    if (confObjets && selectedObjets.length > 0) {
        const objetsList = selectedObjets.map(objetId => {
            const objet = objetsData.find(o => parseInt(o.id_object) === objetId);
            return objet ? `<span class="inline-block px-2 py-1 bg-emerald-50 text-emerald-700 rounded text-xs mr-2 mb-2">${objet.nom_objet} (+${parseFloat(objet.prix).toFixed(2)} DH)</span>` : '';
        }).join('');
        confObjets.innerHTML = objetsList;
    } else if (confObjets) {
        confObjets.innerHTML = '<span class="text-gray-400">Aucun</span>';
    }
}

function confirmReservation() {
    if (!reservationData.date || !reservationData.creneau) {
        alert('Données de réservation incomplètes');
        return;
    }

    if (!reservationData.id_equipe && !reservationData.nouvelle_equipe) {
        alert('Veuillez sélectionner une équipe');
        return;
    }

    // Préparer les données
    const data = {
        id_terrain: idTerrain,
        id_creneau: reservationData.creneau,
        date_reservation: reservationData.date,
        id_equipe: reservationData.id_equipe !== 'nouvelle' ? reservationData.id_equipe : '',
        nouvelle_equipe: reservationData.id_equipe === 'nouvelle' ? reservationData.nouvelle_equipe : '',
        id_equipe_adverse: reservationData.id_equipe_adverse || '',
        nombre_joueurs: reservationData.nombre_joueurs,
        objets: selectedObjets,
        notes: reservationData.notes || ''
    };

    console.log('Envoi des données:', data);

    // Désactiver le bouton pour éviter les doubles clics
    const btn = document.getElementById('btn_confirm');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>En cours...';

    // Envoyer la requête
    fetch('../../../actions/player/reservation/create_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            console.log('Résultat:', result);
            if (result.success) {
                // Nettoyer le localStorage
                localStorage.removeItem('reservation_data');
                // Rediriger directement sans message
                window.location.href = 'my-reservations.php';
            } else {
                alert('Erreur: ' + result.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirmer la réservation';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la création de la réservation');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirmer la réservation';
        });
}