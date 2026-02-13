const API_BASE = 'http://localhost:8000/api'; 
const tokenCookieName = "apiToken";
const roleCookieName = 'ROLE_USER';
const signoutBtn = document.getElementById("signoutBtn");
signoutBtn.addEventListener("click", signout);


function setToken(token){
    setCookie(tokenCookieName, token, 7);
}

function getToken(){
    return getCookie(tokenCookieName);
}
// Fonction pour stocker le rôle de l'utilisateur dans un cookie
function setRole(role){
    setCookie(roleCookieName, role, 7);
}
// Fonction pour récupérer le rôle de l'utilisateur à partir du cookie
function getRole(){
    return getCookie(roleCookieName);
}
function signout(){
    eraseCookie(tokenCookieName);
    eraseCookie(roleCookieName);
    alert("Déconnexion réussie !");
    document.location.href="/";
}
function isConnected(){
    const token = getToken();
    return token !== null;
}
if (isConnected()){
    alert("Vous êtes déjà connecté !");
 } else{
        alert("Vous n'êtes pas connecté !");
    }

function setCookie(name,value,days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i=0;i < ca.length;i++) {
        let c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {   
    document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}
function getUserInfo(){
    
   const myHeaders = new Headers();
myHeaders.append("Content-Type", "application/json");
myHeaders.append("X-AUTH-TOKEN", getToken());

const requestOptions = {
  method: "GET",
  headers: myHeaders,
  body: raw,
  redirect: "follow"
};

fetch("https://127.0.0.1:8000/api/user", requestOptions)
    then(response =>{
        if(response.ok){
            return response.json();
        }
        else{
            console.log("Impossible de récupérer les informations utilisateur");
        }
    })
    .then(result => {
        return result;
    })
    .catch(error =>{
        console.error("erreur lors de la récupération des données utilisateur", error);
    });
}