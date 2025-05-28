document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('contactForm');
  const responseDiv = document.getElementById('formResponse');

  form.addEventListener('submit', function (event) {
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
        responseDiv.innerHTML = '<p style="color: red;">Hubo un error al enviar el mensaje.</p>';
        console.error('Error:', error);
      });
  });
});