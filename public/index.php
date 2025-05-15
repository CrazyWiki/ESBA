<?php include 'includes/header.php'; ?>
<section class="hero">
  <h1>Soluciones Logísticas BTZ</h1>
  <p>Entregamos tus envíos de forma rápida, segura y eficiente.</p>
  <a href="#" class="cta-button">Contáctanos</a>
</section>

<section class="services">
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
</section>

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

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const responseDiv = document.getElementById('formResponse');

    // Предотвращаем стандартную отправку по Enter
    form.addEventListener('submit', function(event) {
      event.preventDefault();
    });

    submitBtn.addEventListener('click', function(event) {
      event.preventDefault();

      const formData = new FormData(form);

      fetch('insert_feedback.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(data => {
          responseDiv.innerHTML = data;
          form.reset();
        })
        .catch(error => {
          responseDiv.innerHTML = 'Hubo un error al enviar el mensaje.';
          console.error('Error:', error);
        });
    });
  });
</script>

<?php include 'includes/footer.php'; ?>