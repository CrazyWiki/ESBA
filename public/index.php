<?php include 'includes/header.php'; ?>

<section class="hero">
  <div class="content-wrapper"> 
    <h1>Soluciones Logísticas BTZ</h1>
    <p>Entregamos tus envíos de forma rápida, segura y eficiente.</p>
    <a href="#" class="cta-button">Contáctanos</a>
  </div> 
</section>

<section class="services">
  <div class="content-wrapper"> 
    <h2>Nuestros Servicios</h2>
    <div class="service-grid">
      <div class="service-item">
        <h3>Entrega1</h3>
        <p>info.</p>
      </div>
      <div class="service-item">
        <h3>Entrega2</h3>
        <p>info.</p>
      </div>
      <div class="service-item">
        <h3>Entrega3</h3>
        <p>info.</p>
      </div>
    </div>
  </div> 
</section>

<section class="contact">
  <div class="content-wrapper"> 
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
  </div> 
</section>
<script src="js/contact.js" defer></script>

<?php include 'includes/footer.php'; ?>