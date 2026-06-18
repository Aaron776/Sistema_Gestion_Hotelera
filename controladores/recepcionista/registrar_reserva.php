<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo recepcionista puede registrar reservas)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_habitacion']) && isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin']) && isset($_POST['id_cliente'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    $id_recepcionista = $_SESSION['id_usuario']; // recepcionista logueado
    $id_cliente       = trim($_POST['id_cliente']);
    $id_habitacion    = trim($_POST['id_habitacion']);
    $fecha_inicio     = trim($_POST['fecha_inicio']);
    $fecha_fin        = trim($_POST['fecha_fin']);
    $errores = [];

    // Validar ID de habitación
    if (empty($id_habitacion)) {
        $errores[] = "El ID de la habitación es requerido";
    } elseif (!is_numeric($id_habitacion) || $id_habitacion <= 0) {
        $errores[] = "El ID de la habitación no es válido";
    }

    // Validar ID de cliente
    if (empty($id_cliente)) {
        $errores[] = "El ID del cliente es requerido";
    } elseif (!is_numeric($id_cliente) || $id_cliente <= 0) {
        $errores[] = "El ID del cliente no es válido";
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

    // Verificar disponibilidad de la habitación en las fechas seleccionadas
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("
                SELECT r.id FROM reservas r
                INNER JOIN reserva_habitacion rh ON rh.reserva_id = r.id
                WHERE rh.habitacion_id = :id_habitacion
                AND r.estado NOT IN ('cancelada')
                AND r.fecha_inicio <= :fecha_fin
                AND r.fecha_fin >= :fecha_inicio
                LIMIT 1
            ");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->bindParam(":fecha_inicio", $fecha_inicio, PDO::PARAM_STR);
            $sql->bindParam(":fecha_fin", $fecha_fin, PDO::PARAM_STR);
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

    // Si no hay errores, registrar la reserva
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // ✅ Obtener precio real de la habitación desde la BD (solo disponible)
            $sql = $conexion->prepare("SELECT precio FROM habitaciones WHERE id = :id_habitacion AND estado = 'disponible'");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $habitacion = $sql->fetch(PDO::FETCH_OBJ);

            if (!$habitacion) {
                $conexion->rollBack();
                $_SESSION['errores'] = ["La habitación seleccionada no existe o se encuentra en mantenimiento indefinido."];
                header("Location: ../../recepcionista/gestion_reservas.php");
                exit;
            }

            // ✅ Calcular el total en el servidor: precio_noche × número de noches
            $noches          = $inicio->diff($fin)->days;
            $total_calculado = $habitacion->precio * $noches;

            // Insertar en reservas (compatible con PostgreSQL mediante RETURNING)
            $sql = $conexion->prepare("INSERT INTO reservas (cliente_id, empleado_id, fecha_inicio, fecha_fin, estado, total) VALUES (:id_cliente, :id_recepcionista, :fecha_inicio, :fecha_fin, 'confirmada', :total) RETURNING id");
            $sql->bindParam(":id_cliente",      $id_cliente,       PDO::PARAM_INT);
            $sql->bindParam(":id_recepcionista", $id_recepcionista, PDO::PARAM_INT);
            $sql->bindParam(":fecha_inicio",   $fecha_inicio,     PDO::PARAM_STR);
            $sql->bindParam(":fecha_fin",      $fecha_fin,        PDO::PARAM_STR);
            $sql->bindParam(":total",          $total_calculado,  PDO::PARAM_STR);
            $sql->execute();
            $id_reserva = $sql->fetchColumn();

            // Insertar en la tabla pivote reserva_habitacion
            $sql = $conexion->prepare("INSERT INTO reserva_habitacion (reserva_id, habitacion_id, precio) VALUES (:reserva_id, :id_habitacion, :precio)");
            $sql->bindParam(":reserva_id",    $id_reserva,         PDO::PARAM_INT);
            $sql->bindParam(":id_habitacion", $id_habitacion,      PDO::PARAM_INT);
            $sql->bindParam(":precio",        $habitacion->precio, PDO::PARAM_STR);
            $sql->execute();

            // Marcar la habitación como ocupada
            $sql_hab = $conexion->prepare("UPDATE habitaciones SET estado = 'ocupada' WHERE id = :id_habitacion");
            $sql_hab->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql_hab->execute();

            // ✅ Notificación al cliente (confirmación de su reserva)
            $fecha_formateada = date("d/m/Y", strtotime($fecha_inicio));
            $msg_cliente = "🏨 ¡Buenas noticias! Hemos registrado y confirmado su reserva (#{$id_reserva}) exitosamente. Lo esperamos el {$fecha_formateada}.";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            $sql_notif->bindParam(':uid', $id_cliente, PDO::PARAM_INT);
            $sql_notif->bindParam(':msg', $msg_cliente, PDO::PARAM_STR);
            $sql_notif->execute();

            // ✅ Notificación al staff activo (admins y recepcionistas)
            $sql_staff = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista') AND estado = 'activo'");
            $sql_staff->execute();
            $staff = $sql_staff->fetchAll(PDO::FETCH_OBJ);

            $msg_staff = "Nueva reserva (#{$id_reserva}) registrada y confirmada por {$_SESSION['nombre']}.";
            $sql_notif_staff = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            foreach ($staff as $miembro) {
                $sql_notif_staff->bindParam(':uid', $miembro->id, PDO::PARAM_INT);
                $sql_notif_staff->bindParam(':msg', $msg_staff,   PDO::PARAM_STR);
                $sql_notif_staff->execute();
            }


            $conexion->commit();

            
            $_SESSION['exito'] = "La reserva #{$id_reserva} ha sido registrada y confirmada exitosamente para el cliente.";
            header("Location: ../../recepcionista/gestion_reservas.php");
            exit;

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al registrar la reserva: " . $e->getMessage());
            $_SESSION['errores'] = ["Ocurrió un error al registrar la reserva. Por favor, verifique los datos e inténtelo nuevamente."];
            header("Location: ../../recepcionista/gestion_reservas.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../recepcionista/gestion_reservas.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../recepcionista/gestion_reservas.php");
    exit;
}
