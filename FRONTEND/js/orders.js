import { createOrder, getUserInfo, getMenus} from './api.js';
import { showAndHideElementsForRoles, isConnected, getToken} from './script.js';
import { fillMenuSelect } from './menu.js';
const API_BASE = 'https://127.0.0.1:8000/api';
export default async function initOrder() {
    console.log("Initialisation page commandes");
    attachEventListeners();
    await fillMenuSelect();
    await loadOrders(); // charge les commandes existantes
    // Attacher les écouteurs pour les boutons Modifier et Annuler (délégués)
    document.addEventListener('click', async (e) => {
        if (e.target.classList.contains('edit-order-btn')) {
            const orderId = e.target.dataset.id;
            await fillEditModal(orderId); 
            const modal = new bootstrap.Modal(document.getElementById('editCommandeModal'));
            modal.show();
        }

        if (e.target.classList.contains('cancel-order-btn')) {
            const orderId = e.target.dataset.id;
            document.getElementById('cancel-order-id').value = orderId;
            const modal = new bootstrap.Modal(document.getElementById('cancelCommandeModal'));
            modal.show();
        }
    });
}
function attachEventListeners() {
    // Écouteurs pour les boutons de la modale de création de commande
    document.getElementById('new-order-btn')?.addEventListener('click', async () => {
        console.log("Bouton nouvelle commande cliqué");
        await fillNewOrderModal();
        console.log("Données utilisateur chargées dans la modale");
        const modal = new bootstrap.Modal(document.getElementById('OrderModal'));
        console.log("Affichage de la modale de nouvelle commande");
        loadOrders(); 
        if (modal) modal.show();
    });
        
    document.getElementById('confirm-order-btn')?.addEventListener('click', async (event) => {
        event.preventDefault();
        const orderData = getCurrentOrderData();
        const user = await getUserInfo();
        fillNewOrderDetailsModal(orderData, user);
        const modal = new bootstrap.Modal(document.getElementById('OrderModal'));//
        if (modal) modal.hide();
        const detailsModal = new bootstrap.Modal(document.getElementById('OrderDetailsModal'));
        detailsModal.show();
    });
     document.getElementById('pay-order-btn')?.addEventListener('click', async (event) => {
        event.preventDefault();
        await handleCreateOrder(event);
        const detailsModal = new bootstrap.Modal(document.getElementById('OrderDetailsModal'));
        if (detailsModal) detailsModal.hide();
    });
}
//recupere les infos de l'utilisateur pour préremplir la modale de création de commande
export async function fillNewOrderModal() {
    const user = await getUserInfo();
    if (user){
        document.getElementById('customerName').value = user.lastName || '';
        document.getElementById('customerPrenom').value = user.firstName || '';
        document.getElementById('customerEmail').value = user.email || '';
        document.getElementById('customerPhone').value = user.phone || '';
        document.getElementById('customerAddress').value = user.address || '';
    }
}

export function fillNewOrderDetailsModal(orderData , user) {
    document.getElementById('detailsName').textContent = user.lastName || '';
    document.getElementById('detailsPrenom').textContent = user.firstName || '';
    document.getElementById('detailsEmail').textContent = user.email || '';
    document.getElementById('detailsPhone').textContent = user.phone || '';
    document.getElementById('detailsMenu').textContent = orderData.menuTitle || '';
    document.getElementById('detailsQuantity').textContent = orderData.minOfPeople || '';
    document.getElementById('detailsDeliveryDate').textContent = orderData.deliveryDate || '';
    document.getElementById('detailsDeliveryTime').textContent = orderData.deliveryTime || '';
    document.getElementById('detailsAddress').textContent = orderData.deliveryAddress || '';
    document.getElementById('detailsTotalPrice').textContent = orderData.totalPrice ? `${orderData.totalPrice.toFixed(2)} €` : '';
}
//creer une nouvelle  avec double confirmation (d'abord affichage d'une modale de récapitulatif, puis création effective à la validation)

export async function handleCreateOrder(event) {
    event.preventDefault(); // Empêche tout comportement par défaut

    if (!isConnected()) {
        alert('Vous devez être connecté.');
        window.location.href = '/connexion';
        return;
    }

    const orderform = document.getElementById('OrderForm');
    if (!orderform) return;

    const formData = new FormData(orderform);
    const orderData = {
        //select menu
        menuId: parseInt(formData.get('menuId'), 10), 
        numberOfPeople: Number.parseInt(formData.get('numberOfPeople'), 10),
        deliveryAddress: formData.get('deliveryAddress'),
        deliveryCity: formData.get('deliveryCity'),
        deliveryDate: formData.get('deliveryDate'),
        deliveryTime: formData.get('deliveryTime'),
        deliveryPostalCode: formData.get('deliveryPostalCode'),
        billingAddress: formData.get('address') || formData.get('deliveryAddress'), 
        billingCity: formData.get('city') || formData.get('deliveryCity'),
        billingPostalCode: formData.get('postalCode') || formData.get('deliveryPostalCode'),
    };

    try {
        const createdOrder = await createOrder(orderData);
        alert('Commande créée avec succès !');
        const detailsModal = new bootstrap.Modal(document.getElementById('OrderDetailsModal'));
        if (detailsModal) detailsModal.hide();
        await loadOrders(); 

    } catch (error) {
        console.error('erreur création commande', error);
        alert('Erreur lors de la création de la commande.');
    }
    showAndHideElementsForRoles();
}

//rempli le select des menus dans la modale de création de commande

function calculateTotalPrice(menuPrice, quantity, deliveryCity) {
    let total = menuPrice * quantity;
    // Frais de livraison : si ville != "bordeaux" (insensible à la casse)
    if (deliveryCity.toLowerCase() !== 'bordeaux') {
        total += 5 + 0.59 * 10; // exemple avec 10km (à améliorer)
    }
    // Réduction de 10% si quantity >= minPersons + 5
    // Pour cela, il faudrait connaître le minPersons du menu. On peut le stocker aussi dans dataset.
    // Simplifions pour l'instant : pas de réduction.
    return total;
}

// Fonction pour récupérer les données actuelles de la commande à partir du formulaire
function getCurrentOrderData() {
    const menuSelect = document.getElementById('menuSelect');
    const selectedOption = menuSelect?.options[menuSelect.selectedIndex];
    const menuTitle = selectedOption ? selectedOption.textContent : '';
    const menuPrice = selectedOption ? parseFloat(selectedOption.dataset.price) : 0;
    const numberOfPeople = parseInt(document.getElementById('menuQuantity')?.value, 10) || 0;
    const deliveryAddress = document.getElementById('deliveryAddress')?.value || '';
    const deliveryCity = document.getElementById('deliveryCity')?.value || '';
    const deliveryDate = document.getElementById('deliveryDate')?.value || '';
    const deliveryTime = document.getElementById('deliveryTime')?.value || '';
    const totalPrice = calculateTotalPrice(menuPrice, numberOfPeople, deliveryCity);

    return {
        menuTitle,
        numberOfPeople,
        deliveryAddress,
        deliveryCity,
        deliveryDate,
        deliveryTime,
        totalPrice
    };
}
async function loadOrders() {
    try {
        const token = getToken();
        const response = await fetch(`${API_BASE}/orders`, {
            headers: { 'X-AUTH-TOKEN': token }
        });
        if (!response.ok) throw new Error('Erreur chargement');
        const orders = await response.json();
        displayOrders(orders);
    } catch (error) {
        console.error(error);
    }
}

function displayOrders(orders) {
    const tbody = document.getElementById('orders-tbody');
    if (!tbody) return;
    if (!Array.isArray(orders) || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">Aucune commande</td></tr>';
        return;
    }
    tbody.innerHTML = orders.map(order => `
        <tr>
            <th scope="row">${order.id}</th>
            <td>Commande #${order.id}</td>
            <td>${order.createdAt ? new Date(order.createdAt).toLocaleDateString() : ''}</td>
            <td>
                <button class="btn btn-warning btn-sm edit-order-btn" data-id="${order.id}">Modifier</button>
            </td>
            <td>
                <button class="btn btn-danger btn-sm delete-order-btn" data-id="${order.id}">Annuler</button>
            </td>
            <td>${order.status || 'En attente'}</td>
        </tr>
    `).join('');
}
