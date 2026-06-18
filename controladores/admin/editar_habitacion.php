<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['numero']) && isset($_POST['tipo']) && isset($_POST['precio']) && isset($_POST['capacidad']) && isset($_POST['id_habitacion'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $numero = trim($_POST['numero']);
    $tipo = trim($_POST['tipo']);
    $precio = trim($_POST['precio']);
    $capacidad = trim($_POST['capacidad']);
    $id_habitacion = trim(Crypto::decrypt($_POST['id_habitacion']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_habitacion)) {
        $errores[] = "El ID de la habitacion es requerido";
    } elseif (!is_numeric($id_habitacion) || $id_habitacion <= 0) {
        $errores[] = "El ID de la habitacion no es válido";
    }

    if (empty($numero)) {
        $errores[] = "El numero es requerido";
    } elseif (!preg_match("/^[0-9\- ]+$/", $numero)) {
        $errores[] = "El numero solo puede contener números, guiones o espacios";
    } elseif (strlen($numero) > 10) {
        $errores[] = "El numero de habitacion no puede exceder 10 caracteres";
    }

    if (empty($tipo)) {
        $errores[] = "El tipo es requerido";
    } elseif (!in_array($tipo, ['simple', 'doble', 'suite','matrimonial','presidencial'])) {
        $errores[] = "El tipo de habitacion no es valido";
    }

    if (empty($precio)) {
        $errores[] = "El precio es requerido";
    } elseif (!is_numeric($precio) || $precio <= 0) {
        $errores[] = "El precio debe ser un numero mayor a 0";
    }

    if (empty($capacidad)) {
        $errores[] = "La capacidad es requerida";
    } elseif (!is_numeric($capacidad) || $capacidad <= 0 || $capacidad != (int)$capacidad) {
        $errores[] = "La capacidad debe ser un numero entero positivo";
    }

     // Verificar que la habitacion exista y no este ocupada
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id, estado FROM habitaciones WHERE id=:id_habitacion LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $habitacion = $sql->fetch(PDO::FETCH_OBJ);

            if (!$habitacion) {
                $errores[] = "La habitación que desea editar no existe";
            } elseif ($habitacion->estado === 'ocupada') {
                $errores[] = "No se puede editar una habitación que está ocupada";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la habitación: " . $e->getMessage());
            $errores[] = "Error al verificar la habitación en la base de datos";
        }
    }

    // Verificar si no hay otra habitacion con el mismo numero diferente a que se va a editar
    if(empty($errores)){
        try{
            $sql = $conexion->prepare("SELECT id FROM habitaciones WHERE numero = :numero AND id != :id_habitacion limit 1");
            $sql->bindParam(":numero", $numero, PDO::PARAM_STR);
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $habitacion = $sql->fetch();
            if($habitacion){
                $errores[] = "Ya existe una habitacion con el numero " . $numero;
            }
        }catch(PDOException $e){
            error_log("Error al verificar la habitacion: " . $e->getMessage());
            $errores[] = "Error crítico al verificar la habitacion en la base de datos";
        }
    }

    // Verificar que la habitacion no tenga reservas confirmadas
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT rh.id FROM reserva_habitacion rh INNER JOIN reservas r ON r.id = rh.reserva_id WHERE rh.habitacion_id=:id_habitacion AND r.estado='confirmada' LIMIT 1");
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();
            $reserva = $sql->fetch(PDO::FETCH_OBJ);

            if ($reserva) {
                $errores[] = "No se puede editar la habitación porque tiene reservas confirmadas";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la habitación: " . $e->getMessage());
            $errores[] = "Error al verificar la habitación en la base de datos";
        }
    }

    // Si no hay errores, registrar al usuario
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("UPDATE habitaciones SET numero = :numero, tipo = :tipo, precio = :precio, capacidad = :capacidad WHERE id = :id_habitacion");
            $sql->bindParam(":numero", $numero, PDO::PARAM_STR);
            $sql->bindParam(":tipo", $tipo, PDO::PARAM_STR);
            $sql->bindParam(":precio", $precio, PDO::PARAM_STR);
            $sql->bindParam(":capacidad", $capacidad, PDO::PARAM_STR);
            $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
            $sql->execute();

            $_SESSION['exito'] = "Habitacion editada correctamente";
            header("Location: ../../admin/gestion_habitaciones.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al editar la habitacion: " . $e->getMessage());
            $errores[] = "Error crítico al editar la habitacion en la base de datos";
            $_SESSION['errores'] = $errores;
            header("Location: ../../admin/editar_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/editar_habitacion.php?id_habitacion=" . Crypto::encrypt($id_habitacion));
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_habitaciones.php");
    exit;
}
