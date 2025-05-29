<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LP Logística</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/contact.css" />
    <link rel="stylesheet" href="css/login.css" />
    <link rel="icon" href="images/logistics_fast_truck.ico" />
</head>



<body>
    <div class="wrapper">
        <header>
            <nav class="container">
                <div class="logo">
                    <img src="images/logohead.png" alt="Logo de la empresa" />
                </div>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="servicios.php">Servicios</a></li>
                    <li><a href="area.php">Área personal</a></li>
                    <li><a href="contacto.php">Contacto</a></li>
                </ul>
            </nav>
        </header>
        <main>
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
        </main>
        <footer>
            <div class="content-wrapper">
                <p>&copy; 2025 LP Logística</p>
            </div>
        </footer>
    </div>
</body>

</html>