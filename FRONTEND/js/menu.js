import { getMenus, getMenuById, getMenuDishes, enregistrerMenu, updateMenu, deleteMenu} from './api.js';
import { showAndHideElementsForRoles } from './script.js';

export default async function initMenu() {
    console.log("Initializing menu page...");
    
    try {
        const menus =await getMenus();
        displayMenus(menus);
        showAndHideElementsForRoles();
        initModals();
        initForm();
        console.log("Menu page initialized successfully.");
    } catch (error) {
        console.error("Error initializing menu page:", error);
    }
}
//afficher les menus 
function displayMenus(menus){
    
    const container = document.getElementById('menu-cards-container');
    if (!container) {
        console.error('Conteneur pour les cartes de menu introuvable.');
        return;
    }
    container.innerHTML = menus.map(menu=> {
        const imageUrl = menu.picture ? `/api/menu/${menu.id}/picture` : '/scss/images/menu.jpg';
            return `
            <div class="col">
            <div class="menu-card h-100">
                <div class="card-image">
                    <img src="${imageUrl}" class="card-img-top" alt="${menu.title}">
                    <div class="action-image-buttons" data-show="ROLE_ADMIN,ROLE_EMPLOYEE" style="display:none">
                        <button class="btn btn-outline-light edit-img-menu-btn" data-id="${menu.id}"><i class="bi bi-pencil"></i>Modifier</button>
                        <button class="btn btn-outline-light delete-img-menu-btn" data-id="${menu.id}"><i class="bi bi-trash"></i>Supprimer</button>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title">${menu.title}</h5>
                    <p class="card-description">${menu.descriptionMenu?.substring(0, 80) || ''}…</p>
                    <ul>
                        <li>Minimum pour ${menu.minOfPeople} personnes</li>
                        <li>Prix: ${menu.price} € / personne</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary view-detail-btn" data-id="${menu.id}">Voir le détail</button>
                            </div>
                            <div class="action-card-buttons" data-show="ROLE_ADMIN,ROLE_EMPLOYEE" style="display: none;">
                    <button class="btn btn-success edit-menu-btn" data-id="${menu.id}"><i class="bi bi-pencil"></i>Modifier</button>
                    <button class="btn btn-danger delete-menu-btn" data-id="${menu.id}"><i class="bi bi-trash"></i>Supprimer</button>
                </div>
            </div>
        </div>`;
}).join('');    
    showAndHideElementsForRoles();
    initButtons();
    }
function initButtons() {
const detailBtns = document.querySelectorAll('.view-detail-btn');
detailBtns.forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const menuId = btn.getAttribute('data-id');
        if (menuId) {
            await getMenuDetail(menuId);
        } else {
            console.error('ID de menu non trouvé pour le bouton cliqué.');
        }
    });
});
const addMenuBtn = document.getElementById('add-Menu-Btn');
    if (addMenuBtn) {
        addMenuBtn.addEventListener('click', () => {
            const modalEl = document.getElementById('editMenuModal');
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        });
    }
const editBtns = document.querySelectorAll('.edit-menu-btn');
editBtns.forEach(btn => {
    btn.addEventListener('click', async (e) => {    
        e.preventDefault();
        const id = btn.getAttribute('data-id')
        const form = document.getElementById('editMenuForm');
       
        if (form) {
            form.setAttribute('data-id', id);
        }
        await editMenu(id);
    });
});
const deleteBtns = document.querySelectorAll('.delete-menu-btn');
deleteBtns.forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const id = btn.getAttribute('data-id');
        const modalEl = document.getElementById('deleteMenuModal');
        if (modalEl) {
            const confirmDeleteBtn = modalEl.querySelector('.confirmDeleteMenu');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.onclick = async () => {
                    try {
                        await deleteMenu(id);
                        
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

    });
}
);
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
function initForm() {
    const form = document.getElementById('editMenuForm');
    
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault(); 
            console.log("Formulaire intercepté !");

            const formData = new FormData(form);
            const data = {
                title: formData.get('title'),
                descriptionMenu: formData.get('descriptionMenu'),
                price: formData.get('priceMenu'),
                minPeople: parseInt(formData.get('minPersons')),
                orderBefore: parseInt(formData.get('orderBefore')),
                stock: parseInt(formData.get('stock')),
                themeMenu: formData.get('theme'),
                dietMenu: formData.get('diet')
            };
            try {
                const menuId = form.getAttribute('data-id');
                if (menuId) {
                    await updateMenu(menuId, data);
                } else {
                    await enregistrerMenu(data);
                }
                // Fermer le modal proprement
                const modalEl = document.getElementById('editMenuModal');
                bootstrap.Modal.getInstance(modalEl).hide();
                window.location.reload(); 
            } catch (error) {
                console.error("Erreur API :", error);
            }
        });
    }

}
export async function editMenu(id) {
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
        form.querySelector('[name="theme"]').value = menu.themeMenu;
        form.querySelector('[name="diet"]').value = menu.dietMenu;

        // Afficher le modal
        const modalEl = document.getElementById('editMenuModal');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    } catch (error) {
        console.error("Erreur lors de l'édition du menu :", error);
    }
}

export async function loadDeleteModal(id) {   
        const deleteModalEl = document.getElementById('deleteMenuModal');
        const confirmDeleteBtn = deleteModalEl.querySelector('#confirmDeleteMenu');
        confirmDeleteBtn.onclick = async () => {
            try {
                await deleteMenu(id);
                window.location.reload();
            } catch (error) {
                console.error("Erreur lors de la suppression du menu :", error);
            }
        };
        const deleteModalInstance = bootstrap.Modal.getOrCreateInstance(deleteModalEl);
        deleteModalInstance.show();
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
        const modalEl = document.getElementById('detailscarteModal');
        // On nettoie les anciens voiles noirs s'ils existent
document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
document.body.classList.remove('modal-open');
document.body.style.overflow = '';

// On affiche le modal
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
        
    } catch (error) {
        console.error('Erreur :', error);
    }
}

function fillDetailModal(menu, dishes) {
    if (!menu || !dishes) return;

    const dishesList = dishes.map(item => {
        const d = item.dish;
        const allergensArray = d.listOfAllergensFromDishes || [];
        
        return {
            name: d.name,
            price: d.price,
            description: d.description,
            category: d.category || '',
            displayAllergens: allergensArray.length > 0 ? allergensArray.join(', ') : 'Aucun'
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
                            <div class="col-lg-2 menu-item-price">${d.price? d.price.toFixed(2) : '...'}€</div>
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
                <li><i class="bi bi-exclamation-triangle-fill"></i><small> Merci de consulter notre liste complète des allergènes pour éviter tout risque.</small></li>
                <li><i class="bi bi-info-circle-fill"></i><small>Contactez notre équipe pour toute demande spéciale ou question concernant le menu.</small></li>
                </ul>
            </div>
        </div>
    </div>
    `;
}
