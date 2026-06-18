<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo clientes pueden editar su propia reserva)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_habitacion']) && isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin']) && isset($_POST['id_reserva'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    $id_cliente    = $_SESSION['id_usuario']; // id del cliente logueado que hizo la reserva
    $id_habitacion = trim($_POST['id_habitacion']);
    $fecha_inicio  = trim($_POST['fecha_inicio']);
    $fecha_fin     = trim($_POST['fecha_fin']);
    $id_reserva    = Crypto::decrypt(trim($_POST['id_reserva']));
    $errores = [];

    // Validar ID de habitación
    if (empty($id_habitacion)) {
        $errores[] = "El ID de la habitación es requerido";
    } elseif (!is_numeric($id_habitacion) || $id_habitacion <= 0) {
        $errores[] = "El ID de la habitación no es válido";
    }

    if (empty($id_reserva) || !is_numeric($id_reserva) || $id_reserva <= 0) {
        $errores[] = "El ID de la reserva no es válido";
    }

    // ✅ Validación robusta de fechas con DateTime
    $hoy   = new DateTime('today');
    $inicio = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
    $fin    = DateTime::createFromFormat('Y-m-d', $fecha_fin);

    if (!$inicio || !$fin) {
        $errores[] = "Formato de fecha inválido. Use el formato YYYY-MM-DD.";
    } else {
        if ($inicio < $hoy) {
            $errores[] = "La fecha de inicio no puede ser anterior a hoy.";
        }
        if ($fin <= $inicio) {
            $errores[] = "La fecha de fin debe ser posterior a la fecha de inicio.";
        }
    }

    // Verifiacir si la reserva existe,la pertenece a ese cliente y esta en estado pendiente
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
                $errores[] = "Solo puedes editar reservas en estado Pendiente. Esta reserva está en estado: " . ucfirst($reserva->estado) . ".";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la reserva: " . $e->getMessage());
            $errores[] = "Error al verificar la reserva en la base de datos.";
        }
    }

    // Verificar disponibilidad de la habitación en las fechas seleccionadas
    if (empty($errores)) {
        try {
            // Se excluye la reserva actual (:id_reserva_actual) para no bloquearse a sí misma
            $sql = $conexion->prepare("
                SELECT r.id FROM reservas r
                INNER JOIN reserva_habitacion rh ON rh.reserva_id = r.id
                WHERE rh.habitacion_id = :id_habitacion
                AND r.id != :id_reserva_actual
                AND r.estado NOT IN ('cancelada')
                AND r.fecha_inicio <= :fecha_fin
                AND r.fecha_fin >= :fecha_inicio
                LIMIT 1
            ");
            $sql->bindParam(":id_habitacion",    $id_habitacion, PDO::PARAM_INT);
            $sql->bindParam(":id_reserva_actual", $id_reserva,   PDO::PARAM_INT);
            $sql->bindParam(":fecha_inicio",      $fecha_inicio,  PDO::PARAM_STR);
            $sql->bindParam(":fecha_fin",         $fecha_fin,     PDO::PARAM_STR);
            $sql->execute();
            $reserva_existente = $sql->fetch();

            if ($reserva_existente) {
                $errores[] = "La habitación no está disponible en las fechas seleccionadas.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar disponibilidad: " . $e->getMessage());
            $errores[] = "Error al verificar disponibilidad en la base de datos";
        }
    }

    // Si no hay errores, editar la reserva
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // ✅ Obtener precio real de la habitación desde la BD
            $sql = $conexion->prepare("SELECT precio FROM habitaciones WHERE id = :id_habitacion AND estado != 'mantenimiento'");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $habitacion = $sql->fetch(PDO::FETCH_OBJ);

            if (!$habitacion) {
                $conexion->rollBack();
                $_SESSION['errores'] = ["La habitación seleccionada no existe."];
                header("Location: ../../cliente/editar_reserva.php?id_reserva=" . Crypto::encrypt($id_reserva));
                exit;
            }

            // ✅ Calcular el total en el servidor: precio_noche × número de noches
            $noches          = $inicio->diff($fin)->days;
            $total_calculado = $habitacion->precio * $noches;

            // Actualizar la reserva en la tabla reservas (con validación extra de seguridad)
            $sql = $conexion->prepare("UPDATE reservas SET fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, total = :total WHERE id = :id_reserva AND cliente_id = :id_cliente AND estado = 'pendiente'");
            $sql->bindParam(":fecha_inicio", $fecha_inicio,    PDO::PARAM_STR);
            $sql->bindParam(":fecha_fin",    $fecha_fin,       PDO::PARAM_STR);
            $sql->bindParam(":total",        $total_calculado, PDO::PARAM_STR);
            $sql->bindParam(":id_reserva",   $id_reserva,      PDO::PARAM_INT);
            $sql->execute();


            // Actualizar en la tabla pivote reserva_habitacion
            $sql = $conexion->prepare("UPDATE reserva_habitacion SET habitacion_id = :id_habitacion, precio = :precio WHERE reserva_id = :id_reserva");
            $sql->bindParam(":id_habitacion", $id_habitacion,      PDO::PARAM_INT);
            $sql->bindParam(":precio",        $habitacion->precio, PDO::PARAM_STR);
            $sql->bindParam(":id_reserva",    $id_reserva,         PDO::PARAM_INT);
            $sql->execute();

            // ✅ Notificación al cliente (actualización de su reserva)
            $msg_cliente = "🕒 Tu reserva (#{$id_reserva}) ha sido actualizada correctamente y se mantiene en revisión. El equipo del hotel te confirmará pronto.";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            $sql_notif->bindParam(':uid', $id_cliente, PDO::PARAM_INT);
            $sql_notif->bindParam(':msg', $msg_cliente, PDO::PARAM_STR);
            $sql_notif->execute();

            // ✅ Notificación al staff activo (admins y recepcionistas)
            $sql_staff = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista') AND estado = 'activo'");
            $sql_staff->execute();
            $staff = $sql_staff->fetchAll(PDO::FETCH_OBJ);

            $msg_staff = "✏️ La reserva web pendiente (#{$id_reserva}) fue modificada por el cliente. Por favor, revísela y confírmela.";
            $sql_notif_staff = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            foreach ($staff as $miembro) {
                $sql_notif_staff->bindParam(':uid', $miembro->id, PDO::PARAM_INT);
                $sql_notif_staff->bindParam(':msg', $msg_staff,   PDO::PARAM_STR);
                $sql_notif_staff->execute();
            }

            $conexion->commit();

            $_SESSION['exito'] = "Reserva actualizada correctamente. Recibirás una confirmación pronto.";
            header("Location: ../../cliente/gestion_reservas.php");
            exit;

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al editar la reserva: " . $e->getMessage());
            $_SESSION['errores'] = ["Error al editar la reserva. Intente nuevamente."];
            header("Location: ../../cliente/editar_reserva.php?id_reserva=" . Crypto::encrypt($id_reserva));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../cliente/editar_reserva.php?id_reserva=" . Crypto::encrypt($id_reserva));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../cliente/gestion_reservas.php");
    exit;
}
