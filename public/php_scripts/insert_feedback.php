<?php
require_once '../server/database.php'; // Ajustá la ruta según tu estructura real

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        echo '<p style="color: red;">Por favor, complete todos los campos correctamente.</p>';
        exit;
    }

    $sql = "INSERT INTO feedback (name, email, message) VALUES (?, ?, ?)";
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
    echo '<p style="color: orange;">Este script solo acepta solicitudes POST.</p>';
}
?>