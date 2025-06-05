<?php
require_once '../../server/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Buscar administrador por email (también obtenemos el rol)
    $stmt = $conn->prepare("SELECT idAdministradores, password, role FROM Administradores WHERE email = ?");
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Verificar si existe el administrador
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id_admin, $password_hash, $role);
        $stmt->fetch();

        // Verificar contraseña
        if (password_verify($password, $password_hash)) {
            $_SESSION['admin_id'] = $id_admin;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_role'] = $role;

            // Redireccionar según el rol
            switch ($role) {
                case 'Administrador':
                    header("Location: ../area_personal_admin.php");
                    break;
                case 'Conductor':
                    header("Location: ../area_personal_conductor.php");
                    break;
                case 'Gerente de ventas':
                    header("Location: ../area_personal_gerente.php");
                    break;
                default:
                    echo "Rol no reconocido.";
                    exit();
            }
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
