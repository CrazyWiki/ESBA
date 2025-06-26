<?php
session_start();

// --- Bloque de Depuración ---
$log_file_empleado = __DIR__ . '/login_empleado_debug.log';
$timestamp_empleado = date("Y-m-d H:i:s");
file_put_contents($log_file_empleado, "---- {$timestamp_empleado} NEW LOGIN ATTEMPT ----\n", FILE_APPEND);
file_put_contents($log_file_empleado, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
// --- Fin del Bloque de Depuración ---

require_once __DIR__ . '/../../server/database.php';

$login_error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_input = $_POST['email'] ?? '';
    $password_input = $_POST['password'] ?? '';

    file_put_contents($log_file_empleado, "Attempting login for email: '{$email_input}'\n", FILE_APPEND);

    if ($conn->connect_error) {
        $login_error_message = 'Error de conexión a la base de datos: (' . $conn->connect_errno . ') ' . $conn->connect_error;
        file_put_contents($log_file_empleado, "DB Connection Error: {$login_error_message}\n", FILE_APPEND);
    } else {
        if (!empty($email_input) && !empty($password_input)) {
            $sql = "SELECT idAdministradores, password, role FROM Administradores WHERE email = ?";
            file_put_contents($log_file_empleado, "SQL: {$sql}\n", FILE_APPEND);
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                $login_error_message = "Error al preparar la consulta SQL: " . $conn->error;
                file_put_contents($log_file_empleado, "SQL Prepare Error: {$conn->error}\n", FILE_APPEND);
            } else {
                $stmt->bind_param("s", $email_input);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    file_put_contents($log_file_empleado, "User found: " . print_r($user, true) . "\n", FILE_APPEND);
                    
                    $user_id = $user['idAdministradores'];
                    $password_from_db = $user['password']; 
                    $user_role = strtolower(trim($user['role'] ?? ''));

                    // Usar password_verify() ya que las contraseñas están hasheadas
                    $password_match = password_verify($password_input, $password_from_db);
                    file_put_contents($log_file_empleado, "password_verify comparison result: " . ($password_match ? 'Match' : 'No Match') . "\n", FILE_APPEND);

                    if (!$password_match) {
                        file_put_contents($log_file_empleado, "Input Pwd for verify: '{$password_input}', DB Hash: '{$password_from_db}'\n", FILE_APPEND);
                    }

                    if ($password_match) {
                        file_put_contents($log_file_empleado, "Password match! Role from DB: '{$user['role']}' (raw), '{$user_role}' (processed)\n", FILE_APPEND);
                        
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_email'] = $email_input;
                        $_SESSION['user_role'] = $user_role;
                        
                        $redirect_page = '';

                        switch ($user_role) {
                            case 'administrador':
                                $_SESSION['admin_id'] = $user_id; 
                                $redirect_page = '../area_personal_admin.php';
                                file_put_contents($log_file_empleado, "Role identified as 'administrador'. Redirecting to admin panel. SESSION admin_id set: {$user_id}\n", FILE_APPEND);
                                break;
                            case 'conductor':
                                $redirect_page = '../area_personal_conductor.php';
                                file_put_contents($log_file_empleado, "Role identified as 'conductor'. Redirecting to conductor panel.\n", FILE_APPEND);
                                break;
                            case 'gerente de ventas':
                                $redirect_page = '../area_personal_gerente.php';
                                file_put_contents($log_file_empleado, "Role identified as 'gerente de ventas'. Redirecting to gerente panel.\n", FILE_APPEND);
                                break;
                            default:
                                $login_error_message = "Su rol ('{$user_role}') no tiene una página de acceso definida.";
                                file_put_contents($log_file_empleado, "Role '{$user_role}' has no specific page. Setting error.\n", FILE_APPEND);
                                break;
                        }

                        if (!empty($redirect_page)) {
                            // Cerrar la conexión antes de redirigir
                            if ($conn && $conn->thread_id) { $conn->close(); } 
                            header("Location: " . $redirect_page);
                            exit();
                        }
                    } else {
                        $login_error_message = "Email o contraseña incorrectos.";
                        file_put_contents($log_file_empleado, "Password mismatch for user '{$email_input}'.\n", FILE_APPEND);
                    }
                } else {
                    $login_error_message = "Email o contraseña incorrectos.";
                    file_put_contents($log_file_empleado, "User with email '{$email_input}' not found.\n", FILE_APPEND);
                }
                $stmt->close();
            }
        } else {
            $login_error_message = "Por favor, ingrese su email y contraseña.";
            file_put_contents($log_file_empleado, "Email or password empty.\n", FILE_APPEND);
        }
        
        if ($conn && !isset($conn->connect_error) && $conn->thread_id) { 
            $conn->close();
        }
    }
} else {
    file_put_contents($log_file_empleado, "Invalid request method: {$_SERVER['REQUEST_METHOD']}\n", FILE_APPEND);
}

// Si llegamos aquí, el inicio de sesión falló o el rol no está definido
$_SESSION['login_error'] = $login_error_message;
file_put_contents($log_file_empleado, "Login failed or role undefined. Error: '{$login_error_message}'. Redirecting to login page.\n", FILE_APPEND);
if ($conn && !isset($conn->connect_error) && $conn->thread_id) {
    $conn->close();
}
header("Location: ../login.php"); 
exit();
?>