<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Hotel Horizon | Acceso Denegado</title>
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
            background: linear-gradient(135deg, #1e1b2e 0%, #0f0c1f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Fondo decorativo */
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
            border-radius: 50%;
            filter: blur(80px);
        }
        .shape-1 {
            width: 400px;
            height: 400px;
            background: rgba(239, 68, 68, 0.15);
            top: -150px;
            right: -100px;
        }
        .shape-2 {
            width: 500px;
            height: 500px;
            background: rgba(139, 92, 246, 0.1);
            bottom: -200px;
            left: -150px;
        }
        .shape-3 {
            width: 300px;
            height: 300px;
            background: rgba(59, 130, 246, 0.08);
            top: 40%;
            left: 30%;
        }

        /* Contenedor principal */
        .error-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 600px;
            margin: 20px;
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Código de error */
        .error-code {
            font-size: 8rem;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ef4444, #f97316);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            text-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
            letter-spacing: 8px;
        }

        .error-icon {
            font-size: 5rem;
            color: #ef4444;
            margin: 20px 0;
            filter: drop-shadow(0 8px 20px rgba(239,68,68,0.3));
        }

        .error-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 16px;
        }

        .error-message {
            color: #a0aec0;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 32px;
            max-width: 450px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .btn-primary {
            background: linear-gradient(95deg, #ef4444, #dc2626);
            border: none;
            padding: 14px 32px;
            border-radius: 60px;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(239,68,68,0.35);
            background: linear-gradient(95deg, #dc2626, #b91c1c);
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid #ef4444;
            padding: 14px 32px;
            border-radius: 60px;
            color: #ef4444;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-outline:hover {
            background: rgba(239, 68, 68, 0.1);
            transform: translateY(-2px);
        }

        /* Información adicional */
        .info-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-card p {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .info-card i {
            color: #ef4444;
            margin-right: 8px;
        }

        /* Footer */
        .footer-note {
            margin-top: 40px;
            font-size: 0.7rem;
            color: #5b6e8c;
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 5rem;
                letter-spacing: 4px;
            }
            .error-title {
                font-size: 1.4rem;
            }
            .action-buttons {
                gap: 12px;
            }
            .btn-primary, .btn-outline {
                padding: 10px 24px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

<div class="bg-shapes">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
</div>

<div class="error-container">
    <div class="error-code">403</div>
    <div class="error-icon">
        <i class="fas fa-ban"></i>
    </div>
    <h1 class="error-title">Acceso Denegado</h1>
    <p class="error-message">
        No tienes permisos suficientes para acceder a esta área del sistema. 
        Por favor, contacta con el administrador si crees que esto es un error.
    </p>

    <div class="action-buttons">
        <a href="dashboard.html" class="btn-primary">
            <i class="fas fa-home"></i> Volver al Dashboard
        </a>
        <a href="login.html" class="btn-outline">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </a>
    </div>

    <div class="info-card">
        <p><i class="fas fa-shield-alt"></i> Razones comunes: sesión expirada, privilegios insuficientes o intento de acceso no autorizado.</p>
        <p style="margin-top: 8px;"><i class="fas fa-headset"></i> Soporte: soporte@hotelhorizon.com</p>
    </div>

    <div class="footer-note">
        <i class="far fa-copyright"></i> 2026 Hotel Horizon - Sistema de Gestión Hotelera
    </div>
</div>

<script>
    // Pequeño efecto interactivo al cargar
    console.log("Acceso denegado - Página 403");
    
    // Opcional: verificar si hay sesión activa
    const isLoggedIn = sessionStorage.getItem('isLoggedIn');
    if (isLoggedIn === 'true') {
        // El usuario tiene sesión pero aún así no tiene permisos
        console.log("Usuario autenticado sin permisos suficientes");
    }
</script>
</body>
</html>