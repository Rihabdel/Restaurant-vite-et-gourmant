import { getMenus, getMenuById, getMenuDishes } from '/js/api.js';
import { showAndHideElementsForRoles } from '/js/script.js';

// ------------------------------
// Affichage des cartes de menus
// ------------------------------
function displayMenus(menus) {
    const container = document.getElementById('menu-cards-container');
    if (!container) return;

    container.innerHTML = menus.map(menu => `
        <div class="col">
            <div class="menu-card h-100">
                <div class="card-image">
                    <img src="${menu.imageUrl || '/scss/images/placeholder.jpg'}" class="card-img-top" alt="${menu.title}">
                    <div class="action-image-buttons" data-role="admin,employee" style="display: none;">
                        <button class="btn btn-outline-light edit-menu-btn" data-id="${menu.id}"><i class="bi bi-pencil"></i>Modifier</button>
                        <button class="btn btn-outline-light delete-menu-btn" data-id="${menu.id}"><i class="bi bi-trash"></i>Supprimer</button>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title">${menu.title}</h5>
                    <p class="card-description">${menu.description?.substring(0, 80) || ''}…</p>
                    <ul>
                        <li>Minimum pour ${menu.minPersons} personnes</li>
                        <li>Prix: ${menu.price} € / personne</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary view-detail-btn" data-id="${menu.id}">Voir le détail</button>
                </div>
                <div class="action-card-buttons" data-role="admin,employee" style="display: none;">
                    <button class="btn btn-success edit-menu-btn" data-id="${menu.id}"><i class="bi bi-pencil"></i>Modifier</button>
                    <button class="btn btn-danger delete-menu-btn" data-id="${menu.id}"><i class="bi bi-trash"></i>Supprimer</button>
                </div>
            </div>
        </div>
    `).join('');

    // Afficher/masquer les boutons selon le rôle
    showAndHideElementsForRoles();
}

// ------------------------------
// Gestion des filtres
// ------------------------------
function getFilterParams() {
    const params = {};

    const diet = document.getElementById('regimeFilter')?.value;
    if (diet && diet !== 'all') params.diet = diet;

    const theme = document.getElementById('themeFilter')?.value;
    if (theme && theme !== 'all') params.theme = theme;

    const fourchette = document.getElementById('fourchetteFilter')?.value;
    if (fourchette && fourchette !== 'all') {
        if (fourchette === 'low') params.price_max = 100;
        else if (fourchette === 'medium') {
            params.price_min = 10;
            params.price_max = 100;
        } else if (fourchette === 'high') params.price_min = 100;
    }

    const prixRange = document.getElementById('prixFilter')?.value;
    if (prixRange && prixRange !== '0') params.price_max = prixRange;

    const personnes = document.getElementById('personnesFilter')?.value;
    if (personnes && personnes !== 'all') {
        const match = personnes.match(/\d+/);
        if (match) params.min_persons = parseInt(match[0], 10);
    }

    Object.keys(params).forEach(key => (params[key] === undefined || params[key] === '') && delete params[key]);
    return params;
}

async function applyFilters() {
    const filters = getFilterParams();
    try {
        const menus = await getMenus(filters);
        displayMenus(menus);
    } catch (error) {
        console.error('Erreur filtrage :', error);
    }
}

function attachFilterListeners() {
    const filterIds = [ 'fourchetteFilter', 'prixFilter', 'personnesFilter', 'themeFilter', 'dietFilter'];
    filterIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', applyFilters);
    });

    const prixRange = document.getElementById('prixFilter');
    if (prixRange) {
        prixRange.addEventListener('input', () => {
            const span = prixRange.nextElementSibling;
            if (span) span.textContent = `0-${prixRange.value}€`;
        });
        prixRange.addEventListener('change', applyFilters);
    }

    document.getElementById('resetFilters')?.addEventListener('click', () => {
        document.querySelectorAll('#filtresCollapse select, #filtresCollapse input[type="range"]').forEach(el => {
            if (el.tagName === 'SELECT') el.value = 'all';
            else if (el.type === 'range') {
                el.value = 0;
                const span = el.nextElementSibling;
                if (span) span.textContent = '0-1000€';
            }
        });
        applyFilters();
    });
}

// ------------------------------
// Gestion du clic sur "Voir le détail"
// ------------------------------
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.view-detail-btn');
    if (!btn) return;

    const id = btn.dataset.id;
    try {
        const menu = await getMenuById(id);
        const dishes = await getMenuDishes(id);
        fillDetailModal(menu, dishes);

        const modal = new bootstrap.Modal(document.getElementById('detailscarteModal'));
        modal.show();
    } catch (error) {
        console.error(error);
        alert('Impossible de charger les détails du menu.');
    }
});

// ------------------------------
// Remplissage de la modale de détail
// ------------------------------
function fillDetailModal(menu, dishesData) {
    // dishesData est le tableau reçu de l'API (avec les objets { menu, dish, displayOrder })
    const dishes = dishesData.map(item => item.dish); // on extrait les plats

    // Titre du menu
    const titleEl = document.querySelector('.Menu-title');
    if (titleEl) titleEl.textContent = menu.title;

    // Fonction pour générer le HTML d'un plat
    const dishHtml = (dish) => {
        // Gestion des allergènes (adapter selon la structure réelle)
        let allergens = '';
        if (dish.listOfAllergensFromDishes && dish.listOfAllergensFromDishes.length > 0) {
            // Si c'est un tableau de tableaux, on aplatit
            allergens = dish.listOfAllergensFromDishes.flat().join(', ');
        }
        return `
            <div class="menu-item">
                <div class="row row-cols-2 menu-item-header">
                    <div class="col-lg-10 menu-item-title">${dish.name}</div>
                    <div class="col-lg-2 menu-item-price">${dish.price ? dish.price + '€' : ''}</div>
                </div>
                <p class="menu-item-description">${dish.description || ''}</p>
                <small><i class="bi bi-exclamation-triangle-fill"></i> Contient de ${allergens || 'aucun allergène'}</small>
            </div>
        `;
    };

    // Entrées
    const entreesContainer = document.getElementById('entrees-container');
    if (entreesContainer) {
        const entrees = dishes.filter(d => d.category === 'entree');
        entreesContainer.innerHTML = entrees.length 
            ? entrees.map(dishHtml).join('') 
            : '<p>Aucune entrée disponible</p>';
    }

    // Plats
    const platsContainer = document.getElementById('plats-container');
    if (platsContainer) {
        const plats = dishes.filter(d => d.category === 'plat');
        platsContainer.innerHTML = plats.length 
            ? plats.map(dishHtml).join('') 
            : '<p>Aucun plat disponible</p>';
    }

    // Desserts
    const dessertsContainer = document.getElementById('desserts-container');
    if (dessertsContainer) {
        const desserts = dishes.filter(d => d.category === 'dessert');
        dessertsContainer.innerHTML = desserts.length 
            ? desserts.map(dishHtml).join('') 
            : '<p>Aucun dessert disponible</p>';
    }

    // Prix total par personne (depuis l'objet menu)
    const priceEl = document.querySelector('.totalPrice h4');
    if (priceEl) priceEl.textContent = `Prix total par personne : ${menu.price} Euros`;

    // Informations supplémentaires
    const infoList = document.querySelector('.info-content ul');
    if (infoList) {
        infoList.innerHTML = `
            <li><i class="bi bi-info-circle-fill"></i><small> Ce menu est conçu pour un minimum de ${menu.minPeople} personnes.</small></li>
            <li><i class="bi bi-exclamation-triangle-fill"></i><small> ${menu.conditions || 'Aucune condition particulière'}</small></li>
            <li><i class="bi bi-exclamation-triangle-fill"></i><small> Merci de consulter notre liste complète des allergènes pour éviter tout risque.</small></li>
            <li><i class="bi bi-info-circle-fill"></i><small>Contactez notre équipe pour toute demande spéciale ou question concernant le menu.</small></li>
        `;
    }
}

// ------------------------------
// Initialisation (appelée par le routeur)
// ------------------------------
export default async function initMenu() {
    console.log("Initialisation de la page menu");
    attachFilterListeners();
    try {
        const menus = await getMenus();
        displayMenus(menus);
    } catch (error) {
        console.error(error);
        const container = document.getElementById('menu-cards-container');
        if (container) container.innerHTML = '<div class="alert alert-danger">Impossible de charger les menus.</div>';
    }
}