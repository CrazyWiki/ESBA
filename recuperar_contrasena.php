<?php
// Incluye el encabezado de la página.
include 'includes/header.php';
?>

<main class="registro">
    <h2>Recuperación de Contraseña</h2>
    <p>Por favor, introduce tu dirección de correo electrónico para recibir un enlace para restablecer tu contraseña.</p>

    <form action="php_scripts/enviar_link_recuperacion.php" method="post">
        <input type="email" name="email" placeholder="Tu dirección de correo electrónico" required>
        <button type="submit">Enviar enlace de restablecimiento</button>
    </form>

    <div class="registro-link-container">
        <p>¿Recordaste tu contraseña? <a href="login.php">Iniciar sesión</a></p>
    </div>
</main>

<?php
// Incluye el pie de página.
include 'includes/footer.php';
?>