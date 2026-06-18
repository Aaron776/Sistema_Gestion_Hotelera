<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['numero']) && isset($_POST['tipo']) && isset($_POST['precio']) && isset($_POST['capacidad'])) {
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
    $errores = [];

    // Validacion y sanitizacion
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

    // Verifiacr si no hay otra habitacion con el mismo numero
    if(empty($errores)){
        try{
            $sql = $conexion->prepare("SELECT id FROM habitaciones WHERE numero = :numero limit 1");
            $sql->bindParam(":numero", $numero, PDO::PARAM_STR);
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


    // Si no hay errores, registrar al usuario
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("INSERT INTO habitaciones (numero,tipo,precio,capacidad) VALUES (:numero,:tipo,:precio,:capacidad)");
            $sql->bindParam(":numero", $numero, PDO::PARAM_STR);
            $sql->bindParam(":tipo", $tipo, PDO::PARAM_STR);
            $sql->bindParam(":precio", $precio, PDO::PARAM_STR);
            $sql->bindParam(":capacidad", $capacidad, PDO::PARAM_STR);
            $sql->execute();

            $_SESSION['exito'] = "Habitacion registrada correctamente";
            header("Location: ../../admin/gestion_habitaciones.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al registrar la habitacion: " . $e->getMessage());
            $errores[] = "Error crítico al guardar la habitacion en la base de datos";
            $_SESSION['errores'] = $errores;
            header("Location: ../../admin/gestion_habitaciones.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/agregar_habitacion.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_habitaciones.php");
    exit;
}
