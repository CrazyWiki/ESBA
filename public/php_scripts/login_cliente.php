<?php
require_once '../../server/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo "Por favor complete todos los campos.";
        exit;
    }

    $stmt = $conn->prepare("SELECT id_usuario, password_hash FROM Usuarios WHERE email = ?");
    if (!$stmt) {
        echo "Error en la consulta: " . htmlspecialchars($conn->error);
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id_usuario, $password_hash);
        $stmt->fetch();

        if (password_verify($password, $password_hash)) {
            $_SESSION['usuario_id'] = $id_usuario;
            $_SESSION['usuario_email'] = $email;

            header("Location: ../area_personal.php");
            exit();
        } else {
            echo "Contraseña incorrecta.";
        }
    } else {
        echo "Correo electrónico no registrado.";
    }

    $stmt->close();
} else {
    echo "Acceso inválido.";
}

$conn->close();
?>
