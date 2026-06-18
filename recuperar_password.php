<?php
require_once 'conexion/session.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Hotel Horizon | Recuperar Contraseña</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #eef2ff 0%, #f1f5f9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Fondo decorativo */
        .bg-decor {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }
        .bg-decor::before {
            content: "";
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(59,130,246,0.12) 0%, rgba(59,130,246,0) 70%);
            top: -150px;
            right: -100px;
            border-radius: 50%;
        }
        .bg-decor::after {
            content: "";
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139,92,246,0.08) 0%, rgba(139,92,246,0) 70%);
            bottom: -200px;
            left: -150px;
            border-radius: 50%;
        }

        /* Contenedor principal */
        .recover-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 520px;
            animation: fadeSlideUp 0.5s ease-out;
        }

        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Tarjeta principal */
        .recover-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(2px);
            border-radius: 48px;
            box-shadow: 0 35px 68px -20px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255,255,255,0.6);
            overflow: hidden;
            transition: transform 0.3s;
        }

        /* Encabezado con branding */
        .card-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 32px 32px 28px;
            text-align: center;
            color: white;
        }

        .logo-icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            width: 64px;
            height: 64px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 16px;
            box-shadow: 0 12px 20px -8px rgba(59,130,246,0.4);
        }

        .card-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .card-header p {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        /* Cuerpo del formulario */
        .card-body {
            padding: 40px 36px 36px;
            background: white;
        }

        .info-message {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 14px 18px;
            border-radius: 20px;
            margin-bottom: 28px;
            font-size: 0.85rem;
            color: #1e40af;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-message i {
            font-size: 1.2rem;
            color: #3b82f6;
        }

        .input-group {
            margin-bottom: 28px;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-group label i {
            margin-right: 6px;
            color: #3b82f6;
        }

        .input-field {
            width: 100%;
            padding: 16px 20px;
            border: 1.5px solid #e2e8f0;
            border-radius: 40px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #fefefe;
            outline: none;
            font-family: 'Inter', sans-serif;
        }

        .input-field:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        .input-field.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .error-message {
            font-size: 0.7rem;
            color: #ef4444;
            margin-top: 6px;
            margin-left: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Botón principal */
        .recover-btn {
            width: 100%;
            background: linear-gradient(95deg, #3b82f6, #2563eb);
            border: none;
            padding: 16px;
            border-radius: 60px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 8px 18px rgba(37,99,235,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .recover-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(37,99,235,0.35);
            background: linear-gradient(95deg, #2563eb, #1d4ed8);
        }

        .recover-btn:disabled {
            opacity: 0.7;
            transform: none;
            cursor: not-allowed;
        }
        /* Alertas de Exito y Error */
        .alert {
            padding: 16px 20px;
            border-radius: 20px;
            margin-bottom: 24px;
            font-size: 0.85rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: fadeSlideUp 0.4s ease-out;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid transparent;
            border-left: 4px solid transparent;
        }

        .alert-danger {
            background: #fef2f2;
            border-color: #fca5a5;
            border-left-color: #ef4444;
            color: #991b1b;
        }

        .alert-danger ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .alert-danger i {
            color: #ef4444;
            margin-right: 4px;
        }

        .alert-success {
            background: #ecfdf5;
            border-color: #6ee7b7;
            border-left-color: #10b981;
            color: #065f46;
            align-items: center;
        }

        .alert-success i {
            color: #10b981;
            font-size: 1.2rem;
        }

        /* Enlaces de navegación */
        .footer-links {
            text-align: center;
            border-top: 1px solid #edf2f7;
            padding-top: 24px;
            margin-top: 8px;
        }

        .footer-links a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .footer-links a:hover {
            color: #1d4ed8;
            transform: translateX(-2px);
        }

        /* Toast notification */
        .toast-message {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: #1e293b;
            color: white;
            padding: 12px 28px;
            border-radius: 60px;
            font-size: 0.85rem;
            z-index: 1100;
            opacity: 0;
            transition: all 0.3s;
            pointer-events: none;
            font-weight: 500;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toast-message.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .card-body {
                padding: 32px 24px;
            }
            .card-header {
                padding: 28px 24px;
            }
            .card-header h2 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>


<div class="recover-container">
    <div class="recover-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-key"></i>
            </div>
            <h2>¿Olvidaste tu contraseña?</h2>
            <p>Te ayudaremos a recuperar el acceso a tu cuenta</p>
        </div>

        <div class="card-body">
            <div class="info-message">
                <i class="fas fa-envelope"></i>
                <span>Ingresa el correo electrónico con el que te registraste. Te enviaremos instrucciones para generar una nueva contraseña.</span>
            </div>

            <form id="recoverForm" action="controladores/recuperar_password.php" method="POST">
                <?php if (isset($_SESSION['errores'])) : ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($_SESSION['errores'] as $error) : ?>
                                <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['errores']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['exito'])) : ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars(is_array($_SESSION['exito']) ? $_SESSION['exito'][0] : $_SESSION['exito']); ?>
                    </div>
                    <?php unset($_SESSION['exito']); ?>
                <?php endif; ?>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="input-group">
                    <label><i class="fas fa-envelope"></i> Correo electrónico registrado</label>
                    <input type="email" id="email" name="email" class="input-field" placeholder="ejemplo@hotelhorizon.com" autocomplete="email">
                    <div class="error-message" id="emailError"></div>
                </div>

                <button type="submit" class="recover-btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Enviar nueva contraseña
                </button>

                <div class="footer-links">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Volver al inicio de sesión
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>

