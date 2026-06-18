<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_habitacion']) && isset($_POST['id_mantenimiento'])  && isset($_POST['motivo']) && isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin_estimada'])) {
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
    $id_mantenimiento = trim(Crypto::decrypt($_POST['id_mantenimiento']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_habitacion)) {
        $errores[] = "El ID de la habitación es requerido";
    } elseif (!is_numeric($id_habitacion) || $id_habitacion <= 0) {
        $errores[] = "El ID de la habitación no es válido";
    }

    if (empty($id_mantenimiento)) {
        $errores[] = "El ID del mantenimiento es requerido";
    } elseif (!is_numeric($id_mantenimiento) || $id_mantenimiento <= 0) {
        $errores[] = "El ID del mantenimiento no es válido";
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
    }

    // Validar fecha de fin estimada
    if (empty($fecha_fin_estimada)) {
        $errores[] = "La fecha de fin estimada es requerida";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_fin_estimada)) {
        $errores[] = "La fecha de fin estimada debe estar en formato YYYY-MM-DD";
    } elseif (strtotime($fecha_fin_estimada) < strtotime($fecha_inicio)) {
        $errores[] = "La fecha de fin estimada no puede ser menor a la fecha de inicio";
    }

    // Verificar que el mantenimeinto exista para esa habitacion y esat en estado en proceso
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id, estado FROM mantenimientos_habitacion WHERE id=:id_mantenimiento AND habitacion_id=:id_habitacion AND estado = 'en_proceso' LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->bindParam(":id_mantenimiento", $id_mantenimiento, PDO::PARAM_INT);
            $sql->execute();
            $mantenimiento_existente = $sql->fetch(PDO::FETCH_OBJ);

            if (!$mantenimiento_existente) {
                $errores[] = "El mantenimiento de la habitación que desea editar no existe o no está en estado en proceso";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el mantenimiento: " . $e->getMessage());
            $errores[] = "Error al verificar el mantenimiento en la base de datos";
        }
    }


    // Si no hay errores, editar el mantenimiento de la habitacion
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $sql = $conexion->prepare("UPDATE mantenimientos_habitacion SET motivo=:motivo, fecha_inicio=:fecha_inicio, fecha_fin_estimada=:fecha_fin_estimada WHERE id=:id_mantenimiento AND habitacion_id=:id_habitacion");
            $sql->bindParam(":motivo", $motivo, PDO::PARAM_STR);
            $sql->bindParam(":fecha_inicio", $fecha_inicio, PDO::PARAM_STR);
            $sql->bindParam(":fecha_fin_estimada", $fecha_fin_estimada, PDO::PARAM_STR);
            $sql->bindParam(":id_mantenimiento", $id_mantenimiento, PDO::PARAM_INT);
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();

            // Notificar a los recepcionistas/admins
            // Obtener numero de la habitacion
            $sqlNum = $conexion->prepare("SELECT numero FROM habitaciones WHERE id = :id_habitacion");
            $sqlNum->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sqlNum->execute();
            $numero_habitacion = $sqlNum->fetchColumn();


            $mensajeNotif = "El mantenimiento de la habitación " . $numero_habitacion . " fue editado correctamente.";
            $id_usuario_actual = $_SESSION['id_usuario'];
            $sqlUsuarios = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista') AND id != :uid");
            $sqlUsuarios->bindParam(':uid', $id_usuario_actual, PDO::PARAM_INT);
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

            $_SESSION['exito'] = "¡Mantenimiento editado correctamente!";
            header("Location: ../../admin/gestion_mantenimientos_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al editar el mantenimiento de la habitación: " . $e->getMessage());
            $errores[] = "Error crítico al editar el mantenimiento de la base de datos";
            $_SESSION['errores'] = $errores;
            header("Location: ../../admin/editar_mantenimiento_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion) . "&id_mantenimiento=" . Crypto::encrypt($id_mantenimiento));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/editar_mantenimiento_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion) . "&id_mantenimiento=" . Crypto::encrypt($id_mantenimiento));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_habitaciones.php");
    exit;
}
