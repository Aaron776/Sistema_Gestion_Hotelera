<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo administrador puede editar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password_actual']) && isset($_POST['password_nueva'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $password_actual = trim($_POST['password_actual']);
    $password_nueva = trim($_POST['password_nueva']);
    $id_cliente = $_SESSION['id_usuario']; // id de cliente logueado
    $errores = [];

    // Validacion y sanitizacion

    if (empty($password_actual)) {
        $errores[] = "La contraseña actual es requerida.";
    }

    if (empty($password_nueva)) {
        $errores[] = "La contraseña nueva es requerida";
    } elseif (strlen($password_nueva) < 5) {
        $errores[] = "La contraseña nueva debe tener al menos 5 caracteres";
    } elseif ($password_actual == $password_nueva) {
        $errores[] = "La nueva contraseña no puede ser igual a la contraseña actual.";
    }

    // Verificar que el cliente exista en la base de datos y este en estado activo
    if(empty($errores)){
        try {
            $sql = $conexion->prepare("SELECT id,password FROM usuarios WHERE id=:id_cliente AND estado='activo' AND rol='cliente' LIMIT 1");
            $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
            $sql->execute();
            $cliente = $sql->fetch(PDO::FETCH_OBJ);
            if (!$cliente) {
                $errores[] = "No se pudo verificar su cuenta. Por favor, inicie sesión nuevamente.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el cliente: " . $e->getMessage());
            $errores[] = "Ocurrió un error interno. Por favor, inténtelo nuevamente.";
        }
    }


    // Si no hay errores, editar la contraseña del  cliente
    if (empty($errores)) {
        try {
            if (password_verify($password_actual, $cliente->password)) {
                $password_nueva_hasheada = password_hash($password_nueva, PASSWORD_DEFAULT);
                $sql = $conexion->prepare("UPDATE usuarios SET password=:password_nueva_hasheada WHERE id=:id_cliente AND rol='cliente'");
                $sql->bindParam(":password_nueva_hasheada", $password_nueva_hasheada, PDO::PARAM_STR);
                $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
                $sql->execute();

                $_SESSION['exito'] = "¡Su contraseña ha sido actualizada exitosamente. Para mayor seguridad, evite compartirla con nadie.";
                header("Location: ../../cliente/cambiar_password.php");
                exit;
            } else {
                $_SESSION['errores'] = ["La contraseña actual ingresada es incorrecta. Verifique e inténtelo de nuevo."];
                header("Location: ../../cliente/cambiar_password.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error al cambiar la contraseña: " . $e->getMessage());
            $_SESSION['errores'] = ["Ocurrió un error al actualizar su contraseña. Por favor, inténtelo más tarde."];
            header("Location: ../../cliente/cambiar_password.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../cliente/cambiar_password.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Por favor, complete todos los campos del formulario."];
    header("Location: ../../cliente/cambiar_password.php");
    exit;
}
