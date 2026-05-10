
import { createOrder, getOrdersByUserId, getMenus, updateOrder, cancelOrder, getMenuById, getOrderById,previewOrder}from './api.js';
import { getUserInfo, showAndHideElementsForRoles } from './script.js';




export default async function initOrder() {
    console.log("Initialisation page commandes");
    initButtons();
    initForm();
    await loadOrders();
} 


async function loadOrders() {
    const ordersTable = document.getElementById("historyOrdersTable");
    if (!ordersTable) {
        console.error("Tableau des commandes introuvable dans le DOM.");
        return;
    }
    try {
        const user = await getUserInfo();
        console.log("Informations utilisateur récupérées pour chargement des commandes :", user);
        const orders = await getOrdersByUserId();
        console.log("Commandes récupérées pour l'utilisateur :", orders);
        displayOrders(orders);
    } catch (error) {
        console.error("Erreur lors du chargement des commandes :", error);
        alert("Impossible de charger vos commandes. Veuillez réessayer plus tard.");
    }
}   
function displayOrders(orders) {

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
                    <td >${order.deliveryCost ? order.deliveryCost.toFixed(2) + ' €' : 'N/A'}</td>
                    <td>${order.totalPrice  ? order.totalPrice.toFixed(2) + ' €' : 'N/A'}</td>
                    <td>${order.deliveryAddress || ''}</td>
                    <td>${order.deliveryCity || ''}</td>
                
                    <td>${
        order.status === 'en attente'
        ? `<button class="btn btn-sm btn-secondary edit-order-btn" data-id="${order.id}">
                <i class="bi bi-pencil"></i>
           </button>`
        : 'Non modifiable'
    }</td>
                    <td>${order.status === 'en attente' ? `<button class="btn btn-sm btn-danger cancel-order-btn" data-id="${order.id}"><i class="bi bi-x"></i></button>` : 'Non annulable'}</td>
                    <td>
                    ${order.status}
                    </td>
                    
            </tr>
        `).join('');
    }
function fillEditOrderModalUser(orderId) {
  const menuSelected = document.getElementById('menuSelected');
    const numberOfPeople = document.getElementById('numberOfPeople');
     const deliveryCity = document.getElementById('deliveryCity');
    const deliveryAddress = document.getElementById('deliveryAddress');
    const deliveryPostalCode = document.getElementById('deliveryPostalCode');
    const deliveryDate = document.getElementById('deliveryDate');
    const deliveryTime = document.getElementById('deliveryTime');
    const editOrderForm = document.getElementById('editOrderForm');
    if (editOrderForm) editOrderForm.dataset.orderId = orderId;
    console.log("Récupération des détails de la commande pour modification, ID :", orderId);
    getOrderById(orderId)
        .then(order => {
            if (!order) {
                alert("Commande introuvable. Veuillez réessayer.");
                return;
            }
            
          const menuId = order.menu ? order.menu.title : null;
            const numberOfPeopleValue = order.numberOfPeople || '';
            const deliveryCityValue = order.deliveryCity || '';
            const deliveryAddressValue = order.deliveryAddress || '';
            const deliveryPostalCodeValue = order.deliveryPostalCode || '';
            const deliveryDateValue = order.deliveryDate ? new Date(order.deliveryDate).toISOString().split('T')[0] : '';
            const deliveryTimeValue = order.deliveryTime || '';
            if (menuSelected) menuSelected.value = menuId;
            numberOfPeople.value = numberOfPeopleValue;
            deliveryCity.value = deliveryCityValue;
            deliveryAddress.value = deliveryAddressValue;
            deliveryPostalCode.value = deliveryPostalCodeValue;
            deliveryDate.value = deliveryDateValue;
            deliveryTime.value = deliveryTimeValue;

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editOrderModal')); 
            modal.show();
        }
        )
        .catch(error => {
            console.error("Erreur lors du chargement des informations de la commande :", error);
            alert("Impossible de charger les détails de la commande. Veuillez réessayer plus tard.");
        });
    }
function initButtons() {
            const ordersTable = document.getElementById("historyOrdersTable");
            ordersTable.addEventListener("click", async (e) => {
                const editBtn = e.target.closest('.edit-order-btn');
                if (editBtn) {
                    const orderId = editBtn.dataset.id;
                   
                    if (!orderId) return;
                    try {
                         fillEditOrderModalUser(orderId);
                        const modal = new bootstrap.Modal(document.getElementById('editOrderModal'));
                        modal.show();
                    } catch (error) {
                        console.error(error);
                    }
                }
                const deleteBtn = e.target.closest('.cancel-order-btn'); 
                    if (deleteBtn) {
                        const orderId = deleteBtn.dataset.id;
                        if (!orderId) return;
    
                        const isConfirmed = confirm("Êtes-vous sûr de vouloir annuler cette commande ?");
    
                        if (!isConfirmed) return; // 🔥 STOP si annulation
    
                        try {
                            await cancelOrder(orderId);
    
                            alert("Commande annulée avec succès !");
                            loadOrders();
    
                        } catch (error) {
                            console.error("Erreur :", error);
                            alert("Une erreur est survenue");
                        }
                    }
           
            });
        
    const newOrderBtn = document.getElementById('new-order-btn');
    if (newOrderBtn) {
        newOrderBtn.addEventListener('click', async () => {
            try {
                
                await fillMenuSelect(); 
                await fillNewOrderModal();
                console.log("Modale de création de commande affichée avec succès !");
            } catch (error) {
                console.error("Erreur lors de l'ouverture de la modale de création de commande :", error);
                alert("Une erreur est survenue lors de l'ouverture du formulaire de commande. Veuillez réessayer.");
            }
            
        });
    }
    const confirmOrderBtn = document.getElementById('confirm-order-btn');
    if (confirmOrderBtn) {
        confirmOrderBtn.addEventListener('click', async () => {
            const orderData = getCurrentOrderData();
            try {
                await fillNewOrderDetailsModal(orderData);
                const modalEL = document.getElementById('OrderModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEL);
                if (modalInstance) modalInstance.hide();

            } catch (error) {
                console.error("Erreur lors de la création de la commande :", error);
                alert("Une erreur est survenue lors de la création de votre commande. Veuillez réessayer.");
            }
        });
    }
    const payOrderBtn = document.getElementById('pay-order-btn');
    if (payOrderBtn) {
        payOrderBtn.addEventListener('click', () => {
            const data = getCurrentOrderData();
          createOrder(data)
                .then(() => {
                    alert("un email de confirmation de commande vous a été envoyé, merci pour votre commande!");
                    const modalEL = document.getElementById('OrderDetailsModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEL);
                    if (modalInstance) modalInstance.hide();
                    loadOrders();
                })
                .catch(error => {
                    console.error("Erreur lors de la création de la commande :", error);
                    alert("Une erreur est survenue lors de la création de votre commande. Veuillez réessayer.");
                });
        });
    }
}
function initForm() {   
    
    const editOrderForm = document.getElementById('editOrderForm');
    if (editOrderForm) {
        editOrderForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const orderId = editOrderForm.dataset.orderId;
            if (!orderId) {
                alert("ID de commande manquant. Impossible de mettre à jour.");
                return;
            }

            const orderData = getCurrentOrderData();
            try {
                await updateOrder(orderId, orderData);
                alert("Votre commande a été mise à jour avec succès !");
                const modalEL = document.getElementById('editOrderModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEL);
                if (modalInstance) modalInstance.hide();
                await loadOrders();
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
        console.log("Informations utilisateur pré-remplies dans la modale de création de commande :", user);
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('OrderModal'));
        modal.show();
    } catch (error) {
        console.error("Erreur lors du remplissage de la modale de création de commande :", error);
        alert("Impossible de charger vos informations. Veuillez réessayer plus tard.");
    }
}


//recupere les infos de la commande pour préremplir la modale de confirmation de commande
export async function fillNewOrderDetailsModal(orderData) {

        // 🔥 récupération menu
        const menuId = Number(orderData.menu);
        const menu = await getMenuById(menuId);
        const preview = await previewOrder(orderData);
        if (!preview) {
            throw new Error("Impossible de prévisualiser la commande");
        }

                if (!menu) {
            throw new Error("Menu introuvable");
        }

        console.log("FULL ORDER DATA:", orderData);
        console.log("MENU:", orderData?.menu);
        console.log("MENU PRICE:", orderData?.menu?.price);
    const orderDetails = document.getElementById('orderDetails');

    if (!orderDetails) {
        console.error("Élément 'orderDetails' introuvable dans le DOM.");
        return;
    }
    orderDetails.innerHTML = ''; // Clear previous content

    const menuSelect = document.getElementById('menuSelect');


    if (orderData && menuSelect) {
        orderDetails.innerHTML = `
        <h3 class="text-center mb-4">Récapitulatif de votre commande</h3>
            <div class="p-3 border rounded bg-light mb-3">
            <h4 class="mb-3">Vos informations</h4>
                <p><strong>Nom :</strong> ${document.getElementById('customerName').value || 'N/A'}</p>
                <p><strong>Prénom :</strong> ${document.getElementById('customerPrenom').value || 'N/A'}</p>
                <p><strong>Email :</strong> ${document.getElementById('customerEmail').value || 'N/A'}</p>
                <p><strong>Téléphone :</strong> ${document.getElementById('customerPhone').value || 'N/A'}</p>
                <p><strong>Adresse de livraison :</strong> ${document.getElementById('factAddress').value || 'N/A'}</p>
            </div>
            <div class="p-3 border rounded bg-light mb-3">
            <h4 class="mb-3">Détails de votre commande</h4>
                <p><strong>Menu :</strong> ${menuSelect?.options[menuSelect.selectedIndex]?.text ?? 'N/A'}</p>
                <p><strong>Nombre de convives :</strong> ${orderData.numberOfPeople ?? 'N/A'}</p>
                <p><strong><i class="fas fa-users" style="color: red;"></i>Nombre minimum de convives :</strong> ${menu.minPeople}</p>
            </div>
            <div class="p-3 border rounded bg-light mb-3">
            <h4 class="mb-3">Informations de livraison</h4>
                <p><strong>Date de livraison :</strong> ${orderData.deliveryDate ? new Date(orderData.deliveryDate).toLocaleDateString('fr-FR') : 'N/A'}</p>
                <p><strong>Heure de livraison :</strong> ${orderData.deliveryTime? new Date(orderData.deliveryTime).toTimeString().slice(0,5)
        : 'N/A'}</p>
            </div>

            <div class="p-3 border rounded bg-light mt-3">  
            <h4 class="mb-3">Prix</h4>
            <p><strong>Statut de la commande :</strong> En attente de paiement</p>
            <p><strong id="menuPrice">Prix du menu :</strong> ${preview.menuPrice} €</p>
            
            <p><strong id="deliveryCost">Frais de livraison :</strong> ${preview.deliveryCost.toFixed(2) ?? 'N/A'} €</p>
            <p><strong id="totalPrice">Prix total :</strong> ${preview.totalPrice ?? 'N/A'} €</p>
            <p><strong id="discount" style="color: green;">Remise :${preview.discount.toFixed(2) ?? 'N/A'} €</strong> </p>
            <small class="text-muted"><em>une remise de ${preview.discount.toFixed(2) ?? 'N/A'} € est appliquée sur votre commande </em></small>
                            
            </div>
        `;
    }
    
    const modalEl = document.getElementById('OrderDetailsModal');
    if (modalEl) {  
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}
// recuperer les donnes de la commande pour préremplir la modale de modification de commande
export function getCurrentOrderData() {
    const menuSelect = document.getElementById('menuSelect');
    return {
        menu: menuSelect ? menuSelect.value : null,
        numberOfPeople: document.getElementById('numberOfPeople').value,
        deliveryAddress: document.getElementById('factAddress').value,
        deliveryCity: document.getElementById('deliveryCity').value,
        deliveryPostalCode: document.getElementById('deliveryPostalCode').value,
        deliveryDate: document.getElementById('deliveryDate').value,
        deliveryTime: document.getElementById('deliveryTime').value,
        



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
                menuSelect.innerHTML += `<option value="${menu.id}">${menu.title}</option>`;
            });
        }
    } catch (error) {
        console.error("Erreur lors du chargement des menus :", error);
    }
}

//fonction pour remplir la modale de modification de commande avec les infos de la commande sélectionnée
export async function fillEditOrderModal(orderId) {
    try{
        const user = await getUserInfo();
        console.log("Informations utilisateur récupérées pour modification de commande :", user);
        const order = await getOrderById(orderId);
        if (!order) {
            alert("Commande introuvable. Veuillez réessayer.");
            return;
        }
        console.log("Détails de la commande récupérés pour modification :", order);
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
    const totalPrice = Number(order.totalPrice ?? 0).toFixed(2);


    // 2. On injecte le HTML
    editMenuSection.innerHTML = `
        <label class="form-label fw-bold">Votre menu commandé :</label>
        <div class="p-3 border rounded bg-light">
            <p class="mb-1"><strong>Menu :</strong> ${order.menu.title || 'N/A'}</p>
            <p class="mb-1 text-muted small">${order.menu.descriptionMenu || 'Pas de description'}</p>
            <p class="mb-1"><strong>Prix :</strong> ${totalPrice} €</p>
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
