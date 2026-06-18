<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Hotel Horizon | Página No Encontrada</title>
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
            background: linear-gradient(145deg, #f0f2f8 0%, #e8ecf4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
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
            background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, rgba(59,130,246,0) 70%);
            top: -150px;
            right: -100px;
            border-radius: 50%;
        }
        .bg-pattern::after {
            content: "";
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139,92,246,0.06) 0%, rgba(139,92,246,0) 70%);
            bottom: -200px;
            left: -150px;
            border-radius: 50%;
        }

        /* Contenedor principal */
        .notfound-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 650px;
            margin: 20px;
            text-align: center;
            animation: fadeSlideUp 0.5s ease-out;
        }

        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Código 404 con estilo */
        .error-code {
            font-size: 10rem;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec489a);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            text-shadow: 0 10px 30px rgba(59,130,246,0.2);
            letter-spacing: 12px;
            line-height: 1;
        }

        .floating-icon {
            font-size: 5rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin: 10px 0 20px;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
        }

        .error-message {
            color: #64748b;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 32px;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Buscador interno opcional */
        .search-box {
            background: white;
            border-radius: 60px;
            padding: 4px 4px 4px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 400px;
            margin: 0 auto 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            padding: 12px 0;
            font-size: 0.9rem;
            background: transparent;
        }
        .search-box button {
            background: linear-gradient(95deg, #3b82f6, #2563eb);
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .search-box button:hover {
            transform: scale(1.02);
            background: linear-gradient(95deg, #2563eb, #1d4ed8);
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
            background: linear-gradient(95deg, #3b82f6, #2563eb);
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
            box-shadow: 0 12px 24px rgba(59,130,246,0.35);
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid #3b82f6;
            padding: 14px 32px;
            border-radius: 60px;
            color: #3b82f6;
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
            background: rgba(59, 130, 246, 0.08);
            transform: translateY(-2px);
        }

        /* Enlaces útiles */
        .helpful-links {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            padding: 24px;
            margin-top: 20px;
            border: 1px solid rgba(59,130,246,0.2);
        }
        .helpful-links h4 {
            font-size: 0.9rem;
            color: #1e293b;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .links-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: center;
        }
        .links-grid a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }
        .links-grid a:hover {
            color: #1d4ed8;
            transform: translateX(3px);
        }

        /* Footer */
        .footer-note {
            margin-top: 40px;
            font-size: 0.7rem;
            color: #94a3b8;
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 5.5rem;
                letter-spacing: 6px;
            }
            .error-title {
                font-size: 1.5rem;
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

<div class="bg-pattern"></div>

<div class="notfound-container">
    <div class="error-code">404</div>
    <div class="floating-icon">
        <i class="fas fa-map-marked-alt"></i>
    </div>
    <h1 class="error-title">¡Ups! Página no encontrada</h1>
    <p class="error-message">
        La página que estás buscando no existe, fue eliminada o cambió de ubicación. 
        Te invitamos a regresar al inicio o explorar nuestras secciones principales.
    </p>

    <!-- Buscador simulado -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Buscar en Hotel Horizon...">
        <button id="searchBtn"><i class="fas fa-search"></i></button>
    </div>

    <div class="action-buttons">
        <a href="dashboard.html" class="btn-primary">
            <i class="fas fa-tachometer-alt"></i> Ir al Dashboard
        </a>
        <a href="index.html" class="btn-outline">
            <i class="fas fa-home"></i> Página de Inicio
        </a>
    </div>

    <div class="helpful-links">
        <h4><i class="fas fa-compass"></i> Enlaces útiles</h4>
        <div class="links-grid">
            <a href="dashboard.html"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="#"><i class="fas fa-bed"></i> Habitaciones</a>
            <a href="#"><i class="fas fa-calendar-check"></i> Reservas</a>
            <a href="#"><i class="fas fa-users"></i> Huéspedes</a>
            <a href="login.html"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a>
            <a href="registro.html"><i class="fas fa-user-plus"></i> Registrarse</a>
        </div>
    </div>

    <div class="footer-note">
        <i class="far fa-copyright"></i> 2026 Hotel Horizon - Sistema de Gestión Hotelera
        <br>
        <span id="currentUrl" style="font-size: 0.65rem; opacity: 0.6;"></span>
    </div>
</div>

<script>
    // Mostrar la URL actual que no se encontró (simulación)
    const currentUrlSpan = document.getElementById('currentUrl');
    if (currentUrlSpan) {
        currentUrlSpan.textContent = `URL solicitada: ${window.location.pathname}`;
    }

    // Búsqueda simulada
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    
    function simulateSearch() {
        const query = searchInput.value.trim().toLowerCase();
        if (query === "") {
            alert("Por favor ingresa un término de búsqueda.");
            return;
        }
        
        // Simulación de búsqueda interna
        const pages = {
            "dashboard": "dashboard.html",
            "login": "login.html",
            "registro": "registro.html",
            "registrarse": "registro.html",
            "habitaciones": "#",
            "reservas": "#",
            "inicio": "index.html",
            "home": "index.html"
        };
        
        let found = false;
        for (const [key, url] of Object.entries(pages)) {
            if (query.includes(key)) {
                alert(`Redirigiendo a: ${url}`);
                window.location.href = url;
                found = true;
                break;
            }
        }
        
        if (!found) {
            alert(`No se encontraron resultados para "${query}". Intenta con: dashboard, login, registro, inicio.`);
        }
    }
    
    searchBtn.addEventListener('click', simulateSearch);
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') simulateSearch();
    });
    
    // Detectar si hay sesión para personalizar mensaje
    const isLoggedIn = sessionStorage.getItem('isLoggedIn');
    if (isLoggedIn === 'true') {
        console.log("Usuario autenticado navegando a página inexistente");
    }
</script>
</body>
</html>