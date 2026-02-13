import Route from "/Router/Route.js";

//Définir ici vos routes
export const allRoutes = [
    new Route("/", "Accueil", "/pages/home.html",[]),
    new Route("/menu", "Menu", "/pages/menu.html",[],"/js/menu.js"),
    new Route("/details", "Détails", "/pages/details.html"),
    new Route("/connexion", "Connexion", "/pages/auth/connexion.html",["disconnected"],"/js/auth/connexion.js"),
    new Route("/inscription", "Inscription", "/pages/auth/inscription.html",["disconnected"],"/js/auth/inscription.js"),
    new Route("/profil", "Profil", "/pages/auth/profil.html",["connected","ROLE_USER","ROLE_ADMIN","ROLE_EMPLOYE"],"/js/script.js"),
    new Route("/monCompte", "Mon Compte", "/pages/profil.html"),
    new Route("/order", "Mes commandes", "/pages/order.html",["connected"],"/js/order.js"),
    new Route("/contact", "Contact", "/pages/contact.html",),    
];

//Le titre s'affiche comme ceci : Route.titre - websitename
export const websiteName = "vite et gourmand";