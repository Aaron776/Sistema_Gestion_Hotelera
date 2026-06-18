<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$env = parse_ini_file(__DIR__ . '/../.env'); // Carga las variables de entorno

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $email = trim($_POST['email']);
    $errores = [];

    // Validacion y sanitizacion
    if (empty($email)) {
        $errores[] = "El email es requerido";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no es valido";
    } elseif (strlen($email) > 100) {
        $errores[] = "El email es muy largo";
    }



    // Verificar si el usuario que el usuario exista,este activo y que su rol sea cliente
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT estado,rol FROM usuarios WHERE email=:email AND rol='cliente' AND estado='activo'");
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->execute();
            $cliente_existe = $sql->fetch(PDO::FETCH_OBJ);
            if (empty($cliente_existe)) {
                $errores[] = "No se ha encontrado un cliente activo con ese correo";
            }
        } catch (PDOException $e) {
            error_log("Error BD recuperar_password: " . $e->getMessage());
            $errores[] = "Error interno del servidor";
        }
    }

    // Si no hay errores, crear una contraseña temporal y enviar al correo del usaurio cliente
    if (empty($errores)) {
        // Generar contraseña aleatoria segura de 8 caracteres
        $nuevaPassword = substr(bin2hex(random_bytes(4)), 0, 8);
        $hashPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        try {
            $conexion->beginTransaction();

            // 1. Actualizar contraseña en BD
            $sql = $conexion->prepare("UPDATE usuarios SET password=:password WHERE email=:email");
            $sql->bindParam(":password", $hashPassword, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->execute();

            // 2. Enviar correo con PHPMailer
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

            $mail->setFrom($env['MAIL_USER'], 'Hotel Horizon - Seguridad');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de Acceso - Hotel Horizon';
            $mail->Body    = "
                <div style='background-color: #f1f5f9; padding: 50px 20px; font-family: \"Inter\", Roboto, Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 32px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);'>
                        
                        <!-- Header con Logo -->
                        <div style='padding: 40px 30px; background: linear-gradient(135deg, #eff6ff, #dbeafe); border-bottom: 1px solid #bfdbfe; text-align: center;'>
                            <div style='margin-bottom: 15px;'>
                                <span style='font-size: 40px; color: #3b82f6;'>🏨</span>
                            </div>
                            <h1 style='margin: 0; font-size: 26px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 3px;'>
                                <span style='color: #2563eb;'>Hotel</span> Horizon
                            </h1>
                            <p style='margin: 10px 0 0 0; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 2px;'>Recuperación de Contraseña</p>
                        </div>

                        <!-- Contenido Principal -->
                        <div style='padding: 50px 40px; color: #334155;'>
                            <h2 style='margin-top: 0; color: #0f172a; font-size: 22px; font-weight: 700;'>Solicitud de Acceso</h2>
                            <p style='line-height: 1.8; color: #475569; font-size: 16px;'>Hemos recibido una solicitud para restablecer tu contraseña. A continuación, te proporcionamos una nueva clave temporal para que puedas volver a ingresar:</p>
                            
                            <!-- Box de Credenciales -->
                            <div style='margin: 40px 0; background: #f8fafc; border: 2px solid #93c5fd; border-radius: 20px; padding: 30px; text-align: center; position: relative;'>
                                <p style='margin: 0 0 12px 0; font-size: 11px; color: #3b82f6; text-transform: uppercase; font-weight: 700; letter-spacing: 1.5px;'>Nueva Contraseña Temporal</p>
                                <p style='margin: 0; font-size: 36px; font-weight: 800; color: #0f172a; letter-spacing: 6px; font-family: \"Courier New\", monospace;'>{$nuevaPassword}</p>
                            </div>

                            <div style='background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 12px; margin-bottom: 30px;'>
                                <p style='margin: 0; font-size: 14px; color: #1e40af; line-height: 1.6;'>
                                    <strong>🔒 Seguridad:</strong> Por favor, utiliza esta clave para iniciar sesión y cámbiala lo antes posible desde la configuración de tu cuenta.
                                </p>
                            </div>

                            <div style='text-align: center;'>
                                <a href='{$env['BASE_URL']}/login.php' style='display: inline-block; background: linear-gradient(95deg, #3b82f6, #2563eb); color: #ffffff; text-decoration: none; padding: 16px 36px; border-radius: 50px; font-weight: 700; font-size: 15px; box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);'>Ir al Login</a>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div style='padding: 30px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;'>
                            <p style='margin: 0; font-size: 13px; color: #64748b;'>
                                © 2026 Hotel Horizon • v1.0<br>
                                <span style='font-size: 11px; opacity: 0.7;'>Si no solicitaste este cambio, puedes ignorar este correo.</span>
                            </p>
                        </div>
                    </div>
                </div>
            ";

            $mail->send();

            // 3. Notificación en BD para el cliente
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, mensaje) VALUES ((SELECT id FROM usuarios WHERE email = :email), :msg)");
            $sql_notif->execute([':email' => $email, ':msg' => "Tu contraseña ha sido restablecida. Revisa tu correo para obtener la nueva clave."]);

            // Si llegamos aquí, todo salió bien
            $conexion->commit();

            $_SESSION['exito'] = "Contraseña actualizada, revisa tu correo electrónico para verificarla.";
            header("Location: ../recuperar_password.php");
            exit();
        } catch (Exception $e) {
            // Si el correo falla o la BD falla, revertimos TODO
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error en Reset Password para {$email}: " . $e->getMessage());

            if (isset($mail) && !empty($mail->ErrorInfo)) {
                error_log("PHPMailer error: " . $mail->ErrorInfo);
            }

            $_SESSION['errores'] = ["Error al enviar el correo. Por favor, asegúrate de estar conectado a la red, o intenta de nuevo más tarde."];
            header("Location: ../recuperar_password.php");
            exit();
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../recuperar_password.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error al recuperar la contraseña"];
    header("Location: ../recuperar_password.php");
    exit;
}
