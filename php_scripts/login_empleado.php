<?php
// Archivo: public/php_scripts/login_empleado.php
// Propósito: Procesa el intento de inicio de sesión para empleados (Administradores, Conductores, Gerentes de Ventas).

session_start();

require_once '../server/database.php'; 

$login_error_message = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_input = $_POST['email'] ?? '';
    $password_input = $_POST['password'] ?? '';

    if ($conn->connect_error) {
        $login_error_message = 'Error de conexión a la base de datos.';
    } else {
        if (empty($email_input) || empty($password_input)) {
            $login_error_message = "Por favor, ingrese su email y contraseña.";
        } else {
            $sql = "SELECT idAdministradores, password, role FROM Administradores WHERE email = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                $login_error_message = "Error interno al procesar el login.";
            } else {
                $stmt->bind_param("s", $email_input);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    $user_id = $user['idAdministradores'];
                    $password_from_db = $user['password']; 
                    $user_role = strtolower(trim($user['role'] ?? '')); 

                    if (password_verify($password_input, $password_from_db)) {
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_email'] = $email_input;
                        $_SESSION['user_role'] = $user_role;
                        
                        $redirect_page = '';
                        switch ($user_role) {
                            case 'administrador':
                                $_SESSION['admin_id'] = $user_id; 
                                $redirect_page = '../area_personal_admin.php';
                                break;
                            case 'conductor':
                                $redirect_page = '../area_personal_conductor.php';
                                break;
                            case 'gerente de ventas':
                                $redirect_page = '../area_personal_gerente.php';
                                break;
                            default:
                                // Rol no reconocido o sin página de acceso definida.
                                $login_error_message = "Su rol ('{$user_role}') no tiene una página de acceso definida.";
                                $redirect_page = '../login.php'; // Redirige a login con mensaje de error.
                                break;
                        }

                        // Realiza la redirección si la página está definida.
                        if (!empty($redirect_page)) {
                            $conn->close(); // Cierra la conexión a la base de datos.
                            header("Location: " . $redirect_page);
                            exit();
                        }

                    } else {
                        // Contraseña incorrecta.
                        $login_error_message = "Email o contraseña incorrectos.";
                    }
                } else {
                    // Usuario no encontrado.
                    $login_error_message = "Email o contraseña incorrectos.";
                }
                $stmt->close(); // Cierra el statement.
            }
        }
    }
    $conn->close(); // Cierra la conexión a la base de datos (si aún está abierta).
} else {
    // Si la solicitud no es POST.
    $login_error_message = "Método de solicitud no válido.";
}

// Si se llega a este punto, el inicio de sesión falló o hubo un problema de rol.
$_SESSION['login_error'] = $login_error_message;
header("Location: ../login.php"); 
exit();
?>