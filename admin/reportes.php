<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // 1. Ocupación Actual
    $qHab = $conexion->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN estado='ocupada' THEN 1 ELSE 0 END) as ocupadas FROM habitaciones");
    $qHab->execute();
    $resHab = $qHab->fetch(PDO::FETCH_OBJ);
    $ocupacionActual = ($resHab->total > 0) ? round(($resHab->ocupadas / $resHab->total) * 100) : 0;

    // 2. Ingresos del Mes (Reservas finalizadas este mes)
    $qIngresos = $conexion->prepare("SELECT SUM(total) FROM reservas WHERE estado='finalizada' AND fecha_fin >= DATE_TRUNC('month', CURRENT_DATE)");
    $qIngresos->execute();
    $ingresosMes = $qIngresos->fetchColumn() ?: 0.00;

    // 3. Mantenimientos Activos
    $qMant = $conexion->prepare("SELECT COUNT(*) FROM mantenimientos_habitacion WHERE estado='en_proceso'");
    $qMant->execute();
    $mantenimientosActivos = $qMant->fetchColumn();

    // 4. Servicios Activos
    $qServ = $conexion->prepare("SELECT COUNT(*) FROM servicios");
    $qServ->execute();
    $serviciosActivos = $qServ->fetchColumn();

    // 5. Usuarios Totales (Staff)
    $qUsers = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE rol != 'cliente' AND estado='activo'");
    $qUsers->execute();
    $usuariosTotales = $qUsers->fetchColumn();

    // 6. Reservas Activas (Confirmadas)
    $qRes = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE estado='confirmada'");
    $qRes->execute();
    $reservasActivas = $qRes->fetchColumn();
} catch (PDOException $e) {
    error_log("Error en reportes: " . $e->getMessage());
    $ocupacionActual = $ingresosMes = $mantenimientosActivos = $serviciosActivos = $usuariosTotales = $reservasActivas = 0;
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/reportes.css">
<div class="content-wrapper">
    <div class="reports-grid">
        <!-- Tarjeta Reporte de Ocupación -->
        <div class="report-card">
            <div class="report-icon icon-ocupacion"><i class="fas fa-bed"></i></div>
            <h3>Reporte de Ocupación</h3>
            <p>Estadísticas de ocupación de habitaciones por período, tasa de ocupación y tendencias.</p>
            <div class="report-stats">
                <span class="stat-label">Ocupación actual</span>
                <span class="stat-value" id="ocupacionActual"><?= $ocupacionActual ?>%</span>
            </div>
            <a href="../reportes/reporte_ocupacion.php" target="_blank" class="btn-pdf" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-file-pdf"></i> Generar Reporte PDF
            </a>
        </div>

        <!-- Tarjeta Reporte Financiero -->
        <div class="report-card">
            <div class="report-icon icon-financiero"><i class="fas fa-dollar-sign"></i></div>
            <h3>Reporte Financiero</h3>
            <p>Ingresos totales, pagos por método, facturación mensual y proyecciones.</p>
            <div class="report-stats">
                <span class="stat-label">Ingresos del mes</span>
                <span class="stat-value" id="ingresosMes">$<?= number_format($ingresosMes, 2) ?></span>
            </div>
            <a href="../reportes/reporte_financiero.php" target="_blank" class="btn-pdf" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-file-pdf"></i> Generar Reporte PDF
            </a>
        </div>

        <!-- Tarjeta Reporte de Mantenimientos -->
        <div class="report-card">
            <div class="report-icon icon-mantenimiento"><i class="fas fa-tools"></i></div>
            <h3>Reporte de Mantenimientos</h3>
            <p>Historial de mantenimientos, habitaciones en servicio y tiempos de reparación.</p>
            <div class="report-stats">
                <span class="stat-label">Mantenimientos activos</span>
                <span class="stat-value" id="mantenimientosActivos"><?= $mantenimientosActivos ?></span>
            </div>
            <a href="../reportes/reporte_mantenimiento.php" target="_blank" class="btn-pdf" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-file-pdf"></i> Generar Reporte PDF
            </a>
        </div>

        <!-- Tarjeta Reporte de Servicios -->
        <div class="report-card">
            <div class="report-icon icon-servicios"><i class="fas fa-concierge-bell"></i></div>
            <h3>Reporte de Servicios</h3>
            <p>Servicios más solicitados, ingresos por servicio y demanda mensual.</p>
            <div class="report-stats">
                <span class="stat-label">Servicios activos</span>
                <span class="stat-value" id="serviciosActivos"><?= $serviciosActivos ?></span>
            </div>
            <a href="../reportes/reporte_servicios.php" target="_blank" class="btn-pdf" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-file-pdf"></i> Generar Reporte PDF
            </a>
        </div>

        <!-- Tarjeta Reporte de Usuarios -->
        <div class="report-card">
            <div class="report-icon icon-usuarios"><i class="fas fa-users"></i></div>
            <h3>Reporte de Usuarios</h3>
            <p>Usuarios registrados, roles, actividad reciente y permisos.</p>
            <div class="report-stats">
                <span class="stat-label">Usuarios totales</span>
                <span class="stat-value" id="usuariosTotales"><?= $usuariosTotales ?></span>
            </div>
            <a href="../reportes/reporte_usuarios.php" target="_blank" class="btn-pdf" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-file-pdf"></i> Generar Reporte PDF
            </a>
        </div>

        <!-- Tarjeta Reporte de Reservas -->
        <div class="report-card">
            <div class="report-icon icon-reservas"><i class="fas fa-calendar-check"></i></div>
            <h3>Reporte de Reservas</h3>
            <p>Reservas confirmadas, canceladas, check-ins y ocupación futura.</p>
            <div class="report-stats">
                <span class="stat-label">Reservas activas</span>
                <span class="stat-value" id="reservasActivas"><?= $reservasActivas ?></span>
            </div>
            <a href="../reportes/reporte_reservas.php" target="_blank" class="btn-pdf" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-file-pdf"></i> Generar Reporte PDF
            </a>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/reportes.js"></script>
<?php include_once '../templates/footer.php'; ?>