import { getMenus, getMenuById, getMenuDishes} from './api.js';
import { showAndHideElementsForRoles } from './script.js';

export default async function initMenu() {
    console.log("Initializing menu page...");
    try {
        const menus =await getMenus();
        displayMenus(menus);
        initModals();
    } catch (error) {
        console.error('Erreur chargement menus :', error);  
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
                        <button class="btn btn-outline-light edit-menu-btn" data-id="${menu.id}"><i class="bi bi-pencil"></i>Modifier</button>
                        <button class="btn btn-outline-light delete-menu-btn" data-id="${menu.id}"><i class="bi bi-trash"></i>Supprimer</button>
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
    btn.addEventListener('click', async () => {
        const menuId = btn.getAttribute('data-id');
        if (menuId) {
            await getMenuDetail(menuId);
            e.currentTarget.blur(); // Retire le focus du bouton après le clic
            e.preventDefault();
        } else {
            console.error('ID de menu non trouvé pour le bouton cliqué.');
        }
    });
});
}
function initModals(){
    try{
    const detailModalEl = document.getElementById('detailscarteModal');
    const editModalEl = document.getElementById('editMenuModal');
    if (detailModalEl) new bootstrap.Modal(detailModalEl);
    if (editModalEl) new bootstrap.Modal(editModalEl);
    } 
    catch(error){
        console.error('Erreur lors de l\'initialisation des modals :', error);
}
}
export async function getMenuDetail(id) {
    try {
        
        const [menu, dishes] = await Promise.all([
            getMenuById(id),
            getMenuDishes(id)
        ]);
        
        fillDetailModal(menu, dishes);
    

        const modalEl = document.getElementById('detailscarteModal');
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
        </div>
        <div class="container mb-4 info-section">
          <div class="info-header">
              <h5>Allergènes et informations supplémentaires :</h5>
          <div class="info-content">
            <ul>
        <li><i class="bi bi-info-circle-fill"></i><small> Ce menu est conçu pour un minimum de ${menu.minPeople || '1'} personnes.</small></li>
        <li><i class="bi bi-exclamation-triangle-fill"></i><small>Ce menu nécessite une commande ${menu.orderBefore || '2'} jours à l'avance.</small></li>
      <li><i class="bi bi-exclamation-triangle-fill"></i><small> Merci de consulter notre liste complète des allergènes pour éviter tout risque.</small></li>
      <li><i class="bi bi-info-circle-fill"></i><small>Contactez notre équipe pour toute demande spéciale ou question concernant le menu.</small></li>
            </ul>
          </div>
    `;
}

