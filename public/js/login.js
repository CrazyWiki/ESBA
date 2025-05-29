document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formClientelogin');
    const responseDiv = document.getElementById('loginResponse');

    form.addEventListener('submit', function (event) {
    event.preventDefault();

    const formData = new FormData(form);

    fetch('php_scripts/login_cliente.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes("Login exitoso")) {
        responseDiv.innerHTML = "✅ " + data;
        responseDiv.style.color = "green";
        setTimeout(() => {
        window.location.href = "area_personal.php";
        }, 1000);
    } else {
        responseDiv.innerHTML = "❌ " + data;
        responseDiv.style.color = "red";
        }
    })
    .catch(error => {
        responseDiv.innerHTML = "❌ Error al iniciar sesión.";
        console.error("Login Error:", error);
        });
    });
});
