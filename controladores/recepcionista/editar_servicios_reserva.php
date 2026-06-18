<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo recepcionista puede editar servicios de una reserva)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_servicio_reserva']) && isset($_POST['id_reserva']) && isset($_POST['cantidad']) && isset($_POST['id_servicio'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    $id_servicio_reserva = Crypto::decrypt(trim($_POST['id_servicio_reserva']));
    $id_reserva       = Crypto::decrypt(trim($_POST['id_reserva']));
    $id_servicio    = trim($_POST['id_servicio']);
    $cantidad        = (int) trim($_POST['cantidad']);
    $errores = [];

    // Validar ID de servicio
    if (empty($id_servicio)) {
        $errores[] = "Debe seleccionar un servicio";
    } elseif (!is_numeric($id_servicio) || $id_servicio <= 0) {
        $errores[] = "El servicio seleccionado no es válido";
    }

    if (empty($id_servicio_reserva)) {
        $errores[] = "El ID del servicio de la reserva es requerido";
    } elseif (!is_numeric($id_servicio_reserva) || $id_servicio_reserva <= 0) {
        $errores[] = "El ID del servicio de la reserva no es válido";
    }

    // Validar ID de reserva
    if (empty($id_reserva)) {
        $errores[] = "El ID de la reserva es requerido";
    } elseif (!is_numeric($id_reserva) || $id_reserva <= 0) {
        $errores[] = "El ID de la reserva no es válido";
    }

    if (empty($cantidad)) {
        $errores[] = "La cantidad es requerida";
    } elseif (!is_numeric($cantidad) || $cantidad <= 0) {
        $errores[] = "La cantidad no es válida";
    }

    // Verifiacir que el servicio para esa reserva exista
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("
                SELECT id,subtotal FROM reserva_servicio WHERE id =:id_servicio_reserva AND reserva_id =:id_reserva
                LIMIT 1
            ");
            $sql->bindParam(":id_servicio_reserva", $id_servicio_reserva, PDO::PARAM_INT);
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->execute();
            $servicio_reserva_existe = $sql->fetch(PDO::FETCH_OBJ);

            if (!$servicio_reserva_existe) {
                $errores[] = "El servicio para esta reserva no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el servicio para esta reserva: " . $e->getMessage());
            $errores[] = "Error al verificar el servicio para esta reserva";
        }
    }

    // Verificar que la reserva exista y esté en estado confirmada
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("
                SELECT id FROM reservas WHERE id =:id_reserva AND estado ='confirmada'
                LIMIT 1
            ");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->execute();
            $reserva_existente = $sql->fetch();

            if (!$reserva_existente) {
                $errores[] = "La reserva no existe o no está confirmada para agregar servicios";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar disponibilidad: " . $e->getMessage());
            $errores[] = "Error al verificar la reserva en la base de datos";
        }
    }

    // Si no hay errores, editar el servicio a la reserva
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // ✅ Obtener precio real del servicio desde la BD
            $sql = $conexion->prepare("SELECT precio FROM servicios WHERE id = :id_servicio");
            $sql->bindParam(":id_servicio", $id_servicio, PDO::PARAM_INT);
            $sql->execute();
            $servicio = $sql->fetch(PDO::FETCH_OBJ);

            if (!$servicio) {
                $conexion->rollBack();
                $_SESSION['errores'] = ["El servicio seleccionado no existe o se encuentra inactivo."];
                header("Location: ../../recepcionista/gestion_reservas.php");
                exit;
            }

            // Restar el subtotal del servicio que se está editando de la reserva
            $sql = $conexion->prepare("UPDATE reservas SET total = COALESCE(total, 0) - :subtotal WHERE id = :id_reserva");
            $sql->bindParam(":subtotal", $servicio_reserva_existe->subtotal, PDO::PARAM_STR);
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->execute();

            // ✅ Calcular el nuevo subtotal a pagar por el servicio
            $subtotal = $servicio->precio * $cantidad;

            // Editar el servicio a la tabla de reserva_servicio
            $sql = $conexion->prepare("UPDATE reserva_servicio SET servicio_id =:id_servicio, cantidad =:cantidad, subtotal =:subtotal WHERE id =:id_servicio_reserva");
            $sql->bindParam(":id_servicio", $id_servicio, PDO::PARAM_INT);
            $sql->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
            $sql->bindParam(":subtotal", $subtotal, PDO::PARAM_STR);
            $sql->bindParam(":id_servicio_reserva", $id_servicio_reserva, PDO::PARAM_INT);
            $sql->execute();

            // Actualizar el total a pagar de la reserva y sumarle el subtotal del servicio
            $sql = $conexion->prepare("UPDATE reservas SET total = COALESCE(total, 0) + :subtotal WHERE id = :id_reserva");
            $sql->bindParam(":subtotal", $subtotal, PDO::PARAM_STR);
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->execute();

            // Obtener nombre del servicio para la notificación
            $sql_nombre = $conexion->prepare("SELECT nombre FROM servicios WHERE id = :id");
            $sql_nombre->execute([':id' => $id_servicio]);
            $nombre_servicio = $sql_nombre->fetchColumn();

            // Obtener el cliente de la reserva
            $sql = $conexion->prepare("SELECT cliente_id FROM reservas WHERE id = :id_reserva");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->execute();
            $cliente = $sql->fetch(PDO::FETCH_OBJ);

            // Notificación al cliente
            $msg_cliente = "El servicio «{$nombre_servicio}» de su reserva #{$id_reserva} ha sido modificado (x{$cantidad}).";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            $sql_notif->bindParam(':uid', $cliente->cliente_id, PDO::PARAM_INT);
            $sql_notif->bindParam(':msg', $msg_cliente, PDO::PARAM_STR);
            $sql_notif->execute();

            // Notificación al staff activo (admins y recepcionistas)
            $sql_staff = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista') AND estado = 'activo'");
            $sql_staff->execute();
            $staff = $sql_staff->fetchAll(PDO::FETCH_OBJ);

            $msg_staff = "El recepcionista modificó «{$nombre_servicio}» (x{$cantidad}) en la reserva #{$id_reserva}.";
            $sql_notif_staff = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            foreach ($staff as $miembro) {
                $sql_notif_staff->bindParam(':uid', $miembro->id, PDO::PARAM_INT);
                $sql_notif_staff->bindParam(':msg', $msg_staff, PDO::PARAM_STR);
                $sql_notif_staff->execute();
            }

            $conexion->commit();

            $_SESSION['exito'] = "Servicio «{$nombre_servicio}» editado correctamente en la reserva #{$id_reserva}.";
            header("Location: ../../recepcionista/gestion_servicios_reserva.php?id_reserva=" . Crypto::encrypt($id_reserva));
            exit;
        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al editar el servicio a la reserva: " . $e->getMessage());
            $_SESSION['errores'] = ["Ocurrió un error al modificar el servicio. Por favor, inténtelo nuevamente."];
            header("Location: ../../recepcionista/editar_servicios_reserva.php?id_servicio_reserva=" . Crypto::encrypt($id_servicio_reserva) . "&id_reserva=" . Crypto::encrypt($id_reserva));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../recepcionista/editar_servicios_reserva.php?id_servicio_reserva=" . Crypto::encrypt($id_servicio_reserva) . "&id_reserva=" . Crypto::encrypt($id_reserva));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../recepcionista/gestion_reservas.php");
    exit;
}
