import { API_BASE,getDishes, createDish, getDishById,updateDish,deleteDish,addDishAllergens,getAllergen
    ,getMenus,getMenuById, updateMenu,addDishToMenu, deleteMenu,getOrderById} from "./api.js";
import { getToken} from "./script.js";
import {fillEditMenuModal} from "./menu.js";
import { validateEmail,validatePassword,validateConfirmPassword,validateRequired } from "./auth/inscription.js";

// Attendre que le DOM soit chargé
export default async function initAdmin() {
    console.log("initialisation admin");

    const ordersTable = document.getElementById("historyOrdersTableAdmin");
    const dishList = document.getElementById("dishList");
    const employeeList = document.getElementById("employeeList");
    const menuList = document.getElementById("menuList");

    
    if (dishList) {
        await loadDishes();
        initDishListeners();
    }
    if (employeeList) {
        await loadEmployees();
        initEmployeeListeners();
    }
    if (menuList) {
        await loadMenus();
        initMenuEvents();
    }
    if (ordersTable) {
        await loadOrders();
        initOrdersListeners();
    }
}

// afficher les plats
async function loadDishes() {
    try {
        const dishes = await getDishes();
        displayDishes(dishes);
    } catch (error) {
        console.error("Erreur lors du chargement des plats :", error);
    }
}
// afficher les plats dans le tableau
async function displayDishes(dishes) {
    
    dishList.innerHTML = "";
    if (!dishes || dishes.length === 0) {
        dishList.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">Aucun plat</td>
            </tr>`;
        return;
    }
    const allergens = await getAllergens();
    dishes.forEach(dish => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${dish.id}</td>
            <td>${dish.category || 'Aucune'}</td>
            <td>${dish.name}</td>
            <td>${dish.description || ''}</td>
            <td>${Number(dish.price).toFixed(2)} €</td>
            
            <td class="badge-allergenes">
                ${dish.allergenName?.join(', ') || 'Aucun'}
            </td>
            <td>
            <select id="allergenSelect" class="form-select form-select-sm allergen-select" data-id="${dish.id}">
                ${allergens.map(allergen => `
                    <option value="${allergen.id}" ${dish.allergenIds?.includes(allergen.id) ? 'selected' : ''}>
                        ${allergen.name}
                    </option>
                `).join('')}
            </select>
            <button class="btn btn-sm btn-primary mt-2 add-allergen-dish-btn" data-id="${dish.id}">Ajouter</button>
            </td>
            <td>
                <button class="btn btn-dm edit-dish-btn" data-id="${dish.id}">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button class="btn btn-dm-danger delete-dish-btn" data-id="${dish.id}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        dishList.appendChild(row);
    });
}
// --- Initialiser les listeners pour ajouter ,modifier et supprimer les plats et ajout d'allergène à un plat ---
function initDishListeners() {
     const confirmBtn = document.getElementById('confirmDeleteDishBtn')
     const modalDelete = new bootstrap.Modal(document.getElementById('deleteDishModal'));
        dishList.addEventListener("click", async (e) => {
        const editDish = e.target.closest('.edit-dish-btn');
        if (editDish) {
            const dishId = editDish.dataset.id;
            if (!dishId) return;

            try {
                const dish = await getDishById(dishId);
                fillEditDishModal(dish);
                const modal = new bootstrap.Modal(document.getElementById('addDishModal'));
                modal.show();
                
            } catch (error) {
                console.error(error);
            }
        }
        const deleteDish = e.target.closest('.delete-dish-btn');
        if (deleteDish) {
            const dishId = deleteDish.dataset.id;
            if (!dishId) return;
            
           ;
            if (confirmBtn) {
                confirmBtn.dataset.id = dishId;
                modalDelete.show();
            }
        }
        const addAllergenBtn = e.target.closest('.add-allergen-dish-btn');
        if (addAllergenBtn) {
            const dishId = addAllergenBtn.dataset.id;
            if (!dishId) return;
            const select = document.querySelector(`select.allergen-select[data-id="${dishId}"]`);
            if (!select) {
                console.error("Sélecteur d'allergènes introuvable pour le plat ID :", dishId);
                alert("Erreur technique");
                return;
            }
            const selectedOptions = Array.from(select.selectedOptions);
            const allergenId = selectedOptions.map(option =>option.value);
            try {
                await addDishAllergens(dishId, allergenId[0]);
                alert("Allergènes mis à jour !");
                loadDishes();
            } catch (error) {
                console.error("Erreur lors de la mise à jour des allergènes :", error);
                alert("Une erreur est survenue lors de la mise à jour des allergènes.");
            }
        }
    });

        confirmBtn.addEventListener('click', async () => {
            const dishId = confirmBtn.dataset.id;
            if (!dishId) return;
            try {
                await deleteDish(dishId);
                const modalEl = document.getElementById('deleteDishModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                alert("Plat supprimé avec succès !");
                loadDishes();
            } catch (error) {
                console.error("Erreur lors de la suppression du plat :", error);
                alert("Une erreur est survenue lors de la suppression du plat.");
            }

        });
        
        const addDishButton = document.getElementById('addDishButton');
        if (addDishButton) {
            addDishButton.addEventListener('click', () => {
                const modal = new bootstrap.Modal(document.getElementById('addDishModal'));
                modal.show();
            });
        }
        
}
// remplir la modale de modification d'un plat
function fillEditDishModal(dish) {
    document.getElementById('dishName').value = dish.name;
    document.getElementById('dishDescription').value = dish.description;
    document.getElementById('dishPrice').value = dish.price;
    document.getElementById('dishCategory').value = dish.category;

    const select = document.getElementById('dishAllergens');

    Array.from(select.options).forEach(option => {
        option.selected = dish.allergenName?.includes(option.value);
    });

    document.getElementById('addDishForm').dataset.id = dish.id;
}
// --- Gérer le formulaire d'ajout ou de modification d'un plat ---
const addDishForm = document.getElementById('addDishForm');

if (addDishForm) {
    addDishForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(addDishForm);

        const dishData = {
            name: formData.get('dishName'),
            description: formData.get('dishDescription'),
            price: formData.get('dishPrice'),
            category: formData.get('dishCategory'),
            allergenName: formData.getAll('dishAllergens')
        };

        try {
            if (addDishForm.dataset.id) {
                await updateDish(addDishForm.dataset.id, dishData);
                alert("Plat mis à jour !");
            } else {
                await createDish(dishData);
                alert("Plat créé !");
            }

            const modalEl = document.getElementById('addDishModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            loadDishes();
        } catch (error) {
            console.error(error);
        }
    });
}



// --- Afficher les menus ---
async function loadMenus() {
    try {
        const menus = await getMenus();
        displayMenus(menus);
    } catch (error) {
        console.error("Erreur lors du chargement des menus :", error);
    }
}
// --- Afficher les menus dans le tableau ---
async function displayMenus(menus) {
  
    const menuList = document.getElementById("menuList");
    if (!menus || menus.length === 0) {
        menuList.innerHTML = `<tr><td colspan="10" class="text-center">Aucun menu</td></tr>`;
        return;
    }
    const dishes = await getDishes();
 
    const rows = menus.map(menu => `
<tr>
    <td><strong>${menu.title}</strong></td>
    <td>${menu.descriptionMenu || ''}</td>
    <td>${menu.minPeople}</td>
    <td>${menu.stock}</td>
    <td>${menu.listOfDishesFromMenu?.map(d => d.name).join('- ') || 'Aucun'}</td>
    <td>
    <select id="dishSelect" class="form-select form-select-sm dish-select" data-id="${menu.id}">
        ${dishes.map(dish => `
            <option value="${dish.id}" ${menu.listOfDishesFromMenu?.some(d => d.id === dish.id) ? 'selected' : ''}>
                ${dish.name}
        </option>
        `).join('')}
        
    </select>
    <button class="btn btn-sm btn-primary mt-2 add-dish-menu-btn" data-id="${menu.id}">Ajouter</button>
    </td>
    <td>
        <button class="available-btn ${menu.isAvailable ? 'available-yes' : 'availables-no'}"
            data-id="${menu.id}">
            ${menu.isAvailable ? "Disponible" : "Indisponible"}
        </button>
    </td>

    <td>
        ${menu.allAllergenes?.join(', ') || '-'}
    </td>

    <td>
        <button class="action-btn btn-edit edit-menu-btn" data-id="${menu.id}">
            <i class="bi bi-pencil"></i>
        </button>

        <button class="action-btn btn-delete delete-menu-btn" data-id="${menu.id}">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
`).join('');
    menuList.innerHTML = rows;
}
// --- Initialiser les listeners pour les actions de modification et de suppression d'un menu et ajout d'un plat à un menu ---
function initMenuEvents() { 
    document.addEventListener("click", async (e) => {
            const btn = e.target.closest(".available-btn");
            if (!btn) return;

            const id = btn.dataset.id;
            // Vérifier l'état actuel à partir du dataset   
            const current = btn.dataset.isAvailable === "1" || btn.dataset.isAvailable === "true";
            if (btn) {
            try {
                await updateMenu(id, { isAvailable: !current });
                btn.dataset.isAvailable = (!current).toString();
                btn.classList.toggle("btn-success", !current);
                btn.classList.toggle("btn-secondary", current);
                btn.textContent = !current ? "Disponible" : "Indisponible";
            } catch (error) {
                console.error("Erreur lors de la mise à jour de la disponibilité :", error);
                alert("Une erreur est survenue lors de la mise à jour de la disponibilité.");
            }
             }
    }    );
    menuList.addEventListener("click", async (e) => {
        const editBtn = e.target.closest('.edit-menu-btn');
        if (editBtn){
            const id = editBtn.dataset.id;
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
        const deleteBtn = e.target.closest('.delete-menu-btn');
        if (deleteBtn) {
        const menuId = deleteBtn.dataset.id;
        if (!menuId) return;
        if (!confirm("Êtes-vous sûr de vouloir supprimer ce menu ?")) {
            return;
        }
        try {
            await deleteMenu(menuId);
            alert("Menu supprimé avec succès !");
            loadMenus(); // recharger la liste après suppression
        } catch (error) {
            console.error("Erreur lors de la suppression du menu :", error);
            alert("Une erreur est survenue lors de la suppression du menu.");
        }
    }

        // selection un plat s'ajoute au menu
        const addDishMenu= e.target.closest('.add-dish-menu-btn');
        if (addDishMenu) {
            const menuId = addDishMenu.dataset.id;
            if (!menuId) {
                console.error("ID de menu manquant");
                alert("Erreur technique");
                return;
            }
                const select = document.querySelector(`select.dish-select[data-id="${menuId}"]`);
                    if (!select) {
                        console.error("Sélecteur de plats introuvable pour le menu ID :", menuId);
                        alert("Erreur technique");
                        return;
                    }
            const selectedOptions = Array.from(select.selectedOptions);
            const dishId = selectedOptions.map(option =>option.value);
            try {
                await addDishToMenu(menuId, dishId[0]);
                alert("Plats mis à jour avec succès !");
                loadMenus(); // Recharger la liste pour refléter les changements
            } catch (error) {
                console.error("Erreur lors de la mise à jour des plats du menu :", error);
                alert("Une erreur est survenue lors de la mise à jour des plats du menu.");
            }
        }
    });
}




//--initilaiser les listeners d'un ajout d'un employee
function initEmployeeListeners() {
    
    const formEmployee= document.getElementById('newEmployeeForm');
    if (!formEmployee) {
        return;
    }
    const inputNom = formEmployee.querySelector("#NomInput");
    const inputPrenom = formEmployee.querySelector("#PrenomInput");
    const inputEmail = formEmployee.querySelector("#EmailInput");
    const inputPassword = formEmployee.querySelector("#PasswordInput");
    const inputConfirmPassword = formEmployee.querySelector("#ValidatePasswordInput");
    const btnvalidation = formEmployee.querySelector("#btnNewEmployee");
    
    function validateForm() {
        const isNomValid = validateRequired(inputNom);
        const isPrenomValid = validateRequired(inputPrenom);
        const isEmailValid = validateEmail(inputEmail);
        const isPasswordValid = validatePassword(inputPassword);
        const isConfirmPasswordValid = validateConfirmPassword(inputPassword, inputConfirmPassword);
        
        if (isNomValid && isPrenomValid && isEmailValid && isPasswordValid && isConfirmPasswordValid) {
            btnvalidation.disabled = false;
            console.log("Formulaire valide");
        } else {
            btnvalidation.disabled = true;
            console.log(" Formulaire invalide");
        }
    }
    
    inputNom.addEventListener('keyup', validateForm);
    inputPrenom.addEventListener('keyup', validateForm);
    inputEmail.addEventListener('keyup', validateForm);
    inputPassword.addEventListener('keyup', validateForm);
    inputConfirmPassword.addEventListener('keyup', validateForm);
    
    newEmployeeForm.addEventListener("submit", newEmployee);

    const employeeList = document.getElementById("employeeList");

if (employeeList) {
    employeeList.addEventListener("click", async (event) => {
        const btn = event.target.closest('.deleteEmployeeBtn');
        if (!btn) return;

        const employeeId = btn.dataset.id;
        if (!employeeId) return;

        if (!confirm("Êtes-vous sûr de vouloir supprimer cet employé ?")) return;

        try {
            const response = await fetch(`${API_BASE}/admin/employees/${employeeId}`, {
                method: "DELETE",
                headers: { "X-AUTH-TOKEN": getToken() }
            });

            if (!response.ok) throw new Error("Erreur suppression");

            alert("Employé supprimé !");
            loadEmployees();

        } catch (error) {
            console.error(error);
        }
    });
}    
}
// --- Ajouter un nouvel utilisateur ---
async function newEmployee(event) {
    event.preventDefault(); // empêche la soumission normale
       const myHeaders = new Headers();
    myHeaders.append("Content-Type", "application/json");
    const form = document.getElementById('newEmployeeForm');
    const formData = new FormData(form);
    const employeeData = {
        email: formData.get('email'),
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        password: formData.get('password'),
        phone: formData.get('phone'),
        address: formData.get('address'),
        roles: [formData.get("role")]
    };
    try {  
        const response = await fetch(`${API_BASE}/registration`, {
            method: "POST",
            headers: myHeaders,
            body: JSON.stringify(employeeData)
        });
        if (!response.ok) throw new Error(`Erreur ${response.status} lors de la création de l'employé`);
        alert("Employé créé avec succès !");
        form.reset();
        loadEmployees(); 
    } catch (error) {
        console.error("Erreur lors de la création de l'employé :", error);
        alert("Une erreur est survenue lors de la création de l'employé.");
    }

}
// Fonction pour charger la liste des employés
async function loadEmployees() {

    try {
        const response = await fetch(`${API_BASE}/admin/employees`, {
            headers: { 'X-AUTH-TOKEN': getToken() }
        });
        if (!response.ok) throw new Error(`Erreur ${response.status} lors du chargement des employés`);
        const employees = await response.json();
        displayEmployees(employees);
    }catch (error) {
        console.error("Erreur lors du chargement des employés :", error);
        employeeList.innerHTML = `<tr><td colspan="3">Erreur : ${error.message}</td></tr>`;
    }
}

// Fonction pour afficher les employés dans le tableau
function displayEmployees(employees) {
    employeeList.innerHTML = "";
    if (employees.length === 0) {
        employeeList.innerHTML = `<tr><td colspan="3">Aucun employé trouvé.</td></tr>`;
        return;
    }
    employees.forEach(user => {
        const row = document.createElement("tr");

        row.innerHTML = `
            
            <td>${user.firstName || ''}</td>
            <td>${user.lastName || ''}</td>
            <td class="d-none d-md-table-cell">${user.email || ''}</td>
            <td>${user.phone || ''}</td>
            <td class="d-none d-md-table-cell">${user.address || ''}</td>
            <td>
                <button class="btn btn-sm btn-dm btn-danger deleteEmployeeBtn" data-id="${user.id}">
                    <i class="bi-x-circle">Désactiver</i>
                </button>
            </td>
        `;
        employeeList.appendChild(row);
    });
}
//---supprimer le compte d'un employé---
function deleteEmployee(employeeId) {
    return fetch(`${API_BASE}/admin/employees/${employeeId}`, {
        method: "DELETE",
        headers: { "X-AUTH-TOKEN": getToken() }
    }).then(response => {
        if (!response.ok) {
            throw new Error(`Erreur ${response.status} lors de la suppression de l'employé`);
        }
    });
}








// --- Afficher les commandes à l'administrateur ---
function displayAdminOrders(orders) {
        
        const ordersList = document.getElementById("ordersList");
        ordersList.innerHTML = "";
        if (!orders || orders.length === 0) {
            ordersList.innerHTML = `<tr><td colspan="12" class="text-center">Aucune commande</td></tr>`;
            return;
        }
        ordersList.innerHTML = orders.map(order => `
            <tr>
                <td class="d-none d-md-table-cell">${order.id}</td>
                <td >${order.menu.title}</td>
                <td>${new Date(order.deliveryDate).toISOString().split('T')[0]}</td>
                    <td>${new Date(order.deliveryTime).toISOString().substring(11,16)}</td>
                    <td>${order.numberOfPeople}</td>
                    <td>${order.deliveryAddress || ''}</td>
                    <td>${order.deliveryCity || ''}</td>
                    
                    <td class="d-none d-md-table-cell">${order.canceledBy || 'N/A'}</td>
                    <td><button class="btn btn-sm btn-secondary edit-order-btn" data-id="${order.id}"><i class="bi bi-pencil"></i></button></td>
                    <td><button class="btn btn-sm btn-danger delete-order-btn" data-id="${order.id}"><i class="bi bi-trash"></i></button></td>
                    <td>
                        <select class="form-select form-select-sm status-order-btn" data-id="${order.id}">
                            <option value="en_attente" ${order.status === "en_attente" ? "selected" : ""}>En attente</option>
                            <option value="accepté" ${order.status === "accepté" ? "selected" : ""}>Accepté</option>
                            <option value="en_préparation" ${order.status === "en_préparation" ? "selected" : ""}>En préparation</option>
                            <option value="livrée" ${order.status === "livrée" ? "selected" : ""}>Livrée</option>
                            <option value="en_attente_de_retour" ${order.status === "en_attente_de_retour" ? "selected" : ""}>En attente de retour</option>
                            <option value="terminée " ${order.status === "terminée" ? "selected" : ""}>Terminée</option>
                            <option value="annulé" ${order.status === "annulé" ? "selected" : ""}>Annulée</option>
                        </select>
                    </td>
            </tr>
        `).join("");
    }
    // --- Afficher les commandes dans le tableau ---
async function loadOrders() {
    const ordersTable = document.getElementById("historyOrdersTableAdmin");
    if (!ordersTable) {
        console.error("Tableau des commandes introuvable");
        return;
    }
    try {
        const orders = await getOrdersAdmin();
        console.log("Commandes récupérées pour affichage :", orders);
        displayAdminOrders(orders);
    } catch (error) {
        console.error("Erreur lors du chargement des commandes :", error);
        const container = document.getElementById('historyOrdersTable');
        if (container) {
            container.innerHTML = `<tr><td colspan="5">Erreur : ${error.message}</td></tr>`;
        }
    }
}
    // --- Initialiser les listeners pour les actions modifier , supprimer des commandes et changer le statut ---
function initOrdersListeners() {
        const ordersTable = document.getElementById("historyOrdersTableAdmin");
        ordersTable.addEventListener("click", async (e) => {
            const editBtn = e.target.closest('.edit-order-btn');
            if (editBtn) {
                const orderId = editBtn.dataset.id;
                if (!orderId) return;
                try {
                    const order = await getOrderById(orderId);
                    const menus = await getMenus();
                    const menuSelect = document.getElementById('menuSelectEdit');
                    if (menuSelect) {
                        menuSelect.innerHTML = `<option value="">Sélectionnez un menu</option>` +
                            menus.map(menu => 
                                `<option value="${menu.id}" ${order.menu?.id === menu.id ? 'selected' : ''}>
                                    ${menu.title}
                                </option>`
                            ).join('');
                    }
                    fillEditOrderModal(order);
                    const modal = new bootstrap.Modal(document.getElementById('editOrderModal'));
                    modal.show();
                } catch (error) {
                    console.error(error);
                }
            }
            const deleteBtn = e.target.closest('.delete-order-btn');
            if (deleteBtn) {
                const id = deleteBtn.dataset.id;
                if (!id) {
                    console.error("ID de commande manquant");
                    alert("Erreur technique");
                    return;
                }
                if (!confirm("Êtes-vous sûr de vouloir supprimer cette commande ?")) {
                    return;
                }
                try {
                    await cancelAdminOrders(id);
                    alert("Commande supprimée avec succès !");
                    loadOrders(); // recharger la liste après annulation
                } catch (error) {
                    console.error("Erreur lors de l'annulation de la commande :", error);
                    alert("Une erreur est survenue lors de l'annulation de la commande.");
                }
            
            }
        });

        const statusSelects = ordersTable.querySelectorAll('.status-order-btn');   
            statusSelects.forEach(select => {
            select.addEventListener('change', (e) => {
                const orderId = e.target.dataset.id;
                const newStatus = e.target.value;
                if (!orderId) {
                    console.error("ID de commande manquant");
                    alert("Erreur technique");
                    return;
                }
                    try{
                        updateOrderStatus(orderId, newStatus);
                        alert("Statut de la commande mis à jour !");
                        loadOrders(); // Recharger la liste pour refléter les changements
                    } catch (error) {
                        console.error("Erreur lors de la mise à jour du statut :", error);
                        alert("Une erreur est survenue lors de la mise à jour du statut.");
                    }
            });
        });

    }
// --- Afficher les commandes à l'administrateur ---
async function getOrdersAdmin() {
    const response = await fetch(`${API_BASE}/admin/orders`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        }
    });
    if (!response.ok) {
        throw new Error('Erreur de récupération des commandes');
    }
    return await response.json();
}
// --- mettre à jour l'administrateur d'une commande ---
async function updateOrder(orderId, orderData) {
    const response = await fetch(`${API_BASE}/admin/orders/${orderId}/edit`, {
        method: 'PUT', 
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify(orderData)
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Erreur lors de la mise à jour');
    }
    
    return await response.json();
}
// --- mettre à jour le statut d'une commande ---
export async function updateOrderStatus(id, status) {
    const response = await fetch(`${API_BASE}/admin/orders/${id}/status`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify({ status })
    });
    if (!response.ok) {
        throw new Error('Erreur de mise à jour du statut de la commande');
    }
    return response.json();
}
//supprimer un administrateur d'une commande
export async function deleteAdminOrders(id) {
    const response = await fetch(`${API_BASE}/admin/orders/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify({ status: 'annule' })

    });
    if (!response.ok) {
        throw new Error('Erreur de suppression du menu');
    }
    console.log("Commande supprimée");
} 
// remplir la modale de modification d'une commande
function fillEditOrderModal(order) {
    
    const menuSelect = document.getElementById('menuSelectEdit');
    const numberOfPeople = document.getElementById('numberOfPeople');
    const deliveryCity = document.getElementById('deliveryCity');
    const deliveryAddress = document.getElementById('deliveryAddress');
    const deliveryDate = document.getElementById('deliveryDate');
    const deliveryTime = document.getElementById('deliveryTime');
    const canceledBy = document.getElementById('canceledBy');

    if (menuSelect) menuSelect.value = order.menu?.id || '';// préselection du menu
    if (numberOfPeople) numberOfPeople.value = order.numberOfPeople || '';
    if (deliveryCity) deliveryCity.value = order.deliveryCity || '';
    if (deliveryAddress) deliveryAddress.value = order.deliveryAddress || '';
    if (canceledBy) canceledBy.value = order.canceledBy || '';


    if (deliveryDate) {
        deliveryDate.value = order.deliveryDate
            ? new Date(order.deliveryDate).toISOString().split('T')[0]
            : '';
    }
    if (deliveryTime) {
        deliveryTime.value = order.deliveryTime
            ? new Date(order.deliveryTime).toISOString().substring(11,16)
            : '';
    }

    // 👉 IMPORTANT
    const form = document.getElementById('editOrderForm');
    if (form) form.dataset.id = order.id;
    const menu =getMenus();
    menu.then(menus => {
        const menuSelect = document.getElementById('menuSelectEdit');
        if (menuSelect) {   
            menuSelect.innerHTML = `<option value="">Sélectionnez un menu</option>` +
                menus.map(menu => `<option value="${menu.id}" ${order.menu?.id === menu.id ? 'selected' : ''}>${menu.title}</option>`).join('');
        }
    }).catch(error => {
        console.error("Erreur lors du chargement des menus pour la modale :", error);
        alert("Une erreur est survenue lors du chargement des menus.");
}
);
}
// --- Gérer le formulaire de modification d'une commande ---
const editOrderForm = document.getElementById("editOrderForm");
if (editOrderForm) {
    editOrderForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const orderId = editOrderForm.dataset.id;
        if (!orderId) {
            console.error("ID de commande manquant");
            alert("Erreur technique");
            return;
        }
        try {
            // Récupérer la commande existante
            const existingOrder = await getOrderById(orderId);
            console.log("Commande existante :", existingOrder);
            
            if (!existingOrdser) {
                alert("Commande introuvable");
                return;
            }
            const formData = new FormData(editOrderForm);
            const orderData = {
           
            numberOfPeople: formData.get('numberOfPeople') || existingOrder.numberOfPeople,
            deliveryDate: formData.get('deliveryDate') 
    ? `${formData.get('deliveryDate')} 00:00:00`
    : existingOrder.deliveryDate,
           deliveryTime: formData.get('deliveryTime') 
    ? `${formData.get('deliveryTime')}:00`
    : existingOrder.deliveryTime,

            canceledBy: getToken() ? "admin" : existingOrder.canceledBy
        };
            
            console.log("Données à mettre à jour :", orderData);
            const updatedOrder = await updateOrder(orderId, orderData);   
            if (updatedOrder) {
                alert("Commande mise à jour avec succès !");           
                // Fermer la modale
                const modalEl = document.getElementById('editOrderModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                await loadOrders();
            }     
        } catch (error) {
            console.error("Erreur lors de la mise à jour :", error);
            alert(`Erreur : ${error.message || "Une erreur est survenue"}`);
        }
    });
}
// Réinitialiser le formulaire et supprimer l'ID de la commande lorsque la modale est fermée
const editOrderModal = document.getElementById('editOrderModal');
if (editOrderModal) {
    editOrderModal.addEventListener('hidden.bs.modal', () => {
        editOrderForm.reset();
        delete editOrderForm.dataset.id;
    });
}