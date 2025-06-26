<?php
session_start(); 
if (isset($_SESSION['admin_id'])) {
    header("Location: ../area_personal_admin.php"); 
    exit();
}

require_once '../server/database.php';

$login_error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['admin_email'] ?? '';
    $password_input = $_POST['admin_password'] ?? '';

    if (!empty($email) && !empty($password_input)) {
       
        $stmt = $conn->prepare("SELECT idAdministradores, password, role FROM Administradores WHERE email = ?");
        
        if (!$stmt) {
            
            $login_error_message = "Error del servidor (preparando la solicitud). Inténtalo de nuevo más tarde.";
          
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                
                if ($password_input === $user['password']) { 
                    $_SESSION['admin_id'] = $user['idAdministradores'];
                    header("Location: ../area_personal_admin.php"); 
                    exit();
                } else {
                    $login_error_message = "Correo electrónico o contraseña de administrador incorrectos.";
                }
            } else {
                $login_error_message = "Correo electrónico o contraseña de administrador incorrectos.";
            }
            $stmt->close();
        }
    } else {
        $login_error_message = "Por favor ingrese su correo electrónico de administrador y contraseña.";
    }
    $conn->close();
} else {
    $login_error_message = "Método de solicitud no válido.";
}

$_SESSION['login_error'] = $login_error_message;
header("Location: ../login.php"); 
exit();
?>