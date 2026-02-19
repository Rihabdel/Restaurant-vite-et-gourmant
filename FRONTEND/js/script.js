
// Constantes
// ----------------------------
const API_BASE = 'https://127.0.0.1:8000/api'; 
const tokenCookieName = "accesstoken";
export const roleCookieName = 'role';
const signoutBtn = document.getElementById("SignoutBtn");

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
    return getToken() !== null && getToken() !== "";
}

signoutBtn.addEventListener("click", signout);

function signout() {
    eraseCookie(tokenCookieName);
    eraseCookie(roleCookieName);
    alert("Vous êtes déconnecté.");
   history.pushState({}, '', '/');
   LoadContentPage();

}

// ----------Affichage ou non des éléments en fonction du role de l'utilisateur----------
export function showAndHideElementsForRoles(){
    const userConnected = isConnected();
    const role = getRole(); 

    const allElementsToEdit = document.querySelectorAll('[data-show]');
    allElementsToEdit.forEach(element => {
        element.classList.remove('d-none'); // reset
        switch(element.dataset.show){
            case 'disconnected': 
                if(userConnected) element.classList.add("d-none");
                break;
            case 'connected': 
                if(!userConnected) element.classList.add("d-none");
                break;
            case 'admin': 
                if(!userConnected || role !== "ROLE_ADMIN") element.classList.add("d-none");
                break;
            case 'user': 
                if(!userConnected || role !== "ROLE_USER") element.classList.add("d-none");
                break;
        }
    });
}
