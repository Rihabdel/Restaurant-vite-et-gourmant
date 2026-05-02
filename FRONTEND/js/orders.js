import { createOrder, getOrders, getMenus,getOrderById, updateOrder, cancelOrder} from './api.js';
import { getUserInfo, showAndHideElementsForRoles } from './script.js';


export default async function initOrder() {
    console.log("Initialisation page commandes");
    initButtons();
    initForm();
}

function initButtons() {
    const newOrderBtn = document.getElementById('new-order-btn');
    if (newOrderBtn) {
        newOrderBtn.addEventListener('click', async () => { 
            await fillMenuSelect();
            await fillNewOrderModal();
        });
    }
    // Event delegation pour les boutons de modification des commandes
    const ordersTable = document.getElementById('historyOrdersTable');
    if (ordersTable) {
        ordersTable.addEventListener('click', async (event) => {    
            if (event.target.classList.contains('edit-order-btn')) {
                const orderId = event.target.getAttribute('data-id');
                fillEditOrderModal(orderId);
            }
            if (event.target.classList.contains('cancel-order-btn')) {
                const orderId = event.target.getAttribute('data-id');
                const modalEL = document.getElementById('deleteOrderModal');
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEL);
                modalInstance.show();
                const confirmCancelBtn = document.getElementById('confirmCancelBtn');
                confirmCancelBtn.onclick = async () => {
                    try {
                        await cancelOrder(orderId);
                        alert("Votre commande a été annulée avec succès !");
                        await loadOrders();
                        const cancelModalEL = document.getElementById('deleteOrderModal');
                        const cancelModalInstance = bootstrap.Modal.getInstance(cancelModalEL);
                        if (cancelModalInstance) cancelModalInstance.hide();
                    }
                    catch (error) {
                        console.error("Erreur lors de l'annulation de la commande :", error);
                        alert("Une erreur est survenue lors de l'annulation de votre commande. Veuillez réessayer.");
                    }
                };
            }
        });
    }
}

function initForm() {   
    const orderForm = document.getElementById('OrderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const orderData = getCurrentOrderData();
            try {
                await createOrder(orderData);
                alert("Votre commande a été créée avec succès !");
                const modalEL = document.getElementById('OrderModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEL);
                if (modalInstance) modalInstance.hide();
                await displayOrders();
            } catch (error) {
                console.error("Erreur lors de la création de la commande :", error);
                alert("Une erreur est survenue lors de la création de votre commande. Veuillez réessayer.");
            }
        });
    }
    const editOrderForm = document.getElementById('edit-order-Form');
    if (editOrderForm) {
        editOrderForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const orderId = document.getElementById('edit-order-id').value;
            const orderData = getCurrentOrderData();
            try {
                await updateOrder(orderId, orderData);
                alert("Votre commande a été mise à jour avec succès !");
                const modalEL = document.getElementById('editOrderModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEL);
                if (modalInstance) modalInstance.hide();
                await displayOrders();
            } catch (error) {
                console.error("Erreur lors de la mise à jour de la commande :", error);
                alert("Une erreur est survenue lors de la mise à jour de votre commande. Veuillez réessayer.");
            }
            });
}
}
//recupere les infos de l'utilisateur pour préremplir la modale de création de commande
export async function fillNewOrderModal() {
    try {
        const user = await getUserInfo();
        document.getElementById('customerName').value = user.firstName || '';
        document.getElementById('customerPrenom').value = user.lastName || '';
        document.getElementById('customerEmail').value = user.email || '';
        document.getElementById('customerPhone').value = user.phone || '';
        document.getElementById('factAddress').value = user.address || '';
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('OrderModal')); 
modal.show();

    } catch (error) {
        console.error("Erreur lors du chargement des informations utilisateur :", error);
    }
}
//recupere les infos de la commande pour préremplir la modale de confirmation de commande
export function fillNewOrderDetailsModal(orderData, user) {
    try {
        
    document.getElementById('detailsName').textContent = orderData.user || user.firstName || 'N/A';
    document.getElementById('detailsEmail').textContent = orderData.userEmail || user.email || 'N/A';
    document.getElementById('detailsPhone').textContent = orderData.userPhone || user.phone || 'N/A';
    document.getElementById('detailsAddress').textContent = orderData.userAddress || user.address || 'N/A';
    document.getElementById('detailsNumberOfPeople').textContent = orderData.numberOfPeople || 'N/A';
    document.getElementById('detailsDeliveryCity').textContent = orderData.deliveryCity || 'N/A';
    document.getElementById('detailsDeliveryPostalCode').textContent = orderData.deliveryPostalCode || 'N/A';
    document.getElementById('detailsMenu').textContent = orderData.menu || 'N/A';
    document.getElementById('detailsDeliveryDate').textContent = orderData.deliveryDate ? new Date(orderData.deliveryDate).toLocaleDateString() : 'N/A';
    document.getElementById('detailsDeliveryTime').textContent = orderData.deliveryTime || 'N/A';
    
    const modal = new bootstrap.Modal.getOrCreateInstance(document.getElementById('OrderDetailsModal'));
    modal.show();
    } catch (error) {
        console.error("Erreur lors du remplissage de la modale de détails de commande :", error);
    }
}
// recuperer les donnes de la commande pour préremplir la modale de modification de commande
export function getCurrentOrderData() {
    return {
        
        menuId: menuSelect ? menuSelect.value : null,
        numberOfPeople: document.getElementById('numberOfPeople').value,
        deliveryCity: document.getElementById('deliveryCity').value,
        deliveryPostalCode: document.getElementById('deliveryPostalCode').value,
        deliveryDate: document.getElementById('deliveryDate').value,
        deliveryTime: document.getElementById('deliveryTime').value,
        user: document.getElementById('customerName').value,
        userEmail: document.getElementById('customerEmail').value,
        userPhone: document.getElementById('customerPhone').value,
        userAddress: document.getElementById('factAddress').value
    };
}
//fonction pour remplir la liste des menus dans le select de la modale de création de commande
export async function fillMenuSelect() {
    try {
        const menus = await getMenus();
        const menuSelect = document.getElementById('menuSelect');
        
        if (menuSelect) {
            menuSelect.innerHTML = '<option value="" disabled selected>Choisissez un menu</option>';
            
            menus.forEach(menu => {
                // Defensive check: ensure the menu has an ID
                if (menu && menu.id) {
                    const option = document.createElement('option');
                    option.value = menu.id;
                    option.textContent = menu.title || "Sans titre";
                    menuSelect.appendChild(option);
                }
            });
        }
    } catch (error) {
        console.error("Erreur lors du chargement des menus :", error);
    }
}
export async function displayOrders() {
    try {
        const orders = await getOrders();
        const ordersTable = document.getElementById('historyOrdersTable');
        if (ordersTable) {
            if (orders.length === 0) {
                ordersTable.innerHTML = '<p>Vous n\'avez pas encore passé de commandes.</p>';
            } else {
                ordersTable.innerHTML = `
                    <thead>
                        <tr>
                            <th scope="col">N°</th>
                            <th scope="col">Commande</th>
                            <th scope="col">Date et heure de la commande</th>
                            <th scope="col">Modification</th>
                            <th scope="col">Annulation</th>
                            <th scope="col">Statut</th>
                    </tr>
                    </thead>
                    <tbody id="orders-tbody">
                        ${orders.map(order => `
                            <tr>
                                <th scope="row">${order.id}</th>
                                <td>${order.menu.title}</td>
                                <td>${new Date(order.createdAt).toLocaleString()}</td>
                                <td><button class="btn btn-sm btn-primary edit-order-btn" data-id="${order.id}">Modifier</button></td>
                                <td><button class="btn btn-sm btn-danger cancel-order-btn" data-id="${order.id}">Annuler</button></td>
                                <td>${order.status}</td>
                            </tr>
                        `).join('')}
                    </tbody>         
                `;
            }
        }
    } catch (error) {
        console.error("Erreur lors du chargement des commandes :", error);
        alert("Impossible de charger vos commandes. Veuillez réessayer plus tard.");
    }
    showAndHideElementsForRoles();

}
//fonction pour remplir la modale de modification de commande avec les infos de la commande sélectionnée
export async function fillEditOrderModal(orderId) {
    try{
        const order = await getOrderById(orderId);
        if (!order) {
            alert("Commande introuvable. Veuillez réessayer.");
            return;
        }
        const user = await getUserInfo();
        document.getElementById('customerName').value = order.user.firstName || '';
        document.getElementById('customerPrenom').value = order.user.lastName || '';
        document.getElementById('customerEmail').value = order.user.email || '';
        document.getElementById('customerPhone').value = order.user.phone || '';
        document.getElementById('factAddress').value = order.user.address || '';
        document.getElementById('deliveryCity').value = order.deliveryCity || '';
        document.getElementById('deliveryPostalCode').value = order.deliveryPostalCode || '';
        document.getElementById('deliveryDate').value = order.deliveryDate ? new Date(order.deliveryDate).toISOString().split('T')[0] : '';
        document.getElementById('deliveryTime').value = order.deliveryTime || '';
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editOrderModal')); 
   
        const editMenuSection = document.getElementById('edit-menu-section');
if (editMenuSection && order.menu) {
    // 1. On sécurise les données numériques
    const priceValue = parseFloat(order.menu.price || 0);
    const formattedPrice = !isNaN(priceValue) ? priceValue.toFixed(2) + ' €' : 'N/A';

    // 2. On injecte le HTML
    editMenuSection.innerHTML = `
        <label class="form-label fw-bold">Votre menu commandé :</label>
        <div class="p-3 border rounded bg-light">
            <p class="mb-1"><strong>Menu :</strong> ${order.menu.title || 'N/A'}</p>
            <p class="mb-1 text-muted small">${order.menu.descriptionMenu || 'Pas de description'}</p>
            <p class="mb-1"><strong>Prix :</strong> ${formattedPrice}</p>
            <p class="mb-1"><strong>Nombre de convives :</strong> ${order.numberOfPeople || 'N/A'}</p>
            <p class="mb-0 text-info"><em>Minimum requis : ${order.menu.minPeople || 'N/A'}</em></p>
        </div>
    `;
}
modal.show();
    } catch (error) {
        console.error("Erreur lors du chargement des informations de la commande :", error);
        alert("Impossible de charger les détails de la commande. Veuillez réessayer plus tard.");
    }
}
