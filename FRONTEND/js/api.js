// api.js
const API_BASE = 'https://127.0.0.1:8000/api';

export async function getMenus(filters = {}) {
    const params = new URLSearchParams(filters);
    const response = await fetch(`${API_BASE}/menu/list?${params}`);
    if (!response.ok) throw new Error('Erreur chargement menus');
    return response.json();
}

export async function getMenuById(id) {
    const response = await fetch(`${API_BASE}/menu/${id}`); 
    if (!response.ok) throw new Error(`Erreur ${response.status}`);
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
