document.addEventListener('DOMContentLoaded', () => {
    const formCliente = document.getElementById('formClienteLogin');
    const formEmpleado = document.getElementById('formEmpleadoLogin');
    const responseCliente = document.getElementById('responseCliente');
    const responseEmpleado = document.getElementById('responseEmpleado');

    formCliente.addEventListener('submit', async (e) => {
        e.preventDefault();
        responseCliente.textContent = 'Cargando...';
        const formData = new FormData(formCliente);
        formData.append('tipo', 'cliente');

        try {
        const res = await fetch('php_scripts/login_usuario.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.text();
        if (data.includes('Login exitoso')) {
            responseCliente.style.color = 'green';
            responseCliente.textContent = data;
            setTimeout(() => window.location.href = 'area_personal.php', 1500);
        } else {
            responseCliente.style.color = 'red';
            responseCliente.textContent = data;
        }
        } catch {
        responseCliente.style.color = 'red';
        responseCliente.textContent = 'Error en la conexión.';
        }
    });

    formEmpleado.addEventListener('submit', async (e) => {
    e.preventDefault();
    responseEmpleado.textContent = 'Cargando...';
    const formData = new FormData(formEmpleado);
    formData.append('tipo', 'empleado');

    try {
        const res = await fetch('php_scripts/login_usuario.php', {
        method: 'POST',
        body: formData
        });
        const data = await res.text();
        if (data.includes('Login exitoso')) {
        responseEmpleado.style.color = 'green';
        responseEmpleado.textContent = data;
        setTimeout(() => window.location.href = 'area_personal.php', 1500);
        } else {
        responseEmpleado.style.color = 'red';
        responseEmpleado.textContent = data;
        }
    } catch {
        responseEmpleado.style.color = 'red';
        responseEmpleado.textContent = 'Error en la conexión.';
        }
    });
});
