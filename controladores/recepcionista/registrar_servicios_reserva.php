<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo recepcionista puede registrar reservas)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_reserva']) && isset($_POST['id_servicio']) && isset($_POST['cantidad'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

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

    // Si no hay errores, registrar el servicio a la reserva
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
                $_SESSION['errores'] = ["El servicio seleccionado no existe."];
                header("Location: ../../recepcionista/gestion_reservas.php");
                exit;
            }

            // ✅ Calcular el subtotal a pagar por el servicio
            $subtotal = $servicio->precio * $cantidad;

            // Insertar el servicio a la tabla de reserva_servicio
            $sql = $conexion->prepare("INSERT INTO reserva_servicio (reserva_id, servicio_id, cantidad, subtotal) VALUES (:reserva_id, :servicio_id, :cantidad, :subtotal)");
            $sql->bindParam(":reserva_id", $id_reserva, PDO::PARAM_INT);
            $sql->bindParam(":servicio_id", $id_servicio, PDO::PARAM_INT);
            $sql->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
            $sql->bindParam(":subtotal", $subtotal, PDO::PARAM_STR);
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
            $msg_cliente = "Se ha agregado el servicio «{$nombre_servicio}» (x{$cantidad}) a su reserva #{$id_reserva}.";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            $sql_notif->bindParam(':uid', $cliente->cliente_id, PDO::PARAM_INT);
            $sql_notif->bindParam(':msg', $msg_cliente, PDO::PARAM_STR);
            $sql_notif->execute();

            // Notificación al staff activo (admins y recepcionistas)
            $sql_staff = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista') AND estado = 'activo'");
            $sql_staff->execute();
            $staff = $sql_staff->fetchAll(PDO::FETCH_OBJ);

            $msg_staff = "El recepcionista agregó «{$nombre_servicio}» (x{$cantidad}) a la reserva #{$id_reserva}.";
            $sql_notif_staff = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            foreach ($staff as $miembro) {
                $sql_notif_staff->bindParam(':uid', $miembro->id, PDO::PARAM_INT);
                $sql_notif_staff->bindParam(':msg', $msg_staff, PDO::PARAM_STR);
                $sql_notif_staff->execute();
            }

            $conexion->commit();

            $_SESSION['exito'] = "Servicio «{$nombre_servicio}» registrado correctamente en la reserva #{$id_reserva}.";
            header("Location: ../../recepcionista/gestion_servicios_reserva.php?id_reserva=" . Crypto::encrypt($id_reserva));
            exit;
        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al registrar el servicio a la reserva: " . $e->getMessage());
            $_SESSION['errores'] = ["Ocurrió un error al agregar el servicio a la reserva. Por favor, inténtelo nuevamente."];
            header("Location: ../../recepcionista/registrar_servicios_reserva.php?id_reserva=" . Crypto::encrypt($id_reserva));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../recepcionista/registrar_servicios_reserva.php?id_reserva=" . Crypto::encrypt($id_reserva));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../recepcionista/gestion_reservas.php");
    exit;
}
