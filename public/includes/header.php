<?php
// Iniciar la sesión al principio de todo para poder acceder a las variables de sesión
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LP Logística</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/contact.css" />
    <link rel="stylesheet" href="css/login.css" />
    <link rel="stylesheet" href="css/admin_panel.css" /> <link rel="icon" href="images/logistics_fast_truck.ico" />
</head>

<body>
    <div class="wrapper d-flex flex-column min-vh-100">
        <header>
            <nav class="navbar">
                <div class="logo">
                    <img src="images/logohead.png" alt="Logo de la empresa" />
                </div>
                <ul class="menu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="servicios.php">Servicios</a></li>
                    <li><a href="contacto.php">Contacto</a></li>
                </ul>

                <ul class="menu personal-area">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_email']); ?></li>
                        <li><a href="php_scripts/logout.php" class="logout-button">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Área personal</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        <main>