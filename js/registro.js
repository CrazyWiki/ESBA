document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('#formCliente form');
    if (!form) return;

    const responseDiv = document.createElement('div');
    form.appendChild(responseDiv);

    const nombreInput = form.querySelector('input[name="nombre_cliente"]');
    const apellidoInput = form.querySelector('input[name="apellido_cliente"]');
    const docInput = form.querySelector('input[name="numero_documento"]');
    const telefonoInput = form.querySelector('input[name="telefono"]');
    const emailInput = form.querySelector('input[name="email"]');
    const passwordInput = form.querySelector('input[name="password"]');

    const allInputs = [nombreInput, apellidoInput, docInput, telefonoInput, emailInput, passwordInput];

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isOnlyLetters(text) {
        return /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(text);
    }

    function showResponse(message, isSuccess) {
        responseDiv.innerHTML = message;
        responseDiv.className = isSuccess ? 'response-message success' : 'response-message error';
    }

    // 📞 Mascara para teléfono: +54 __ ____-____
    telefonoInput.addEventListener('input', function () {
        let digits = telefonoInput.value.replace(/\D/g, '');
        if (digits.startsWith('54')) digits = digits.slice(2);
        let formatted = '+54 ';
        if (digits.length > 0) formatted += digits.slice(0, 2);
        if (digits.length > 2) formatted += ' ' + digits.slice(2, 6);
        if (digits.length > 6) formatted += '-' + digits.slice(6, 10);
        telefonoInput.value = formatted;
    });

    // 🪪 Mascara para documento: __ ____.____ __
    docInput.addEventListener('input', function () {
        let digits = docInput.value.replace(/\D/g, '');
        let formatted = '';
        if (digits.length > 0) formatted += digits.slice(0, 2);
        if (digits.length > 2) formatted += '.' + digits.slice(2, 5);
        if (digits.length > 5) formatted += '.' + digits.slice(5, 10);
        else if (digits.length > 5) formatted += digits.slice(5);
        docInput.value = formatted;
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        allInputs.forEach(input => input.classList.remove('is-invalid'));
        showResponse('', true);

        const nombre = nombreInput.value.trim();
        const apellido = apellidoInput.value.trim();
        const rawDocumento = docInput.value.replace(/\D/g, '');
        const telefono = telefonoInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value;

        let errors = [];

        if (nombre === '' || !isOnlyLetters(nombre)) {
            errors.push('El nombre es obligatorio y debe contener solo letras.');
            nombreInput.classList.add('is-invalid');
        }

        if (apellido === '' || !isOnlyLetters(apellido)) {
            errors.push('El apellido es obligatorio y debe contener solo letras.');
            apellidoInput.classList.add('is-invalid');
        }

        if (rawDocumento.length < 6) {
            errors.push('El número de documento debe tener al menos 6 dígitos.');
            docInput.classList.add('is-invalid');
        }

        if (telefono === '') {
            errors.push('El número de teléfono es obligatorio.');
            telefonoInput.classList.add('is-invalid');
        } else if (!/^\+54 \d{2} \d{4}-\d{4}$/.test(telefono)) {
            errors.push('El número de teléfono no tiene el formato correcto (+54 __ ____-____).');
            telefonoInput.classList.add('is-invalid');
        }

        if (email === '') {
            errors.push('El correo electrónico es obligatorio.');
            emailInput.classList.add('is-invalid');
        } else if (!isValidEmail(email)) {
            errors.push('El formato del correo electrónico no es válido.');
            emailInput.classList.add('is-invalid');
        }

        if (password.length < 8) {
            errors.push('La contraseña debe tener al menos 8 caracteres.');
            passwordInput.classList.add('is-invalid');
        }

        if (errors.length > 0) {
            const errorHtml = '<ul><li>' + errors.join('</li><li>') + '</li></ul>';
            showResponse(errorHtml, false);
            return;
        }

        showResponse('Procesando registro...', true);
        const formData = new FormData(form);
        formData.set('numero_documento', rawDocumento);

        fetch('php_scripts/registrar_cliente.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) throw new Error('Error de red o del servidor.');
                return response.json();
            })
            .then(data => {
                showResponse(data.message, data.success);
                if (data.success) {
                    form.reset();
                    setTimeout(() => window.location.href = 'login.php', 1500);
                }
            })
            .catch(() => {
                showResponse('Ocurrió un error inesperado al procesar su solicitud.', false);
            });
    });
});
