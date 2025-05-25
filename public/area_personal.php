<?php include 'includes/header.php'; ?>

<section class="fullscreen-section">
    <div class="auth-container">
    <!-- Formulario Login -->
    <div class="auth-box">
        <h2>Iniciar Sesión</h2>
        <form id="formClientelogin" method="POST" action="php_scripts/login_cliente.php">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>

    <!-- Formulario Registro -->
    <div class="auth-box">
        <h2>Registrarse</h2>
        <form id="formCliente" method="POST">
            <input type="hidden" name="tipo" value="cliente">
            <input type="text" name="nombre_cliente" placeholder="Nombre" required>
            <input type="text" name="apellido_cliente" placeholder="Apellido" required>
            <input type="text" name="numero_documento" placeholder="DNI" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="text" name="telefono" placeholder="Teléfono">
            <input type="text" name="direccion" placeholder="Dirección">
            <input type="text" name="codigo_postal" placeholder="Código Postal">
            <button type="submit">Registrar</button>
            <div id="registroResponse"></div>
        </form>
    </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="js/registro.js"></script>