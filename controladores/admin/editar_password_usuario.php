<?php
require_once '../../conexion/bd.php';
require_once '../../conexion/session.php';
require_once '../../helpers/Encriptar.php';
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$env = parse_ini_file(__DIR__ . '/../../.env'); // Carga las variables de entorno

// Verificar que tenga rol de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $id_usuario = Crypto::decrypt(trim($_POST['id_usuario']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_usuario)) {
        $errores[] = "El id del usuario es obligatorio";
    } elseif (!is_numeric($id_usuario)) {
        $errores[] = "El id del usuario debe ser numerico";
    } elseif ($id_usuario <= 0) {
        $errores[] = "El id del usuario debe ser mayor a cero";
    }

    // Verificar que el usuario a editar existe Y está activo en la base de datos
    // (No se deben poder editar la contraseña de usuarios que han sido desactivados/eliminados)
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id,email FROM usuarios WHERE id=:id AND estado='activo' LIMIT 1");
            $sql->bindParam(":id", $id_usuario, PDO::PARAM_INT);
            $sql->execute();
            $usuario_existe = $sql->fetch(PDO::FETCH_OBJ);
            if (!$usuario_existe) {
                $errores[] = "El usuario no existe o ha sido desactivado y no puede editar la contraseña.";
            }
        } catch (PDOException $e) {
            $errores[] = "Error de base de datos al verificar existencia del usuario.";
        }
    }

    // Si no hay errores procedemos a editar la contraseña del usuario
    if (empty($errores)) {
        // Generar contraseña aleatoria segura de 8 caracteres
        $nuevaPassword = substr(bin2hex(random_bytes(4)), 0, 8);
        $hashPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        try {
            $conexion->beginTransaction();

            // 1. Actualizar contraseña en BD
            $sql = $conexion->prepare("UPDATE usuarios SET password=:password WHERE id=:id_usuario");
            $sql->bindParam(":password", $hashPassword, PDO::PARAM_STR);
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            // 2. Registrar Notificación al Usuario Afectado
            $mensaje_audit = "El administrador " . $_SESSION['nombre'] . " ha restablecido tu contraseña del sistema, revisa tu bandeja de entrada.";
            $usuario_destino = $usuario_existe->id;
            $leida = 'f';

            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje, leida) 
                                           VALUES (:id_usuario, :mensaje, :leida)");
            $sql_notif->bindParam(":id_usuario", $usuario_destino, PDO::PARAM_INT);
            $sql_notif->bindParam(":mensaje", $mensaje_audit, PDO::PARAM_STR);
            $sql_notif->bindParam(":leida", $leida, PDO::PARAM_STR);
            $sql_notif->execute();

            // 3. Enviar correo con PHPMailer
            $mail = new PHPMailer(true);

            // Configuramos PHPMailer
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['MAIL_USER'];
            $mail->Password   = $env['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Opciones para evitar errores de certificado SSL en entorno local (XAMPP)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom($env['MAIL_USER'], 'Hotel Horizon - Gestión Administrativa');
            $mail->addAddress($usuario_existe->email);

            $mail->isHTML(true);
            $mail->Subject = 'Restablecimiento de Credenciales - Hotel Horizon';
            $mail->Body    = "
                <div style='background-color: #f8fafc; padding: 50px 20px; font-family: \"Inter\", \"Segoe UI\", Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 32px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);'>
                        
                        <!-- Header con Logo -->
                        <div style='padding: 40px 30px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); text-align: center;'>
                            <div style='margin-bottom: 15px;'>
                                <span style='font-size: 40px;'>🏨</span>
                            </div>
                            <h1 style='margin: 0; font-size: 24px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;'>
                                Hotel <span style='color: #3b82f6;'>Horizon</span>
                            </h1>
                            <p style='margin: 8px 0 0 0; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px;'>Departamento de Seguridad</p>
                        </div>

                        <!-- Contenido Principal -->
                        <div style='padding: 50px 40px; color: #1e293b;'>
                            <h2 style='margin-top: 0; color: #0f172a; font-size: 20px; font-weight: 700;'>Gestión de Credenciales</h2>
                            <p style='line-height: 1.6; color: #64748b; font-size: 15px;'>Se ha generado una nueva contraseña temporal para su cuenta por solicitud administrativa. Utilice los siguientes datos para acceder:</p>
                            
                            <!-- Box de Credenciales -->
                            <div style='margin: 35px 0; background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 20px; padding: 30px; text-align: center;'>
                                <p style='margin: 0 0 10px 0; font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;'>Contraseña Temporal</p>
                                <p style='margin: 0; font-size: 32px; font-weight: 700; color: #3b82f6; letter-spacing: 4px; font-family: monospace;'>{$nuevaPassword}</p>
                            </div>

                            <div style='background: #fff7ed; border-left: 4px solid #f97316; padding: 20px; border-radius: 12px; margin-bottom: 30px;'>
                                <p style='margin: 0; font-size: 13px; color: #9a3412; line-height: 1.5;'>
                                    <strong>Seguridad obligatoria:</strong> Por políticas del hotel, deberá cambiar esta clave inmediatamente después de ingresar al sistema desde su panel de perfil.
                                </p>
                            </div>

                            <div style='text-align: center; margin-top: 40px;'>
                                <a href='{$env['BASE_URL']}' style='display: inline-block; background: #3b82f6; color: #ffffff; text-decoration: none; padding: 16px 45px; border-radius: 50px; font-weight: 600; font-size: 15px; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);'>Entrar al Dashboard</a>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div style='padding: 30px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;'>
                            <p style='margin: 0; font-size: 12px; color: #94a3b8;'>
                                &copy; 2026 Hotel Horizon - Gestión de Hospitalidad<br>
                                <span style='font-size: 11px; opacity: 0.7;'>Este es un mensaje automático, por favor no respondas.</span>
                            </p>
                        </div>
                    </div>
                </div>
            ";

            $mail->send();

            // Si llegamos aquí, todo salió bien
            $conexion->commit();

            $_SESSION['exito'] = "Contraseña actualizada y enviada correctamente a: " . $usuario_existe->email;
            header("Location: ../../admin/gestion_usuarios.php");
            exit();
        } catch (Exception $e) {
            // Si el correo falla o la BD falla, revertimos TODO
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error en Reset Password para {$usuario_existe->email}: " . $e->getMessage());

            // Detectar si el error fue de PHPMailer
            $msg_error = "Error al procesar la solicitud.";
            if (isset($mail) && !empty($mail->ErrorInfo)) {
                $msg_error = "Error al enviar el correo. Detalle técnico: " . $mail->ErrorInfo;
            } else {
                $msg_error = "Error: " . $e->getMessage();
            }

            $_SESSION['errores'] = [$msg_error];
            header("Location: ../../admin/gestion_usuarios.php");
            exit();
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_usuarios.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error en el envio del formulario"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}
