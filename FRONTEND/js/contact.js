import { API_BASE } from "./api.js";
export default async function initContactPage() {
    console.log("Initialisation de la page contact");
    const contactForm = document.getElementById("contactForm");

    if (!contactForm) {
        console.error("Formulaire contact introuvable");
        return;
    }
    contactForm.addEventListener("submit", newContactMsg);
}
async function newContactMsg(event) {
    event.preventDefault();
    const form= document.getElementById("contactForm");
    const formData = new FormData(form);
    const contactData = {
       
        email: formData.get("email"),
        title: formData.get("subject"),
        message: formData.get("message")
    };
    try {
        await addConctactMsg(contactData);
        alert("Message envoyé avec succès !");
        form.reset();
    }
    catch (error) {
        console.error("Erreur lors de l'envoi du message : ", error);
        alert("Une erreur est survenue. Veuillez réessayer.");
    }
}


async function addConctactMsg(contactData) {
    const response = await fetch(`${API_BASE}/contact/add`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(contactData)
    })

    if (!response.ok) {
        throw new Error('Erreur lors de l\'envoi du message de contact');
    }
    return await response.json();
}