<?php
require_once '../../server/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar entrada
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($message)) {
        echo '<p style="color: red;">Todos los campos son obligatorios.</p>';
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<p style="color: red;">Correo electrónico inválido.</p>';
        exit;
    }

    $sql = "INSERT INTO `esbaproj`.`feedback` (`name`, `email`, `message`) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $name, $email, $message);
        if ($stmt->execute()) {
            echo '<p style="color: green;">¡Mensaje enviado exitosamente!</p>';
        } else {
            echo '<p style="color: red;">Error al enviar el mensaje: ' . htmlspecialchars($stmt->error) . '</p>';
        }
        $stmt->close();
    } else {
        echo '<p style="color: red;">Error al preparar la consulta: ' . htmlspecialchars($conn->error) . '</p>';
    }
    $conn->close();
} else {
    echo '<p style="color: yellow;">Este script solo acepta solicitudes POST.</p>';
}
?>
