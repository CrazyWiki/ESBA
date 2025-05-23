function mostrarFormulario(tipo) {
        document.getElementById('formClientelogin').style.display = (tipo === 'cliente') ? 'block' : 'none';
        document.getElementById('formEmpleadologin').style.display = (tipo === 'empleado') ? 'block' : 'none';
    }