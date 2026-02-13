
const mailInput = document.getElementById("EmailInput");
const passwordInput = document.getElementById("PasswordInput");
const btnConnexion = document.getElementById("BtnLogin");
btnConnexion.addEventListener("click", function(event){
    event.preventDefault();
    checkCredentials();
});
function checkCredentials(){
    let dataForm = new FormData(document.getElementById("ConnexionForm"));
    
    let myHeaders = new Headers();
    myHeaders.append("Content-Type", "application/json");

    let raw = JSON.stringify({
        "username": dataForm.get("email"),
        "password": dataForm.get("password")
    });

    let requestOptions = {
        method: 'POST',
        headers: myHeaders,
        body: raw,
        redirect: 'follow'
    };

    fetch("https://127.0.0.1:8000/api/login", requestOptions)
    .then(response => {
        if(response.ok){
            return response.json();
        }
        else{
            mailInput.classList.add("is-invalid");
            passwordInput.classList.add("is-invalid");
        }
    })
    .then(result => {
        const token = result.apiToken;
        setToken(token);
        //placer ce token en cookie
        setCookie(tokenCookieName, token, 7);

        setCookie(roleCookieName, result.roles[0], 7);
        globalThis.location.replace("/");
    })
    .catch(error => console.log('error', error));
}