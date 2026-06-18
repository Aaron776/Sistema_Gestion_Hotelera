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
    <title>Hotel Horizon | Iniciar Sesión</title>
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
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Fondo decorativo con formas abstractas */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            background: rgba(59, 130, 246, 0.08);
            border-radius: 50%;
            filter: blur(60px);
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            top: -150px;
            right: -100px;
            background: radial-gradient(circle, #3b82f6, #60a5fa);
        }

        .shape-2 {
            width: 500px;
            height: 500px;
            bottom: -200px;
            left: -150px;
            background: radial-gradient(circle, #8b5cf6, #a78bfa);
            opacity: 0.6;
        }

        .shape-3 {
            width: 300px;
            height: 300px;
            top: 40%;
            left: 20%;
            background: #38bdf8;
            opacity: 0.1;
        }

        /* Contenedor principal del login */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1200px;
            margin: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Tarjeta moderna con efecto glassmorphism */
        .login-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(2px);
            border-radius: 56px;
            box-shadow: 0 40px 70px -25px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.5);
            display: flex;
            flex-wrap: wrap;
            overflow: hidden;
            width: 100%;
            transition: transform 0.3s ease;
        }

        /* Columna izquierda (información / branding) */
        .login-info {
            flex: 1.2;
            background: linear-gradient(145deg, #0f172a 0%, #111827 100%);
            padding: 48px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 40px;
        }

        .logo-icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            width: 52px;
            height: 52px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            box-shadow: 0 12px 20px -8px rgba(59, 130, 246, 0.4);
        }

        .logo-text h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .logo-text p {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 4px;
        }

        .welcome-text h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 30px 0 20px 0;
            line-height: 1.2;
        }

        .welcome-text p {
            opacity: 0.8;
            line-height: 1.5;
            margin-bottom: 40px;
            font-size: 0.95rem;
        }

        .feature-list {
            list-style: none;
            margin-top: 30px;
        }

        .feature-list li {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.95rem;
        }

        .feature-list i {
            width: 28px;
            color: #60a5fa;
            font-size: 1.2rem;
        }

        /* Columna derecha (formulario) */
        .login-form {
            flex: 1;
            padding: 52px 44px;
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

        .input-group {
            margin-bottom: 24px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .input-field {
            width: 100%;
            padding: 15px 18px;
            border: 1.5px solid #e2e8f0;
            border-radius: 28px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #fefefe;
            outline: none;
            font-family: 'Inter', sans-serif;
        }

        .input-field:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: 0.2s;
        }

        .toggle-password:hover {
            color: #3b82f6;
        }

        .forgot-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 28px;
        }

        .forgot-link {
            font-size: 0.8rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .login-btn {
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
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.35);
            background: linear-gradient(95deg, #2563eb, #1d4ed8);
        }

        .facebook-btn {
            width: 100%;
            background: #1877F2;
            border: none;
            padding: 15px;
            border-radius: 40px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 8px 18px rgba(24, 119, 242, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            margin-top: 16px;
        }

        .facebook-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(24, 119, 242, 0.35);
            background: #166fe5;
        }

        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .separator::before, .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .separator:not(:empty)::before { margin-right: .5em; }
        .separator:not(:empty)::after { margin-left: .5em; }

        .register-prompt {
            text-align: center;
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #edf2f7;
        }

        .register-prompt p {
            color: #5b6e8c;
            font-size: 0.9rem;
        }

        .register-link {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            margin-left: 5px;
        }

        .demo-credentials {
            background: #f1f5f9;
            border-radius: 20px;
            padding: 14px 18px;
            margin-top: 30px;
            font-size: 0.75rem;
            color: #334155;
            text-align: center;
        }

        .demo-credentials i {
            color: #3b82f6;
            margin-right: 6px;
        }

        /* Responsive */
        @media (max-width: 880px) {
            .login-card {
                flex-direction: column;
                border-radius: 40px;
            }

            .login-info {
                padding: 32px 30px;
            }

            .login-form {
                padding: 40px 32px;
            }

            .welcome-text h1 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 12px;
            }

            .login-form {
                padding: 32px 24px;
            }

            .form-header h3 {
                font-size: 1.6rem;
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

        /* Animaciones */
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

        .login-card {
            animation: fadeSlideUp 0.5s ease-out;
        }

        /* Mensaje toast personalizado */
        .toast-message {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: #1e293b;
            color: white;
            padding: 12px 24px;
            border-radius: 60px;
            font-size: 0.85rem;
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s;
            pointer-events: none;
            font-weight: 500;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .toast-message.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>

<body>

    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <!-- Lado izquierdo: branding y features -->
            <div class="login-info">
                <div>
                    <div class="logo-area">
                        <div class="logo-icon"><i class="fas fa-hotel"></i></div>
                        <div class="logo-text">
                            <h2>Hotel Horizon</h2>
                            <p>Gestión Inteligente</p>
                        </div>
                    </div>
                    <div class="welcome-text">
                        <h1>Bienvenido<br>de vuelta</h1>
                        <p>Accede a tu panel centralizado y controla cada aspecto de tu hotel: reservas, ocupación, facturación y más.</p>
                    </div>
                </div>
                <ul class="feature-list">
                    <li><i class="fas fa-chart-line"></i> <span>Dashboard analítico en tiempo real</span></li>
                    <li><i class="fas fa-calendar-check"></i> <span>Gestión de reservas y check-in/out</span></li>
                    <li><i class="fas fa-shield-alt"></i> <span>Seguridad y control de accesos</span></li>
                    <li><i class="fas fa-headset"></i> <span>Soporte 24/7 para administradores</span></li>
                </ul>
            </div>

            <!-- Lado derecho: formulario de login -->
            <div class="login-form">
                <div class="form-header">
                    <h3>Iniciar sesión</h3>
                    <p>Ingresa tus credenciales para acceder al sistema</p>
                </div>

                <form id="loginForm" action="controladores/login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
                    <div class="input-group">
                        <label for="email"><i class="fas fa-envelope" style="margin-right: 6px;"></i> Correo electrónico</label>
                        <input type="email" id="email" name="email" class="input-field" placeholder="admin@hotelhorizon.com" autocomplete="email">
                    </div>

                    <div class="input-group">
                        <label for="password"><i class="fas fa-lock" style="margin-right: 6px;"></i> Contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="input-field" placeholder="••••••••" autocomplete="current-password">
                            <i class="far fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="forgot-row">
                        <a href="recuperar_password.php" class="forgot-link" id="forgotPasswordBtn">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="login-btn" id="loginSubmitBtn">
                        <i class="fas fa-arrow-right-to-bracket"></i> Acceder al Dashboard
                    </button>

                    <div class="separator">O también</div>

                    <a href="controladores/facebook_login.php" class="facebook-btn">
                        <i class="fa-brands fa-facebook-f"></i> Iniciar sesión con Facebook
                    </a>

                    <div class="register-prompt">
                        <p>¿No tienes una cuenta? <a id="registerRedirectBtn" href="registro.php" class="register-link">Regístrate ahora</a></p>
                    </div>

                    <div class="demo-credentials">
                        <i class="fas fa-flask"></i> Credenciales de demostración: <br>
                        <strong>admin@hotelhorizon.com</strong> / <strong>123456</strong>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="toastMsg" class="toast-message">✨ Mensaje</div>

    <script>
        // Elementos del DOM
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const loginForm = document.getElementById('loginForm');
        const togglePassword = document.getElementById('togglePassword');
        const loginBtn = document.getElementById('loginSubmitBtn');
        const forgotBtn = document.getElementById('forgotPasswordBtn');
        const registerRedirect = document.getElementById('registerRedirectBtn');
        const toast = document.getElementById('toastMsg');

        // Función para mostrar notificaciones flotantes
        function showToast(message, isError = false) {
            toast.textContent = message;
            toast.style.background = isError ? '#b91c1c' : '#0f172a';
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2800);
        }

        // Toggle mostrar/ocultar contraseña
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Redirección a página de registro (simulada - se puede abrir un modal o una nueva vista)


        // Agregar estilo interactivo en el botón de submit con efecto ripple suave
        loginBtn.addEventListener('mousedown', function(e) {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 120);
        });
    </script>

</body>

</html>