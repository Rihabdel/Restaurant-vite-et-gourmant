// Données de test (normalement viendraient d'une API)
let menuData = {
  entrees: [
    { id: 1, nom: "Salade César", description: "Laitue romaine, croûtons, parmesan et sauce césar", prix: 12.50, allergenes: ["gluten", "oeufs"], code: "#ENT001" }
  ],
  plats: [
    { id: 2, nom: "Steak Frites", description: "Steak de bœuf 200g avec frites maison", prix: 18.90, allergenes: [], code: "#PLT001" }
  ],
  desserts: [
    { id: 3, nom: "Tiramisu", description: "Dessert italien au café et mascarpone", prix: 8.50, allergenes: ["lait", "gluten"], code: "#DES001" }
  ]
};

// Remplir le modal d'édition avec les données du plat
document.querySelectorAll('.edit-plat-btn').forEach(button => {
  button.addEventListener('click', function() {
    const platId = parseInt(this.dataset.platId);
    const platType = this.dataset.platType;
    
    // Trouver le plat dans les données
    let plat = null;
    if (platType === 'entree') {
      plat = menuData.entrees.find(p => p.id === platId);
    } else if (platType === 'principal') {
      plat = menuData.plats.find(p => p.id === platId);
    } else if (platType === 'dessert') {
      plat = menuData.desserts.find(p => p.id === platId);
    }
    
    if (plat) {
      document.getElementById('editPlatId').value = plat.id;
      document.getElementById('editPlatType').value = platType;
      document.getElementById('editPlatNom').value = plat.nom;
      document.getElementById('editPlatDescription').value = plat.description;
      document.getElementById('editPlatPrix').value = plat.prix;
      
      // Réinitialiser les cases à cocher
      document.querySelectorAll('#editPlatForm input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
      });
      
      // Cocher les allergènes
      plat.allergenes.forEach(allergene => {
        const checkbox = document.getElementById(`editAllergene${capitalizeFirstLetter(allergene)}`);
        if (checkbox) checkbox.checked = true;
      });
    }
  });
});

// Sauvegarder les modifications
document.getElementById('saveEditBtn').addEventListener('click', function() {
  const platId = parseInt(document.getElementById('editPlatId').value);
  const platType = document.getElementById('editPlatType').value;
  const platNom = document.getElementById('editPlatNom').value;
  const platDescription = document.getElementById('editPlatDescription').value;
  const platPrix = parseFloat(document.getElementById('editPlatPrix').value);
  
  // Récupérer les allergènes cochés
  const allergenes = [];
  document.querySelectorAll('#editPlatForm input[type="checkbox"]:checked').forEach(cb => {
    allergenes.push(cb.value);
  });
  
  // Trouver et mettre à jour le plat
  let platArray;
  if (platType === 'entree') {
    platArray = menuData.entrees;
  } else if (platType === 'principal') {
    platArray = menuData.plats;
  } else if (platType === 'dessert') {
    platArray = menuData.desserts;
  }
  
  const platIndex = platArray.findIndex(p => p.id === platId);
  if (platIndex !== -1) {
    platArray[platIndex] = {
      ...platArray[platIndex],
      nom: platNom,
      description: platDescription,
      prix: platPrix,
      allergenes: allergenes
    };
    
    // Mettre à jour l'affichage
    updatePlatDisplay(platArray[platIndex]);
    
    // Fermer le modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('editCarteMenu'));
    modal.hide();
    
    // Afficher une notification
    showNotification('Plat modifié avec succès!', 'success');
  }
});

// Supprimer un plat
document.querySelectorAll('.delete-plat-btn').forEach(button => {
  button.addEventListener('click', function() {
    const platId = parseInt(this.dataset.platId);
    const platCard = this.closest('[data-plat-id]');
    const platType = platCard.closest('[id$="-list"]').id.replace('-list', '');
    
    if (confirm('Voulez-vous vraiment supprimer ce plat?')) {
      // Supprimer de l'affichage
      platCard.remove();
      
      // Supprimer des données
      if (platType === 'entrees') {
        menuData.entrees = menuData.entrees.filter(p => p.id !== platId);
      } else if (platType === 'plats') {
        menuData.plats = menuData.plats.filter(p => p.id !== platId);
      } else if (platType === 'desserts') {
        menuData.desserts = menuData.desserts.filter(p => p.id !== platId);
      }
      
      showNotification('Plat supprimé avec succès!', 'warning');
    }
  });
});

// Ajouter un nouveau plat
document.getElementById('saveNewPlatBtn').addEventListener('click', function() {
  const platType = document.getElementById('addPlatType').value;
  const platNom = document.getElementById('addPlatNom').value;
  const platDescription = document.getElementById('addPlatDescription').value;
  const platPrix = parseFloat(document.getElementById('addPlatPrix').value);
  
  if (!platType || !platNom || !platPrix) {
    showNotification('Veuillez remplir tous les champs obligatoires', 'error');
    return;
  }
  
  // Générer un nouvel ID
  const newId = Math.max(
    ...menuData.entrees.map(p => p.id),
    ...menuData.plats.map(p => p.id),
    ...menuData.desserts.map(p => p.id)
  ) + 1;
  
  const nouveauPlat = {
    id: newId,
    nom: platNom,
    description: platDescription,
    prix: platPrix,
    allergenes: [],
    code: generatePlatCode(platType, newId)
  };
  
  // Ajouter aux données
  if (platType === 'entree') {
    menuData.entrees.push(nouveauPlat);
  } else if (platType === 'principal') {
    menuData.plats.push(nouveauPlat);
  } else if (platType === 'dessert') {
    menuData.desserts.push(nouveauPlat);
  }
  
  // Ajouter à l'affichage
  addPlatToDisplay(nouveauPlat, platType);
  
  // Fermer le modal et réinitialiser le formulaire
  const modal = bootstrap.Modal.getInstance(document.getElementById('addPlatModal'));
  modal.hide();
  document.getElementById('addPlatForm').reset();
  
  showNotification('Plat ajouté avec succès!', 'success');
});

// Fonctions utilitaires
function updatePlatDisplay(plat) {
  const platElement = document.querySelector(`[data-plat-id="${plat.id}"]`);
  if (platElement) {
    // Mettre à jour les informations
    platElement.querySelector('.card-title').textContent = plat.nom;
    platElement.querySelector('.card-text').textContent = plat.description;
    platElement.querySelector('.text-primary').textContent = plat.prix.toFixed(2) + ' €';
    
    // Mettre à jour les allergènes
    const badgesContainer = platElement.querySelector('.mb-2');
    if (badgesContainer) {
      badgesContainer.innerHTML = '';
      plat.allergenes.forEach(allergene => {
        const badge = document.createElement('span');
        badge.className = 'badge bg-warning text-dark me-1';
        badge.textContent = formatAllergene(allergene);
        badgesContainer.appendChild(badge);
      });
    }
  }
}

function addPlatToDisplay(plat, type) {
  const containerId = type === 'entree' ? 'entrees-list' : 
                     type === 'principal' ? 'plats-list' : 'desserts-list';
  
  const container = document.getElementById(containerId);
  
  const platHTML = `
    <div class="col-md-6 col-lg-4 mb-4" data-plat-id="${plat.id}">
      <div class="card h-100 shadow-sm">
        <img src="https://via.placeholder.com/300x200" class="card-img-top" alt="${plat.nom}">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <h5 class="card-title">${plat.nom}</h5>
            <div class="btn-group">
              <button class="btn btn-sm btn-outline-primary edit-plat-btn"
                      data-bs-toggle="modal"
                      data-bs-target="#editCarteMenu"
                      data-plat-id="${plat.id}"
                      data-plat-type="${type}">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger delete-plat-btn" data-plat-id="${plat.id}">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
          <p class="card-text">${plat.description}</p>
          <div class="d-flex justify-content-between align-items-center">
            <span class="h5 text-primary">${plat.prix.toFixed(2)} €</span>
            <span class="text-muted">${plat.code}</span>
          </div>
        </div>
      </div>
    </div>
  `;
  
  container.insertAdjacentHTML('beforeend', platHTML);
  
  // Ajouter les event listeners aux nouveaux boutons
  attachEventListeners();
}

function generatePlatCode(type, id) {
  const prefix = type === 'entree' ? 'ENT' : 
                type === 'principal' ? 'PLT' : 'DES';
  return `#${prefix}${id.toString().padStart(3, '0')}`;
}

function formatAllergene(allergene) {
  const map = {
    gluten: 'Gluten',
    lait: 'Produits laitiers',
    oeufs: 'Œufs',
    soja: 'Soja'
  };
  return map[allergene] || allergene;
}

function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

function showNotification(message, type = 'info') {
  // Créer une notification simple
  const alert = document.createElement('div');
  alert.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
  alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
  alert.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  document.body.appendChild(alert);
  
  setTimeout(() => {
    alert.remove();
  }, 3000);
}

function attachEventListeners() {
  // Attacher les listeners aux nouveaux boutons
  document.querySelectorAll('.edit-plat-btn').forEach(btn => {
    if (!btn.hasAttribute('data-listener-attached')) {
      btn.setAttribute('data-listener-attached', 'true');
      btn.addEventListener('click', function() {
        // Le gestionnaire d'événement est déjà défini plus haut
      });
    }
  });
  
  document.querySelectorAll('.delete-plat-btn').forEach(btn => {
    if (!btn.hasAttribute('data-listener-attached')) {
      btn.setAttribute('data-listener-attached', 'true');
      btn.addEventListener('click', function() {
        // Le gestionnaire d'événement est déjà défini plus haut
      });
    }
  });
}

// Initialiser les event listeners
attachEventListeners();