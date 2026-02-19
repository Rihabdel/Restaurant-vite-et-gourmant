import { getToken, isConnected } from './script.js';
const API_BASE = 'https://127.0.0.1:8000/api';

export async function getMenus(filters = {}) {
    const params = new URLSearchParams(filters);
    const response = await fetch(`${API_BASE}/menu/list?${params}`);
    if (!response.ok) throw new Error('Erreur chargement menus');
    return response.json();
}

export async function getMenuById(id) {
    const response = await fetch(`${API_BASE}/menu/${id}`);
    if (!response.ok) throw new Error('Erreur chargement menu');
    return response.json();
}
export async function getMenuDishes(id) {
    const response = await fetch(`${API_BASE}/menus-dishes/${id}/list`);
    if (!response.ok) throw new Error(`Erreur ${response.status}`);
    return response.json();
}
export async function getDishAllergenes(id) {
    const response = await fetch(`${API_BASE}/dish-allergen/${id}/allergenes`);
    if (!response.ok) throw new Error(`Erreur ${response.status}`);
    return response.json();
}
export async function getUserInfo() {
    const token = getToken();
    if (!token) return null;
    try {
        const response = await fetch(`${API_BASE}/user`, {
            headers: { 'X-AUTH-TOKEN': token }
        });
        if (!response.ok) throw new Error('Erreur chargement profil');
        return await response.json();
    } catch (error) {
        console.error(error);
        return null;
    }
}
export async function updateUserInfo(profileForm) {
    getUserInfo();
    const token = getToken();
    let formData=new FormData(profileForm);
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

fetch("https://127.0.0.1:8000/api/user/", requestOptions)
  .then((response) => response.text())
  .then((result) => console.log(result))
  .catch((error) => console.error(error));
}


export async function getUserOrders() {
  let formData = new FormData();
    const token = getToken();
    if (!token) return null;
const myHeaders = new Headers();
myHeaders.append("Content-Type", "application/json");
myHeaders.append("X-AUTH-TOKEN", token);

const requestOptions = {
  method: "GET",
  headers: myHeaders,
  redirect: "follow"
};
const response = await fetch("https://127.0.0.1:8000/api/orders", requestOptions);
if (!response.ok) throw new Error('Erreur chargement commandes en cours');
}


export async function createOrder(orderForm) {
    let formData = new FormData(orderForm);
    const token = getToken();
    if (!token) throw new Error('Utilisateur non connecté');
    const myHeaders = new Headers();
    myHeaders.append("Content-Type", "application/json");
    myHeaders.append("X-AUTH-TOKEN", token);
    const raw = JSON.stringify({
        "numberOfPeople": formData.get("numberOfPeople"),
        "totalPrice": formData.get("totalPrice"),
        "deliveryCost": formData.get("deliveryCost"),
        "deliveryAddress": formData.get("deliveryAddress"),
        "deliveryDate": formData.get("deliveryDate"),
        "deliveryTime": formData.get("deliveryTime"),
        "deliveryCity": formData.get("deliveryCity"),
        "deliveryPostalCode": formData.get("deliveryPostalCode"),
        "menu": formData.get("menuId")

    });
    const requestOptions = {
        method: "POST",
        headers: myHeaders,
        body: raw,
    };
    const response = await fetch(`${API_BASE}/orders/new`, requestOptions);
    if (!response.ok) throw new Error('Erreur lors de la création de la commande');
    return await response.json();
}

