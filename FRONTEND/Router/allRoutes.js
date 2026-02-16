import Route from "./Route.js";

//Définir ici vos routes²
export const allRoutes = [
    new Route("/", "Accueil", "/pages/home.html",[]),
    new Route("/menu", "Menu", "/pages/menu.html",[],"/js/menu.js"),
    new Route("/details", "Détails", "/pages/details.html",[]),
    new Route("/connexion", "Connexion", "/pages/auth/connexion.html",["disconnected"],"/js/auth/connexion.js"),
    new Route("/inscription", "Inscription", "/pages/auth/inscription.html",["disconnected"],"/js/auth/inscription.js"),
    new Route("/profil", "Profil", "/pages/auth/profil.html",["connected","user","admin","employe"],"/js/script.js"),
    new Route("/monCompte", "Mon Compte", "/pages/auth/profil.html",[],"/js/script.js"),
    new Route("/order", "Mes commandes", "/pages/order.html",["connected","user","admin","employe"],"/js/script.js"),
    new Route("/contact", "Contact", "/pages/contact.html",[]),   
    new Route ("/404", "Page introuvable", "/pages/404.html",[]), 
];

//Le titre s'affiche comme ceci : Route.titre - websitename
export const websiteName = "vite et gourmand";