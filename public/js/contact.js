document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('contactForm');
  const submitBtn = document.getElementById('submitBtn');
  const responseDiv = document.getElementById('formResponse');

  // Prevenir envío estándar
  form.addEventListener('submit', function (event) {
    event.preventDefault();
  });

  submitBtn.addEventListener('click', function (event) {
    event.preventDefault();

    const formData = new FormData(form);

    fetch('php_scripts/inser_feedback.php', {  // Ojo que el PHP se llama inser_feedback.php
      method: 'POST',
      body: formData
    })
    .then(response => response.text())
    .then(data => {
      responseDiv.innerHTML = data;
      // Solo resetear si no hubo error
      if (data.includes('exitosamente')) {
        form.reset();
      }
    })
    .catch(error => {
      responseDiv.innerHTML = 'Hubo un error al enviar el mensaje.';
      console.error('Error:', error);
    });
  });
});
