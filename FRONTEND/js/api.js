import { getToken} from './script.js';
export const API_BASE = "http://localhost:8000/api";


// --- Fonctions API pour les plats ---
export async function getDishes() {
    const response = await fetch(`${API_BASE}/dishes/list`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    }); 
    if (!response.ok) {
        throw new Error('Erreur de chargement des plats');
    }
    return await response.json();
}
export async function createDish(dishData) {
    const response = await fetch(`${API_BASE}/dishes/new`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify(dishData) 
    });
    if (!response.ok) {
        const errorData = await response.json();
        console.error("Détails serveur :", errorData);
        throw new Error('Erreur de création du plat');
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

export async function updateDish(id, dishData) {
    const response = await fetch(`${API_BASE}/dishes/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify(dishData) 
    });
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erreur de mise à jour du plat');
    }
    return await response.json();
}
export async function deleteDish(id) {
    const response = await fetch(`${API_BASE}/dishes/${id}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
    });
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erreur de suppression du plat');
    }
    return await response.json();
}

// --- Fonctions API pour les menus ---
export async function getMenus(filters = {}) {
    const params = new URLSearchParams(filters);
    const response = await fetch(`${API_BASE}/menu/list?${params.toString()}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    });
    if (!response.ok) {
        throw new Error('Erreur de chargement des menus');
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

// --- Fonction API pour menuDishes ---
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
//ajouter des plats à un menu
export async function addDishToMenu(menuId, dishId) {
    const response = await fetch(`${API_BASE}/menus-dishes/${menuId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify({ dish_id: dishId }) 
    });
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erreur d\'ajout du plat au menu');
    }
    return await response.json();
}
export async function getListDesDishesByMenuId(id) {
    const response = await fetch(`${API_BASE}/menus-dishes/${id}/list`,{
        method: 'GET',
        headers: {
            'content-Type': 'application/json'
        },
    })
    .then(response => {
        if(response.ok){
            return response.json();
        }
        else{
            console.log("Impossible de récupérer les plats du menu");
        }
    })
    .then(result => {
        return result;
    })
    .catch(error => {
        console.error("Erreur lors de la récupération des plats du menu", error);
    });
    return response;
}

// --- Fonction API pour les allergènes ---

export async function addAllergen(allergenName) {
    const response = await fetch(`${API_BASE}/allergens/new`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify({ name: allergenName }) 
    });
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erreur d\'ajout de l\'allergène');
    }
    return await response.json();
}
export async function getDishAllergens(dishId) {
   
    const response = await fetch(`${API_BASE}/dish_allergen/${dishId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    });
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erreur de mise à jour de la commande');
    }
    return await response.json();
}
export async function getAllergens() {
    const response = await fetch(`${API_BASE}/allergens`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    });
    if (!response.ok) throw new Error('Erreur chargement allergènes');
    return await response.json();
}
// --- Fonction API pour dishAllegen ---
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
export async function addDishAllergens(dishId, allergenIds) {
    const response = await fetch(`${API_BASE}/dish_allergen/${dishId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify({ allergen_id: allergenIds }) 
    });
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erreur d\'ajout des allergènes au plat');
    }
    return await response.json();
}

// --- Fonctions API pour les commandes ---
export async function createOrder(orderData) {
    console.log("Données de la commande envoyées à l'API :", orderData);
    const response = await fetch(`${API_BASE}/orders/new`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify(orderData) 
    });
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erreur de création de la commande');
    }
    return await response.json();
}
export async function getOrders() {
    const response = await fetch(`${API_BASE}/orders`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        }
    });
    if (!response.ok) throw new Error('Erreur chargement commandes');
    return await response.json();
}
export async function getOrderById(id) {
    const response = await fetch(`${API_BASE}/orders/${id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        }
    });
    if (!response.ok) throw new Error('Erreur chargement commande');
    return await response.json();
}
export async function updateOrder(id, orderData) {
    const response = await fetch(`${API_BASE}/orders/${id}/edit`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },        body: JSON.stringify(orderData) 
    });
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erreur de mise à jour de la commande');
    }
    return await response.json();
}
export async function cancelOrder(id) {
    const response = await fetch(`${API_BASE}/orders/${id}/cancel`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
    });
    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erreur de suppression de la commande');
    }
    return await response.json();
}
// --- Fonction API pour récupérer les informations de l'utilisateur connecté ---
export async function getUserInfo() {
     let myHeaders = new Headers();
    myHeaders.append("X-AUTH-TOKEN", getToken());

    let requestOptions = {
        method: 'GET',
        headers: myHeaders,
        redirect: 'follow'
    };

    const response = await fetch(`${API_BASE}/user`, requestOptions)
    .then(response =>{
        if(response.ok){
            return response.json();
        }
        else{            console.log("Impossible de récupérer les informations utilisateur");
        }
    })
    .then(result => {
        return result;
    })
    .catch(error =>{
        console.error("erreur lors de la récupération des données utilisateur", error);
    });
    return response;
}

