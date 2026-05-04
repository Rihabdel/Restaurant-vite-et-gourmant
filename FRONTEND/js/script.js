const API_BASE = 'http://127.0.0.1:8000/api'; 
const tokenCookieName = "accesstoken";
export const roleCookieName = 'role';
const signoutBtn = document.getElementById("SignoutBtn");

// Gestion des cookies
export function setToken(token) {
    setCookie(tokenCookieName, token, 7); 
}
export function getToken() {
    return getCookie(tokenCookieName);
}

export function getRole() {
    return getCookie(roleCookieName);
}

export function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}


export function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i=0;i < ca.length;i++) {
        let c = ca[i].trim();
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {   
    document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

export function isConnected() {
    return getToken() !== null;
}

// Fonction pour attendre que les cookies soient chargés (utile pour le router)
export function waitForAuth() {
    return new Promise((resolve) => {
        // Vérifier plusieurs fois sur 500ms
        let attempts = 0;
        const checkInterval = setInterval(() => {
            const token = getToken();
            const role = getRole();
            attempts++;
            if (token !== null || role !== null || attempts > 50) { // 500ms max
                clearInterval(checkInterval);
                resolve({ token, role, isConnected: !!token });
            }
        }, 10);
    });
}

// Déconnexion
if (signoutBtn) {
    signoutBtn.addEventListener("click", signout);
}

export function signout() {
    eraseCookie(tokenCookieName);
    eraseCookie(roleCookieName);
    alert("Vous êtes déconnecté.");
    window.location.href = "/connexion";
}

// Affichage des éléments selon le rôle
export function showAndHideElementsForRoles() {
    const userConnected = isConnected(); 
    const role = getRole();

    console.log("showAndHideElementsForRoles - Connecté:", userConnected, "Rôle:", role);

    document.querySelectorAll('[data-show]').forEach(element => {
        const values = element.dataset.show
            .split(',')
            .map(v => v.trim());

        const shouldShow =
            (values.includes("disconnected") && !userConnected) ||
            (values.includes("connected") && userConnected) ||
            (userConnected && role && values.includes(role));

        element.style.display = shouldShow ? "" : "none";
        
    
    });
}

// Récupération des infos utilisateur
export async function getUserInfo() {
    const myHeaders = new Headers();
    myHeaders.append("X-AUTH-TOKEN", getToken());
    const requestOptions = {
        method: "GET",
        headers: myHeaders,
    };
    const response = await fetch(`${API_BASE}/user`, requestOptions);
    if (!response.ok) throw new Error(`Erreur ${response.status}`);
    return await response.json();
}

// Mise à jour des infos utilisateur
export async function updateUserInfo(updatedData) {
    const response = await fetch(`${API_BASE}/user`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-AUTH-TOKEN': getToken()
        },
        body: JSON.stringify(updatedData) 
    });

    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `Erreur ${response.status}`);
    }
    return await response.json();
}

// Initialisation au chargement
console.log("script.js chargé - Token:", getToken(), "Rôle:", getRole());
showAndHideElementsForRoles();