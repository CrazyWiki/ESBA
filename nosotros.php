<?php
// Archivo: public/about.php (o index.php si es tu página principal)
// Propósito: Muestra información sobre la empresa PF Logística.
include_once 'includes/header.php'; // Asegúrate de que header.php conecta Bootstrap y Font Awesome
?>

<section class="hero-about d-flex align-items-center text-center text-white py-5">
    <div class="container">
        <h1 class="display-2 fw-bold mb-3 animate__animated animate__fadeInDown">PF Logística</h1>
        <p class="lead mb-4 animate__animated animate__fadeInUp">Tu aliado confiable en envíos rápidos y seguros.</p>
        <a href="#about-content" class="btn btn-light btn-lg rounded-pill px-4 animate__animated animate__zoomIn">Conoce más</a>
    </div>
</section>

<section id="about-content" class="py-5 bg-light animate__animated animate__fadeIn">
    <div class="container">
        <h2 class="text-center mb-5 display-4 fw-bold text-dark">Nuestra Historia</h2>
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <p class="lead text-muted mb-4">En PF Logística entendemos que la rapidez, la confianza y la atención personalizada son valores esenciales en el mundo actual. Por eso, nacimos con el firme propósito de brindar una solución eficiente y accesible para el envío de paquetes y documentos, tanto para empresas como para clientes particulares.</p>
                <p class="text-secondary mb-4">Somos un equipo de emprendedores con sólida experiencia en logística y distribución, que desde el primer día asumimos el desafío de marcar la diferencia en el sector. Comenzamos con una flota de motocicletas y vehículos livianos, prestando servicios a negocios locales y usuarios que buscaban una opción segura, cercana y efectiva. Con el tiempo, gracias al compromiso con cada entrega, logramos posicionarnos como un aliado confiable en el día a día de nuestros clientes.</p>
                <p class="text-secondary mb-5">Nuestro crecimiento constante nos impulsa a revisar y perfeccionar nuestros procesos, siempre con la mirada puesta en ofrecer un servicio de excelencia. Creemos que cada envío es una oportunidad para cumplir, para sumar valor y para seguir construyendo relaciones duraderas.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-white animate__animated animate__fadeIn">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="p-4 border rounded shadow-sm h-100">
                    <i class="fas fa-bullseye fa-3x text-primary mb-3"></i>
                    <h3 class="fw-bold mb-3 text-primary">Nuestra Misión</h3>
                    <p class="text-muted">Brindar soluciones de mensajería, logística y distribución confiables y accesibles, asegurando entregas rápidas y seguras para nuestros clientes. Nos comprometemos a ofrecer un servicio eficiente con un equipo dedicado y una logística optimizada para satisfacer sus necesidades.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-4 border rounded shadow-sm h-100">
                    <i class="fas fa-eye fa-3x text-success mb-3"></i>
                    <h3 class="fw-bold mb-3 text-success">Nuestra Visión</h3>
                    <p class="text-muted">Ser la empresa líder en mensajería, logística y distribución en tiempo y forma, reconocida por su innovación, confiabilidad y su excelencia operativa.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-5">
            <p class="lead text-dark">En PF Logística, trabajamos todos los días con la misma convicción: superar expectativas y convertirnos en el socio estratégico que cada cliente necesita.</p>
            <a href="contacto.php">¡Contáctanos!</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<style>
/* Hero Section */
.hero-about {
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://via.placeholder.com/1920x800?text=Logistica+PF+Background') no-repeat center center; /* Reemplaza con tu imagen real */
    background-size: cover;
    min-height: 500px; /* Altura mínima para una buena visualización */
    position: relative;
    overflow: hidden;
    color: white;
}
.hero-about::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.2); /* Sutil superposición para el texto */
    z-index: 1;
}
.hero-about .container {
    position: relative;
    z-index: 2; /* Asegura que el contenido esté por encima del overlay */
}
.hero-about h1 {
    font-size: 4.5rem; /* Fuente más grande para el título */
    text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.7); /* Sombra para mejor lectura */
    animation-duration: 1.5s; /* Duración de la animación de entrada */
}
.hero-about p.lead {
    font-size: 1.75rem;
    animation-duration: 2s;
}

/* General Section Styling */
section {
    padding: 80px 0; /* Más padding para espacio */
}
section h2 {
    font-size: 2.8rem;
    margin-bottom: 50px;
    position: relative;
    padding-bottom: 15px;
}
section h2::after {
    content: '';
    position: absolute;
    left: 50%;
    bottom: 0;
    transform: translateX(-50%);
    height: 4px;
    width: 80px;
    background-color: var(--bs-primary); /* Color de línea debajo del título */
    border-radius: 2px;
}
p.lead {
    font-size: 1.1rem;
    line-height: 1.7;
}

/* Card Styling */
.card {
    border: none;
    transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    background: #f8f9fa; /* Fondo claro para las tarjetas */
}
.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
}
.card-title {
    font-size: 1.75rem;
    margin-bottom: 15px;
}
.card i {
    color: var(--bs-primary); /* Color de ícono principal */
    margin-bottom: 20px;
}

/* Custom button for contact */
.btn-outline-primary {
    border-width: 2px;
}
.btn-outline-primary:hover {
    background-color: var(--bs-primary);
    color: white;
}

/* Animate.css for subtle entrance effects (optional, requires CDN) */
/*
If you want these animations, add this to your header.php:
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
*/
</style>