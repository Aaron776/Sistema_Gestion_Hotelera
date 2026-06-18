<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

// 1. Verificación de Rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit;
}

try {
    // Obtener cantidad total de habitaciones
    $stmtTotal = $conexion->prepare("SELECT COUNT(*) FROM habitaciones");
    $stmtTotal->execute();
    $totalHabitaciones = $stmtTotal->fetchColumn();

    // Obtener cantidad de habitaciones ocupadas
    $stmtOcup = $conexion->prepare("SELECT COUNT(*) FROM habitaciones WHERE estado='ocupada'");
    $stmtOcup->execute();
    $totalOcupadas = $stmtOcup->fetchColumn();

    // Obtener cantidad de reservas que esta confirmadas y que inician el dia de hoy
    $sql = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE estado='confirmada' AND fecha_inicio=CURRENT_DATE");
    $sql->execute();
    $totalCheckinHoy = $sql->fetchColumn();

    // Obtener cantidad de reservas que esta confirmadas y que finalizan el dia de hoy
    $sql = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE estado='confirmada' AND fecha_fin=CURRENT_DATE");
    $sql->execute();
    $totalCheckoutHoy = $sql->fetchColumn();

    // Obtener ingresos totales de las reservas que estan estado finalizada del dia de hoy
    $sql = $conexion->prepare("SELECT SUM(total) FROM reservas WHERE estado='finalizada' AND fecha_fin=CURRENT_DATE");
    $sql->execute();
    $totalIngresos = $sql->fetchColumn();

    // --- 2. Reservas para hoy (Arrivals) ---
    $sqlReservasHoy = $conexion->prepare("
        SELECT h.numero as habitacion, u.nombre as huesped, r.fecha_inicio as checkin, r.estado
        FROM reservas r
        JOIN usuarios u ON r.cliente_id = u.id
        JOIN reserva_habitacion rh ON r.id = rh.reserva_id
        JOIN habitaciones h ON rh.habitacion_id = h.id
        WHERE r.fecha_inicio = CURRENT_DATE AND r.estado NOT IN ('cancelada', 'finalizada')
        ORDER BY r.id DESC LIMIT 5
    ");
    $sqlReservasHoy->execute();
    $reservasHoy = $sqlReservasHoy->fetchAll(PDO::FETCH_OBJ);

    // --- 3. Salidas para hoy (Departures) ---
    $sqlSalidasHoy = $conexion->prepare("
        SELECT h.numero as habitacion, u.nombre as huesped, r.fecha_fin as checkout, r.estado
        FROM reservas r
        JOIN usuarios u ON r.cliente_id = u.id
        JOIN reserva_habitacion rh ON r.id = rh.reserva_id
        JOIN habitaciones h ON rh.habitacion_id = h.id
        WHERE r.fecha_fin = CURRENT_DATE AND r.estado NOT IN ('cancelada')
        ORDER BY r.id DESC LIMIT 5
    ");
    $sqlSalidasHoy->execute();
    $salidasHoy = $sqlSalidasHoy->fetchAll(PDO::FETCH_OBJ);

    // --- 4. Ocupación por tipo (Chart) ---
    $sqlOcupacion = $conexion->prepare("
        SELECT tipo, COUNT(*) as cantidad
        FROM habitaciones
        WHERE estado = 'ocupada'
        GROUP BY tipo
    ");
    $sqlOcupacion->execute();
    $ocupacionData = $sqlOcupacion->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $counts = [];
    foreach ($ocupacionData as $row) {
        $labels[] = ucfirst($row['tipo']);
        $counts[] = (int)$row['cantidad'];
    }

    // --- 5. Servicios más solicitados ---
    $sqlServicios = $conexion->prepare("
        SELECT s.nombre, COUNT(rs.id) as solicitudes, SUM(rs.subtotal) as ingresos
        FROM reserva_servicio rs
        JOIN servicios s ON rs.servicio_id = s.id
        GROUP BY s.nombre
        ORDER BY solicitudes DESC LIMIT 5
    ");
    $sqlServicios->execute();
    $serviciosHoy = $sqlServicios->fetchAll(PDO::FETCH_OBJ);

    $porcentajeOcupacion = ($totalHabitaciones > 0) ? round(($totalOcupadas / $totalHabitaciones) * 100) : 0;
} catch (Exception $e) {
    error_log("Error al obtener datos del dashboard:" . $e->getMessage());
    $totalHabitaciones = $totalOcupadas = $totalCheckinHoy = $totalCheckoutHoy = $totalIngresos = $porcentajeOcupacion = 0;
    $reservasHoy = $salidasHoy = $serviciosHoy = [];
    $labels = $counts = [];
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/recepcionista/dash_recepcionista.css">

<div class="content-wrapper">
    <!-- Tarjetas de KPIs -->
    <div class="dashboard-cards">
        <div class="stat-card">
            <div class="card-icon"><i class="fas fa-bed"></i></div>
            <div class="stat-title">OCUPACIÓN HOY</div>
            <div class="stat-value"><?php echo htmlspecialchars($totalOcupadas); ?>/<?php echo htmlspecialchars($totalHabitaciones); ?></div>
        </div>
        <div class="stat-card">
            <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-title">CHECK-IN HOY</div>
            <div class="stat-value"><?php echo htmlspecialchars($totalCheckinHoy); ?></div>
        </div>
        <div class="stat-card">
            <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-title">CHECK-OUT HOY</div>
            <div class="stat-value"><?php echo htmlspecialchars($totalCheckoutHoy); ?></div>
        </div>
        <div class="stat-card">
            <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-title">INGRESOS HOY</div>
            <div class="stat-value">$<?php echo htmlspecialchars(number_format($totalIngresos, 2)); ?></div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Reservas del día -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-day"></i> Reservas para hoy</h3><a href="#">Ver todas</a>
            </div>
            <table class="reservas-table">
                <thead>
                    <tr>
                        <th>Hab.</th>
                        <th>Huésped</th>
                        <th>Check-in</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservasHoy)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 20px;">Sin ingresos pendientes hoy</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservasHoy as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r->habitacion) ?></strong></td>
                                <td><?= htmlspecialchars($r->huesped) ?></td>
                                <td><?= date('H:i', strtotime($r->checkin)) ?></td>
                                <td><span class="status-badge status-<?= strtolower($r->estado) ?>"><?= ucfirst($r->estado) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Salidas del día -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-sign-out-alt"></i> Salidas para hoy</h3><a href="#">Ver todas</a>
            </div>
            <table class="reservas-table">
                <thead>
                    <tr>
                        <th>Hab.</th>
                        <th>Huésped</th>
                        <th>Check-out</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salidasHoy)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 20px;">Sin salidas programadas hoy</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($salidasHoy as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s->habitacion) ?></strong></td>
                                <td><?= htmlspecialchars($s->huesped) ?></td>
                                <td><?= date('H:i', strtotime($s->checkout)) ?></td>
                                <td><span class="status-badge status-<?= strtolower($s->estado) ?>"><?= $s->estado == 'finalizada' ? 'Realizado' : 'Pendiente' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Acciones rápidas -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Acciones rápidas</h3>
            </div>
            <div class="quick-actions">
                <a href="gestion_reservas.php" class="action-item">
                    <div class="action-info">
                        <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                        <div class="action-text">
                            <h4>Check-in/Out</h4>
                            <p>Gestión de estados</p>
                        </div>
                    </div><span class="action-btn">Ir</span>
                </a>
                <a href="registrar_reserva.php" class="action-item">
                    <div class="action-info">
                        <div class="action-icon"><i class="fas fa-calendar-plus"></i></div>
                        <div class="action-text">
                            <h4>Nueva Reserva</h4>
                            <p>Crear para cliente</p>
                        </div>
                    </div><span class="action-btn">Crear</span>
                </a>
                <a href="gestion_pagos.php" class="action-item">
                    <div class="action-info">
                        <div class="action-icon"><i class="fas fa-credit-card"></i></div>
                        <div class="action-text">
                            <h4>Pagos</h4>
                            <p>Recaudación de caja</p>
                        </div>
                    </div><span class="action-btn">Ver</span>
                </a>
                <a href="gestion_reservas.php" class="action-item">
                    <div class="action-info">
                        <div class="action-icon"><i class="fas fa-concierge-bell"></i></div>
                        <div class="action-text">
                            <h4>Servicios</h4>
                            <p>Añadir a reserva</p>
                        </div>
                    </div><span class="action-btn">Añadir</span>
                </a>
            </div>
        </div>

        <!-- Ocupación y servicios -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Ocupación por tipo</h3>
            </div>
            <canvas id="ocupacionChart" height="180"></canvas>
            <div class="occupancy-bar">
                <div class="progress-bar-bg">
                    <div class="progress-fill" style="width: <?= $porcentajeOcupacion ?>%;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size:0.7rem;">
                    <span>Total: <?= $totalOcupadas ?> / <?= $totalHabitaciones ?> ocupadas</span>
                    <strong><?= $porcentajeOcupacion ?>%</strong>
                </div>
            </div>
        </div>

        <!-- Servicios más solicitados -->
        <div class="card full-width">
            <div class="card-header">
                <h3><i class="fas fa-concierge-bell"></i> Servicios más solicitados hoy</h3><a href="#">Ver todos</a>
            </div>
            <table class="reservas-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Solicitudes</th>
                        <th>Ingresos</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($serviciosHoy)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding: 20px;">Sin consumos registrados recientemente</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($serviciosHoy as $sh): ?>
                            <tr>
                                <td><?= htmlspecialchars($sh->nombre) ?></td>
                                <td><?= $sh->solicitudes ?></td>
                                <td><strong>$<?= number_format($sh->ingresos, 2) ?></strong></td>
                                <td><span class="status-badge status-confirmada">Activo</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Gráfico de ocupación por tipo
    const ctx = document.getElementById('ocupacionChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= empty($labels) ? "['Sin Ocupación']" : json_encode($labels) ?>,
            datasets: [{
                data: <?= empty($counts) ? "[1]" : json_encode($counts) ?>,
                backgroundColor: <?= empty($counts) ? "['#e2e8f0']" : "['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ec4899', '#14b8a6']" ?>,
                borderWidth: 0,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        font: {
                            size: 10
                        }
                    }
                }
            }
        }
    });
</script>


<?php include_once '../templates/footer.php'; ?>