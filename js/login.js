function mostrarFormulario(tipo) {
    const clienteForm = document.getElementById('formClientelogin');
    const empleadoForm = document.getElementById('formEmpleadologin');

    // Ocultar ambos
    clienteForm.style.display = 'none';
    empleadoForm.style.display = 'none';

    // Mostrar el seleccionado
    if (tipo === 'cliente') {
        clienteForm.style.display = 'block';
    } else if (tipo === 'empleado') {
        empleadoForm.style.display = 'block';
    }
}

