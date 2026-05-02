import {getToken, showAndHideElementsForRoles,getUserInfo,updateUserInfo,signout , isConnected } from '../script.js';

export default async function initProfil() {
    console.log("Initialisation page profil");
    try {
        if (!isConnected()) {
            alert("Vous devez être connecté pour accéder à cette page.");
            window.location.href = "/connexion";
            return;
        }
        const user = await getUserInfo();
        displayUserData(user);
        showAndHideElementsForRoles();
        initform();
        console.log("Page profil initialisée avec succès");
    } catch (error) {
        console.error("Erreur lors de l'initialisation du profil :", error);
    }
}

async function displayUserData(user) {
    const profileInfo = document.getElementById('profileInfo');
    if (!profileInfo) return;
    profileInfo.innerHTML = `  

    <h1 class="mb-4 text-center" >Bienvenue sur votre profil, ${user.firstName || ''} ${user.lastName || ''}</h1>

    <p class="text-muted"><strong>Nom :</strong> ${user.lastName || ''}</p>
    <p class="text-muted"><strong>Prénom :</strong> ${user.firstName || ''}</p>
    <p class="text-muted"><strong>Numéro de téléphone :</strong> ${user.phone || ''}</p>
    <p class="text-muted"><strong>Adresse postale :</strong> ${user.address || ''}</p>
    <p class="text-muted"><strong>Email :</strong> ${user.email || ''}</p>
    `;
    showAndHideElementsForRoles();
    initButtons();
}

function initButtons() {

    //ouvrir un collapse pour la modification des informations de l'utilisateur 
    const editProfileBtn = document.getElementById('editProfileBtn');
    const editProfileForm = document.getElementById('editProfileForm');

    if (editProfileBtn ) {
        editProfileBtn.addEventListener('click', () => {
            const isExpanded = editProfileBtn.getAttribute('aria-expanded') === 'true';
            editProfileBtn.setAttribute('aria-expanded', !isExpanded);
            if (editProfileForm) {
                editProfileForm.classList.toggle('show');
                LoadProfil();
            }
        });
    }
    // Ouvrir le modal pour la modification du mot de passe
    const editPasswordBtn = document.getElementById('editPasswordBtn');
    if (editPasswordBtn) {
        editPasswordBtn.addEventListener('click', () => {
            const modalEl = document.getElementById('changePasswordModal');
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        }
        );
    }

    // Bouton de déconnexion
    const signoutBtn = document.getElementById('signoutBtn');
    if (signoutBtn) {
        signoutBtn.addEventListener('click', signout);
    }
    // Bouton de suppression de compte
    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', deleteAccount);
    }
}

function initform() {
    function initform() {

    // --- Mise à jour du profil ---
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(profileForm);
            const updatedData = {
                lastName: formData.get('Nom'),
                firstName: formData.get('Prenom'),
                phone: parseInt(formData.get('NumeroTelephone').replace(/\D/g, '')) || null,
                address: formData.get('AdressePostale')
            };

            try {
                await updateUserInfo(updatedData); // backend 204 compatible
                const user = await getUserInfo(); // récupère les infos mises à jour
                displayUserData(user);
                alert("Informations mises à jour avec succès !");
            } catch (error) {
                console.error("Erreur lors de la mise à jour des informations :", error);
                alert("Une erreur est survenue lors de la mise à jour des informations.");
            }
        });
    }

    // --- Mise à jour du mot de passe ---
    const updatePasswordButton = document.getElementById('updatePasswordButton');
    if (updatePasswordButton) {
        updatePasswordButton.addEventListener('click', async (event) => {
            event.preventDefault();

            const form = document.getElementById('PasswordUpdateForm');
            const formData = new FormData(form);

            const currentPassword = formData.get('currentPassword');
            const newPassword = formData.get('newPassword');
            const confirmNewPassword = formData.get('confirmNewPassword');

            if (!currentPassword || !newPassword || !confirmNewPassword) {
                alert("Veuillez remplir tous les champs du formulaire.");
                return;
            }

            if (newPassword !== confirmNewPassword) {
                alert("Les nouveaux mots de passe ne correspondent pas.");
                return;
            }

            const updatedData = { currentPassword, newPassword, confirmNewPassword };

            try {
                await updateUserInfo(updatedData); 
                alert("Mot de passe mis à jour avec succès !");
                
                const modalEl = document.getElementById('changePasswordModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance.hide();
                
                const user = await getUserInfo();
                displayUserData(user);

            } catch (error) {
                console.error("Erreur lors de la mise à jour du mot de passe :", error);
                alert("Une erreur est survenue lors de la mise à jour du mot de passe.");
            }
        });
    }
}
}

async function deleteAccount() {
    if (!confirm("Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.")) {
        return;
    }
    const myHeaders = new Headers();
    myHeaders.append("X-AUTH-TOKEN", getToken());
    const requestOptions = {
        method: "DELETE",
        headers: myHeaders,
    };
    const response = await fetch(`${API_BASE}/user`, requestOptions);
    if (!response.ok) throw new Error(`Erreur ${response.status}`);
    alert("Votre compte a été supprimé. Vous allez être redirigé vers la page d'accueil.");
    signout();
}
async function LoadProfil() {
    try {
        const user = await getUserInfo(); 
        console.log("Données de l'utilisateur récupérées pour modification :", user);

        if (!user) {
            alert("Impossible de récupérer les informations de l'utilisateur.");
            return;
        }

        const form = document.getElementById('profileForm');
        if (!form) {
            alert("Formulaire de modification non trouvé.");
            return;
        }
        // Pré-remplir le formulaire parse les données de l'utilisateur
        form.elements['Nom'].value = user.lastName || '';
        form.elements['Prenom'].value = user.firstName || '';
        form.elements['NumeroTelephone'].value = user.phone || '';
        form.elements['AdressePostale'].value = user.address || '';
    }
        catch (error) {
        console.error("Erreur lors de la modification du profil :", error);
        alert("Une erreur est survenue lors de la modification du profil.");
    }
}
