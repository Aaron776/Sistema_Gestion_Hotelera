<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede editar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['precio']) && isset($_POST['id_servicio'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $id_servicio = Crypto::decrypt(trim($_POST['id_servicio']));
    $precio = trim($_POST['precio']);
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_servicio)) {
        $errores[] = "El ID del servicio es requerido";
    } elseif (!is_numeric($id_servicio) || $id_servicio <= 0) {
        $errores[] = "El ID del servicio no es válido";
    }

    if (empty($nombre)) {
        $errores[] = "El nombre es requerido";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\&\-\+\,\.]+$/", $nombre)) {
        $errores[] = "El nombre contiene caracteres no permitidos";
    } elseif (strlen($nombre) > 100) {
        $errores[] = "El nombre es muy largo";
    }

    if ($precio === '' || $precio === null) {
        $errores[] = "El precio es requerido";
    } elseif (!is_numeric($precio) || $precio < 0) {
        $errores[] = "El precio debe ser un número válido mayor o igual a 0";
    }


    // Verificar que el servicio exista
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id FROM servicios WHERE id=:id_servicio LIMIT 1");
            $sql->bindParam(":id_servicio", $id_servicio, PDO::PARAM_INT);
            $sql->execute();
            $servicio = $sql->fetch(PDO::FETCH_OBJ);

            if (!$servicio) {
                $errores[] = "El servicio que desea editar no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el servicio: " . $e->getMessage());
            $errores[] = "Error al verificar el servicio en la base de datos";
        }
    }


    // Verificar si ya existe un servicio con el mismo nombre (case-insensitive) excluyendo el actual
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id FROM servicios WHERE LOWER(nombre)=LOWER(:nombre) AND id != :id_servicio");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":id_servicio", $id_servicio, PDO::PARAM_INT);
            $sql->execute();
            $servicio_existente = $sql->fetch();

            if ($servicio_existente) {
                $errores[] = "Ya existe un servicio con el nombre '" . $nombre . "'";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el servicio existente: " . $e->getMessage());
            $errores[] = "Error crítico al verificar el servicio existente en la base de datos";
        }
    }

    // Si no hay errores, editar el servicio
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("UPDATE servicios SET nombre=:nombre,precio=:precio WHERE id=:id_servicio");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":precio", $precio, PDO::PARAM_STR);
            $sql->bindParam(":id_servicio", $id_servicio, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito'] = "Servicio editado correctamente";
            header("Location: ../../admin/gestion_servicios.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al editar el servicio: " . $e->getMessage());
            $_SESSION['errores'] = ["Error al editar el servicio. Intente nuevamente."];
            header("Location: ../../admin/editar_servicio.php?id_servicio=" . Crypto::encrypt($id_servicio));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/editar_servicio.php?id_servicio=" . Crypto::encrypt($id_servicio));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_servicios.php");
    exit;
}
