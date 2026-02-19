import {getToken, showAndHideElementsForRoles } from '../script.js';
import { getUserInfo, updateUserInfo } from '../api.js';

const API_BASE = 'https://127.0.0.1:8000/api';

export default async function initProfil() {
    console.log("Initialisation page profil");
    await loadUserProfile();
    attachFormListeners();
    displayUserInfo();
    showAndHideElementsForRoles();
}
//---------profil----------------

async function loadUserProfile() {
    const user = await getUserInfo();
    if (!user) {
        console.warn("Aucun utilisateur connecté");
        return;
    }
    // Remplir les champs
    document.getElementById('NomInput').value = user.lastName || '';
    document.getElementById('PrenomInput').value = user.firstName || '';
    document.getElementById('gsmInput').value = user.phone || '';
    document.getElementById('AdressePostaleInput').value = user.address || '';

    document.getElementById('EmailInput').value = user.email || '';

    const welcome = document.querySelector('.profile-section h2');
    if (welcome && user.firstName) {
        welcome.textContent = `Bienvenue sur votre profil, ${user.firstName} !`;
    }
        showAndHideElementsForRoles();
}


function attachFormListeners() {
    const form = document.getElementById('profileForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const data = {
                lastName: formData.get('Nom'),  
                firstName: formData.get('Prenom'),
                phone: formData.get('NumeroTelephone'),
                address: formData.get('AdressePostale'),
                email: formData.get('Email'),
            };
            updateUserInfo(data);
    displayUserInfo();

            alert('Profil mis à jour avec succès');
        });
    }
}
    
async function displayUserInfo() {
    const user = await getUserInfo();
    const profileInfo = document.getElementById('profileInfo');
    if (profileInfo && user) {
    document.getElementById('profileName').textContent = user.lastName || '';
    document.getElementById('profileFirstName').textContent = user.firstName || '';
    document.getElementById('profilePhone').textContent = user.phone || '';
    document.getElementById('profileAddress').textContent = user.address || '';

    document.getElementById('EmailInput').value = user.email || '';
        profileInfo.innerHTML = `
            <h2 class="text-center mb-4">Informations Personnelles</h2>
            <p class="mb-2"><strong>Nom :</strong> ${user.lastName || ''}</p>
            <p class="mb-2"><strong>Prénom :</strong> ${user.firstName || ''}</p>   
            <p class="mb-2"><strong>Email :</strong> ${user.email || ''}</p>
            <p class="mb-2"><strong>Numéro de téléphone :</strong> ${user.phone || ''}</p>
            <p class="mb-2"><strong>Adresse postale :</strong> ${user.address || ''}</p>
        `;
    }
    const welcome = document.querySelector('.profile-section h2');
    if (welcome && user.firstName) {
        welcome.textContent = `Bienvenue sur votre profil, ${user.firstName} !`;
    }
    loadUserProfile();
    showAndHideElementsForRoles();
}

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
            globalThis.location.href = '/';
        } catch (error) {
            console.error(error);
            alert('Erreur lors de la suppression');
        }
    });


