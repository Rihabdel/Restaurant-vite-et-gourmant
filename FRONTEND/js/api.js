import { getToken} from './script.js';
export const API_BASE = "http://localhost:8000/api";


export async function getMenus(filters = {}) {
    const params = new URLSearchParams(filters);
    const response = await fetch(`${API_BASE}/menu/list?${params.toString()}`, {
        method: 'GET',
         headers: { 
            'Content-Type': 'application/json',
},
});
    if (!response.ok) throw new Error('Erreur chargement menus');
    return await response.json();
}

export async function getMenuById(id) {
    const response = await fetch(`${API_BASE}/menu/${id}`,{
        method: 'GET',
        headers: {
            'content-Type': 'application/json'
        },
    });
    if (!response.ok) {
        throw new Error('Erreur d\'affichage de menu');
    }
    return await response.json();
}
export async function getMenuDishes(id) {
    const response = await fetch(`${API_BASE}/menus-dishes/${id}/list`,{
        method: 'GET',
        headers: {
            'content-Type': 'application/json'
        },
    });
    if (!response.ok) {
        throw new Error('Erreur d\'affichage des plats du menu');
    }
    return await response.json();
}
export async function getDishById(id) {
    const response = await fetch(`${API_BASE}/dishes/${id}`,{
        method: 'GET',
        headers: {
            'content-Type': 'application/json'
        },
    }); 
    if (!response.ok) {
        throw new Error('Erreur d\'affichage du plat');
    }
    return await response.json();
}
export async function getDishAllergenes(id) {
    const response = await fetch(`${API_BASE}/dish_allergen/${id}`,{
        method: 'GET',  
        headers: {
            'content-Type': 'application/json'
        },
    });
    if (!response.ok) {
        throw new Error('Erreur d\'affichage des allergènes du plat');
    }
    return await response.json();
}

export async function enregistrerMenu(menuData) {
    
    const response = await fetch(`${API_BASE}/menu/new`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify(menuData) 
    });

    if (!response.ok) {
        throw new Error('Erreur d\'ajout du menu');
    }
    return await response.json();
}
export async function updateMenu(id, menuData) {
    const response = await fetch(`${API_BASE}/menu/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify(menuData) 
    });
    if (!response.ok) {
        throw new Error('Erreur de mise à jour du menu');
    }
    return await response.json();
}
export async function deleteMenu(id) {
    const response = await fetch(`${API_BASE}/menu/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
    });
    if (!response.ok) {
        throw new Error('Erreur de suppression du menu');
    }
    return await response.json();
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
