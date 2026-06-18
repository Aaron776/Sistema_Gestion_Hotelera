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
    <title>Hotel Horizon | Registro de Usuario</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- AOS para animaciones -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
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
            padding: 40px 20px;
            position: relative;
        }

        /* Fondo decorativo */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .bg-pattern::before {
            content: "";
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0) 70%);
            top: -150px;
            right: -100px;
            border-radius: 50%;
        }

        .bg-pattern::after {
            content: "";
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.08) 0%, rgba(139, 92, 246, 0) 70%);
            bottom: -200px;
            left: -150px;
            border-radius: 50%;
        }

        /* Contenedor principal */
        .register-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
        }

        /* Tarjeta principal estilo glassmorphism moderno */
        .register-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(2px);
            border-radius: 56px;
            box-shadow: 0 35px 68px -20px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.6);
            overflow: hidden;
            display: flex;
            flex-wrap: wrap;
            transition: transform 0.3s;
        }

        /* Columna izquierda (información + beneficios) */
        .register-info {
            flex: 1.1;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 48px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }

        .logo-icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            width: 48px;
            height: 48px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 12px 18px -6px rgba(59, 130, 246, 0.4);
        }

        .logo-text h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .logo-text p {
            font-size: 0.7rem;
            opacity: 0.75;
        }

        .info-title h2 {
            font-size: 1.9rem;
            font-weight: 700;
            margin: 30px 0 20px 0;
            line-height: 1.2;
        }

        .info-title p {
            opacity: 0.85;
            line-height: 1.5;
            margin-bottom: 35px;
            font-size: 0.95rem;
        }

        .benefits-list {
            list-style: none;
        }

        .benefits-list li {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
            font-size: 0.9rem;
        }

        .benefits-list i {
            width: 28px;
            color: #60a5fa;
            font-size: 1.2rem;
        }

        /* Columna derecha: formulario */
        .register-form {
            flex: 1.4;
            padding: 48px 44px;
            background: white;
        }

        .form-header {
            margin-bottom: 32px;
        }

        .form-header h3 {
            font-size: 1.9rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .form-header p {
            color: #5b6e8c;
            font-size: 0.9rem;
        }

        /* Grid de 2 columnas para campos */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 24px;
        }

        .full-width {
            grid-column: span 2;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            letter-spacing: 0.3px;
        }

        .input-group label i {
            margin-right: 6px;
            color: #3b82f6;
            width: 18px;
        }

        .input-field {
            padding: 14px 18px;
            border: 1.5px solid #e2e8f0;
            border-radius: 28px;
            font-size: 0.9rem;
            transition: all 0.2s;
            background: #fefefe;
            outline: none;
            font-family: 'Inter', sans-serif;
            width: 100%;
        }

        .input-field:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .input-field.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .error-message {
            font-size: 0.7rem;
            color: #ef4444;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Botón registro */
        .register-btn {
            width: 100%;
            background: linear-gradient(95deg, #3b82f6, #2563eb);
            border: none;
            padding: 15px;
            border-radius: 40px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 12px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.35);
            background: linear-gradient(95deg, #2563eb, #1d4ed8);
        }

        .login-prompt {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #edf2f7;
            font-size: 0.9rem;
            color: #5b6e8c;
        }

        .login-link {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            margin-left: 5px;
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
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .toast-message.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @media (max-width: 880px) {
            .register-card {
                flex-direction: column;
                border-radius: 40px;
            }

            .register-info {
                padding: 32px 28px;
            }

            .register-form {
                padding: 36px 28px;
            }

            .form-grid {
                gap: 16px;
            }
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }

            .info-title h2 {
                font-size: 1.5rem;
            }
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
            animation: fadeUp 0.4s ease-out;
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

        /* Animación */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-card {
            animation: fadeUp 0.5s ease-out;
        }
    </style>
</head>

<body>
    <div class="register-wrapper">
        <div class="register-card" data-aos="fade-up" data-aos-duration="700">
            <!-- Columna izquierda - Branding y beneficios -->
            <div class="register-info">
                <div>
                    <div class="logo-area">
                        <div class="logo-icon"><i class="fas fa-hotel"></i></div>
                        <div class="logo-text">
                            <h3>Hotel Horizon</h3>
                            <p>Sistema de Gestión Hotelera</p>
                        </div>
                    </div>
                    <div class="info-title">
                        <h2>Únete a la experiencia<br>Hotel Horizon</h2>
                        <p>Regístrate y accede al mejor panel de administración hotelera. Control total, analítica avanzada y herramientas profesionales.</p>
                    </div>
                </div>
                <ul class="benefits-list">
                    <li><i class="fas fa-chart-simple"></i> <span>Dashboard en tiempo real</span></li>
                    <li><i class="fas fa-calendar-alt"></i> <span>Gestión de reservas y habitaciones</span></li>
                    <li><i class="fas fa-id-card"></i> <span>Control de huéspedes y documentación</span></li>
                    <li><i class="fas fa-shield-alt"></i> <span>Seguridad y soporte prioritario</span></li>
                </ul>
            </div>

            <!-- Columna derecha - Formulario de registro con todos los campos -->
            <div class="register-form">
                <div class="form-header">
                    <h3>Crear cuenta nueva</h3>
                    <p>Completa el formulario para registrarte en el sistema</p>
                </div>

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

                <form id="registerForm" action="controladores/registro.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-grid">
                        <!-- Nombre Completo -->
                        <div class="input-group full-width">
                            <label><i class="fas fa-user"></i> Nombre completo</label>
                            <input type="text" id="fullName" name="nombre" class="input-field" placeholder="Ej: María Fernanda López" autocomplete="name">
                            <div class="error-message" id="fullNameError"></div>
                        </div>

                        <!-- Cédula -->
                        <div class="input-group">
                            <label><i class="fas fa-id-card"></i> Cédula / Identificación</label>
                            <input type="text" id="cedula" name="cedula" class="input-field" placeholder="Ej: 12345678-9" autocomplete="off">
                            <div class="error-message" id="cedulaError"></div>
                        </div>

                        <!-- Teléfono -->
                        <div class="input-group">
                            <label><i class="fas fa-phone-alt"></i> Teléfono / Celular</label>
                            <input type="tel" id="telefono" name="telefono" class="input-field" placeholder="Ej: +34 612345678 o 0412-1234567">
                            <div class="error-message" id="telefonoError"></div>
                        </div>

                        <!-- Correo Electrónico -->
                        <div class="input-group full-width">
                            <label><i class="fas fa-envelope"></i> Correo electrónico</label>
                            <input type="email" id="email" name="email" class="input-field" placeholder="usuario@ejemplo.com" autocomplete="email">
                            <div class="error-message" id="emailError"></div>
                        </div>


                        <!-- Dirección (campo solicitado) -->
                        <div class="input-group full-width">
                            <label><i class="fas fa-map-marker-alt"></i> Dirección completa</label>
                            <input type="text" id="direccion" name="direccion" class="input-field" placeholder="Calle, número, ciudad, código postal" autocomplete="street-address">
                            <div class="error-message" id="direccionError"></div>
                        </div>
                    </div>

                    <!-- Campos adicionales de seguridad visual (contraseña) - aunque no se pidió, para un registro completo añadimos contraseña básica opcional? 
                pero es mejor agregar contraseña por coherencia con el sistema. Incluimos para el acceso posterior, pero respetando los campos solicitados + pass-->
                    <div class="form-grid" style="margin-top: 16px;">
                        <div class="input-group">
                            <label><i class="fas fa-lock"></i> Contraseña</label>
                            <input type="password" id="password" name="password" class="input-field" placeholder="Mínimo 6 caracteres">
                            <div class="error-message" id="passwordError"></div>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-lock"></i> Confirmar contraseña</label>
                            <input type="password" id="confirmPassword" name="confirmar_password" class="input-field" placeholder="Repite tu contraseña">
                            <div class="error-message" id="confirmPasswordError"></div>
                        </div>
                    </div>

                    <button type="submit" class="register-btn" id="registerSubmitBtn">
                        <i class="fas fa-user-check"></i> Registrarse
                    </button>

                    <div class="login-prompt">
                        ¿Ya tienes una cuenta?
                        <a id="loginRedirectBtn" href="login.php" class="login-link">Iniciar sesión</a>
                    </div>
                    <div style="font-size: 0.7rem; text-align:center; margin-top: 16px; color:#8ba0bc;">
                        <i class="fas fa-shield-alt"></i> Tus datos están protegidos.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="toastMsg" class="toast-message">✨ Mensaje</div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 600
        });

        // Elementos del formulario
        const fullNameInput = document.getElementById('fullName');
        const cedulaInput = document.getElementById('cedula');
        const telefonoInput = document.getElementById('telefono');
        const emailInput = document.getElementById('email');
        const confirmEmailInput = document.getElementById('confirmEmail');
        const direccionInput = document.getElementById('direccion');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerSubmitBtn');
        const toast = document.getElementById('toastMsg');


        // Limpiar errores de un campo específico
        function clearError(inputId, errorId) {
            const input = document.getElementById(inputId);
            const errorSpan = document.getElementById(errorId);
            if (input) input.classList.remove('error');
            if (errorSpan) errorSpan.innerHTML = '';
        }



        // Limpiar errores en tiempo real mientras se escribe
        const inputsToValidate = ['fullName', 'cedula', 'telefono', 'email', 'confirmEmail', 'direccion', 'password', 'confirmPassword'];
        inputsToValidate.forEach(field => {
            const inputElem = document.getElementById(field);
            if (inputElem) {
                inputElem.addEventListener('input', function() {
                    clearError(field, `${field}Error`);
                });
            }
        });
    </script>
</body>

</html>