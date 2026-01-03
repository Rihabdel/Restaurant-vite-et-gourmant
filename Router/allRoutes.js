import Route from "/Router/Route.js";

//Définir ici vos routes
export const allRoutes = [
    new Route("/", "Accueil", "/pages/home.html"),
    new Route("/menu", "Menu", "/pages/menu.html"),
    new Route("/details", "Détails", "/pages/details.html"),
    new Route("/connexion", "Connexion", "/pages/connexion.html"),
    new Route("/inscription", "Inscription", "/pages/inscription.html"),
    new Route("/profil", "Profil", "/pages/profil.html"),
    new Route("/monCompte", "Mon Compte", "/pages/profil.html"),
    new Route("/order", "Mes commandes", "/pages/order.html"),
];

//Le titre s'affiche comme ceci : Route.titre - websitename
export const websiteName = "vite et gourmand";