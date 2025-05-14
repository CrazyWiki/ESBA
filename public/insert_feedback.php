<?php
require_once '../server/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    $sql = "INSERT INTO `esbaproj`.`feedback` (`name`, `email`, `message`) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $name, $email, $message);
        if ($stmt->execute()) {
            echo '<p style="color: green;">¡Mensaje enviado exitosamente!</p>'; // Mensaje para JavaScript
        } else {
            echo '<p style="color: red;">Error al enviar el mensaje: ' . htmlspecialchars($stmt->error) . '</p>'; // Mensaje para JavaScript
        }
        $stmt->close();
    } else {
        echo '<p style="color: red;">Error al preparar la consulta: ' . htmlspecialchars($conn->error) . '</p>'; // Mensaje para JavaScript
    }
    $conn->close();
} else {
    echo '<p style="color: yellow;">Este script solo acepta solicitudes POST.</p>'; // Mensaje para JavaScript
}

?>
