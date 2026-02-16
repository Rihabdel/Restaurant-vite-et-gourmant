
// Constantes
// ----------------------------
const API_BASE = 'https://127.0.0.1:8000/api'; 
const tokenCookieName = "accesstoken";
export const roleCookieName = 'role';
const signoutBtn = document.getElementById("SignoutBtn");

export function setToken(token) {
    setCookie(tokenCookieName, token, 7); // Le token expire dans 7 jours
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
    for (let i=0;i < ca.length;i++) {
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
    globalThis.location.reload();
}

// ----------Affichage ou non des éléments en fonction du role de l'utilisateur----------
export function showAndHideElementsForRoles(){
    const userConnected = isConnected();
    const role = getRole();

    const allElementsToEdit = document.querySelectorAll('[data-show]');
    
        allElementsToEdit.forEach(element =>{
        switch(element.dataset.show){
            case 'disconnected': 
                if(userConnected){
                    element.classList.add("d-none");
                }
                break;
            case 'connected': 
                if(!userConnected){
                    element.classList.add("d-none");
                }
                break;
            case 'admin': 
                if(!userConnected || role != "admin"){
                    element.classList.add("d-none");
                }
                break;
            case 'client': 
                if(!userConnected || role != "user"){
                    element.classList.add("d-none");
                }
                break;
        }
    })
}
// ----------Affichage ou non des éléments en fonction du role de l'utilisateur----------

export default async function initProfil() {
    console.log("Initialisation page profil");
    await loadUserProfile(); // réutiliser la fonction existante ou en créer une
    attachFormListeners();
}
function getUserInfo() {
    const token = getToken();
    if (!token) return null;
    return fetch(`${API_BASE}/user`, {
        headers: { 'X-Auth-Token': token },
        method: 'GET'

    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur chargement profil');
        return response.json();
    }
    ).catch(error => {
        console.error(error);
        return null;
    });
}
async function loadUserProfile() {
    const user = await getUserInfo();
    if (!user) {
        console.warn("Aucun utilisateur connecté");
        return;
    }
    // Remplir les champs
    document.getElementById('NomInput').value = user.firstName|| '';
    document.getElementById('PrenomInput').value = user.lastName || '';
    document.getElementById('gsmInput').value = user.phone || '';
    document.getElementById('AdressePostaleInput').value = user.address || '';
    document.getElementById('CodePostaleInput').value = user.postalCode || '';
    document.getElementById('EmailInput').value = user.email || '';
    document.getElementById('AllergeneInput').value = user.allergene || ''; // à adapter
    // Mettre à jour le titre de bienvenue
    const welcome = document.querySelector('.profile-section h2');
    if (welcome && user.firstName) {
        welcome.textContent = `Bienvenue sur votre profil, ${user.firstName} !`;
    }
}

function attachFormListeners() {
    const form = document.getElementById('profileForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        // Récupérer les données du formulaire
        const formData = new FormData(form);
        const data = {
            nom: formData.get('firstName'),
            prenom: formData.get('lastName'),
            numeroTelephone: formData.get('phone'),
            adressePostale: formData.get('adress'),
            email: formData.get('Email'),
            allergene: formData.get('Allergene')
        };
        try {
            const response = await fetch(`${API_BASE}/user`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-AUTH-TOKEN': getToken()
                },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error('Erreur mise à jour');
            alert('Profil mis à jour avec succès');
            loadUserProfile(); // Recharger les données du profil pour afficher les changements

        } catch (error) {
            console.error(error);
            alert('Erreur lors de la mise à jour');
        }
    });
    // Changement de mot de passe
    const passwordUpdateForm = document.getElementById('PasswordUpdateForm');
    passwordUpdateForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(passwordUpdateForm);
        const data = {
            currentPassword: formData.get('currentPassword'),
            password: formData.get('newPassword'),
            confirmPassword: formData.get('confirmNewPassword')
        };
        try {
            const response = await fetch(`${API_BASE}/user`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-AUTH-TOKEN': getToken()
                },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error('Erreur changement mot de passe');
            alert('Mot de passe changé avec succès');
            passwordForm.reset();
        } catch (error) {
            console.error(error);
            alert('Erreur lors du changement de mot de passe');
        }
    });
    // Suppression de compte
    document.querySelector('#deleteAccountModal .btn-danger').addEventListener('click', async () => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer votre compte ?')) return;
        try {
            const response = await fetch(`${API_BASE}/user`, {
                method: 'DELETE',
                headers: { 'X-AUTH-TOKEN': getToken() }
            });
            if (!response.ok) throw new Error('Erreur suppression');
            alert('Compte supprimé');
            // Déconnexion
            eraseCookie(tokenCookieName);
            eraseCookie(roleCookieName);
            window.location.href = '/';
        } catch (error) {
            console.error(error);
            alert('Erreur lors de la suppression');
        }
    });
}
