<?php
// Archivo: public/php_scripts/handle_contact.php
// Propósito: Procesa el formulario de contacto y guarda el mensaje en la base de datos (versión simplificada).

require_once '../server/database.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

    $sql = "INSERT INTO `Feedback` (`name`, `email`, `message`, `fecha_envio`) VALUES (?, ?, ?, NOW())";
    
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