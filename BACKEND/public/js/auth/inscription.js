import { API_BASE } from "../api.js";
;
// Attendre que le DOM soit chargé
export default function initInscription() {
    console.log("🚀 initInscription appelée !");
    inituserListener();
}

function inituserListener() {
    console.log("🎧 INITLISTENER - DÉBUT DE LA FONCTION");
    
    const formInscription = document.getElementById('InscriptionForm');
    if (!formInscription) {
        return;
    }
    const inputNom = formInscription.querySelector("#NomInput");
    const inputPrenom = formInscription.querySelector("#PrenomInput");
    const inputEmail = formInscription.querySelector("#EmailInput");
    const inputPassword = formInscription.querySelector("#PasswordInput");
    const inputConfirmPassword = formInscription.querySelector("#ValidatePasswordInput");
    const btnvalidation = formInscription.querySelector("#btnSubmitInscription");
    
    function validateForm() {
        const isNomValid = validateRequired(inputNom);
        const isPrenomValid = validateRequired(inputPrenom);
        const isEmailValid = validateEmail(inputEmail);
        const isPasswordValid = validatePassword(inputPassword);
        const isConfirmPasswordValid = validateConfirmPassword(inputPassword, inputConfirmPassword);
        
        if (isNomValid && isPrenomValid && isEmailValid && isPasswordValid && isConfirmPasswordValid) {
            btnvalidation.disabled = false;
            console.log("✅ Formulaire valide");
        } else {
            btnvalidation.disabled = true;
            console.log("❌ Formulaire invalide");
        }
    }
    
    inputNom.addEventListener('keyup', validateForm);
    inputPrenom.addEventListener('keyup', validateForm);
    inputEmail.addEventListener('keyup', validateForm);
    inputPassword.addEventListener('keyup', validateForm);
    inputConfirmPassword.addEventListener('keyup', validateForm);
    
    formInscription.addEventListener("submit", newUser);
    
    console.log("🎧 Écouteurs configurés avec succès !");
}

// Fonctions de validation
export function validateEmail(input) {
    const emailRregex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if (input.value.match(emailRregex)) {
        input.classList.remove("is-invalid");
        input.classList.add("is-valid");
        return true;
    } else {
        input.classList.remove("is-valid");
        input.classList.add("is-invalid");
        return false;
    }
}

export function validatePassword(input) {
    const passwordRregex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,}$/;
    if (input.value.match(passwordRregex)) {
        input.classList.remove("is-invalid");
        input.classList.add("is-valid");
        return true;
    } else {
        input.classList.remove("is-valid");
        input.classList.add("is-invalid");
        return false;
    }
}

export function validateConfirmPassword(inputPassword, inputConfirmPassword) {
    if (inputConfirmPassword.value === inputPassword.value) {
        inputConfirmPassword.classList.remove("is-invalid");
        inputConfirmPassword.classList.add("is-valid");
        return true;
    } else {
        inputConfirmPassword.classList.remove("is-valid");
        inputConfirmPassword.classList.add("is-invalid");
        return false;
    }
}

export function validateRequired(input) {
    if (input.value.trim() === "") {
        input.classList.remove("is-valid");
        input.classList.add("is-invalid");
        return false;
    } else {
        input.classList.remove("is-invalid");
        input.classList.add("is-valid");
        return true;
    }
}

export function newUser(event) {
    event.preventDefault();
    
    const myHeaders = new Headers();
    myHeaders.append("Content-Type", "application/json");
    
    const raw = JSON.stringify({
        firstName: document.getElementById("PrenomInput").value,
        lastName: document.getElementById("NomInput").value,
        phone: document.getElementById("gsmInput")?.value || "",
        address: document.getElementById("AddressePostaleInput")?.value || "",
        email: document.getElementById("EmailInput").value,
        password: document.getElementById("PasswordInput").value
    });
    
    console.log("Données envoyées:", raw);
    
    const requestOptions = {
        method: "POST",
        headers: myHeaders,
        body: raw,
        redirect: "follow"
    };
    
    fetch(`${API_BASE}/registration`, requestOptions)
        .then(response => {
            if (response.ok) {
                return response.json();
            } else {
                return response.json().then(err => {
                    throw new Error(err.message || "Erreur lors de l'inscription");
                });
            }
        })
        .then(result => {
            const firstName = document.getElementById("PrenomInput")?.value || "";
            alert(`Bravo ${firstName}, vous êtes maintenant inscrit, vous pouvez vous connecter.`);
            document.location.href = "/connexion";
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert(error.message || "Une erreur est survenue lors de l'inscription");
        });
}