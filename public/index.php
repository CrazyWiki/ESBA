<?php include 'includes/header.php'; ?>

<section class="hero">
  <div class="content-wrapper"> 
    <h1>Soluciones Logísticas PF</h1>
    <p>Transporte de carga pesada, paquetería urgente и logística de última milla. Su envío, nuestra prioridad.</p>
    <a href="#contact" class="cta-button">Solicitar Cotización</a>
  </div> 
</section>

<section class="services">
  <div class="content-wrapper"> 
    <h2>Nuestros Servicios</h2>
    
    <div id="servicesCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php
            // Создаем массив с услугами
            $lista_servicios = [
                [ "titulo" => "Carga Pesada y Voluminosa", "descripcion" => "Transporte seguro de mercadería de gran tamaño y peso.", "imagen" => "https://images.pexels.com/photos/1078884/pexels-photo-1078884.jpeg" ],
                [ "titulo" => "Mensajería Urgente", "descripcion" => "Entrega express para sus documentos y paquetes importantes.", "imagen" => "https://images.pexels.com/photos/4391470/pexels-photo-4391470.jpeg" ],
                [ "titulo" => "Logística de Última Milla", "descripcion" => "Optimizamos la entrega final desde el almacén hasta el consumidor.", "imagen" => "https://images.pexels.com/photos/4393433/pexels-photo-4393433.jpeg" ],
                [ "titulo" => "Entrega Express", "descripcion" => "Servicio de entrega rápida en el mismo día dentro del área metropolitana.", "imagen" => "https://images.pexels.com/photos/7245326/pexels-photo-7245326.jpeg" ],
                [ "titulo" => "Distribución Nacional", "descripcion" => "Nuestra red de distribución cubre todo el territorio nacional.", "imagen" => "https://images.pexels.com/photos/2199293/pexels-photo-2199293.jpeg" ],
                [ "titulo" => "Logística Empresarial (3PL)", "descripcion" => "Gestionamos su almacenamiento, inventario y distribución.", "imagen" => "https://images.pexels.com/photos/5902927/pexels-photo-5902927.jpeg" ]
            ];
            
            // Разбиваем массив услуг на группы по 3
            $servicios_chunks = array_chunk($lista_servicios, 3);
            $is_first = true;
            
            // Создаем по одному слайду на каждую группу из 3х услуг
            foreach ($servicios_chunks as $chunk):
            ?>
                <div class="carousel-item <?php if($is_first) { echo 'active'; $is_first = false; } ?>">
                    <div class="row">
                        <?php foreach ($chunk as $servicio): ?>
                            <div class="col-md-4 mb-3">
                                <div class="service-card">
                                    <img src="<?php echo $servicio['imagen']; ?>?auto=compress&cs=tinysrgb&w=600&h=360&dpr=1" alt="<?php echo htmlspecialchars($servicio['titulo']); ?>">
                                    <h3><?php echo htmlspecialchars($servicio['titulo']); ?></h3>
                                    <p><?php echo htmlspecialchars($servicio['descripcion']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#servicesCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#servicesCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

  </div> 
</section>

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

<script src="js/contact.js" defer></script>

<?php include 'includes/footer.php'; ?>