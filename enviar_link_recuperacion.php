<?php
// Incluimos el archivo de configuración de la base de datos y otras funciones necesarias
// Asegúrate de que la ruta a tu archivo db_config.php sea correcta y que contenga la función getDbConnection().
// Ejemplo de contenido para db_config.php:
/*
<?php
function getDbConnection() {
    $servername = "localhost";
    $username = "tu_usuario_bd"; // Reemplaza con tu usuario de BD
    $password = "tu_contraseña_bd"; // Reemplaza con tu contraseña de BD
    $dbname = "tu_nombre_bd"; // Reemplaza con el nombre de tu base de datos

    // Crear conexión
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verificar conexión
    if ($conn->connect_error) {
        // En un entorno de producción, esto debería ir a un log, no mostrarse al usuario.
        die("Error de conexión a la base de datos: " . $conn->connect_error);
    }
    return $conn;
}
?>
*/
include '../includes/db_config.php';
include '../includes/functions.php'; // Se asume que tienes un archivo con funciones útiles, por ejemplo, para enviar correos electrónicos

// Iniciamos la sesión si no ha sido iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificamos si la solicitud es de tipo POST y si se ha enviado el email
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = $_POST['email'];

    // Conexión a la base de datos
    $conn = getDbConnection(); // Función para obtener la conexión a la BD, definida en db_config.php

    if ($conn) {
        // Verificamos si existe un usuario con ese email
        $stmt = $conn->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];

            // Generamos un token único para el restablecimiento de contraseña
            $token = bin2hex(random_bytes(32)); // Genera un token de 64 caracteres
            $expires = date("Y-m-d H:i:s", strtotime('+1 hour')); // El token será válido por 1 hora

            // Guardamos el token en la base de datos
            // Se asume que tienes una tabla 'password_resets'
            // con los campos 'user_id', 'token', 'expires_at'
            $stmt_insert = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iss", $user_id, $token, $expires);
            $stmt_insert->execute();

            if ($stmt_insert->affected_rows > 0) {
                // Formamos el enlace para restablecer la contraseña
                // Asegúrate de que 'http://localhost/' coincida con tu URL base
                $reset_link = "http://localhost/resetear_contrasena.php?token=" . $token;

                // Enviamos el correo electrónico al usuario
                $subject = "Restablecimiento de contraseña para PF Logística";
                $message = "Hola,\n\n"
                         . "Has solicitado un restablecimiento de contraseña para tu cuenta de PF Logística.\n"
                         . "Por favor, haz clic en el siguiente enlace para restablecer tu contraseña:\n"
                         . $reset_link . "\n\n"
                         . "Este enlace expirará en 1 hora.\n"
                         . "Si no solicitaste un restablecimiento de contraseña, por favor ignora este correo.\n\n"
                         . "Saludos cordiales,\n"
                         . "El equipo de PF Logística";

                // Ejemplo de función para enviar email. Deberás implementarla.
                // Posiblemente necesites usar una librería como PHPMailer para un envío de email robusto.
                // sendEmail($email, $subject, $message); // Reemplaza con tu función real de envío de email

                // En este ejemplo, para demostración, simplemente mostraremos un mensaje de éxito.
                // En una aplicación real, no deberías mostrar esto al usuario para no revelar
                // si el email existe o no.
                $_SESSION['login_error'] = "Si tu dirección de correo electrónico existe en nuestro sistema, te hemos enviado un enlace para restablecer tu contraseña.";
                header("Location: ../login.php"); // Redirigimos de vuelta a la página de login
                exit();

            } else {
                $_SESSION['login_error'] = "Ocurrió un error al crear el token de restablecimiento de contraseña. Por favor, inténtalo de nuevo.";
                header("Location: ../recuperar_contrasena.php");
                exit();
            }
            $stmt_insert->close();
        } else {
            // Si el email no se encuentra, informamos, pero sin revelar si el email existe
            $_SESSION['login_error'] = "Si tu dirección de correo electrónico existe en nuestro sistema, te hemos enviado un enlace para restablecer tu contraseña.";
            header("Location: ../login.php"); // Redirigimos de vuelta a la página de login
            exit();
        }
        $stmt->close();
        $conn->close();
    } else {
        $_SESSION['login_error'] = "Error de conexión a la base de datos. Por favor, inténtalo más tarde.";
        header("Location: ../recuperar_contrasena.php");
        exit();
    }
} else {
    // Si la solicitud no es POST o el email no está presente, redirigimos de vuelta a la página de recuperación
    header("Location: ../recuperar_contrasena.php");
    exit();
}
?>