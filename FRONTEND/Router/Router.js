import  Route  from "/Router/Route.js";
import { allRoutes, websiteName } from "./allRoutes.js";
import { showAndHideElementsForRoles, isConnected, getRole } from "../js/script.js";

// Création d'une route pour la page 404 (page introuvable)
const route404 = new Route("404", "Page introuvable", "/pages/404.html");

// Fonction pour récupérer la route correspondant à une URL donnée
const getRouteByUrl = (url) => {
  const route = allRoutes.find((route) => route.url === url) || route404;
  return route;
}

// Fonction pour charger le contenu de la page
const LoadContentPage = async () => {
    const path = globalThis.location.pathname;
  // Récupération de l'URL actuelle
  const actualRoute = getRouteByUrl(path);

//Vérifier les droits d'accès à la page
  const allRolesArray = actualRoute.authorize || [];

  if(allRolesArray.length > 0){
    if(allRolesArray.includes("disconnected")){
      if(isConnected()){
        globalThis.location.replace("/");
        return;
      }
    }
    else{
      if(!isConnected()){
        globalThis.location.replace("/connexion");
        return;
      }

    }
  }

  // Récupération du contenu HTML de la route
  const html = await fetch(actualRoute.pathHtml).then((data) => data.text());
  // Ajout du contenu HTML à l'élément avec l'ID "main-page"
  document.getElementById("main-page").innerHTML = html;

 // Chargement du script JS (import dynamique)
  if (actualRoute.pathJS && actualRoute.pathJS !== "") {
    try {
      const module = await import(actualRoute.pathJS);
      if (module.default && typeof module.default === 'function') {
        await module.default();
      } else {
        console.warn("Pas de fonction default dans", actualRoute.pathJS);
      }
    } catch (error) {
      console.error("Erreur import dynamique :", error);
    }
  }

  // Changement du titre de la page
  document.title = actualRoute.title + " - " + websiteName;
  // Appel de la fonction pour afficher ou cacher les éléments en fonction des rôles
showAndHideElementsForRoles()
};

// Fonction pour gérer les événements de routage (clic sur les liens)
const routeEvent = (event) => {
  event.preventDefault();
  // Mise à jour de l'URL dans l'historique du navigateur
  globalThis.history.pushState({}, "", event.target.href);
  // Chargement du contenu de la nouvelle page
  LoadContentPage();
};

// Gestion de l'événement de retour en arrière dans l'historique du navigateur
globalThis.onpopstate = LoadContentPage;
// Assignation de la fonction routeEvent à la propriété route de la fenêtre
globalThis.route = routeEvent;
// Chargement du contenu de la page au chargement initial
LoadContentPage();
export { LoadContentPage };


