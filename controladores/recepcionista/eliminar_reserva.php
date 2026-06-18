<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo recepcionista puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
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
    $id_recepcionista = $_SESSION['id_usuario'];
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_reserva)) {
        $errores[] = "El ID de la reserva es requerido";
    } elseif (!is_numeric($id_reserva) || $id_reserva <= 0) {
        $errores[] = "El ID de la reserva no es válido";
    }


    // Verificar que la reserva exista y el recepcionista le haya agendado 
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id, estado, cliente_id FROM reservas WHERE id = :id_reserva AND empleado_id = :id_recepcionista LIMIT 1");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->bindParam(":id_recepcionista", $id_recepcionista, PDO::PARAM_INT);
            $sql->execute();
            $reserva = $sql->fetch(PDO::FETCH_OBJ);

            if (!$reserva) {
                $errores[] = "La reserva no existe o no fue encontrada en el sistema.";
            } elseif ($reserva->estado === 'finalizada') {
                $errores[] = "No es posible eliminar una reserva que ya ha sido finalizada.";
            } elseif ($reserva->estado === 'cancelada') {
                $errores[] = "La reserva ya fue cancelada anteriormente.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la reserva: " . $e->getMessage());
            $errores[] = "Error al verificar la reserva en la base de datos.";
        }
    }

    // Verificar que no tenga pagos registrados
    if (empty($errores)) {
        try {
            $sql_pago = $conexion->prepare("SELECT COUNT(*) FROM pagos WHERE reserva_id = :id_reserva AND estado = 'pagado'");
            $sql_pago->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql_pago->execute();
            if ($sql_pago->fetchColumn() > 0) {
                $errores[] = "No se puede eliminar la reserva #{$id_reserva} porque tiene pagos registrados. Anule el pago primero.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar pagos de la reserva: " . $e->getMessage());
            $errores[] = "Error al verificar pagos de la reserva en la base de datos.";
        }
    }


    // Si no hay errores, eliminar la reserva
    if (empty($errores)) {
        try {

            $conexion->beginTransaction();

            // 1. Liberar la habitación en caso de que estuviera ocupada
            $sql_hab = $conexion->prepare("UPDATE habitaciones SET estado = 'disponible' WHERE id IN (SELECT habitacion_id FROM reserva_habitacion WHERE reserva_id = :id_reserva)");
            $sql_hab->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql_hab->execute();

            // 2. Eliminar reserva de la tabla reserva_habitacion
            $sql_pivote = $conexion->prepare("DELETE FROM reserva_habitacion WHERE reserva_id=:id_reserva");
            $sql_pivote->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql_pivote->execute();

            // 3. Eliminar reserva de la tabla reservas
            $sql_del = $conexion->prepare("DELETE FROM reservas WHERE id=:id_reserva and empleado_id=:id_recepcionista");
            $sql_del->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql_del->bindParam(":id_recepcionista", $id_recepcionista, PDO::PARAM_INT);
            $sql_del->execute();

            // 4. Notificación al cliente como confirmación de la cancelación
            $msg = "❌ Lamentamos informarle que su reserva (#{$id_reserva}) ha sido eliminada del sistema. Contáctenos si requiere asistencia.";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            $sql_notif->bindParam(':uid', $reserva->cliente_id, PDO::PARAM_INT);
            $sql_notif->bindParam(':msg', $msg, PDO::PARAM_STR);
            $sql_notif->execute();

            $conexion->commit();

            $_SESSION['exito'] = "La reserva #{$id_reserva} ha sido eliminada del sistema correctamente.";
            header("Location: ../../recepcionista/gestion_reservas.php");
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al eliminar la reserva: " . $e->getMessage());
            $_SESSION['errores'] = ["Ocurrió un error al eliminar la reserva. Por favor, inténtelo nuevamente o contacte al administrador."];
            header("Location: ../../recepcionista/gestion_reservas.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../recepcionista/gestion_reservas.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../recepcionista/gestion_reservas.php");
    exit;
}
