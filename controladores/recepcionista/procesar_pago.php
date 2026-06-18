<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo recepcionista puede registrar pago)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_reserva']) && isset($_POST['metodo']) && isset($_POST['monto'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    $id_reserva       = Crypto::decrypt(trim($_POST['id_reserva']));
    $id_recepcionista = $_SESSION['id_usuario']; // id del recpecionsita que registra el pago
    $metodo           = trim($_POST['metodo']);
    $monto            = trim($_POST['monto']);
    $errores = [];

    // Validaciones y Sanitizacion
    if (empty($id_reserva)) {
        $errores[] = "Debe seleccionar una reserva";
    } elseif (!is_numeric($id_reserva) || $id_reserva <= 0) {
        $errores[] = "El ID de la reserva no es válido";
    }

    $metodos_validos=['efectivo','tarjeta','transferencia'];
    if(empty($metodo)){
        $errores[]="El método de pago es requerido";
    }elseif(!in_array($metodo,$metodos_validos)){
        $errores[]="El método de pago no es válido";
    }

    // Validar monto
    if (empty($monto)) {
        $errores[] = "El monto es requerido";
    } elseif (!is_numeric($monto) || $monto <= 0) {
        $errores[] = "El monto no es válido";
    }



    // Verificar que la reserva exista y esté en estado finalizada
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("
                SELECT r.id AS id_reserva, r.total AS reserva_total, u.id AS id_cliente, u.nombre AS cliente_nombre, u.cedula AS cliente_documento, u.direccion AS cliente_direccion
                FROM reservas r
                JOIN usuarios u ON r.cliente_id = u.id
                WHERE r.id = :id_reserva AND r.estado = 'finalizada'
                LIMIT 1
            ");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->execute();
            $reserva_existente = $sql->fetch(PDO::FETCH_OBJ);

            if (!$reserva_existente) {
                $errores[] = "La reserva no existe o no está finalizada para poder realizar el pago";
            } else {
                // Validar que el monto cubra el total de la reserva
                if ($monto < $reserva_existente->reserva_total) {
                    $errores[] = "El monto ingresado ({$moneda} {$monto}) es menor al total de la reserva ({$moneda} {$reserva_existente->reserva_total}).";
                }
            }
        } catch (PDOException $e) {
            error_log("Error al verificar la reserva: " . $e->getMessage());
            $errores[] = "Error al verificar la reserva en la base de datos";
        }
    }

    // Verificar que la reserva no tenga ya un pago registrado
    if (empty($errores)) {
        try {
            $sql_pago = $conexion->prepare("SELECT COUNT(*) FROM pagos WHERE reserva_id = :id_reserva AND estado = 'pagado'");
            $sql_pago->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql_pago->execute();
            if ($sql_pago->fetchColumn() > 0) {
                $errores[] = "La reserva #{$id_reserva} ya ha sido pagada anteriormente.";
            }
        } catch (PDOException $e) {
            error_log("Error al verificar pagos: " . $e->getMessage());
            $errores[] = "Error al verificar el estado de pago de la reserva.";
        }
    }

    // Obtener configuración del hotel (impuesto y moneda)
    $porcentaje_impuesto = 12.00;
    $moneda = 'USD';
    if (empty($errores)) {
        try {
            $sql_conf = $conexion->prepare("SELECT porcentaje_impuesto, moneda FROM configuracion_hotel LIMIT 1");
            $sql_conf->execute();
            $config = $sql_conf->fetch(PDO::FETCH_OBJ);
            if ($config) {
                $porcentaje_impuesto = (float) $config->porcentaje_impuesto;
                $moneda = $config->moneda;
            }
        } catch (PDOException $e) {
            error_log("Error al obtener configuración del hotel: " . $e->getMessage());
        }
    }


    // Manejamos la subida del comprobante de pago caso de haber uno
    $comprobante_nombre = null; // Cambiado a null por defecto
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
         // Obtenemos la ruta temporal donde el servidor almacenó el archivo inmediatamente
        $comprobante_temp = $_FILES['comprobante']['tmp_name'];
        
        // Extraemos la extensión del archivo original (ej: jpg, png, gif)
        $comprobante_extension = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);

        // VALIDAR TIPO DE ARCHIVO (solo comprobantes permitidos)
        // Definimos las extensiones válidas para evitar subir archivos peligroso
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp','pdf'];

        // Convertimos la extensión a minúsculas para comparar sin importar mayúsculas/minúsculas
        // Si la extensión NO está en el array de permitidas, agregamos error
        if (!in_array(strtolower($comprobante_extension), $extensiones_permitidas)) {
            $errores[] = "El archivo debe ser una imagen válida (jpg, jpeg, png, gif, webp,pdf)";
        }
        
        // ---------------------------------------------------------
        // VALIDAR TAMAÑO (máximo 2MB)
        // ---------------------------------------------------------
        // Definimos el límite en bytes (2MB = 2097152 bytes)
        $tamano_maximo = 2097152;
        
        // Si el archivo supera el tamaño permitido, agregamos error
        if ($_FILES['comprobante']['size'] > $tamano_maximo) {
            $errores[] = "El comprobante no debe superar los 2MB";
        }

        // GENERAR NOMBRE ÚNICO PARA EL ARCHIVO
        $comprobante_nombre = uniqid("comprobante_", true) . "." . $comprobante_extension;

        if (empty($errores)) {
            move_uploaded_file($comprobante_temp, __DIR__ . "/../../app/comprobantes_pagos/" . $comprobante_nombre);
        }
    }

    // Si no hay errores, registrar el pago
    if (empty($errores)) {
        try {
            $conexion->beginTransaction();

            // Insertar el pago en la tabla pagos
            $sql = $conexion->prepare("INSERT INTO pagos (reserva_id, usuario_id, monto, metodo, estado, comprobante_referencia) VALUES (:reserva_id, :usuario_id, :monto, :metodo, 'pagado', :comprobante)");
            $sql->bindParam(":reserva_id", $id_reserva, PDO::PARAM_INT);
            $sql->bindParam(":usuario_id", $id_recepcionista, PDO::PARAM_INT);
            $sql->bindParam(":monto", $monto, PDO::PARAM_STR);
            $sql->bindParam(":metodo", $metodo, PDO::PARAM_STR);
            $sql->bindParam(":comprobante", $comprobante_nombre, PDO::PARAM_STR);
            $sql->execute();

            // Insertar datos en tabla factura
            $sec = $conexion->prepare("SELECT COALESCE(MAX(id), 0) + 1 FROM facturas");
            $sec->execute();
            $num_fac = 'FACT-' . str_pad($sec->fetchColumn(), 6, '0', STR_PAD_LEFT);
            $impuestos = $monto * ($porcentaje_impuesto / 100);
            $total = $monto + $impuestos;
            $sql=$conexion->prepare("INSERT INTO facturas (reserva_id, numero_factura,cliente_documento,cliente_nombre,cliente_direccion,subtotal,impuestos,total) VALUES (:id_reserva, :num_fac, :cliente_documento, :cliente_nombre, :cliente_direccion, :subtotal, :impuestos, :total)");
            $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
            $sql->bindParam(":num_fac", $num_fac, PDO::PARAM_STR);
            $sql->bindParam(":cliente_documento", $reserva_existente->cliente_documento, PDO::PARAM_STR);
            $sql->bindParam(":cliente_nombre", $reserva_existente->cliente_nombre, PDO::PARAM_STR);
            $sql->bindParam(":cliente_direccion", $reserva_existente->cliente_direccion, PDO::PARAM_STR);
            $sql->bindParam(":subtotal", $monto, PDO::PARAM_STR);
            $sql->bindParam(":impuestos", $impuestos, PDO::PARAM_STR);
            $sql->bindParam(":total", $total, PDO::PARAM_STR);
            $sql->execute();

            // Notificación al cliente
            $msg_cliente = "Se ha registrado el pago de su reserva #{$id_reserva} por {$moneda} {$monto} (método: " . ucfirst($metodo) . "). Factura #{$num_fac}.";
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            $sql_notif->bindParam(':uid', $reserva_existente->cliente_id, PDO::PARAM_INT);
            $sql_notif->bindParam(':msg', $msg_cliente, PDO::PARAM_STR);
            $sql_notif->execute();

            // Notificación al staff activo (admins y recepcionistas)
            $sql_staff = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista') AND estado = 'activo'");
            $sql_staff->execute();
            $staff = $sql_staff->fetchAll(PDO::FETCH_OBJ);

            $msg_staff = "El recepcionista {$_SESSION['nombre']} registró un pago de {$moneda} {$monto} ({$metodo}) en la reserva #{$id_reserva}. Factura #{$num_fac}.";
            $sql_notif_staff = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES (:uid, :msg)");
            foreach ($staff as $miembro) {
                $sql_notif_staff->bindParam(':uid', $miembro->id, PDO::PARAM_INT);
                $sql_notif_staff->bindParam(':msg', $msg_staff, PDO::PARAM_STR);
                $sql_notif_staff->execute();
            }

            $conexion->commit();

            $_SESSION['exito'] = "El pago de la reserva #{$id_reserva} ha sido registrado correctamente.";
            header("Location: ../../recepcionista/gestion_reservas.php");
            exit;
        } catch (Exception $e) {
            $conexion->rollBack();
            // Si se subió una imagen, podríamos intentar borrarla para no dejar basura
            if($comprobante_nombre && file_exists(__DIR__ . "/../../app/comprobantes_pagos/" . $comprobante_nombre)){
                unlink(__DIR__ . "/../../app/comprobantes_pagos/" . $comprobante_nombre);
            }
            error_log("Error al registrar el pago de la reserva #{$id_reserva}: " . $e->getMessage());
            $_SESSION['errores'] = ["Error al guardar el pago de la reserva #{$id_reserva}. Intente nuevamente."];
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
