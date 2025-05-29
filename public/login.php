<?php include 'includes/header.php'; ?>

<main class="login">
    <h2>Iniciar Sesión</h2>

    <div class="contact-form">
        <form id="formClientelogin">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Iniciar Sesión</button>
            <div id="loginResponse" class="mensaje"></div>
        </form>
    </div>
</main>

<script src="js/login.js" defer></script>

<?php include 'includes/footer.php'; ?>
