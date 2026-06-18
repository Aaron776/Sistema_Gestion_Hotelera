<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo cliente puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_reserva'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_reserva = trim(Crypto::decrypt($_POST['id_reserva']));
    $id_cliente = $_SESSION['id_usuario'];
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_reserva)) {
        $errores[] = "El ID de la reserva es requerido";
    } elseif (!is_numeric($id_reserva) || $id_reserva <= 0) {
        $errores[] = "El ID de la reserva no es válido";
    }


    // Verificar que la reserva exista y le pertenezca al cliente (sin filtrar por estado para dar mensajes específicos)
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id, estado FROM reservas WHERE id = :id_reserva AND cliente_id = :id_cliente LIMIT 1");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
            $sql->execute();
            $reserva = $sql->fetch(PDO::FETCH_OBJ);

            if (!$reserva) {
                $errores[] = "La reserva no existe o no te pertenece.";
            } elseif ($reserva->estado !== 'pendiente') {
                $errores[] = "Solo puedes eliminar reservas en estado Pendiente. Esta reserva está en estado: " . ucfirst($reserva->estado) . ".";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la reserva: " . $e->getMessage());
            $errores[] = "Error al verificar la reserva en la base de datos.";
        }
    }


    // Si no hay errores, eliminar la reserva
    if (empty($errores)) {
        try {

            $conexion->beginTransaction();

            // Eliminar primero el detalle de habitación (tabla pivote)
            $sql = $conexion->prepare("DELETE FROM reserva_habitacion WHERE reserva_id=:id_reserva");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->execute();

            // Eliminar la reserva principal
            $sql = $conexion->prepare("DELETE FROM reservas WHERE id=:id_reserva AND cliente_id=:id_cliente");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
            $sql->execute();

            // Notificación al cliente dentro de la transacción
            $msg = "Su reserva #{$id_reserva} ha sido cancelada y eliminada exitosamente del sistema.";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            $sql_notif->bindParam(':uid', $id_cliente, PDO::PARAM_INT);
            $sql_notif->bindParam(':msg', $msg, PDO::PARAM_STR);
            $sql_notif->execute();

            $conexion->commit();

            $_SESSION['exito'] = "Su reserva #{$id_reserva} ha sido cancelada y eliminada correctamente.";
            header("Location: ../../cliente/gestion_reservas.php");
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al eliminar la reserva: " . $e->getMessage());
            $_SESSION['errores'] = ["Ocurrió un error al cancelar la reserva. Por favor, inténtelo nuevamente o contacte al hotel."];
            header("Location: ../../cliente/gestion_reservas.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../cliente/gestion_reservas.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../cliente/gestion_reservas.php");
    exit;
}
