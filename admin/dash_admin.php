<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Consultas SQL
try {
    // Consulta total de habitacion que hay
    $sql = $conexion->prepare("SELECT COUNT(*) FROM habitaciones");
    $sql->execute();
    $total_habitaciones = $sql->fetchColumn();

    // Obtener total de habitacion en estado ocupado
    $sql = $conexion->prepare("SELECT COUNT(*) FROM habitaciones WHERE estado = 'ocupada'");
    $sql->execute();
    $total_ocupadas = $sql->fetchColumn();

    // Obtener cantidad de reservas con fecha de hoy
    $sql = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE fecha_inicio = CURRENT_DATE");
    $sql->execute();
    $total_reservas_hoy = $sql->fetchColumn();

    // Obtener la cantidad total de ingresos del mes actual de las reservas finalizadas
    $sql = $conexion->prepare("SELECT SUM(total) FROM reservas WHERE estado = 'finalizada' AND fecha_fin >= DATE_TRUNC('month', CURRENT_DATE)");
    $sql->execute();
    $total_ingresos_mes = $sql->fetchColumn() ?: 0.00;

    // Obtener total de habitaciones en mantenimiento (Sustituye a Satisfacción)
    $sql = $conexion->prepare("SELECT COUNT(*) FROM mantenimientos_habitacion WHERE estado = 'en_proceso'");
    $sql->execute();
    $total_mantenimientos = $sql->fetchColumn();

    // Obtener las últimas 5 reservas registradas con datos reales
    $sql = $conexion->prepare("
        SELECT 
            r.id, 
            u.nombre AS huesped, 
            h.numero AS habitacion, 
            r.fecha_inicio AS checkin, 
            r.estado 
        FROM reservas r
        INNER JOIN usuarios u ON r.cliente_id = u.id
        INNER JOIN reserva_habitacion rh ON r.id = rh.reserva_id
        INNER JOIN habitaciones h ON rh.habitacion_id = h.id
        ORDER BY r.id DESC 
        LIMIT 5
    ");
    $sql->execute();
    $ultimas_reservas = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener datos del dashboard: " . $e->getMessage());
    $total_habitaciones = 0;
    $total_ocupadas = 0;
    $total_reservas_hoy = 0;
    $total_ingresos_mes = 0;
    $total_mantenimientos = 0;
    $ultimas_reservas = [];
}

$ocupacion_porcentaje = ($total_habitaciones > 0) ? round(($total_ocupadas / $total_habitaciones) * 100, 1) : 0;
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/dash_admin.css">
<!-- Tarjetas de KPIs Modernas -->
<div class="dashboard-cards">
    <div class="stat-card">
        <div class="card-icon"><i class="fas fa-door-open"></i></div>
        <div class="stat-title">HABITACIONES OCUPADAS</div>
        <div class="stat-value"><?php echo htmlspecialchars($total_ocupadas); ?> <small>/ <?php echo htmlspecialchars($total_habitaciones); ?></small></div>
    </div>
    <div class="stat-card">
        <div class="card-icon"><i class="fas fa-calendar-week"></i></div>
        <div class="stat-title">RESERVAS HOY</div>
        <div class="stat-value"><?php echo htmlspecialchars($total_reservas_hoy); ?></div>
    </div>
    <div class="stat-card">
        <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-title">INGRESOS (MES)</div>
        <div class="stat-value">$<?= number_format($total_ingresos_mes, 2); ?></div>
    </div>
    <div class="stat-card">
        <div class="card-icon"><i class="fas fa-tools"></i></div>
        <div class="stat-title">EN MANTENIMIENTO</div>
        <div class="stat-value"><?php echo htmlspecialchars($total_mantenimientos); ?></div>
        <div class="stat-trend">Revisiones técnicas</div>
    </div>
</div>

<!-- gráficos y tabla recientes -->
<div class="row-grid">
    <div class="chart-card">
        <div class="card-header">
            <h4><i class="fas fa-chart-simple" style="margin-right: 8px; color:#3b82f6;"></i> Ocupación semanal</h4>
            <i class="fas fa-ellipsis-h" style="color:#94a3b8;"></i>
        </div>
        <canvas id="ocupacionChart" width="400" height="240" class="dash-canvas"></canvas>
    </div>
    <div class="chart-card">
        <div class="card-header">
            <h4><i class="fas fa-chart-line" style="margin-right: 8px; color:#10b981;"></i> Ingresos diarios (últimos 7 días)</h4>
        </div>
        <canvas id="ingresosChart" width="400" height="240" class="dash-canvas"></canvas>
    </div>
</div>

<!-- Reservas recientes + otra card combinada -->
<div class="recent-bookings dash-bookings-card">
    <div class="card-header">
        <h4><i class="fas fa-list-ul" style="margin-right: 10px;"></i> Últimas Reservas</h4>
        <a href="gestion_reservas.php" class="view-all-link">Ver todas <i class="fas fa-arrow-right"></i></a>
    </div>
    <table class="booking-table">
        <thead>
            <tr>
                <th>Huésped</th>
                <th>Habitación</th>
                <th>Check-in</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ultimas_reservas)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">No hay reservas registradas recientemente.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($ultimas_reservas as $reserva): ?>
                    <tr>
                        <td><?= htmlspecialchars($reserva->huesped) ?></td>
                        <td>Habitación <?= htmlspecialchars($reserva->habitacion) ?></td>
                        <td>
                            <?php
                            $fecha_db = $reserva->checkin;
                            $hoy = date('Y-m-d');
                            echo ($fecha_db == $hoy) ? "Hoy" : date('d/m/Y', strtotime($fecha_db));
                            ?>
                        </td>
                        <td>
                            <?php
                            $estado = strtolower($reserva->estado);
                            $clase = 'confirmed';
                            if ($estado == 'pendiente') $clase = 'pending';
                            if ($estado == 'check-in') $clase = 'checkin';
                            ?>
                            <span class="status <?= $clase ?>"><?= htmlspecialchars(ucfirst($reserva->estado)) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ocupación hotelera + alerta moderna-->
<div class="occupancy-section">
    <div class="occupancy-header">
        <div>
            <h4><i class="fas fa-percent"></i> Ocupación general del hotel</h4>
            <p class="occupancy-subtitle"><?= $ocupacion_porcentaje ?>% de ocupación actual</p>
        </div>
        <div><span class="stat-trend trend-badge-alt"><i class="fas fa-chart-simple"></i> +5% vs semana pasada</span></div>
    </div>
    <div class="progress-bar-bg occupancy-progress">
        <div class="progress-fill" style="width: <?= $ocupacion_porcentaje ?>%;"></div>
    </div>
    <div class="occupancy-details">
        <span>🛏️ Suites: 82%</span> <span>✨ Deluxe: 71%</span> <span>🏡 Estándar: 59%</span>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/dash_admin.js"></script>
<?php include_once '../templates/footer.php'; ?>