<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_servicio'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_servicio = trim(Crypto::decrypt($_POST['id_servicio']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_servicio)) {
        $errores[] = "El ID del servicio es requerido";
    } elseif (!is_numeric($id_servicio) || $id_servicio <= 0) {
        $errores[] = "El ID del servicio no es válido";
    }

    // Verificar que el servicio exista
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id FROM servicios WHERE id=:id_servicio LIMIT 1");
            $sql->bindParam(":id_servicio", $id_servicio, PDO::PARAM_INT);
            $sql->execute();
            $servicio = $sql->fetch(PDO::FETCH_OBJ);

            if (!$servicio) {
                $errores[] = "El servicio que desea eliminar no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el servicio: " . $e->getMessage());
            $errores[] = "Error al verificar el servicio en la base de datos";
        }
    }

    // Si no hay errores, eliminar el servicio
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("DELETE FROM servicios WHERE id=:id_servicio");
            $sql->bindParam(":id_servicio", $id_servicio, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito'] = "¡Servicio eliminado correctamente!";
            header("Location: ../../admin/gestion_servicios.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al eliminar el servicio: " . $e->getMessage());
            $mensaje_errores = "No se puede eliminar el servicio porque está asociado a una o más reservas.";
            $_SESSION['errores'] = [$mensaje_errores];
            header("Location: ../../admin/gestion_servicios.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_servicios.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_servicios.php");
    exit;
}
