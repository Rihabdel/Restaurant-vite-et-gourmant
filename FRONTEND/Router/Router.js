// Router/Router.js
import Route from "./Route.js";
import { allRoutes, websiteName } from "./allRoutes.js";
import { showAndHideElementsForRoles, isConnected, getRole, waitForAuth } from "../js/script.js";

// Création d'une route pour la page 404
const route404 = new Route("404", "Page introuvable", "/pages/404.html", "", []);

// Éviter les boucles
let isLoading = false;
let lastPath = "";
let isInitialized = false;

// Fonction pour récupérer la route
const getRouteByUrl = (url) => {
    const cleanUrl = url.split('?')[0].split('#')[0];
    const route = allRoutes.find(route => route.url === cleanUrl);
    return route || route404;
};

// Vérification des autorisations
const checkAuthorization = (route) => {
    const allowedRoles = route.authorize || [];
    
    // Si pas de restrictions, accès autorisé
    if (allowedRoles.length === 0) return true;
    
    // Cas "disconnected" (accessible uniquement aux non-connectés)
    if (allowedRoles.includes("disconnected")) {
        const connected = isConnected();
        console.log("Vérification disconnected - Connecté:", connected);
        return !connected;
    }
    
    // Vérification des rôles
    const userRole = getRole();
    const isAuth = isConnected();
    
    console.log(`Vérification accès ${route.url}:`, {
        userRole,
        isAuth,
        allowedRoles,
        accesAutorise: allowedRoles.includes(userRole)
    });
    
    // Si l'utilisateur n'est pas connecté et que la route nécessite un rôle
    if (!isAuth && allowedRoles.length > 0 && !allowedRoles.includes("disconnected")) {
        return false;
    }
    
    return allowedRoles.includes(userRole);
};

// Fonction pour charger le contenu
const LoadContentPage = async () => {
    if (isLoading) return;
    
    // Attendre que l'authentification soit prête au premier chargement
    if (!isInitialized) {
        console.log("Attente de l'initialisation de l'authentification...");
        await waitForAuth();
        isInitialized = true;
        console.log("Authentification prête - Rôle:", getRole(), "Connecté:", isConnected());
    }
    
    const path = globalThis.location.pathname;
    
    if (lastPath === path && !window.forceReload) {
        console.log("Même page, pas de rechargement");
        return;
    }
    
    isLoading = true;
    lastPath = path;
    
    const actualRoute = getRouteByUrl(path);
    
    // Vérifier les autorisations
    if (!checkAuthorization(actualRoute)) {
        console.log("Accès non autorisé à", path, "redirection vers /");
        globalThis.history.pushState({}, "", "/");
        window.forceReload = true;
        isLoading = false;
        await LoadContentPage();
        return;
    }
    
    try {
        console.log("Chargement du HTML :", actualRoute.pathHtml);
        const response = await fetch(actualRoute.pathHtml);
        
        if (!response.ok) {
            throw new Error("Fichier HTML introuvable : " + actualRoute.pathHtml);
        }
        
        const html = await response.text();
        const mainPage = document.getElementById("main-page");
        
        if (mainPage) {
            mainPage.innerHTML = html;
        } else {
            console.error("Element #main-page non trouvé");
        }
        
        // Charger le JS spécifique si présent
        if (actualRoute.pathJS && actualRoute.pathJS !== "") {
            try {
                const module = await import(actualRoute.pathJS);
                if (module.default) {
                    module.default();
                }
            } catch (err) {
                console.error("Erreur dans le script JS de la page :", err);
            }
        }
        
        document.title = `${actualRoute.title} - ${websiteName}`;
        
        // Mettre à jour l'affichage selon le rôle
        showAndHideElementsForRoles();
        
    } catch (error) {
        console.error("Erreur de routage :", error);
        const mainPage = document.getElementById("main-page");
        if (mainPage) {
            mainPage.innerHTML = `<div class="alert alert-danger">Erreur : ${error.message}</div>`;
        }
    }
    
    isLoading = false;
    window.forceReload = false;
};

// Gestion des clics
const routeEvent = (event) => {
    if (!event.target) return;
    
    let target = event.target;
    while (target && target.tagName !== 'A') {
        target = target.parentElement;
    }
    
    if (!target) return;
    
    const href = target.getAttribute('href');
    if (!href || href === '#' || href.startsWith('http')) return;
    
    event.preventDefault();
    globalThis.history.pushState({}, "", href);
    window.forceReload = true;
    LoadContentPage();
};

// Événements
globalThis.onpopstate = () => {
    window.forceReload = true;
    LoadContentPage();
};

globalThis.route = routeEvent;

// Initialisation au chargement de la page
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        LoadContentPage();
    });
} else {
    LoadContentPage();
}

export { LoadContentPage };