// Archivo: public/js/contact.js
// Propósito: Gestiona la lógica del formulario de contacto en la página de contacto.

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('contactForm');
    if (!form) return;

    const submitBtn = document.getElementById('submitBtn');
    const responseDiv = document.getElementById('formResponse');

    const nameInput = form.querySelector('input[name="name"]');
    const emailInput = form.querySelector('input[name="email"]');
    const messageInput = form.querySelector('textarea[name="message"]');

    // Función de validación de formato de email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Función para mostrar mensajes de respuesta al usuario
    // messageHtml: Contenido HTML del mensaje a mostrar
    // isSuccess: true para éxito (verde), false para error (rojo)
    function showResponse(messageHtml, isSuccess) {
        responseDiv.innerHTML = messageHtml;
        responseDiv.className = isSuccess ? 'alert alert-success' : 'alert alert-danger';
    }

    // Listener para el clic del botón de enviar
    submitBtn.addEventListener('click', function (event) {
        event.preventDefault(); // Previene la recarga de la página al enviar el formulario

        // Limpia mensajes de error previos y estilos de validación
        nameInput.classList.remove('is-invalid');
        emailInput.classList.remove('is-invalid');
        messageInput.classList.remove('is-invalid');
        responseDiv.innerHTML = '';
        responseDiv.className = '';

        // Obtiene y limpia los valores de los campos
        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const message = messageInput.value.trim();

        let errors = []; // Array para almacenar errores de validación del cliente

        // --- Validación del lado del cliente ---
        if (name === '') {
            errors.push('El campo Nombre es obligatorio.');
            nameInput.classList.add('is-invalid');
        } else if (!/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]+$/.test(name)) {
            errors.push('El campo Nombre solo debe contener letras y espacios.');
            nameInput.classList.add('is-invalid');
        }

        if (email === '') {
            errors.push('El campo Correo Electrónico es obligatorio.');
            emailInput.classList.add('is-invalid');
        } else if (!isValidEmail(email)) {
            errors.push('Por favor, ingrese un correo electrónico válido.');
            emailInput.classList.add('is-invalid');
        }

        if (message === '') {
            errors.push('El campo Mensaje es obligatorio.');
            messageInput.classList.add('is-invalid');
        }

        // Si hay errores de validación en el cliente, mostrarlos y detener el proceso
        if (errors.length > 0) {
            const errorHtml = '<ul><li>' + errors.join('</li><li>') + '</li></ul>';
            showResponse(errorHtml, false);
            return;
        }

        // Muestra mensaje de "enviando"
        showResponse('Enviando mensaje...', true);

        // Prepara los datos del formulario para enviar
        const formData = new FormData(form);

        // Realiza la solicitud Fetch (AJAX) al script PHP del servidor
        fetch('php_scripts/insert_feedback.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(text); });
                }
                return response.text();
            })
            .then(data => {
                showResponse(data, true);
                form.reset();
            })
            .catch(error => {
                showResponse('Hubo un error al enviar el mensaje. Intente de nuevo. Detalles: ' + error.message, true); 
            });
    });
});