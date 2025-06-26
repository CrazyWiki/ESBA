<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LP Logística</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css" />
    
    <link rel="icon" href="images/logistics_fast_truck.ico" />
</head>

<body>
    <div class="wrapper">
        <header>
            <nav class="navbar">
                <div class="logo">
                    <a href="index.php"><img src="images/logohead.png" alt="Logo de la empresa" /></a>
                </div>
                
                <ul class="menu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="calculadora.php">Calculadora de servicios</a></li>
                    <li><a href="contacto.php">Contacto</a></li>
                    <li><a href="nosotros.php">Nosotros</a></li>
                </ul>

                    <ul class="menu personal-area">
                    <?php 
                    if (isset($_SESSION['user_id'])): 
                       
                        $personal_area_link = 'login.php'; 
                        if (isset($_SESSION['user_role'])) {
                            switch ($_SESSION['user_role']) {
                                case 'administrador': 
                                    $personal_area_link = 'area_personal_admin.php'; 
                                    break;
                                case 'conductor': 
                                    $personal_area_link = 'area_personal_conductor.php'; 
                                    break;
                                case 'cliente': 
                                    $personal_area_link = 'area_personal_cliente.php'; 
                                    break;
                                case 'gerente de ventas': 
                                    $personal_area_link = 'area_personal_gerente.php'; 
                                    break;
                                
                            }
                        }
                    ?>
                        <li class="nav-user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_email'] ?? 'Usuario'); ?></li>
                        <li><a href="<?php echo htmlspecialchars($personal_area_link); ?>">Área personal</a></li> 
                        <li><a href="php_scripts/logout.php" class="logout-button">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Área personal</a></li>
                    <?php endif; ?>
            </nav>
        </header>
        <main>