<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede registrar mantenimiento)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['motivo']) && isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin_estimada']) && isset($_POST['id_habitacion'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $motivo = trim($_POST['motivo']);
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_fin_estimada = trim($_POST['fecha_fin_estimada']);
    $id_habitacion = trim(Crypto::decrypt($_POST['id_habitacion']));
    $id_usuario_registra = $_SESSION['id_usuario'];
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_habitacion)) {
        $errores[] = "El ID de la habitacion es requerido";
    } elseif (!is_numeric($id_habitacion) || $id_habitacion <= 0) {
        $errores[] = "El ID de la habitacion no es válido";
    }

    if (empty($id_usuario_registra)) {
        $errores[] = "El ID del usuario que registra es requerido";
    } elseif (!is_numeric($id_usuario_registra) || $id_usuario_registra <= 0) {
        $errores[] = "El ID del usuario que registra no es válido";
    }

    if (empty($motivo)) {
        $errores[] = "El motivo es requerido";
    } elseif (strlen($motivo) > 150) {
        $errores[] = "El motivo no puede exceder 150 caracteres";
    }

    // Validar fecha de inicio
    if (empty($fecha_inicio)) {
        $errores[] = "La fecha de inicio es requerida";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_inicio)) {
        $errores[] = "La fecha de inicio debe estar en formato YYYY-MM-DD";
    } elseif (strtotime($fecha_inicio) < strtotime(date('Y-m-d'))) {
        $errores[] = "La fecha de inicio no puede ser menor a la fecha actual";
    }

    // Validar fecha de fin estimada
    if (empty($fecha_fin_estimada)) {
        $errores[] = "La fecha de fin estimada es requerida";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_fin_estimada)) {
        $errores[] = "La fecha de fin estimada debe estar en formato YYYY-MM-DD";
    } elseif (strtotime($fecha_fin_estimada) < strtotime($fecha_inicio)) {
        $errores[] = "La fecha de fin estimada no puede ser menor a la fecha de inicio";
    }

     // Verificar que la habitacion exista y no este ocupada
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id, estado FROM habitaciones WHERE id=:id_habitacion LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $habitacion = $sql->fetch(PDO::FETCH_OBJ);

            if (!$habitacion) {
                $errores[] = "La habitación a la que desea registrar mantenimiento no existe";
            } elseif ($habitacion->estado === 'ocupada') {
                $errores[] = "No se puede registrar mantenimiento a una habitación que está ocupada";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la habitación: " . $e->getMessage());
            $errores[] = "Error al verificar la habitación en la base de datos";
        }
    }

    // Verificar que la habitacion no tenga mantenimiento en proceso
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id FROM mantenimientos_habitacion WHERE habitacion_id=:id_habitacion AND estado='en_proceso' LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $mantenimiento = $sql->fetch(PDO::FETCH_OBJ);

            if ($mantenimiento) {
                $errores[] = "No se puede registrar mantenimiento para la habitación porque ya tiene un mantenimiento en proceso";
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
                $errores[] = "No se puede registrar mantenimiento a la habitación porque tiene reservas";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la habitación: " . $e->getMessage());
            $errores[] = "Error al verificar la habitación en la base de datos";
        }
    }

    
    // Si no hay errores, registrar el mantenimiento
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $sql = $conexion->prepare("INSERT INTO mantenimientos_habitacion (habitacion_id, motivo, fecha_inicio, fecha_fin_estimada, registrado_por) VALUES (:habitacion_id, :motivo, :fecha_inicio, :fecha_fin_estimada, :registrado_por)");
            $sql->bindParam(":habitacion_id", $id_habitacion, PDO::PARAM_INT);
            $sql->bindParam(":motivo", $motivo, PDO::PARAM_STR);
            $sql->bindParam(":fecha_inicio", $fecha_inicio, PDO::PARAM_STR);
            $sql->bindParam(":fecha_fin_estimada", $fecha_fin_estimada, PDO::PARAM_STR);
            $sql->bindParam(":registrado_por", $id_usuario_registra, PDO::PARAM_INT);
            $sql->execute();

            // 2. Actualizar el estado de la habitación
            $sqlHab = $conexion->prepare("UPDATE habitaciones SET estado = 'mantenimiento' WHERE id = :id_habitacion");
            $sqlHab->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sqlHab->execute();

            // 3. Obtener el número de habitación (para la notificación)
            $sqlNum = $conexion->prepare("SELECT numero FROM habitaciones WHERE id = :id_habitacion");
            $sqlNum->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sqlNum->execute();
            $habNum = $sqlNum->fetchColumn();

            // 4. Notificar a los recepcionistas/admins
            $mensajeNotif = "La habitación " . $habNum . " entró en mantenimiento. Motivo: " . $motivo;
            $sqlUsuarios = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista') AND id != :uid");
            $sqlUsuarios->bindParam(':uid', $id_usuario_registra, PDO::PARAM_INT);
            $sqlUsuarios->execute();
            
            if ($sqlUsuarios->rowCount() > 0) {
                $sqlNotif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:usuario_id, :mensaje)");
                while ($u = $sqlUsuarios->fetch(PDO::FETCH_OBJ)) {
                    $sqlNotif->bindValue(':usuario_id', $u->id, PDO::PARAM_INT);
                    $sqlNotif->bindValue(':mensaje', $mensajeNotif, PDO::PARAM_STR);
                    $sqlNotif->execute();
                }
            }

            $conexion->commit();

            $_SESSION['exito'] = "Mantenimiento registrado y habitación actualizada correctamente";
            header("Location: ../../admin/gestion_mantenimientos_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al registrar el mantenimiento: " . $e->getMessage());
            $errores[] = "Error crítico al procesar la solicitud en la base de datos";
            $_SESSION['errores'] = $errores;
            header("Location: ../../admin/registrar_mantenimiento_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/registrar_mantenimiento_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_habitaciones.php");
    exit;
}
