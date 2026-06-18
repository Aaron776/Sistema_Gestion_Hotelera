<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';
require_once '../../helpers/Encriptar.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['direccion']) && isset($_POST['email']) && isset($_FILES['logo_url']) && isset($_POST['telefono']) && isset($_POST['ruc']) && isset($_POST['porcentaje_impuesto']) && isset($_POST['moneda']) && isset($_POST['id_configuracion'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_configuracion = Crypto::decrypt(trim($_POST['id_configuracion']));
    $nombre = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $ruc = trim($_POST['ruc']);
    $porcentaje_impuesto = trim($_POST['porcentaje_impuesto']);
    $moneda = trim($_POST['moneda']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9#.\- ]+$/", $nombre)){
        $errores[]="El nombre contiene caracteres no permitidos (solo letras, números, #, . y -)";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre es muy largo";
    }

    if(!empty($telefono)){
        if(!preg_match("/^[0-9]+$/", $telefono)){
            $errores[]="El telefono solo puede contener numeros";
        }elseif(strlen($telefono)>20){
            $errores[]="El telefono es muy largo";
        }
    }

    if(!empty($email)){
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errores[]="El email no es valido";
        }elseif(strlen($email)>100){
            $errores[]="El email es muy largo";
        }
    }
    
    if(empty($ruc)){
        $errores[]="El RUC es requerido";
    }elseif(!preg_match("/^[0-9]+$/", $ruc)){
        $errores[]="El RUC solo puede contener numeros";
    }elseif(strlen($ruc)>20){
        $errores[]="El RUC es muy largo";
    }

    if(!empty($porcentaje_impuesto)){
        if(!preg_match("/^[0-9.]+$/", $porcentaje_impuesto)){
            $errores[]="El porcentaje de impuesto solo puede contener numeros y puntos";
        }
        if($porcentaje_impuesto<0 || $porcentaje_impuesto>100){
            $errores[]="El porcentaje de impuesto debe estar entre 0 y 100";
        }
    }

    $monedas=['USD', 'EUR', 'PEN','MXN','COP','CLP','ARS','BOB','PYG','UYU'];
    if(!empty($moneda)){
        if(strlen($moneda)>10){
            $errores[]="La moneda es muy larga";
        }elseif(!in_array($moneda, $monedas)){
            $errores[]="La moneda no es valida";
        }
    }

    if(!empty($direccion)){
        if(strlen($direccion)>255){
            $errores[]="La direccion es muy larga";
        }
    }

    // Manejamos la subida de la imagen caso de haber una
    $imagen_nombre = null;
    $hay_nueva_imagen = false;

    if (isset($_FILES['logo_url']) && $_FILES['logo_url']['error'] === UPLOAD_ERR_OK) {
        $imagen_temp = $_FILES['logo_url']['tmp_name'];
        $imagen_extension = pathinfo($_FILES['logo_url']['name'], PATHINFO_EXTENSION);
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array(strtolower($imagen_extension), $extensiones_permitidas)) {
            $errores[] = "El archivo debe ser una imagen válida (jpg, jpeg, png, gif, webp)";
        }
        
        $tamano_maximo = 2097152;
        if ($_FILES['logo_url']['size'] > $tamano_maximo) {
            $errores[] = "La imagen no debe superar los 2MB";
        }

        $imagen_nombre = uniqid("logo_", true) . "." . $imagen_extension;

        if (empty($errores)) {
            move_uploaded_file($imagen_temp, __DIR__ . "/../../app/logo/" . $imagen_nombre);
            $hay_nueva_imagen = true;
        }
    }
        

    // Si no hay errores, actualizar la información del hotel
    if(empty($errores)){
        try{
            $conexion->beginTransaction(); // Iniciamos la transacción

            // 1. Actualizar la información del hotel
            if ($hay_nueva_imagen) {
                $sql = $conexion->prepare("UPDATE configuracion_hotel SET nombre=:nombre, ruc=:ruc, direccion=:direccion, telefono=:telefono, email=:email, logo_url=:logo_url, porcentaje_impuesto=:porcentaje_impuesto, moneda=:moneda WHERE id=:id_configuracion");
                $sql->bindParam(":logo_url", $imagen_nombre, PDO::PARAM_STR);
            } else {
                $sql = $conexion->prepare("UPDATE configuracion_hotel SET nombre=:nombre, ruc=:ruc, direccion=:direccion, telefono=:telefono, email=:email, porcentaje_impuesto=:porcentaje_impuesto, moneda=:moneda WHERE id=:id_configuracion");
            }

            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":ruc", $ruc, PDO::PARAM_STR);
            $sql->bindParam(":direccion", $direccion, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":porcentaje_impuesto", $porcentaje_impuesto, PDO::PARAM_STR);
            $sql->bindParam(":moneda", $moneda, PDO::PARAM_STR);
            $sql->bindParam(":id_configuracion", $id_configuracion, PDO::PARAM_INT);
            $sql->execute();

            // 2. Obtener todos los destinatarios (Administradores y Recepcionistas)
            $stmt_users = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista')");
            $stmt_users->execute();
            $usuarios_notif = $stmt_users->fetchAll(PDO::FETCH_OBJ);

            // 3. Registrar Notificación individual para cada uno
            $mensaje_notif = "El administrador " . $_SESSION['nombre'] . " ha modificado la información del hotel";
            
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) 
                                           VALUES (:id_usuario, :mensaje)");

            foreach ($usuarios_notif as $user) {
                $sql_notif->bindParam(":id_usuario", $user->id, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            // Si todo salió bien, confirmamos los cambios
            $conexion->commit();

            $_SESSION['exito']="¡Información del hotel actualizada exitosamente!";
            header("Location: ../../admin/configuracion_hotel.php");
            exit;
        }catch(PDOException $e){
            // Si algo falla, revertimos los cambios en la BD
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            
            // Si se subió una imagen, podríamos intentar borrarla para no dejar basura
            if($imagen_nombre && file_exists(__DIR__ . "/../../app/logo/" . $imagen_nombre)){
                unlink(__DIR__ . "/../../app/logo/" . $imagen_nombre);
            }

            error_log("Error en transacción de hotel: " . $e->getMessage());
            $errores[]="Error crítico al guardar en la base de datos. Se han revertido los cambios.";
            $_SESSION['errores']=$errores;
            header("Location: ../../admin/configuracion_hotel.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/configuracion_hotel.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/configuracion_hotel.php");
    exit;
}



?>