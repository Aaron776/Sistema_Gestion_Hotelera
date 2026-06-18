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

    // Verificar que el mantenimeinto exista para esa habitacion
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id, estado FROM mantenimientos_habitacion WHERE id=:id_mantenimiento AND habitacion_id=:id_habitacion LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->bindParam(":id_mantenimiento", $id_mantenimiento, PDO::PARAM_INT);
            $sql->execute();
            $mantenimiento_existente = $sql->fetch(PDO::FETCH_OBJ);

            if (!$mantenimiento_existente) {
                $errores[] = "El mantenimiento de la habitación que desea eliminar no existe";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el mantenimiento: " . $e->getMessage());
            $errores[] = "Error al verificar el mantenimiento en la base de datos";
        }
    }


    // Si no hay errores, eliminar el mantenimiento de la habitacion
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $sql = $conexion->prepare("DELETE FROM mantenimientos_habitacion WHERE id=:id_mantenimiento AND habitacion_id=:id_habitacion");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->bindParam(":id_mantenimiento", $id_mantenimiento, PDO::PARAM_INT);
            $sql->execute();

            // Si el mantenimiento estaba en proceso, liberar la habitación
            if (strtolower($mantenimiento_existente->estado) === 'en_proceso') {
                $sqlHab = $conexion->prepare("UPDATE habitaciones SET estado = 'disponible' WHERE id = :id_habitacion");
                $sqlHab->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
                $sqlHab->execute();

                // Obtener número de habitación para la notificación
                $sqlNum = $conexion->prepare("SELECT numero FROM habitaciones WHERE id = :id_habitacion");
                $sqlNum->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
                $sqlNum->execute();
                $habNum = $sqlNum->fetchColumn();

                // Notificar a los recepcionistas/admins
                $mensajeNotif = "El mantenimiento de la habitación " . $habNum . " fue eliminado/cancelado. La habitación vuelve a estar disponible.";
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
            }

            $conexion->commit();

            $_SESSION['exito'] = "¡Mantenimiento eliminado correctamente!";
            header("Location: ../../admin/gestion_mantenimientos_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
            exit;
        } catch (PDOException $e) {
            $conexion->rollBack();
            error_log("Error al eliminar el mantenimiento de la habitación: " . $e->getMessage());
            $errores[] = "Error crítico al eliminar el mantenimiento de la base de datos";
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
