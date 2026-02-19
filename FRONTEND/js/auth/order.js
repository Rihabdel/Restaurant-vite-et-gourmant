import { showAndHideElementsForRoles, getToken,isCnnected} from './script.js';
import { fillMenuSelect } from './menu.js';

const API_BASE = 'https://127.0.0.1:8000/api';

//ancien
const tbody = document.getElementById("orders-tbody");


export default async function initOrder() {
    console.log("Initialisation page commandes");
    await loadOrders();
    attachEventListeners();
}

async function loadOrders() {
    try {
        if (!isConnected()) {
            displayOrders([]);
            return;
        }
    console.log("Chargement des commandes...");
    try {
        const Orders= await getUserOrders();
        displayOrders(Orders);
        console.log(Orders);
    } catch (error) {
        console.error('Erreur chargement commandes :', error);
        tbody.innerHTML = '<tr><td colspan="6">Erreur chargement commandes</td></tr>';
    }
}
catch (error) {    console.error('Erreur chargement commandes :', error);
    tbody.innerHTML = '<tr><td colspan="6">Erreur chargement commandes</td></tr>';
}
function displayOrders(orders) {
    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">Aucune commande en cours</td></tr>';
        return;
    }
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td>${order.id}</td>
            <td>${order.menuTitle}</td>
            <td>${order.numberOfPeople}</td>
            <td>${order.totalPrice} €</td>
            <td>${new Date(order.deliveryDate).toLocaleDateString()}</td>
            <td>${order.deliveryTime}</td>
            <td>${order.deliveryCity}</td>
            <td>${order.deliveryAddress}</td>
            <td>${order.deliveryPostalCode}</td>

            <td><span class="badge bg-${getStatusColor(order.status)}">${order.status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary edit-order-btn" data-id="${order.id}">Modifier</button>
                ${order.status === 'en_attente' ? `<button class="btn btn-sm btn-danger cancel-order-btn" data-id="${order.id}">Annuler</button>` : ''}
            </td>
        </tr>
    `).join('');
showAndHideElementsForRoles();
}

function displayMenus(menus) {  
    const container = document.getElementById('menusContainer');
    container.innerHTML = menus.map(menu => `
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <img src="${menu.imageUrl}" class="card-img-top" alt="${menu.title}">
                <div class="card-body">
                    <h5 class="card-title">${menu.title}</h5>
                    <p class="card-text">${menu.description}</p>
                    <p class="card-text"><strong>Prix:</strong> ${menu.price} € / personne</p>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary view-detail-btn" data-id="${menu.id}">Voir le détail</button>    
                </div>
            </div>
        </div>
    `).join('');
showAndHideElementsForRoles();
}
//
function getStatusColor(status) {
    switch(status) {
        case 'en_attente': return 'warning';
        case 'acceptée': return 'success';
        case 'en_preparation': return 'info';
        case 'livrée': return 'primary';
        case 'en_attente_de_retour': return 'teal';
        case 'terminée': return 'success';
        case 'annulée': return 'danger';
        default: return 'secondary';
    }
}

function attachEventListeners() {
    // Gestionnaire pour les boutons Modifier (délégation)
    document.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-order-btn');
        if (editBtn) {
            e.preventDefault();
            const orderId = editBtn.dataset.id;
            await loadOrderForEdit(orderId);
            const modal = new bootstrap.Modal(document.getElementById('editCommandeModal'));
            modal.show();
        }
    });

    // Gestionnaire pour les boutons Annuler
    document.addEventListener('click', async (e) => {
        const cancelBtn = e.target.closest('.cancel-order-btn');
        if (cancelBtn) {
            e.preventDefault();
            const orderId = cancelBtn.dataset.id;
            if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
                await cancelOrder(orderId);
            }
        }
    });

    // Gestionnaire pour la confirmation d'annulation dans la modale
    document.querySelector('#deleteCommandeModal .btn-danger')?.addEventListener('click', async () => {
        // Récupérer l'ID de la commande à annuler (stocké dans un data attribute)
        const orderId = document.getElementById('deleteCommandeModal').dataset.orderId;
        if (orderId) {
            await cancelOrder(orderId);
            bootstrap.Modal.getInstance(document.getElementById('deleteCommandeModal')).hide();
        }
    });

    // Gestionnaire pour le bouton "Passer une nouvelle commande"
    document.querySelector('[data-bs-target="#orderModal"]')?.addEventListener('click', (e) => {
        // Rediriger vers la page de commande
        window.location.href = '/commande';
    });
}

async function loadOrderForEdit(orderId) {
    try {
        const response = await fetch(`${API_BASE}/orders/${orderId}`, {
            headers: { 'X-AUTH-TOKEN': getToken() }
        });
        if (!response.ok) throw new Error('Erreur chargement commande');
        const order = await response.json();
        // Pré-remplir la modale d'édition avec les données
        fillEditModal(order);
    } catch (error) {
        console.error(error);
        alert('Impossible de charger la commande');
    }
}
//
function fillEditModal(order) {
    // Remplir les champs de la modale editCommandeModal
    document.getElementById('customerName').value = order.lastName || '';
    document.getElementById('customerPrenom').value = order.firstName || '';
    document.getElementById('customerEmail').value = order.email || '';
    document.getElementById('customerPhone').value = order.phone || '';
    document.getElementById('customerAddress').value = order.deliveryAddress || '';
    document.getElementById('customerDate').value = order.deliveryDate ? order.deliveryDate.split('T')[0] : '';
    // Afficher le nom du menu (peut-être dans un <p>)
    const menuInfo = document.querySelector('#editCommandeModal .col p:first-child');
    if (menuInfo) menuInfo.textContent = `Menu ${order.menuTitle}`;
    // Stocker l'ID de la commande dans la modale
    document.getElementById('editCommandeModal').dataset.orderId = order.id;

}

    // Afficher le nom du menu (peut-être dans un <p>)
    const menuInfo = document.querySelector('#editCommandeModal .col p:first-child');
    if (menuInfo) menuInfo.textContent = `Menu ${order.menuTitle}`;
    // Stocker l'ID de la commande dans la modale
    document.getElementById('editCommandeModal').dataset.orderId = order.id;
}

async function cancelOrder(orderId) {
    try {
        // Appel API pour annuler (peut-être un DELETE ou un PUT avec statut annulé)
        const response = await fetch(`${API_BASE}/orders/${orderId}`, {
            method: 'DELETE', // ou PUT selon votre API
            headers: { 'X-AUTH-TOKEN': getToken() }
        });
        if (!response.ok) throw new Error('Erreur annulation');
        alert('Commande annulée');
        // Recharger la liste
        await loadOrders();
    } catch (error) {
        console.error(error);
        alert('Impossible d\'annuler la commande');
    }
}
showAndHideElementsForRoles(['ROLE_USER']);
