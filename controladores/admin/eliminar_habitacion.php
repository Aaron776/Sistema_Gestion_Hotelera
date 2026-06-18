<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_habitacion'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_habitacion = trim(Crypto::decrypt($_POST['id_habitacion']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_habitacion)) {
        $errores[] = "El ID de la habitación es requerido";
    } elseif (!is_numeric($id_habitacion) || $id_habitacion <= 0) {
        $errores[] = "El ID de la habitación no es válido";
    }


    // Verificar que la habitacion exista y no este ocupada
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id, estado FROM habitaciones WHERE id=:id_habitacion LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $habitacion = $sql->fetch(PDO::FETCH_OBJ);

            if (!$habitacion) {
                $errores[] = "La habitación que desea eliminar no existe";
            } elseif ($habitacion->estado === 'ocupada') {
                $errores[] = "No se puede eliminar una habitación que está ocupada";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la habitación: " . $e->getMessage());
            $errores[] = "Error al verificar la habitación en la base de datos";
        }
    }

    // Verificar que la habitacion no se encuentre reservada y el estado de la reserva se encuentre confirmada
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT rh.id FROM reserva_habitacion rh INNER JOIN reservas r ON r.id = rh.reserva_id WHERE rh.habitacion_id=:id_habitacion AND r.estado='confirmada' LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $reserva = $sql->fetch(PDO::FETCH_OBJ);

            if ($reserva) {
                $errores[] = "No se puede eliminar la habitación porque tiene reservas confirmadas";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la habitación: " . $e->getMessage());
            $errores[] = "Error al verificar la habitación en la base de datos";
        }
    }

    // Verificar que la habitacion no tenga registros historicos en reserva_habitacion (FK)
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT COUNT(*) FROM reserva_habitacion WHERE habitacion_id = :id_habitacion");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            if ($sql->fetchColumn() > 0) {
                $errores[] = "No se puede eliminar la habitación porque tiene reservas asociadas en el sistema.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar reservas historicas: " . $e->getMessage());
            $errores[] = "Error al verificar las reservas de la habitación en la base de datos";
        }
    }

    // Si no hay errores, eliminar la habitacion
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("DELETE FROM habitaciones WHERE id=:id_habitacion");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito'] = "¡Habitación eliminada correctamente!";
            header("Location: ../../admin/gestion_habitaciones.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al eliminar la habitación: " . $e->getMessage());
            $_SESSION['errores'] = $errores;
            header("Location: ../../admin/gestion_habitaciones.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_habitaciones.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_habitaciones.php");
    exit;
}
