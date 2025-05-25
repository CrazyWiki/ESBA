<?php include 'includes/header.php'; ?>

<main class="container content-wrapper">
    <h1>Contacto</h1>

    <form action="/php_scripts/procesar_contacto.php" method="POST" class="contact-form">
        <label for="nombre">Nombre completo:</label>
        <input type="text" id="nombre" name="nombre" required />

        <label for="email">Correo electrónico:</label>
        <input type="email" id="email" name="email" required />

        <label for="mensaje">Mensaje:</label>
        <textarea id="mensaje" name="mensaje" rows="5" required></textarea>

        <button type="submit">Enviar</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>
