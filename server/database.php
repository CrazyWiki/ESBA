<?php
/**
 * db_config.php
 *
 * Este archivo contiene la función para establecer una conexión a la base de datos.
 * Es incluido por otros scripts que necesitan interactuar con la base de datos.
 */

// Establece la zona horaria por defecto para todas las operaciones de fecha/hora.
date_default_timezone_set('America/Argentina/Buenos_Aires');

/**
 * Establece y devuelve una nueva conexión a la base de datos MySQL.
 *
 * @return mysqli Un objeto de conexión a la base de datos.
 * @throws Exception Si la conexión a la base de datos falla.
 */
function getDbConnection() {
    // Define las credenciales de tu base de datos
    $servername = "127.0.0.1";
    $username = "root";
    $password = "";
    $dbname = "esbaproj";

    // Crea una nueva conexión a la base de datos usando MySQLi
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verifica si la conexión fue exitosa
    if ($conn->connect_error) {
        // Si hay un error de conexión, detiene la ejecución del script y muestra un mensaje.
        // En un entorno de producción, es mejor registrar el error y mostrar un mensaje genérico al usuario.
        die("Error de conexión a la base de datos: " . $conn->connect_error);
    }

    // Establece el conjunto de caracteres a UTF-8 para evitar problemas con caracteres especiales
    // Se utiliza "utf8mb4" para un soporte más amplio de caracteres, incluyendo emojis.
    $conn->set_charset("utf8mb4");

    // Devuelve el objeto de conexión
    return $conn;
}

// Nota: No cierres la etiqueta PHP aquí si no hay más contenido HTML o texto plano debajo.
// Esto ayuda a prevenir problemas con espacios en blanco al inicio de los archivos.
