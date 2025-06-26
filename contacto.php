<?php 
// Archivo: public/contacto.php
// Propósito: Muestra la información de contacto y un formulario para enviar mensajes.

// Incluye el encabezado de la página.
include 'includes/header.php'; ?>

<section class="contact-page-section">
    <div class="container">
        <h2 style="text-align: center; font-size: 2.5em;">Ponte en Contacto con Nosotros</h2>
        <p style="text-align: center; max-width: 700px; margin: 0 auto 40px auto; color: var(--color-text-muted);">
            Estamos aquí para ayudarte. Visítanos, llámanos o envíanos un mensaje a través del formulario o WhatsApp.
        </p>

        <div class="map-container">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3283.699119934394!2d-58.3663386847698!3d-34.61176898045735!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95a2c7a5a48dd347%3A0x7c735591a27694b7!2sAv.%20Alicia%20Moreau%20de%20Justo%201150%2C%20C1107%20AAV%2C%20Buenos%20Aires!5e0!3m2!1ses-419!2sar!4v1686180000000"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>

        <div class="contact-layout">

            <div class="contact-details">
                <h3>Nuestra Oficina</h3>
                <p>
                    <strong>Dirección:</strong><br>
                    Av. Alicia Moreau de Justo 1150<br>
                    (C1107AAV) Puerto Madero, CABA<br>
                    Argentina
                </p>
                <p>
                    <strong>Teléfono de Oficina:</strong><br>
                    <a href="tel:+5491157439767">+54 11 5734-9767</a>
                </p>
                <p>
                    <strong>Email General:</strong><br>
                    <a href="mailto:info@lplogistica.com">info@lplogistica.com</a>
                </p>
                <hr style="margin: 30px 0;">
                <p>¿Prefieres un contacto más rápido?</p>
                <a href="https://wa.me/5491157349767" target="_blank" class="whatsapp-link">
    <i class="bi bi-whatsapp"></i> <span>Contactar por WhatsApp</span>
</a>
            </div>

            <section class="contact" id="contact">
                <div class="content-wrapper">
                    <h2>Contáctanos</h2>
                    <p style="max-width: 600px; margin: 0 auto 30px auto;">¿Listo para optimizar tu logística? Completa el formulario y uno de nuestros asesores se comunicará a la brevedad.</p>
                    <div class="contact-form">
                        <form id="contactForm">
                            <input type="text" placeholder="Nombre" name="name" required>
                            <input type="email" placeholder="Correo Electrónico" name="email" required>
                            <textarea placeholder="Mensaje (Describe brevemente tu necesidad)" name="message" rows="5" required></textarea>
                            <button type="button" id="submitBtn">Enviar Mensaje</button>
                        </form>
                        <div id="formResponse"></div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</section>

<script src="js/contact.js" defer></script>

<?php include 'includes/footer.php'; ?>