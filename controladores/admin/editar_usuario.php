<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede editar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['id_usuario']) && isset($_POST['email']) && isset($_POST['rol']) && isset($_POST['telefono']) && isset($_POST['direccion']) && isset($_POST['cedula'])) { // Se eliminaron las referencias a 'password'
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $rol = trim($_POST['rol']);
    $id_usuario = trim(Crypto::decrypt($_POST['id_usuario']));
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $cedula = trim($_POST['cedula']);
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_usuario)) {
        $errores[] = "El ID del usuario es requerido";
    } elseif (!is_numeric($id_usuario) || $id_usuario <= 0) {
        $errores[] = "El ID del usuario no es válido";
    }

    if (empty($nombre)) {
        $errores[] = "El nombre es requerido";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $nombre)) {
        $errores[] = "El nombre solo puede contener letras y espacios";
    } elseif (strlen($nombre) > 100) {
        $errores[] = "El nombre es muy largo";
    }

    if (empty($cedula)) {
        $errores[] = "La cedula es requerida";
    } elseif (!preg_match("/^[0-9\- ]+$/", $cedula)) {
        $errores[] = "La cédula solo puede contener números, guiones o espacios";
    } elseif (strlen($cedula) > 20) {
        $errores[] = "La cedula es muy larga";
    }

    if (!empty($direccion)) {
        if (strlen($direccion) > 255) {
            $errores[] = "La direccion es muy larga";
        }
    }

    if (!empty($telefono)) {
        if (!preg_match("/^[0-9\+ ]+$/", $telefono)) {
            $errores[] = "El teléfono solo puede contener números, el signo + o espacios";
        } elseif (strlen($telefono) > 20) {
            $errores[] = "El telefono es muy largo";
        }
    }

    if (empty($email)) {
        $errores[] = "El email es requerido";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no es valido";
    } elseif (strlen($email) > 100) {
        $errores[] = "El email es muy largo";
    }

    if (empty($rol)) {
        $errores[] = "El rol es requerido";
    } elseif (!in_array($rol, ['admin', 'recepcionista'])) {
        $errores[] = "El rol seleccionado no es válido";
    }

    // Verificar que el usuario exista en la base de datos y este en estado activo
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id FROM usuarios WHERE id=:id_usuario AND estado='activo' LIMIT 1");
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();
            $usuario = $sql->fetch(PDO::FETCH_OBJ);
            if (!$usuario) {
                $errores[] = "El usuario que desea editar no existe o no está activo";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar el usuario: " . $e->getMessage());
            $errores[] = "Error al consultar el usuario en la base de datos";
        }
    }

    // Verificar si existe otro usuario con el mimso email o telefono o cedula que sea diferente al que se quiere editar
    if (empty($errores)) {
        try {
            // Modificación: Excluir al usuario actual de la verificación de unicidad
            $query = "SELECT id, email, telefono, cedula FROM usuarios WHERE (email=:email OR cedula=:cedula";
            if (!empty($telefono)) {
                $query .= " OR telefono=:telefono";
            }
            $query .= ") AND id != :id_usuario_actual";

            $sql = $conexion->prepare($query);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            if (!empty($telefono)) {
                $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            }

            $sql->bindParam(":id_usuario_actual", $id_usuario, PDO::PARAM_INT);

            $sql->execute();
            $usuario_existe = $sql->fetch(PDO::FETCH_OBJ);
            if ($usuario_existe) {
                // Verificar si el email, cedula o telefono ya existen para OTRO usuario
                if ($usuario_existe->email === $email && $usuario_existe->id != $id_usuario) {
                    $errores[] = "Ya existe un usuario con este email";
                }
                if ($usuario_existe->cedula === $cedula && $usuario_existe->id != $id_usuario) {
                    $errores[] = "Ya existe un usuario con esta cedula";
                }
                if (!empty($telefono) && $usuario_existe->telefono === $telefono && $usuario_existe->id != $id_usuario) {
                    $errores[] = "Ya existe un usuario con este teléfono";
                }
            }
        } catch (PDOException $e) {
            error_log("Error al verificar usuario existente: " . $e->getMessage());
            $errores[] = "Error al verificar usuario existente";
        }
    }


    // Protección contra "Orfandad del Sistema" por cambio de rol
    if (empty($errores) && $rol !== 'admin') {
        try {
            // Verificamos el rol actual del usuario antes del cambio
            $sqlCheck = $conexion->prepare("SELECT rol FROM usuarios WHERE id=:id_usuario LIMIT 1");
            $sqlCheck->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sqlCheck->execute();
            $rolActual = $sqlCheck->fetchColumn();

            if ($rolActual === 'admin') {
                // Verificamos cuántos administradores activos quedarían
                $sqlCount = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin' AND estado='activo'");
                $totalAdmins = $sqlCount->fetchColumn();

                if ($totalAdmins <= 1) {
                    $errores[] = "Protección de Sistema: No puedes quitarle el rol al único administrador activo";
                }
            }
        } catch (PDOException $e) {
            error_log("Error en protección de orfandad: " . $e->getMessage());
            $errores[] = "Error de seguridad al validar roles críticos";
        }
    }

    // Si no hay errores, editar al usuario
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            $sql = $conexion->prepare("UPDATE usuarios SET nombre=:nombre, cedula=:cedula, email=:email,telefono=:telefono, direccion=:direccion, rol=:rol WHERE id=:id_usuario");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $sql->bindParam(":direccion", $direccion, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->bindParam(":rol", $rol, PDO::PARAM_STR);
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            // 2. Insertar notificación para el usuario afectado
            $mensaje_notif = "El administrador " . $_SESSION['nombre'] . " ha modificado la información de tu cuenta o tus permisos.";

            $sqlNotif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:id_usuario, :mensaje)");
            $sqlNotif->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sqlNotif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
            $sqlNotif->execute();

            $conexion->commit();

            $_SESSION['exito'] = "¡Usuario actualizado correctamente!";
            header("Location: ../../admin/editar_usuario.php?id_usuario=" . urlencode(Crypto::encrypt($id_usuario)));
            exit;
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            error_log("Error al editar el usuario: " . $e->getMessage());
            $errores[] = "Error crítico al intentar actualizar los datos";
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/editar_usuario.php?id_usuario=" . urlencode(Crypto::encrypt($id_usuario)));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos insuficientes"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}
