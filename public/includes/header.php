<?php
// Используем надежный способ запуска сессии
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
                </ul>

                <ul class="menu personal-area">
                    <?php if (isset($_SESSION['user_id']) || isset($_SESSION['usuario_id'])): ?>
                        <li class="nav-user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_email'] ?? $_SESSION['usuario_email']); ?></li>
                        <li><a href="php_scripts/logout.php" class="logout-button">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Área personal</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        <main>