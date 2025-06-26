document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('contactForm');
  const submitBtn = document.getElementById('submitBtn');
  const responseDiv = document.getElementById('formResponse');

  // Предотвращаем стандартную отправку по Enter
  form.addEventListener('submit', function (event) {
    event.preventDefault();
  });

  submitBtn.addEventListener('click', function (event) {
    event.preventDefault();

    const formData = new FormData(form);

    fetch('php_scripts/insert_feedback.php', {
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