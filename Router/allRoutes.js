import Route from "/Router/Route.js";

//Définir ici vos routes
export const allRoutes = [
    new Route("/", "Accueil", "/pages/home.html"),
    new Route("/menu", "Menu", "/pages/menu.html"),
    new Route("/details", "Détails", "/pages/details.html"),
];

//Le titre s'affiche comme ceci : Route.titre - websitename
export const websiteName = "vite et gourmand";