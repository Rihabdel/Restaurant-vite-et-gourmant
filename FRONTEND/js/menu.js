import { getMenus, getMenuById, getMenuDishes } from './api.js';
import { getToken, showAndHideElementsForRoles } from './script.js';


// Initialisation de la page
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
function displayMenus(menus) {
    const imageUrl = menus.picture ? `/api/menu/${menus.id}/picture` : '/scss/images/menu.jpg';
    menus.forEach(menu => console.log('Menu ID:', menu.id, 'picture:', menu.picture));
    const container = document.getElementById('menu-cards-container');
    if (!container) return;

    container.innerHTML = menus.map(menu => `
        <div class="col">
            <div class="menu-card h-100">
                <div class="card-image">
                    <img src="${imageUrl}" class="card-img-top" alt="${menu.title}">
                    <div class="action-image-buttons" ddata-show="ROLE_ADMIN,ROLE_EMPLOYEE" style="display:none">
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
        </div>
    `).join('');
    // Afficher/masquer les boutons selon le rôle de l'utilisateur
    showAndHideElementsForRoles();
}


// ====================== FILTRES ======================
function getFilterParams() {
    const params = {};
    const diet = document.getElementById('dietFilter')?.value;
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
    const filterIds = ['fourchetteFilter', 'prixFilter', 'personnesFilter', 'themeFilter', 'dietFilter'];
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

// afficher les détails d'un menu dans une modale au clic sur "Voir le détail"
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
 
// remplir la carte de menu avec les données du menu et des plats
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
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.edit-menu-btn');
    if (btn) {
        const menuId = btn.dataset.id;
        editMenuModal(menuId);
    }
});

document.addEventListener('click', async (e) => {   
    const btn = e.target.closest('.delete-menu-btn');
    if (btn) {
        const menuId = btn.dataset.id;
        // Afficher une confirmation avant de supprimer
        if (confirm('Êtes-vous sûr de vouloir supprimer ce menu ?')) {
            try {
                await deleteMenu(menuId);
                alert('Menu supprimé avec succès');
                // Recharger les menus après suppression
                const menus = await getMenus();
                displayMenus(menus);
            } catch (error) {
                console.error('Erreur suppression menu :', error);
                alert('Erreur lors de la suppression du menu.');
            }
        }
    }
});


async function editMenuModal(menuId)     {
    const menuData = await getMenuById(menuId);
    document.getElementById('editMenuTitle').value = menuData.title || '';
    document.getElementById('editMenuDescription').value = menuData.descriptionMenu || '';
    document.getElementById('editMenuPrice').value = menuData.price || '';
    document.getElementById('editMenuMinPersons').value = menuData.minOfPeople || '';
    const container = document.getElementById('currentPictureContainer');
    if (menuData.picture) {
        document.getElementById('currentPicture').src = menuData.picture;
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
    document.getElementById('editMenuModal').dataset.menuId = menuId;
    new bootstrap.Modal(document.getElementById('editMenuModal')).show();
}

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
    });
}
document.getElementById('saveMenuBtn')?.addEventListener('click', saveMenu);
async function saveMenu(event) {
    event.preventDefault();
    const form = document.getElementById('editMenuForm');
    const formData = new FormData(form);
    const menuData = {
        title: formData.get('title'),
        descriptionMenu: formData.get('description'),
        price: Number.parseFloat(formData.get('price')),
        minOfPeople: Number.parseInt(formData.get('minPersons'), 10),

    };
    // Gérer l'image si un fichier a été sélectionné
    const pictureInput = document.getElementById('menuPicture');
    if (pictureInput && pictureInput.files.length > 0) {
        try {
            menuData.picture = await fileToBase64(pictureInput.files[0]);
        } catch (error) {
            console.error('Erreur conversion image :', error);
            alert('Erreur lors de la lecture de l\'image. Veuillez réessayer.');
            return;
        }
    }
    // Si aucune nouvelle image n'est fournie, on peut choisir de ne pas inclure le champ "picture" ou de l'envoyer à null selon l'API
    const base64 = menuData.picture ? menuData.picture.split(',')[1] : null; // Extraire la partie base64 si c'est une data URL
    menuData.picture = base64; // Envoyer uniquement la partie base64 au backend
  
    const menuId = document.getElementById('editMenuModal').dataset.menuId;  
    const url = menuId ? `/api/menu/${menuId}` : '/api/menu/new';
    const method = menuId ? 'PUT' : 'POST';
    try {
        const response = await fetch(url, {
            method,
            headers: { 'X-AUTH-TOKEN': getToken(), 'Content-Type': 'application/json' },
            body: JSON.stringify(menuData)
        });
        if (!response.ok) throw new Error('Erreur sauvegarde menu');
        alert('Menu sauvegardé avec succès');
        const modal = bootstrap.Modal.getInstance(document.getElementById('editMenuModal'));
        if (modal) modal.hide();
        // Recharger les menus
        const menus = await getMenus();
        displayMenus(menus);
    } catch (error) {
        console.error(error);
        alert('Erreur lors de la sauvegarde du menu.');
    }
}
document.getElementById('removePictureBtn')?.addEventListener('click', async () => {
    const menuId = document.getElementById('editMenuForm').dataset.menuId;
    if (!menuId) return;
    if (!confirm('Supprimer l\'image ?')) return;
    try {
        const response = await fetch(`/api/menu/${menuId}/remove-picture`, {
            method: 'POST',
            headers: { 'X-AUTH-TOKEN': getToken() }
        });
        if (!response.ok) throw new Error('Erreur suppression');
        // Masquer le conteneur
        document.getElementById('currentPictureContainer').style.display = 'none';
        // Effacer le champ file
        document.getElementById('menuPicture').value = '';
        alert('Image supprimée');
    } catch (error) {
        console.error(error);
        alert('Erreur : ' + error.message);
    }
});

async function deleteMenu(menuId) {
    try {
        const response = await fetch(`/api/menu/${menuId}`, {
            method: 'DELETE',
            headers: { 'X-AUTH-TOKEN': getToken() }
        });
        if (!response.ok) throw new Error('Erreur suppression menu');
    } catch (error) {
        console.error(error);
        throw error; // Propager l'erreur pour la gestion dans le caller
    }

}
async function loadMenusIntoSelect() {
    try {
        const menus = await getMenus();
        const menuSelect = document.getElementById('menuSelect');
        if (!menuSelect) return;
        menuSelect.innerHTML = '<option value="">Sélectionnez un menu</option>';
        menus.forEach(menu => {
            const option = document.createElement('option');
            option.value = menu.id;
            option.textContent = menu.title;
            option.dataset.price = menu.price;
            menuSelect.appendChild(option);
        }
        );
    }
        catch (error) {
        console.error('Erreur chargement menus', error);
        alert('Erreur lors du chargement des menus.');
    }
}

export async function getMenuDetails(menuId) {
    try {
        const menu = await getMenuById(menuId);
        const dishes = await getMenuDishes(menuId);
        return { menu, dishes };
    } catch (error) {
        console.error('Erreur chargement détails menu :', error);
        throw new Error('Impossible de charger les détails du menu.');
    }
}
async function updateUserProfile(profileForm) {
    const token = getToken();
    let formData = new FormData(profileForm);
    const myHeaders = new Headers();
    myHeaders.append("Content-Type", "application/json");
    myHeaders.append("X-AUTH-TOKEN", token);
    const raw = JSON.stringify({
        "email": formData.get("email"),
        "firstName": formData.get("prenom"),
        "lastName": formData.get("nom"),
        "phone": formData.get("NumeroTelephone"),
        "adresse": formData.get("adressePostale"),
    });
    const requestOptions = {
        method: "PUT",
        headers: myHeaders,
        body: raw,
    };
    try {        const response = await fetch(`${API_BASE}/user`, requestOptions);
        if (!response.ok) throw new Error('Erreur mise à jour profil');
        alert('Profil mis à jour avec succès');
} catch (error) {
        console.error(error);
        alert('Erreur lors de la mise à jour du profil.');
    }
}
export async function handleProfileUpdate(event) {
    event.preventDefault();
    const profileForm = document.getElementById('profileForm');
    if (!profileForm) return;
    await updateUserProfile(profileForm);
        // Rediriger vers la page de commandes après la mise à jour du profil
        window.location.href = '/commande';
}

export async function loadMenusIntoOrderForm() {
    try {        const menus = await getMenus();
        const menuSelect = document.getElementById('menuSelect');
        if (!menuSelect) return;
        menuSelect.innerHTML = '<option value="">Sélectionnez un menu</option>';
        menus.forEach(menu => {
            const option = document.createElement('option');
            option.value = menu.id;
            option.textContent = menu.title;
            option.dataset.price = menu.price;
            menuSelect.appendChild(option);
        }
        );
    } catch (error) {        console.error('Erreur chargement menus', error);
        alert('Erreur lors du chargement des menus.');
    }
}
export async function fillMenuSelect() {
    try {
        const menus = await getMenus();
        const menuSelect = document.getElementById('menuSelect');
        if (!menuSelect) return;
        menuSelect.innerHTML = '';
        menus.forEach(menu => {
            const option = document.createElement('option');
            option.value = menu.id;
            option.textContent = menu.title;
            option.dataset.price = menu.price; // stocker le prix dans un data-attribute pour le calcul du total
            menuSelect.appendChild(option);
        }
        );
    } catch (error) {
        console.error('erreur chargement menus', error);
        alert('Erreur lors du chargement des menus.');
    }
}