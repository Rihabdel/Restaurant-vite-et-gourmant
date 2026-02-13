
const inputNom= document.getElementById("NomInput");
const inputPrenom= document.getElementById("PrenomInput");
const inputEmail= document.getElementById("EmailInput");
const inputPassword= document.getElementById("PasswordInput");
const inputConfirmPassword= document.getElementById("ValidatePasswordInput");
const btnvalidation=document.getElementById('btnSubmitInscription');
const formInscription=document.getElementById('InscriptionForm');

const connexionBtn = document.getElementById("btnConnexion");
const formConnexion = document.getElementById("connexionForm")


inputNom.addEventListener('keyup',validateForm);
inputPrenom.addEventListener('keyup',validateForm);
inputEmail.addEventListener('keyup',validateForm);
inputPassword.addEventListener('keyup',validateForm);
inputConfirmPassword.addEventListener('keyup',validateForm);
btnvalidation.addEventListener("click", (event) => InscrireUtilisateur(event));





//Function permettant de valider tout le formulaire 

function validateForm(){
   const isNomValid = validateRequired(inputNom);
    const isPrenomValid = validateRequired(inputPrenom);

    const isEmailValid = validateEmail(inputEmail);
    const isPasswordValid = validatePassword(inputPassword);
    const isConfirmPasswordValid = validateConfirmPassword(inputPassword, inputConfirmPassword);
    if (isNomValid && isPrenomValid && isEmailValid && isPasswordValid && isConfirmPasswordValid) {
            btnvalidation.disabled = false;
    } else {
        btnvalidation.disabled = true;
    }
}
function validateEmail(input){
    const emailRregex=/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if(input.value.match(emailRregex)){
        input.classList.remove("is-invalid");
        input.classList.add("is-valid");
        return true;
    }else{
        input.classList.remove("is-valid");
        input.classList.add("is-invalid");
        return false;   
    }
}
function validatePassword(input){
    const passwordRregex=/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,}$/;
    if(input.value.match(passwordRregex)){
        input.classList.remove("is-invalid");
        input.classList.add("is-valid");
        return true;
    }else{
        input.classList.remove("is-valid");
        input.classList.add("is-invalid");
        return false;   
    }
}
function validateConfirmPassword(inputPassword, inputConfirmPassword){
    if(inputConfirmPassword.value === inputPassword.value){
        inputConfirmPassword.classList.remove("is-invalid");
        inputConfirmPassword.classList.add("is-valid");
        return true;
    }else{
        inputConfirmPassword.classList.remove("is-valid");
        inputConfirmPassword.classList.add("is-invalid");
        return false;   
    }
}

function validateRequired(input){
    if(input.value.trim() === ""){
        input.classList.remove("is-valid"); 
        input.classList.add("is-invalid");
        return false;
    }else{
        input.classList.remove("is-invalid");
        input.classList.add("is-valid");
        return true;
    }
} 
function InscrireUtilisateur(event){
    event.preventDefault(); // Empêche le comportement par défaut du bouton (soumission du formulaire)
// Crée un nouvel objet FormData à partir du formulaire contenu dans la variable "formInscription"
let formData = new FormData(formInscription);
let myHeaders = new Headers();

myHeaders.append("Content-Type", "application/json");

const raw = JSON.stringify({
    "firstName": formData.get('firstName'),
    "lastName": formData.get('lastName'),
    "email": formData.get('email'),
    "password": formData.get('password')});

const requestOptions = {
    method: "POST",
    headers: myHeaders,
    body: raw,
    redirect: "follow"
};

fetch("https://127.0.0.1:8000/api/registration", requestOptions)
    .then(response =>{
        if(response.ok){
            return response.json();
        }
        else{
            alert("Erreur lors de l'inscription");
        }
    })
    .then(result => {
        alert("Bravo "+formData.get("firstName")+", vous êtes maintenant inscrit, vous pouvez vous connecter.");
        document.location.href="/connexion";
    })
    .catch(error => console.log('error', error));
}