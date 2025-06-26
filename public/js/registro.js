document.addEventListener('DOMContentLoaded', function () {
    console.log('El archivo registro.js se está ejecutando.');

    const form = document.querySelector('#formCliente form');

    if (!form) {
        console.error('Error Crítico: No se encontró el formulario con el selector "#formCliente form".');
        return;
    }

    const responseDiv = document.createElement('div');
    responseDiv.style.marginTop = '15px';
    form.appendChild(responseDiv);

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        responseDiv.innerHTML = 'Procesando...'; // Mensaje de espera

        const formData = new FormData(form);

        // Usar ruta absoluta al script de PHP
        fetch('/ESBA/php_scripts/registrar_cliente.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                // Si la respuesta del servidor es un error (ej. 404 o 500)
                throw new Error('Error de red o del servidor: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            responseDiv.innerHTML = data.message;
            responseDiv.style.color = data.success ? 'green' : 'red';

            if (data.success) {
                console.log('Registro exitoso. Redirigiendo...');
                form.reset();

                // Usar ruta absoluta para la redirección
                setTimeout(function() {
                    window.location.href = '/ESBA/public/login.php';
                }, 1500); // Pausa de 1.5 segundos
            }
        })
        .catch(error => {
            console.error('Error en el proceso fetch:', error);
            responseDiv.innerHTML = 'Ocurrió un error inesperado. Revisa la consola para más detalles.';
            responseDiv.style.color = 'red';
        });
    });
});