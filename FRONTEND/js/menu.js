import { getMenus, getMenuById, getMenuDishes, enregistrerMenu, updateMenu, deleteMenu, createOrder, getUserInfo, getDishes} from './api.js';
import { showAndHideElementsForRoles, getToken } from './script.js';

// Initialiser la page des menus
export default async function initMenu() {
    console.log("Initializing menu page...");
    
    try {
        const menus =await getMenus();
        await displayMenus(menus);
        showAndHideElementsForRoles();
        initModals();
        initForm();
        console.log("Menu page initialized successfully.");
    } catch (error) {
        console.error("Error initializing menu page:", error);
    }
}
//afficher les menus 
export async function displayMenus(menus){
    
    const container = document.getElementById('menu-cards-container');
    if (!container) {
        console.error('Conteneur pour les cartes de menu introuvable.');
        return;
    }
    if (!Array.isArray(menus)) {
        console.error("menus n'est pas un tableau :", menus);
        return;
    }

    
    const visibleMenus = menus.filter(menu => menu.isAvailable);
    container.innerHTML = visibleMenus.map(menu=> {

const pictureUrl = menu.pictureUrl ? `http://127.0.0.1:8000${menu.pictureUrl}` : '/scss/images/viteGourmand.png';
         return `
            <div class="col-lg-3 col-md-4 mb-4 p-2">
                <div class="card menu-card">
                    <div class="card-image">
                        <img src="${pictureUrl}" class="card-img-top" alt="Image du menu ${pictureUrl}">
                        <div class="action-image-buttons" data-show="ROLE_ADMIN,ROLE_EMPLOYEE" style="display:none">
                            <button class="btn btn-outline-light edit-img-menu-btn" data-id="${menu.id}">
                                <i class="bi bi-pencil"></i> Modifier
                            </button> 
                            <button class="btn btn-outline-light delete-img-menu-btn" data-id="${menu.id}">
                                <i class="bi bi-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h4 class="menu-card__title text-center">${menu.title}</h4>
                        <div class="menu-card__tags">
                            <span class="tag tag-theme"><i class="bi bi-palette"></i>
                                    ${menu.themeMenu}
                            </span>
                            <span class="tag tag-regime"><i class="bi bi-egg-fried"></i>
                                    ${menu.dietMenu}
                            </span>
                        </div>
                        <p class="menu-card__desc">${menu.descriptionMenu?.substring(0, 80) || ''}…</p>
                        <div class="menu-price">
                            <i class="bi bi-currency-euro"></i> ${parseFloat(menu.price).toFixed(2)} € / pers
                        </div>
                        <small><i class="bi bi-exclamation-triangle-fill"></i> Allergènes : ${menu.allAllergens || 'Aucune allergènes'}</small>
                        <div class="d-flex justify-content-center py-2">
                        <button class="btn btn-primary view-detail-btn" data-id="${menu.id}">
                            Détails du menu
                        </button>
                        </div>
                    <div class="card-footer menu-card__footer" data-show="ROLE_ADMIN,ROLE_EMPLOYEE" style="display:none;">
                        <button class="btn btn-outline-success btn-sm edit-menu-btn" data-id="${menu.id}">Modifier</button>
                        <button class="btn btn-outline-danger btn-sm delete-menu-btn" data-id="${menu.id}">Supprimer</button>
                    </div>
                    </div>
                </div>
                </div>
            </div>
`;
}).join('');    
    showAndHideElementsForRoles();
    initButtons();
    }

// Initialiser les listeners pour les boutons d'action sur les menus (détails, édition, suppression, pré-commande)
function initButtons() {
    // Détails du menu
    const container = document.getElementById('menu-cards-container');
    if (!container) {
        console.error('Conteneur pour les cartes de menu introuvable.');
        return;
    }
    container.addEventListener('click', async (e) => {
        
        const detailBtn = e.target.closest('.view-detail-btn');
        if (detailBtn) {
            const id = detailBtn.dataset.id;
            if (!id) return;
            try{
                await getMenuDetail(id);
            } catch (error) {
                console.error("Erreur lors de la récupération des détails du menu :", error);
                alert("Impossible de charger les détails du menu. Veuillez réessayer plus tard.");
            }
        }
    const editBtns = e.target.closest('.edit-menu-btn');
    if (editBtns) {
        e.preventDefault();
        const id = editBtns.dataset.id;
        const form = document.getElementById('editMenuForm');
        if (form) {
            form.setAttribute('data-id', id);
        }
        if (id) {
            fillEditMenuModal(id);
        } else {
            console.error('ID de menu non trouvé pour le bouton de modification cliqué.');
        }
    }
    const deleteBtns = e.target.closest('.delete-menu-btn');
    if (deleteBtns) {
        e.preventDefault();
        const id = deleteBtns.dataset.id;
        const modalEl = document.getElementById('deleteMenuModal');
        if (modalEl) {
            const confirmDeleteBtn = modalEl.querySelector('.confirmDeleteMenu');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.onclick = async () => {
                    try {
                    await deleteMenu(id );
                        window.location.reload();
                    } catch (error) {
                        console.error("Erreur lors de la suppression du menu :", error);
                    }
                };
            }
            const deleteModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            deleteModalInstance.show();
        } else {
            console.error('Modal de confirmation de suppression introuvable.');
        }
    }
      const editDetailBtn = e.target.closest('.editCarteMenuBtn');
    if (editDetailBtn) {
        e.preventDefault();
        const id = editDetailBtn.dataset.id;
        if (id) {
            getMenuDetail(id);
            await fillEditDetailMenuCardModal(id);
        } else {
            console.error('ID de menu non trouvé pour le bouton d\'édition dans les détails du menu.');
        }
    }
    
    const editImgBtn = e.target.closest('.edit-img-menu-btn');
        if (editImgBtn) {
            e.preventDefault();
            const id = editImgBtn.dataset.id;
            console.log("EDIT IMAGE ID:", id);
            if (id) openImageUpload(id);
            return;
        }
    const deleteImgBtn = e.target.closest('.delete-img-menu-btn');
        if (deleteImgBtn) {
            e.preventDefault();
            const id = deleteImgBtn.dataset.id;
            console.log("DELETE IMAGE ID:", id);
            if (id) {
                try {
                    await updateMenu(id, { picture: null });
                    alert("Image supprimée !");
                    const menus = await getMenus();
                    displayMenus(menus);
                }
                catch (error) {
                    console.error("Erreur lors de la suppression de l'image :", error);
                    alert("Erreur lors de la suppression de l'image. Veuillez réessayer.");
                }
            }
            return;
        }
});
// Bouton d'ajout de menu
const addMenuBtn = document.getElementById('add-Menu-Btn');
    if (addMenuBtn) {
        addMenuBtn.addEventListener('click', () => {
            const modalEl = document.getElementById('editMenuModal');
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        });
    }
}

    // Boutons de modification et suppression de menu
function openImageUpload(id) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';

    input.onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert("Format invalide");
            return;
        }

        const formData = new FormData();
        formData.append('picture', file);

        try {
            const res = await fetch(`http://localhost:8000/api/menu/${id}/picture`, {
                method: "POST",
                headers: {
                    'X-AUTH-TOKEN': getToken()
                },
                body: formData
            });

            const text = await res.text();

            if (!res.ok) {
                throw new Error(text);
            }

            alert("Image mise à jour !");

            const menus = await getMenus();
            displayMenus(menus);

        } catch (err) {
            console.error(err);
            alert("Erreur lors de la mise à jour de l'image");
        }
    };

    input.click();
}


function initModals(){
    try{
    const detailModalEl = document.getElementById('detailscarteModal');
    const editMenuModalEl = document.getElementById('editMenuModal');
    const deleteMenuModalEl = document.getElementById('deleteMenuModal');

    if (detailModalEl) new bootstrap.Modal(detailModalEl);
    if (editMenuModalEl) new bootstrap.Modal(editMenuModalEl);
    if (deleteMenuModalEl) new bootstrap.Modal(deleteMenuModalEl);


    } 
    catch(error){
        console.error('Erreur lors de l\'initialisation des modals :', error);
}

}
// Initialiser le formulaire d'édition de menu pour la création et la modification
function initForm() {
    const form = document.getElementById('editMenuForm');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        console.log("Formulaire intercepté !");
        const formData = new FormData(form);
        const $data = {
            title: formData.get('title'),
            descriptionMenu: formData.get('descriptionMenu'),
            price: formData.get('priceMenu'),
            minPeople:parseInt(formData.get('minPersons')),
            orderBefore: parseInt(formData.get('orderBefore')),
            stock: parseInt(formData.get('stock')),
            themeMenu: formData.get('themeMenu'),
            dietMenu: formData.get('dietMenu'),
            isAvailable: formData.get('isAvailable') === 'on',
            picture: formData.get('file') || null
        }

        const fileInput = document.getElementById('editMenuPicture');

        // 👉 image seulement si choisie
        if (fileInput && fileInput.files.length > 0) {
            formData.append("picture", fileInput.files[0]);
        }

        const menuId = form.getAttribute('data-id');

        try {
            if (menuId) {
                await updateMenu(menuId, $data);
                alert("Menu mis à jour !");
            }
                else {
                await enregistrerMenu($data, formData);
                alert("Menu créé !");
            }

            const modalEl = document.getElementById('editMenuModal');
            bootstrap.Modal.getInstance(modalEl).hide();

            window.location.reload();

        } catch (error) {
            console.error("Erreur API :", error);
        }
    });

    const OrderForm = document.getElementById('OrderForm');
    OrderForm.addEventListener('submit', async (e) => {
        e.preventDefault(); 
        const user = await getUserInfo();
        console.log("Données utilisateur récupérées pour la commande :", user);
        
        // Récupérer les données du formulaire
        const menu = e.target.getAttribute('data-id');
        const orderData = {
                    menu : menu,
                    userId: user.id,
                    numberOfPeople: parseInt(document.getElementById('numberOfPeople').value),
                    deliveryAddress: document.getElementById('deliveryAddress').value,
                    deliveryCity: document.getElementById('deliveryCity').value,
                    deliveryPostalCode: document.getElementById('deliveryPostalCode').value,
                    deliveryDate: document.getElementById('deliveryDate').value, 
                    deliveryTime: document.getElementById('deliveryTime').value,
                    
        };
        console.log("Données de commande prêtes :", orderData);

        try {
            await createOrder(orderData);

            const modalEl = document.getElementById('PreOrderModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            modalInstance.hide();// Ferme le modal de pré-commande

            // Affiche le modal de détails de la commande
            fillNewOrderDetailsModal(orderData, user);

            const detailsModal = new bootstrap.Modal(document.getElementById('OrderDetailsModal'));
            detailsModal.show();
        } catch (error) {
            console.error("Erreur lors de l'envoi :", error);
        }
    });

}
// Pré-remplir le formulaire d'édition avec les détails du menu
export async function fillEditMenuModal(id) {
    try {
        const menu = await getMenuById(id);
        if (!menu) {
            console.error("Menu non trouvé pour l'ID :", id);
            return;
        }
        const form = document.getElementById('editMenuForm');
        if (!form) {
            console.error("Formulaire d'édition de menu introuvable.");
            return;
        }

        // Pré-remplir le formulaire avec les détails du menu
        form.querySelector('[name="title"]').value = menu.title;
        form.querySelector('[name="descriptionMenu"]').value = menu.descriptionMenu;
        form.querySelector('[name="priceMenu"]').value = menu.price;
        form.querySelector('[name="minPersons"]').value = menu.minPeople;
        form.querySelector('[name="orderBefore"]').value = menu.orderBefore;
        form.querySelector('[name="stock"]').value = menu.stock;
        form.querySelector('[name="themeMenu"]').value = menu.themeMenu;
        form.querySelector('[name="dietMenu"]').value = menu.dietMenu;
        
        

        // Afficher le modal
        const modalEl = document.getElementById('editMenuModal');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    } catch (error) {
        console.error("Erreur lors de l'édition du menu :", error);
    }
}
// Afficher les détails d'un menu dans une modal
export async function getMenuDetail(id) {
    try {
        const [menu, dishes] = await Promise.all([
            getMenuById(id),
            getMenuDishes(id)
        ]);     
        console.log("Détails du menu récupérés :", { menu, dishes });
        fillDetailModal(menu, dishes);
        const modalOrderBtn = document.querySelector('.pre-order-btn');
        if (modalOrderBtn) {
            modalOrderBtn.setAttribute('data-id', id);
        }
        const modalEl = document.getElementById('detailscarteModal');
        // On nettoie les anciens voiles noirs s'ils existent
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
        const editDetailBtn = document.getElementById('editCarteMenuBtn');  
        if (editDetailBtn) {
            editDetailBtn.setAttribute('data-id', id);
        }
        

    } catch (error) {
        console.error('Erreur :', error);
    }
}
// Pré-remplir la modale de détails du menu avec les informations du menu et de ses plats
export function fillDetailModal(menu, dishes) {
    if (!menu || !dishes) return;

    const dishesList = dishes.map(dish => {
        return {
            name: dish.name,
            price: dish.price,
            description: dish.description,
            category: dish.category || '',
            displayAllergens: dish.allergensName && dish.allergensName.length > 0 ? dish.allergensName.join(', ') : 'Aucun'
        };
    });
    console.log("Plats prêts pour le filtrage :", dishesList);
    const modalBody = document.getElementById('detailsMenuContent');
    const modalTitle = document.getElementById('detailscarteModalLabel');
    modalTitle.textContent = menu.title;
    // 2. FILTRAGE : séparer les plats par catégorie
    const entrees = dishesList.filter(d => d.category?.toLowerCase().includes('entree'));
    const plats = dishesList.filter(d => d.category?.toLowerCase().includes('plat'));
    const desserts = dishesList.filter(d => d.category?.toLowerCase().includes('dessert'));
   
    const generateHtml = (title, icon, list) => {
        if (list.length === 0) return '';
        
        return `
            <div class="menu-category mt-3">
                <h4><i class="bi ${icon}"></i> ${title}</h4>
                ${list.map(d => `
                    <div class="menu-item py-2">
                        <div class="d-flex justify-content-between">
                            <div class="col-lg-10 menu-item-title">${d.name}?</div>
                            <div class="col-lg-2 menu-item-price">${parseFloat(d.price).toFixed(2)} €</div>
                        </div>
                        <p class="menu-item-description">${d.description || ''}</p>
                    </div>
                    <small ><i class="bi bi-exclamation-triangle-fill"></i><small> Allergènes : ${d.displayAllergens}</small></small>
                `).join('')}
            </div>`;
    };

    modalBody.innerHTML = `
    <h2 class="Menu-title text-center " id="detailscarteModalLabel">${menu.title}</h2>
    <div class="container">
            ${generateHtml('Entrées', 'bi-egg', entrees)}
            ${generateHtml('Plats Principaux', 'bi-main-dish', plats)}
            ${generateHtml('Desserts', 'bi-cake', desserts)}
            
            <div class="mt-4 p-3 bg-light text-center">
                <strong>Prix Menu : ${menu.price}€ / personne</strong>
            </div>
        <div class="container mb-4 info-section">
            <div class="info-header">
                <h5>Allergènes et informations supplémentaires :</h5>
            </div>
            <div class="info-content">
                <ul>
                <li><i class="bi bi-info-circle-fill"></i><small> Ce menu est conçu pour un minimum de ${menu.minPeople || '1'} personnes.</small></li>
                <li><i class="bi bi-exclamation-triangle-fill"></i><small>Ce menu nécessite une commande ${menu.orderBefore || '2'} jours à l'avance.</small></li>
                <li><i class="bi bi-exclamation-triangle-fill"></i><small> Ce menu contient de ${menu.allAllergenes || 'aucun'} allergènes.</small></li>
                <li><i class="bi bi-info-circle-fill"></i><small>Contactez notre équipe pour toute demande spéciale ou question concernant le menu.</small></li>
                </ul>
            </div>
        </div>
    </div>
    `;
}

// ====================== FILTRES ======================
// Construire les paramètres de filtre à partir des sélections de l'utilisateur
export function buildFilters() {
  const filters = {};

  const price = document.getElementById("prixFilter").value;
  const fourchette = document.getElementById("fourchetteFilter").value;
  const minPersons = document.getElementById("personnesFilter").value;
  const themeMenu = document.getElementById("themeFilter").value;
  const dietMenu = document.getElementById("dietFilter").value;

    // 🎯 prix
    if (price && price > 0) {
        filters.price_max = price;
    }

  // 🎯 personnes
  if (minPersons && minPersons !== "all") {
    filters.min_persons = minPersons;
  }

  // 🎯 thème
  if (themeMenu !== "all") {
    filters.theme = themeMenu;
  }
  if (fourchette !== "all") {
    const [min, max] = fourchette.split('-');
    filters.price_min = min;
    filters.price_max = max;
  }

  // 🎯 régime
  if (dietMenu !== "all") {
    filters.diet = dietMenu;
  }

  return filters;
}
async function applyFilters() {
  try {
    const filters = buildFilters();
    console.log("Filtres appliqués :", filters);
    const filteredMenus = await getMenus(filters);
    displayMenus(filteredMenus);
  } catch (error) {
    console.error("Erreur lors de l'application des filtres :", error);
    alert("Une erreur est survenue lors de l'application des filtres. Veuillez réessayer.");
  }
}
// Ajouter des écouteurs d'événements aux éléments de filtre
const price = document.getElementById("prixFilter");
const fourchette = document.getElementById("fourchetteFilter");
const minPersons = document.getElementById("personnesFilter");
const themeMenu = document.getElementById("themeFilter");
const dietMenu = document.getElementById("dietFilter");

const resetFiltersBtn = document.getElementById("resetFilters");
if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener("click", () => {
        price.value = 50;
        fourchette.value = "all";
        minPersons.value = "1";
        themeMenu.value = "all";
        dietMenu.value = "all";
        applyFilters();
    });
}
// ====================== FIN FILTRES ======================

