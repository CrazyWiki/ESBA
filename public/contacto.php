<?php include 'includes/header.php'; ?>

<section class="contact">
  <h2>Contáctanos</h2>
  <div class="contact-form">
    <form id="contactForm">
      <input type="text" placeholder="Nombre" name="name" required>
      <input type="email" placeholder="Correo Electrónico" name="email" required>
      <textarea placeholder="Mensaje" name="message" rows="5" required></textarea>
      <button type="button" id="submitBtn">Enviar Mensaje</button>
    </form>
    <div id="formResponse"></div>
  </div>
</section>


<?php include 'includes/footer.php'; ?>
