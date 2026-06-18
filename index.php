<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Hotel Horizon | Bienvenidos al Sistema de Gestión</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- AOS Library para animaciones suaves -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            overflow-x: hidden;
            color: #1e293b;
        }

        /* Animaciones y scroll suave */
        html {
            scroll-behavior: smooth;
        }

        /* Navbar moderna y transparente con glassmorphism */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            z-index: 1000;
            padding: 1rem 2rem;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            width: 44px;
            height: 44px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: white;
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4);
        }

        .logo-text h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0f172a, #2d3a5e);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .logo-text span {
            font-size: 0.7rem;
            font-weight: 500;
            color: #5b6e8c;
            letter-spacing: 1px;
        }

        .nav-buttons {
            display: flex;
            gap: 16px;
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid #3b82f6;
            color: #3b82f6;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.25s;
            text-decoration: none;
        }

        .btn-outline:hover {
            background: #3b82f6;
            color: white;
            box-shadow: 0 8px 18px rgba(59, 130, 246, 0.25);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(95deg, #3b82f6, #2563eb);
            border: none;
            color: white;
            padding: 10px 28px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.25s;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 22px rgba(37, 99, 235, 0.4);
            background: linear-gradient(95deg, #2563eb, #1d4ed8);
        }

        /* HERO SECTION */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px 6% 80px;
            background: radial-gradient(circle at 10% 20%, rgba(239, 246, 255, 0.6) 0%, #ffffff 90%);
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
        }

        .hero-content {
            flex: 1;
            min-width: 280px;
        }

        .hero-badge {
            background: #eef2ff;
            display: inline-block;
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #2563eb;
            margin-bottom: 24px;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(125deg, #0f172a, #2c3e66);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 20px;
        }

        .hero-content p {
            font-size: 1.1rem;
            color: #475569;
            line-height: 1.5;
            margin-bottom: 32px;
            max-width: 500px;
        }

        .hero-buttons {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        .btn-large {
            padding: 14px 36px;
            font-size: 1rem;
        }

        .hero-image {
            flex: 1;
            min-width: 280px;
            text-align: center;
        }

        .hero-image img {
            max-width: 100%;
            border-radius: 48px;
            box-shadow: 0 30px 40px -25px rgba(0, 0, 0, 0.2);
        }

        /* Features Section */
        .features {
            padding: 90px 6%;
            background: #fafcff;
        }

        .section-title {
            text-align: center;
            margin-bottom: 64px;
        }

        .section-title h3 {
            font-size: 2.3rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
        }

        .section-title p {
            color: #5b6e8c;
            max-width: 600px;
            margin: 12px auto 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            max-width: 1300px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            border-radius: 32px;
            padding: 2rem 1.8rem;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid #eef2f8;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 40px -20px rgba(59, 130, 246, 0.2);
            border-color: #cbdffc;
        }

        .feature-icon {
            background: #eef2ff;
            width: 70px;
            height: 70px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2rem;
            color: #3b82f6;
        }

        .feature-card h4 {
            font-size: 1.4rem;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: #5c6f8c;
            line-height: 1.5;
        }

        /* CTA MODERNA */
        .cta-section {
            background: linear-gradient(110deg, #0f172a 0%, #1e293b 100%);
            margin: 40px 6% 80px;
            border-radius: 60px;
            padding: 70px 40px;
            text-align: center;
            color: white;
        }

        .cta-section h2 {
            font-size: 2.2rem;
            margin-bottom: 18px;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 36px;
            flex-wrap: wrap;
        }

        .btn-white {
            background: white;
            color: #0f172a;
            padding: 12px 32px;
            border-radius: 60px;
            font-weight: 700;
            border: none;
        }

        .btn-white-outline {
            background: transparent;
            border: 1.5px solid white;
            color: white;
            padding: 12px 32px;
            border-radius: 60px;
            font-weight: 600;
        }

        /* Footer */
        footer {
            background: #f8fafc;
            padding: 40px 6% 30px;
            border-top: 1px solid #e9edf2;
            text-align: center;
            color: #5b6c8e;
        }

        /* Modales (Login y Registro) - elegantes */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            transition: all 0.2s;
        }

        .modal-content {
            background: white;
            max-width: 460px;
            width: 90%;
            border-radius: 48px;
            padding: 32px 28px;
            box-shadow: 0 30px 50px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: fadeUp 0.3s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close-modal {
            position: absolute;
            top: 24px;
            right: 28px;
            font-size: 1.6rem;
            cursor: pointer;
            color: #94a3b8;
            transition: 0.2s;
        }

        .modal h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .input-group {
            margin: 22px 0 16px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px;
            border-radius: 60px;
            border: 1px solid #e2e8f0;
            font-size: 1rem;
            outline: none;
            transition: 0.2s;
            background: #fefefe;
        }

        .input-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .modal-btn {
            width: 100%;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 8px;
        }

        .modal-switch {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .modal-switch a {
            color: #3b82f6;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        @media (max-width: 780px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .navbar {
                padding: 0.8rem 1.2rem;
            }

            .hero {
                padding: 100px 5% 60px;
            }

            .cta-section h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar moderna -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-hotel"></i></div>
                <div class="logo-text">
                    <h2>Hotel Horizon</h2>
                    <span>Gestión Inteligente</span>
                </div>
            </div>
            <div class="nav-buttons">
                <a href="login.php" class="btn-outline" id="loginBtnNav"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</a>
                <a href="registro.php" class="btn-primary" id="registerBtnNav"><i class="fas fa-user-plus"></i> Registrarse</a>
            </div>
        </div>
    </nav>

    <!-- Hero (Home/Welcome) -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content" data-aos="fade-right" data-aos-duration="800">
                <div class="hero-badge"><i class="fas fa-crown"></i> Liderazgo hotelero</div>
                <h1>Bienvenido a<br>Hotel Horizon Suite</h1>
                <p>El sistema todo-en-uno para administrar reservas, habitaciones, huéspedes y finanzas. Moderno, rápido y elegante. Accede a tu dashboard y potencia tu hotel.</p>
                <div class="hero-buttons">
                    <a href="registro.php" class="btn-primary btn-large" id="heroRegisterBtn"><i class="fas fa-user-check"></i> Crear cuenta</a>
                    <a href="login.php" class="btn-outline btn-large" id="heroLoginBtn"><i class="fas fa-arrow-right-to-bracket"></i> Iniciar sesión</a>
                </div>
                <div style="margin-top: 28px; font-size: 0.8rem; color:#6c86a3;">
                    <i class="fas fa-shield-alt"></i> Gestión segura · Demo interactiva
                </div>
            </div>
            <div class="hero-image" data-aos="fade-left" data-aos-duration="900">
                <!-- Imagen de hotel real -->
                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=500&q=80" alt="Hotel Horizon" style="border-radius: 40px; box-shadow: 0 30px 40px -25px rgba(0,0,0,0.3); width: 100%; object-fit: cover;">
            </div>
        </div>
    </section>

    <!-- Sección de características -->
    <div class="features">
        <div class="section-title" data-aos="fade-up">
            <h3>Experiencia todo en uno</h3>
            <p>Herramientas profesionales para la gestión hotelera del siglo XXI</p>
        </div>
        <div class="features-grid">
            <div class="feature-card" data-aos="zoom-in" data-aos-delay="100">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <h4>Analytics en tiempo real</h4>
                <p>Métricas de ocupación, ingresos diarios y tendencias con gráficos intuitivos.</p>
            </div>
            <div class="feature-card" data-aos="zoom-in" data-aos-delay="200">
                <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
                <h4>Reservas y check-in</h4>
                <p>Control total de disponibilidad, reservas online y check-in express.</p>
            </div>
            <div class="feature-card" data-aos="zoom-in" data-aos-delay="300">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h4>Gestión de huéspedes</h4>
                <p>Base de datos centralizada, preferencias e historial de estancias.</p>
            </div>
            <div class="feature-card" data-aos="zoom-in" data-aos-delay="400">
                <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
                <h4>Reportes financieros</h4>
                <p>Estado de cuentas, facturación y proyecciones mensuales.</p>
            </div>
        </div>
    </div>

    <!-- Call to action con doble botón -->
    <div class="cta-section" data-aos="flip-up">
        <h2>¿Listo para transformar tu hotel?</h2>
        <p style="opacity:0.9;">Únete a cientos de hoteleros que ya optimizan su operación con Horizon.</p>
        <div class="cta-buttons">
            <button class="btn-white" id="ctaRegisterBtn"><i class="fas fa-user-plus"></i> Registrarse ahora</button>
            <button class="btn-white-outline" id="ctaLoginBtn"><i class="fas fa-lock-open"></i> Acceder al panel</button>
        </div>
    </div>

    <footer>
        <p><i class="far fa-copyright"></i> 2026 Hotel Horizon - Sistema de Gestión Hotelera. Todos los derechos reservados. <br> Innovación y hospitalidad.</p>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Inicializar AOS
        AOS.init({
            once: true,
            duration: 800
        });

        // Elementos modales
        const loginModal = document.getElementById('loginModal');
        const registerModal = document.getElementById('registerModal');

        // Botones que abren login
        const loginBtns = ['loginBtnNav', 'heroLoginBtn', 'ctaLoginBtn'];
        loginBtns.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.addEventListener('click', () => {
                loginModal.style.display = 'flex';
            });
        });

        // Botones que abren registro
        const registerBtns = ['registerBtnNav', 'heroRegisterBtn', 'ctaRegisterBtn'];
        registerBtns.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.addEventListener('click', () => {
                registerModal.style.display = 'flex';
            });
        });

        // Cerrar modales con X
        document.getElementById('closeLoginModal').onclick = () => {
            loginModal.style.display = 'none';
        };
        document.getElementById('closeRegisterModal').onclick = () => {
            registerModal.style.display = 'none';
        };
        window.onclick = (e) => {
            if (e.target === loginModal) loginModal.style.display = 'none';
            if (e.target === registerModal) registerModal.style.display = 'none';
        };

        // Switch entre modales
        document.getElementById('switchToRegister').onclick = (e) => {
            e.preventDefault();
            loginModal.style.display = 'none';
            registerModal.style.display = 'flex';
        };
        document.getElementById('switchToLogin').onclick = (e) => {
            e.preventDefault();
            registerModal.style.display = 'none';
            loginModal.style.display = 'flex';
        };

        // Acción login: redirigir al dashboard (que es la página anterior o dashboard creado)
        const doLogin = document.getElementById('doLoginBtn');
        doLogin.addEventListener('click', () => {
            const email = document.getElementById('loginEmail').value;
            // Simulación de autenticación simple (demo)
            if (email.trim() !== "") {
                // Redirigir al dashboard (podemos abrir la vista de dashboard que creamos o simular)
                // En entorno real sería otra URL, pero aquí abrimos el dashboard creado previamente (si está disponible)
                // Como el dashboard está en el mismo contexto, podemos redirigir a una página hipotética o mostrar un mensaje.
                // Para no romper la experiencia, mostramos alerta y recargamos página? Mejor redirigir a la misma página pero con 'dashboard' 
                // Simulamos navegación al dashboard integrado (si el código anterior está en otro archivo, pero al estar en diferentes contextos, 
                // asumimos que la página del dashboard se encuentra en el mismo dominio. Para una demo completa, mostramos un mensaje de éxito y abrimos la pagina dashboard en nueva pestaña? 
                // Con el fin de mantener coherencia y dado que me solicitaste anteriormente un dashboard estilo AdminLTE, podemos redirigir a la vista que hicimos.
                alert(`Bienvenido ${email}, redirigiendo al Panel de Control Hotelero.`);
                // Redirigimos al dashboard que creamos anteriormente (se debe guardar en mismo proyecto, pero aquí simulamos)
                // Si deseamos mostrar el dashboard real podemos enlazar. Por practicidad, abrimos una nueva ventana con el dashboard en blanco o redirigimos esta misma.
                // Como no tenemos un archivo separado, podemos reemplazar el contenido actual con el dashboard, pero mejor lo dejamos como enlace ilustrativo.
                // Indicamos que en un entorno real se abriría el dashboard.
                window.location.href = "dashboard.html"; // Intentamos redirigir a la página del dashboard.
                // Si no existe, se quedará igual. Para la demo, mostramos mensaje y opcionalmente abrimos el código del dashboard en new tab
                setTimeout(() => {
                    if (window.location.pathname.includes("dashboard")) return;
                    // fallback: Mostrar sugerencia
                    alert("Demo integrada: si deseas ver el dashboard, copia el código HTML anterior. Ahora simulamos ingreso.");
                }, 100);
            } else {
                alert("Ingresa un correo válido.");
            }
        });

        // Acción registro con feedback
        const doRegister = document.getElementById('doRegisterBtn');
        doRegister.addEventListener('click', () => {
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const pass = document.getElementById('regPassword').value;
            if (name && email && pass) {
                alert(`¡Registro exitoso ${name}! Ahora puedes iniciar sesión.`);
                registerModal.style.display = 'none';
                loginModal.style.display = 'flex';
                document.getElementById('loginEmail').value = email;
            } else {
                alert("Por favor completa todos los campos.");
            }
        });

        // Cambiar navbar al hacer scroll (opcional efecto)
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 20) {
                navbar.style.background = 'rgba(255,255,255,0.96)';
                navbar.style.boxShadow = '0 8px 25px rgba(0,0,0,0.05)';
            } else {
                navbar.style.background = 'rgba(255,255,255,0.85)';
                navbar.style.boxShadow = 'none';
            }
        });
    </script>
</body>

</html>