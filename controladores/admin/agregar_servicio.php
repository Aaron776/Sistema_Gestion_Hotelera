<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['precio'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $precio = trim($_POST['precio']);
    $errores = [];

    // Validacion y sanitizacion
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


    // Verifiacr si ya existe un servicio con el mismo nombre(no importa si esta en mayusculas o minusculas)
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id FROM servicios WHERE LOWER(nombre)=LOWER(:nombre)");
            $sql->bindParam(":nombre",$nombre,PDO::PARAM_STR);
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

    // Si no hay errores, registrar al usuario
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("INSERT INTO servicios (nombre,precio) VALUES (:nombre,:precio)");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":precio", $precio, PDO::PARAM_STR);
            $sql->execute();

            $_SESSION['exito'] = "Servicio registrado correctamente";
            header("Location: ../../admin/gestion_servicios.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al registrar el servicio: " . $e->getMessage());
            $_SESSION['errores'] = ["Error al guardar el servicio. Intente nuevamente."];
            header("Location: ../../admin/agregar_servicio.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/agregar_servicio.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_servicios.php");
    exit;
}
