<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo recepcionista puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_servicio_reserva'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_servicio_reserva = trim(Crypto::decrypt($_POST['id_servicio_reserva']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_servicio_reserva)) {
        $errores[] = "El ID del servicio de la reserva es requerido";
    } elseif (!is_numeric($id_servicio_reserva) || $id_servicio_reserva <= 0) {
        $errores[] = "El ID del servicio de la reserva no es válido";
    }

    // Verificar que el servicio exista y traer datos completos
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT rs.id, rs.subtotal, rs.reserva_id, rs.servicio_id, rs.cantidad, r.estado AS reserva_estado FROM reserva_servicio rs INNER JOIN reservas r ON rs.reserva_id = r.id WHERE rs.id = :id_servicio_reserva LIMIT 1");
            $sql->bindParam(":id_servicio_reserva", $id_servicio_reserva, PDO::PARAM_INT);
            $sql->execute();
            $servicio_reserva = $sql->fetch(PDO::FETCH_OBJ);
            if (!$servicio_reserva) {
                $errores[] = "El servicio de la reserva no existe.";
            } elseif ($servicio_reserva->reserva_estado !== 'confirmada') {
                $errores[] = "No se puede eliminar servicios de una reserva que no está confirmada.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el servicio de la reserva: " . $e->getMessage());
            $errores[] = "Error al verificar el servicio de la reserva en la base de datos.";
        }
    }

    // Si no hay errores, eliminar el servicio de la reserva
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // 1. Eliminar servicio de la tabla reserva_servicio
            $sql_pivote = $conexion->prepare("DELETE FROM reserva_servicio WHERE id = :id_servicio_reserva");
            $sql_pivote->bindParam(":id_servicio_reserva", $id_servicio_reserva, PDO::PARAM_INT);
            $sql_pivote->execute();

            // 2. Actualizar el total en la reserva (evitar negativos)
            $sql = $conexion->prepare("UPDATE reservas SET total = GREATEST(COALESCE(total, 0) - :subtotal, 0) WHERE id = :id_reserva");
            $sql->bindParam(":subtotal", $servicio_reserva->subtotal, PDO::PARAM_STR);
            $sql->bindParam(":id_reserva", $servicio_reserva->reserva_id, PDO::PARAM_INT);
            $sql->execute();

            // 3. Notificación al cliente
            $sql_cliente = $conexion->prepare("SELECT cliente_id FROM reservas WHERE id = :id_reserva");
            $sql_cliente->bindParam(":id_reserva", $servicio_reserva->reserva_id, PDO::PARAM_INT);
            $sql_cliente->execute();
            $cliente = $sql_cliente->fetch(PDO::FETCH_OBJ);

            // Obtener nombre del servicio
            $sql_servicio = $conexion->prepare("SELECT nombre FROM servicios WHERE id = :id_servicio");
            $sql_servicio->bindParam(":id_servicio", $servicio_reserva->servicio_id, PDO::PARAM_INT);
            $sql_servicio->execute();
            $nombre_servicio = $sql_servicio->fetchColumn();

            $msg_cliente = "El servicio «{$nombre_servicio}» (x{$servicio_reserva->cantidad}) ha sido retirado de su reserva #{$servicio_reserva->reserva_id}. Contáctenos si requiere asistencia.";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            $sql_notif->bindParam(':uid', $cliente->cliente_id, PDO::PARAM_INT);
            $sql_notif->bindParam(':msg', $msg_cliente, PDO::PARAM_STR);
            $sql_notif->execute();

            // 4. Notificación al staff
            $sql_staff = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista') AND estado = 'activo'");
            $sql_staff->execute();
            $staff = $sql_staff->fetchAll(PDO::FETCH_OBJ);

            $msg_staff = "El recepcionista eliminó «{$nombre_servicio}» (x{$servicio_reserva->cantidad}) de la reserva #{$servicio_reserva->reserva_id}.";
            $sql_notif_staff = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            foreach ($staff as $miembro) {
                $sql_notif_staff->bindParam(':uid', $miembro->id, PDO::PARAM_INT);
                $sql_notif_staff->bindParam(':msg', $msg_staff, PDO::PARAM_STR);
                $sql_notif_staff->execute();
            }

            $conexion->commit();

            $_SESSION['exito'] = "Servicio «{$nombre_servicio}» eliminado correctamente de la reserva #{$servicio_reserva->reserva_id}.";
            header("Location: ../../recepcionista/gestion_servicios_reserva.php?id_reserva=" . Crypto::encrypt($servicio_reserva->reserva_id));
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al eliminar el servicio de la reserva: " . $e->getMessage());
            $_SESSION['errores'] = ["Ocurrió un error al eliminar el servicio. Inténtelo nuevamente."];
            $id_reserva_enc = isset($servicio_reserva) ? Crypto::encrypt($servicio_reserva->reserva_id) : '';
            header("Location: ../../recepcionista/gestion_servicios_reserva.php?id_reserva=" . $id_reserva_enc);
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        $id_reserva_enc = isset($servicio_reserva) ? Crypto::encrypt($servicio_reserva->reserva_id) : (isset($_POST['id_reserva']) ? htmlspecialchars($_POST['id_reserva']) : '');
        header("Location: ../../recepcionista/gestion_servicios_reserva.php?id_reserva=" . $id_reserva_enc);
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../recepcionista/gestion_reservas.php");
    exit;
}
