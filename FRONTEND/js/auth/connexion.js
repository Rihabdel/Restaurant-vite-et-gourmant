import { setToken, setCookie, showAndHideElementsForRoles, roleCookieName } from "../script.js";
const emailInput = document.getElementById("EmailInput");
const passwordInput = document.getElementById("PasswordInput");
const btnConnexion = document.getElementById("BtnLogin");
const formConnexion = document.getElementById("FormConnexion");

btnConnexion.addEventListener("click", (e) => checkCredentials(e)); 

function checkCredentials(e) {
    e.preventDefault();

    let dataForm = new FormData(formConnexion);
    let myHeaders = new Headers();
    myHeaders.append("Content-Type", "application/json");
    const raw = JSON.stringify({
        username: dataForm.get("email"),  
        password: dataForm.get("password")
    });
    const requestOptions = {
    method: "POST",
    headers: myHeaders,
    body: raw,
};
fetch("https://127.0.0.1:8000/api/login", requestOptions)
    .then(response => {
        if (!response.ok) throw new Error(response.status); 
        return response.json();
    })
    .then(result => {
        const token = result.apiToken;
        setToken(token);
        setCookie(roleCookieName, result.roles[0], 7);
        showAndHideElementsForRoles();
        globalThis.location.replace("/");
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Identifiants incorrects. Veuillez réessayer.");
    });
}


