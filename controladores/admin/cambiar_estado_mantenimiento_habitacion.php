<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede eliminar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_habitacion']) && isset($_POST['id_mantenimiento'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
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

    // Verificar que la habitacion exista y se encuentre en estado en mantenimiento
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id FROM habitaciones WHERE id = :id_habitacion AND estado='mantenimiento' LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $habitacion_existente = $sql->fetch(PDO::FETCH_OBJ);

            if (!$habitacion_existente) {
                $errores[] = "La habitación que desea cambiar el estado no se encuentra en estado de mantenimiento";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la habitación: " . $e->getMessage());
            $errores[] = "Error al verificar la habitación en la base de datos";
        }
    }

    // Verificar que el mantenimiento de la habitacion exista y este en esatdo en proceso
    if(empty($errores)){
        try{
            $sql = $conexion->prepare("SELECT id FROM mantenimientos_habitacion WHERE id = :id_mantenimiento AND habitacion_id = :id_habitacion AND estado='en_proceso' LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->bindParam(":id_mantenimiento", $id_mantenimiento, PDO::PARAM_INT);
            $sql->execute();
            $mantenimiento_existente = $sql->fetch(PDO::FETCH_OBJ);

            if (!$mantenimiento_existente) {
                $errores[] = "El mantenimiento que desea cambiar el estado no existe o no se encuentra en estado en proceso";
            }
        }catch (PDOException $e) {
            error_log("Error al verificar el mantenimiento: " . $e->getMessage());
            $errores[] = "Error al verificar el mantenimiento en la base de datos";
        }
    }


    // Si no hay errores, cambiar el mantenimiento de la habitacion
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // Finalizar mantenimiento
            $sqlMant = $conexion->prepare("UPDATE mantenimientos_habitacion SET estado='finalizado' WHERE id=:id_mantenimiento AND habitacion_id=:id_habitacion");
            $sqlMant->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sqlMant->bindParam(":id_mantenimiento", $id_mantenimiento, PDO::PARAM_INT);
            $sqlMant->execute();

            // Cambiar el estado de la habitacion a disponible
            $sqlHab = $conexion->prepare("UPDATE habitaciones SET estado='disponible' WHERE id=:id_habitacion");
            $sqlHab->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sqlHab->execute();

            // Obtener el número de habitación directamente para la notificación
            $sqlNum = $conexion->prepare("SELECT numero FROM habitaciones WHERE id = :id_habitacion");
            $sqlNum->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sqlNum->execute();
            $habNum = $sqlNum->fetchColumn();

            // Notificar a los recepcionistas/admins
            $mensajeNotif = "El mantenimiento de la habitación " . $habNum . " ha finalizado. La habitación vuelve a estar disponible.";
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

            $_SESSION['exito'] = "¡Mantenimiento finalizado y habitación disponible!";
            header("Location: ../../admin/gestion_mantenimientos_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al cambiar el estado del mantenimiento de la habitación: " . $e->getMessage());
            $errores[] = "Error crítico al cambiar el estado del mantenimiento de la base de datos";
            $_SESSION['errores'] = $errores;
            header("Location: ../../admin/gestion_mantenimientos_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_mantenimientos_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_habitaciones.php");
    exit;
}
