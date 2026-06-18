<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['telefono']) && isset($_POST['direccion']) && isset($_POST['cedula'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre     = trim($_POST['nombre']);
    $email      = trim($_POST['email']);
    $id_cliente = $_SESSION['id_usuario'];
    $telefono   = trim($_POST['telefono']);
    $direccion  = trim($_POST['direccion']);
    $cedula     = trim($_POST['cedula']);
    $errores    = [];

    // Validaciones
    if (empty($nombre)) {
        $errores[] = "El nombre es requerido.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $nombre)) {
        $errores[] = "El nombre solo puede contener letras y espacios.";
    } elseif (strlen($nombre) > 100) {
        $errores[] = "El nombre no puede superar los 100 caracteres.";
    }

    if (empty($cedula)) {
        $errores[] = "La cédula es requerida.";
    } elseif (!preg_match("/^[0-9\- ]+$/", $cedula)) {
        $errores[] = "La cédula solo puede contener números y guiones.";
    } elseif (strlen($cedula) > 20) {
        $errores[] = "La cédula no puede superar los 20 caracteres.";
    }

    if (!empty($direccion) && strlen($direccion) > 255) {
        $errores[] = "La dirección no puede superar los 255 caracteres.";
    }

    if (!empty($telefono)) {
        if (!preg_match("/^[0-9\+ ]+$/", $telefono)) {
            $errores[] = "El teléfono solo puede contener números, el signo + o espacios.";
        } elseif (strlen($telefono) > 20) {
            $errores[] = "El teléfono no puede superar los 20 caracteres.";
        }
    }

    if (empty($email)) {
        $errores[] = "El correo electrónico es requerido.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo electrónico no es válido.";
    } elseif (strlen($email) > 100) {
        $errores[] = "El correo electrónico no puede superar los 100 caracteres.";
    }

    // Verificar que el cliente exista (solo si no hay errores previos)
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id FROM usuarios WHERE id=:id_cliente AND estado='activo' AND rol='cliente' LIMIT 1");
            $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
            $sql->execute();
            $cliente = $sql->fetch(PDO::FETCH_OBJ);
            if (!$cliente) {
                $errores[] = "No se pudo verificar su cuenta. Por favor, inicie sesión nuevamente.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el cliente: " . $e->getMessage());
            $errores[] = "Ocurrió un error interno al verificar su cuenta. Inténtelo más tarde.";
        }
    }

    // Verificar unicidad de email, cédula y teléfono
    if (empty($errores)) {
        try {
            $query = "SELECT id, email, telefono, cedula FROM usuarios WHERE rol='cliente' AND (email=:email OR cedula=:cedula";
            if (!empty($telefono)) {
                $query .= " OR telefono=:telefono";
            }
            $query .= ") AND id != :id_cliente_actual";

            $sql = $conexion->prepare($query);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            if (!empty($telefono)) {
                $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            }
            $sql->bindParam(":id_cliente_actual", $id_cliente, PDO::PARAM_INT);
            $sql->execute();
            $usuario_existe = $sql->fetch(PDO::FETCH_OBJ);

            if ($usuario_existe) {
                if ($usuario_existe->email === $email && $usuario_existe->id != $id_cliente) {
                    $errores[] = "El correo electrónico ingresado ya está registrado por otra cuenta.";
                }
                if ($usuario_existe->cedula === $cedula && $usuario_existe->id != $id_cliente) {
                    $errores[] = "La cédula ingresada ya está asociada a otra cuenta.";
                }
                if (!empty($telefono) && $usuario_existe->telefono === $telefono && $usuario_existe->id != $id_cliente) {
                    $errores[] = "El número de teléfono ingresado ya está registrado por otra cuenta.";
                }
            }
        } catch (PDOException $e) {
            error_log("Error al verificar usuario existente: " . $e->getMessage());
            $errores[] = "Ocurrió un error al verificar la disponibilidad de sus datos. Inténtelo nuevamente.";
        }
    }

    // Si no hay errores, actualizar al cliente
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("UPDATE usuarios SET nombre=:nombre, cedula=:cedula, email=:email, telefono=:telefono, direccion=:direccion WHERE id=:id_cliente AND rol='cliente'");
            $sql->bindParam(":nombre",     $nombre,     PDO::PARAM_STR);
            $sql->bindParam(":cedula",     $cedula,     PDO::PARAM_STR);
            $sql->bindParam(":direccion",  $direccion,  PDO::PARAM_STR);
            $sql->bindParam(":email",      $email,      PDO::PARAM_STR);
            $sql->bindParam(":telefono",   $telefono,   PDO::PARAM_STR);
            $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito'] = "¡Sus datos han sido actualizados exitosamente.";
            header("Location: ../../cliente/configuracion_cuenta.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al editar el cliente: " . $e->getMessage());
            $_SESSION['errores'] = ["No fue posible guardar los cambios. Por favor, inténtelo nuevamente."];
            header("Location: ../../cliente/configuracion_cuenta.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../cliente/configuracion_cuenta.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes."];
    header("Location: ../../cliente/configuracion_cuenta.php");
    exit;
}