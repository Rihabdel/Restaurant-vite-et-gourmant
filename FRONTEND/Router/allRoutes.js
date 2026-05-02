import Route from "/Router/Route.js";

export const allRoutes = [
    new Route("/", "Accueil", "/pages/home.html", []),
    new Route("/menu", "Menu", "/pages/menu.html", [], "/js/menu.js"),
    new Route("/contact", "Contact", "/pages/contact.html", []),
    
    // ADMIN : Vérifie que le fichier est bien à la racine de /pages/
    new Route("/admin", "Administration", "/pages/admin.html", ["ROLE_ADMIN"], "/js/admin.js"),

    // CONNEXION : Dans le sous-dossier auth
    new Route("/connexion", "Connexion", "/pages/auth/connexion.html", ["disconnected"], "/js/auth/connexion.js"),

    // MON COMPTE : Ton fichier s'appelle office.html et est à la racine de /pages/
    new Route("/monCompte", "Mon Compte", "/pages/auth/profil.html", ["ROLE_USER", "ROLE_ADMIN"], "/js/auth/profil.js"),

    // COMMANDES : Ton fichier est à la racine de /pages/
    new Route("/order", "Mes commandes", "/pages/order.html", ["ROLE_USER", "ROLE_ADMIN"], "/js/orders.js"),

    // INSCRIPTION : Ton fichier s'appelle inscript.html à la racine de /pages/
    new Route("/inscription", "Inscription", "/pages/auth/inscription.html", ["disconnected"], "/js/auth/inscription.js"),

    new Route("/404", "Page introuvable", "/pages/404.html", [])
];

export const websiteName = "vite et gourmand";