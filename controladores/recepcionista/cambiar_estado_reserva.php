<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo recepcionistas pueden cambiar el estado de reservas)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['estado']) && isset($_POST['id_reserva'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }


    $estado = trim($_POST['estado']);
    $id_reserva    = Crypto::decrypt(trim($_POST['id_reserva']));
    $errores = [];

    // Validar ID de reserva
    if (empty($id_reserva)) {
        $errores[] = "El ID de la reserva es requerido";
    } elseif (!is_numeric($id_reserva) || $id_reserva <= 0) {
        $errores[] = "El ID de la reserva no es válido";
    }

    $estados_validos = ['confirmada', 'cancelada', 'finalizada'];
    if (empty($estado)) {
        $errores[] = "El estado de la reserva es requerido";
    } elseif (!in_array($estado, $estados_validos)) {
        $errores[] = "El estado de la reserva no es válido";
    }

    // Verifiacr que la resera exista y no este en estado finalizada
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id,estado,cliente_id FROM reservas WHERE id = :id_reserva");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->execute();
            $reserva = $sql->fetch(PDO::FETCH_OBJ);

            if (!$reserva) {
                $errores[] = "La reserva no existe o no fue encontrada en el sistema.";
            } elseif ($reserva->estado == 'finalizada') {
                $errores[] = "No es posible modificar una reserva que ya ha sido finalizada.";
            } elseif ($reserva->estado == 'cancelada') {
                $errores[] = "No es posible modificar una reserva que ha sido cancelada.";
            } elseif ($reserva->estado == 'pendiente' && $estado == 'finalizada') {
                $errores[] = "No es posible finalizar una reserva que no ha sido confirmada primero.";
            }
        } catch (Exception $e) {
            error_log("Error al verificar la reserva: " . $e->getMessage());
            $errores[] = "Ocurrió un error interno al verificar la reserva. Inténtelo nuevamente.";
        }
    }


    // Si no hay errores, cambiar el esatdo de la reserva
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $sql = $conexion->prepare("UPDATE reservas SET estado = :estado WHERE id = :id_reserva");
            $sql->bindParam(":estado", $estado, PDO::PARAM_STR);
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->execute();

             // Sincronizar el estado de la habitación
            // Si la reserva se cancela o finaliza, liberar la(s) habitación(es)
            if ($estado === 'cancelada' || $estado === 'finalizada') {
                $sql_hab = $conexion->prepare("UPDATE habitaciones SET estado = 'disponible' WHERE id IN (SELECT habitacion_id FROM reserva_habitacion WHERE reserva_id = :id_reserva)");
                $sql_hab->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
                $sql_hab->execute();
            }

            // Si la reserva pasa a estado confirmada, poner el estado de la habitación en ocupada
            if ($estado === 'confirmada') {
                $sql_hab = $conexion->prepare("UPDATE habitaciones SET estado = 'ocupada' WHERE id IN (SELECT habitacion_id FROM reserva_habitacion WHERE reserva_id = :id_reserva)");
                $sql_hab->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
                $sql_hab->execute();
            }

            // ✅ Notificaciones Mejoradas y Profesionales
            if ($estado === 'confirmada') {
                $msg_cliente = "🏨 ¡Buenas noticias! Su reserva (#{$id_reserva}) ha sido confirmada exitosamente. Lo esperamos pronto.";
                $msg_staff = "✅ La reserva #{$id_reserva} ha sido Confirmada por recepción.";
            } elseif ($estado === 'cancelada') {
                $msg_cliente = "❌ Lamentamos informarle que su reserva (#{$id_reserva}) ha sido cancelada. Si cree que se trata de un error, contáctenos.";
                $msg_staff = "❌ La reserva #{$id_reserva} ha sido Cancelada.";
            } else {
                $msg_cliente = "🛎️ Gracias por hospedarse con nosotros. Su reserva (#{$id_reserva}) ha finalizado. ¡Esperamos verle nuevamente!";
                $msg_staff = "🏁 La reserva #{$id_reserva} completó su ciclo y ha sido marcada como Finalizada.";
            }

            // Enviar notificación al cliente si tiene cliente asociado
            if ($reserva->cliente_id) {
                $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
                $sql_notif->bindParam(':uid', $reserva->cliente_id, PDO::PARAM_INT);
                $sql_notif->bindParam(':msg', $msg_cliente, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            // Enviar notificación al staff activo
            $sql_staff = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista') AND estado = 'activo'");
            $sql_staff->execute();
            $staff = $sql_staff->fetchAll(PDO::FETCH_OBJ);

            $sql_notif_staff = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            foreach ($staff as $miembro) {
                $sql_notif_staff->bindParam(':uid', $miembro->id, PDO::PARAM_INT);
                $sql_notif_staff->bindParam(':msg', $msg_staff, PDO::PARAM_STR);
                $sql_notif_staff->execute();
            }

            $conexion->commit();

            $_SESSION['exito'] = "El estado de la reserva #{$id_reserva} ha sido actualizado a '" . ucfirst($estado) . "' correctamente.";
            header("Location: ../../recepcionista/gestion_reservas.php");
            exit;

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al editar el estado de la reserva: " . $e->getMessage());
            $_SESSION['errores'] = ["Ocurrió un error al actualizar el estado de la reserva. Por favor, inténtelo nuevamente."];
            header("Location: ../../recepcionista/cambiar_estado_reserva.php?id_reserva=" . Crypto::encrypt($id_reserva));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../recepcionista/cambiar_estado_reserva.php?id_reserva=" . Crypto::encrypt($id_reserva));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../recepcionista/gestion_reservas.php");
    exit;
}
