<?php include 'includes/header.php'; ?>

<main class="login">
    <h2>Iniciar Sesión</h2>

    <div class="login-form-buttons">
        <button onclick="mostrarFormulario('cliente')">Login Cliente</button>
        <button onclick="mostrarFormulario('empleado')">Login Empleado</button>
    </div>

    <div id="formClientelogin" class="login-form" style="display: none;">
        <h3>Login Cliente</h3>
        <form id="formClienteLogin">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Iniciar Sesión</button>
        </form>
        <div id="responseCliente" class="response-message"></div>
    </div>

    <div id="formEmpleadologin" class="login-form" style="display: none;">
        <h3>Login Empleado</h3>
        <form id="formEmpleadoLogin">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Iniciar Sesión</button>
        </form>
        <div id="responseEmpleado" class="response-message"></div>
    </div>
</main>

<script>
    function mostrarFormulario(tipo) {
    document.getElementById('formClientelogin').style.display = (tipo === 'cliente') ? 'block' : 'none';
    document.getElementById('formEmpleadologin').style.display = (tipo === 'empleado') ? 'block' : 'none';
    }
</script>

<script src="js/login.js" defer></script>

<?php include 'includes/footer.php'; ?>
