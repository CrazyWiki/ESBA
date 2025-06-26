<?php
session_start();
require_once '../server/database.php';

$login_error_message = 'Correo electrónico o contraseña incorrectos.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id_usuario, password_hash FROM Usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_email'] = $email;
                $_SESSION['role'] = 'cliente'; 

                $stmt->close();
                $conn->close();
                header("Location: ../area_personal_cliente.php"); 
                exit();
            }
        }
    } else {
        $login_error_message = 'Por favor, ingrese su email y contraseña.';
    }
    $stmt->close();
}

$_SESSION['login_error'] = $login_error_message;
$conn->close();
header("Location: ../login.php");
exit();