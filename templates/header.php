<?php
require_once '../conexion/bd.php';
// Comprobar el estado actual de la sesión
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../conexion/session.php';
}

// Si no hay sesión iniciada, redirigir al login
if (!isset($_SESSION['rol'])) {
    header("Location: ../index.php");
    exit;
}

// Calcular la ruta base relativa hacia la raíz del proyecto
// Obtenemos el archivo que incluye este header
$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
$including_file = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : __FILE__;
$including_dir = dirname($including_file);
$root_dir = dirname(__DIR__); // Directorio raíz del proyecto

// Normalizar las rutas para que funcionen en Windows y Linux
$including_dir = str_replace('\\', '/', $including_dir);
$root_dir = str_replace('\\', '/', $root_dir);

// Calcular la ruta relativa desde el directorio del archivo que incluye el header hacia la raíz
$relative_path = str_replace($root_dir, '', $including_dir);
$relative_path = trim($relative_path, '/');
$depth = !empty($relative_path) ? substr_count($relative_path, '/') + 1 : 0;

// Construir la ruta base: si está en bodeguero/ o cajero/ o admin/, necesitamos "../", si está en la raíz, ""
$base_url = $depth > 0 ? str_repeat('../', $depth) : '';

// Obtener la página actual para marcar el menú activo
$current_page = basename($_SERVER['PHP_SELF']);

// Función para verificar si el enlace está activo
function isActive($page, $current)
{
    return $page === $current ? 'active' : '';
}

// Función mejorada para verificar si una página está activa (soporta múltiples páginas por sección)
function isMenuActive($pages, $current_page)
{
    foreach ($pages as $page) {
        if (stripos($current_page, $page) !== false) {
            return 'active';
        }
    }
    return '';
}

// Obtener informacion de hotel
$sql = $conexion->prepare("SELECT id as id_configuracion,nombre,logo_url,telefono,ruc,direccion,email,porcentaje_impuesto,moneda FROM configuracion_hotel");
$sql->execute();
$info_hotel = $sql->fetch(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= ucfirst($info_hotel->nombre) ?> | Dashboard Administrativo</title>
    <!-- Google Fonts: Poppins e Inter para estilo moderno -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 (gratis) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js para gráficas modernas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- SweetAlert2 para alertas modernas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Flatpickr para selección de fechas -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f8;
            overflow-x: hidden;
        }

        /* ========== LAYOUT INSPIRADO EN ADMIN LTE ========== */
        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* SIDEBAR MODERNO */
        .sidebar {
            width: 280px;
            background: linear-gradient(145deg, #0f172a 0%, #111827 100%);
            color: #e2e8f0;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            box-shadow: 8px 0 20px rgba(0, 0, 0, 0.06);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 28px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            background: #3b82f6;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 8px 14px rgba(59, 130, 246, 0.3);
        }

        .logo-text h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.3rem;
            font-weight: 600;
            letter-spacing: -0.3px;
            color: white;
        }

        .logo-text p {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 2px;
        }

        .sidebar-menu {
            padding: 24px 16px;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 18px;
            border-radius: 14px;
            color: #cbd5e1;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 0.95rem;
            text-decoration: none;
        }

        .nav-link i {
            width: 24px;
            font-size: 1.2rem;
            text-align: center;
        }

        .nav-link:hover {
            background: rgba(59, 130, 246, 0.15);
            color: white;
        }

        .nav-link.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 6px 12px rgba(59, 130, 246, 0.25);
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            margin-left: 280px;
            transition: all 0.3s;
            background: #f8fafc;
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            background: white;
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid #eef2f6;
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .page-title h2 {
            font-size: 1.6rem;
            font-weight: 600;
            background: linear-gradient(135deg, #1e293b, #2d3a5e);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .notification-badge {
            position: relative;
            cursor: pointer;
        }

        .badge-dot {
            position: absolute;
            top: -6px;
            right: -8px;
            background: #ef4444;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 12px;
            border: 2px solid white;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }

        .avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(145deg, #3b82f6, #2563eb);
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
        }

        /* CARDS */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            padding: 30px 30px 20px 30px;
        }

        .stat-card {
            background: white;
            border-radius: 28px;
            padding: 1.4rem 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.02), 0 1px 3px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 30px -12px rgba(0, 0, 0, 0.1);
        }

        .card-icon {
            width: 52px;
            height: 52px;
            background: #eef2ff;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #3b82f6;
        }

        .stat-title {
            color: #4b5563;
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            margin-top: 16px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            margin: 8px 0 4px;
        }

        .stat-trend {
            font-size: 0.75rem;
            color: #10b981;
        }

        /* FILAS Y GRAFICAS */
        .row-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            padding: 0 30px 30px 30px;
        }

        .chart-card,
        .recent-bookings {
            background: white;
            border-radius: 28px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.02);
            border: 1px solid #eff3f8;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }

        .card-header h4 {
            font-weight: 600;
            font-size: 1.1rem;
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

        /* TABLA RESERVAS RECIENTES */
        .booking-table {
            width: 100%;
            border-collapse: collapse;
        }

        .booking-table th {
            text-align: left;
            padding: 12px 4px 8px 0;
            font-weight: 600;
            font-size: 0.75rem;
            color: #5b6e8c;
            letter-spacing: 0.5px;
        }

        .booking-table td {
            padding: 12px 4px 12px 0;
            border-bottom: 1px solid #f0f2f8;
            font-size: 0.85rem;
            font-weight: 500;
            color: #1e293b;
        }

        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .status.confirmed {
            background: #dcfce7;
            color: #15803d;
        }

        .status.pending {
            background: #fff3e3;
            color: #b45309;
        }

        .status.checkin {
            background: #dbeafe;
            color: #1e40af;
        }

        /* ocupación extra */
        .occupancy {
            background: white;
            border-radius: 28px;
            padding: 1rem 1.5rem;
            margin: 0 30px 30px 30px;
            border: 1px solid #eff3f8;
        }

        .progress-bar-bg {
            background: #e2e8f0;
            border-radius: 40px;
            height: 12px;
            width: 100%;
            overflow: hidden;
        }

        .progress-fill {
            width: 78%;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            height: 12px;
            border-radius: 40px;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .sidebar {
                margin-left: -280px;
            }

            .main-content {
                margin-left: 0;
            }

            .row-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            .topbar {
                padding: 12px 20px;
            }
        }

        /* scroll sidebar */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #1e293b;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 6px;
        }

        footer {
            text-align: center;
            padding: 24px 30px;
            color: #6c757d;
            font-size: 0.8rem;
            border-top: 1px solid #edf2f7;
            margin-top: 10px;
        }

        /* ========== ESTILOS DE BADGES PARA HABITACIONES ========== */
        .badge-tipo {
            padding: 6px 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-transform: capitalize;
        }

        /* Colores por Tipo */
        .tipo-simple {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .tipo-doble {
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }

        .tipo-matrimonial {
            background: #fdf2f8;
            color: #be185d;
            border: 1px solid #fbcfe8;
        }

        .tipo-suite {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .tipo-presidencial {
            background: #faf5ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        .badge-estado-hab {
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Colores por Estado */
        .estado-disponible {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }

        .estado-mantenimiento {
            background: #ffedd5;
            color: #9a3412;
            border: 1px solid #fdba74;
        }

        .estado-ocupada {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* Animación suave para los badges */
        .badge-tipo:hover,
        .badge-estado-hab:hover {
            transform: translateY(-1px);
            filter: brightness(0.95);
            transition: all 0.2s;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- SIDEBAR estilo AdminLTE moderno -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-icon">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="logo-text">
                    <h3><?= htmlspecialchars(ucfirst($info_hotel->nombre)) ?></h3>
                    <p>Gestión Integral</p>
                </div>
            </div>
            <div class="sidebar-menu">
                <?php if ($_SESSION['rol'] == 'admin') { ?>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>admin/dash_admin.php" class="nav-link <?= isActive('dash_admin.php', $current_page) ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>admin/gestion_usuarios.php" class="nav-link <?= isActive('gestion_usuarios.php', $current_page) ?>">
                            <i class="fas fa-users"></i>
                            <span>Usuarios</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>admin/gestion_habitaciones.php" class="nav-link <?= isMenuActive(['habitacion'], $current_page) ?>">
                            <i class="fas fa-bed"></i>
                            <span>Habitaciones</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>admin/gestion_servicios.php" class="nav-link <?= isMenuActive(['servicio'], $current_page) ?>">
                            <i class="fas fa-utensils"></i>
                            <span>Servicios</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>admin/gestion_pagos.php" class="nav-link <?= isMenuActive(['pago'], $current_page) ?>">
                            <i class="fas fa-cash-register"></i>
                            <span>Pagos</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>admin/reportes.php" class="nav-link <?= isActive('reportes.php', $current_page) ?>">
                            <i class="fas fa-chart-line"></i>
                            <span>Reportes</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>admin/configuracion_hotel.php" class="nav-link <?= isActive('configuracion_hotel.php', $current_page) ?>">
                            <i class="fas fa-cog"></i>
                            <span>Configuración Hotel</span>
                        </a>
                    </div>
                <?php } else if ($_SESSION['rol'] == 'recepcionista') { ?>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>recepcionista/dash_recepcionista.php" class="nav-link <?= isActive('dash_recepcionista.php', $current_page) ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>recepcionista/gestion_reservas.php" class="nav-link <?= isMenuActive(['reserva'], $current_page) ?>">
                            <i class="fas fa-calendar-check"></i>
                            <span>Reservas</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>recepcionista/gestion_pagos.php" class="nav-link <?= isMenuActive(['pago'], $current_page) ?>">
                            <i class="fas fa-cash-register"></i>
                            <span>Pagos</span>
                        </a>
                    </div>
                <?php } else if ($_SESSION['rol'] == 'cliente') { ?>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>cliente/dash_cliente.php" class="nav-link <?= isActive('dash_cliente.php', $current_page) ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>cliente/gestion_reservas.php" class="nav-link <?= isMenuActive(['reserva'], $current_page) ?>">
                            <i class="fas fa-chart-line"></i>
                            <span>Mis Reservas</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>cliente/configuracion_cuenta.php" class="nav-link <?= isMenuActive(['configuracion'], $current_page) ?>">
                            <i class="fas fa-cog"></i>
                            <span>Configuración</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?= $base_url ?>cliente/cambiar_password.php" class="nav-link <?= isMenuActive(['cambiar_password'], $current_page) ?>">
                            <i class="fas fa-key"></i>
                            <span>Cambiar Contraseña</span>
                        </a>
                    </div>
                <?php } ?>
            </div>
            <div style="margin-top: auto; padding: 20px 20px 30px; border-top: 1px solid rgba(255,255,255,0.05);">
                <a href="<?= $base_url ?>controladores/logout.php" class="nav-link" style="opacity:0.8; text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span style="color: #cbd5e1;">Cerrar Sesión</span>
                </a>
            </div>
        </aside>

        <div class="main-content">
            <?php if ($_SESSION['rol'] == 'admin') { ?>
                <?php include_once 'sidebar_admin.php'; ?>
            <?php } else if ($_SESSION['rol'] == 'recepcionista') { ?>
                <?php include_once 'sidebar_recepcionista.php'; ?>
            <?php } else if ($_SESSION['rol'] == 'cliente') { ?>
                <?php include_once 'sidebar_cliente.php'; ?>
            <?php } ?>