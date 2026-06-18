<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../acceso_denegado.php");
    exit();
}

$id_cliente = $_SESSION['id_usuario'];

// Consulas SQL
try {
    // Obtener la cantidad de reservas de este cliente con estado confirmada
    $sql = "SELECT COUNT(*) AS total FROM reservas WHERE cliente_id = :id_cliente AND estado = 'confirmada'";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_OBJ);
    $cantidad_reservas_confirmadas = $resultado->total;

    // Obtener el valor total que ha gastado en las reservas que hizo este cliente que tienes estado finalizada
    $sql = "SELECT SUM(total) AS total FROM reservas WHERE cliente_id = :id_cliente AND estado = 'finalizada'";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_OBJ);
    $total_gastado = $resultado->total ?? 0.00; // Usar 0.00 si es null

    // Obtener la cantidad de servicios solicitados en las reservas finalizadas
    $sql = "SELECT COUNT(rs.servicio_id) AS total 
            FROM reservas r 
            JOIN reserva_servicio rs ON r.id = rs.reserva_id
            WHERE r.cliente_id = :id_cliente AND r.estado = 'finalizada'";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_OBJ);
    $cantidad_servicios_solicitados = $resultado->total;

    // --- Nuevas consultas para las secciones dinámicas ---

    // Últimas 3 reservas del cliente
    $sql_ultimas_reservas = "
        SELECT
            h.numero AS habitacion_numero,
            r.fecha_inicio,
            r.fecha_fin,
            r.estado,
            r.id AS reserva_id
        FROM reservas r
        JOIN reserva_habitacion rh ON r.id = rh.reserva_id
        JOIN habitaciones h ON rh.habitacion_id = h.id
        WHERE r.cliente_id = :id_cliente
        ORDER BY r.id DESC
        LIMIT 3
    ";
    $stmt = $conexion->prepare($sql_ultimas_reservas);
    $stmt->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
    $stmt->execute();
    $ultimas_reservas = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Últimos 3 servicios contratados en reservas finalizadas
    $sql_ultimos_servicios = "
        SELECT
            s.nombre AS servicio_nombre,
            rs.cantidad,
            s.precio AS servicio_precio,
            r.fecha_inicio AS fecha_contratacion
        FROM reserva_servicio rs
        JOIN servicios s ON rs.servicio_id = s.id
        JOIN reservas r ON rs.reserva_id = r.id
        WHERE r.cliente_id = :id_cliente AND r.estado = 'finalizada'
        ORDER BY rs.id DESC
        LIMIT 3
    ";
    $stmt = $conexion->prepare($sql_ultimos_servicios);
    $stmt->bindParam(":id_cliente", $id_cliente, PDO::PARAM_INT);
    $stmt->execute();
    $ultimos_servicios = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al realizar las consultas SQL: " . $e->getMessage());
    $cantidad_reservas_confirmadas = 0;
    $total_gastado = 0;
    $cantidad_servicios_solicitados = 0;
    $ultimas_reservas = [];
    $ultimos_servicios = [];
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/cliente/dash_cliente.css">

<div class="main-container">
    <!-- Saludo de bienvenida -->
    <div class="welcome-section">
        <h1>¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</h1>
        <p>Bienvenido de nuevo a tu panel de control de Hotel Horizon.</p>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-title">PRÓXIMAS RESERVAS</div>
            <div class="stat-value"><?= htmlspecialchars($cantidad_reservas_confirmadas) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-title">TOTAL GASTADO</div>
            <div class="stat-value">$<?= htmlspecialchars(number_format((float)$total_gastado, 2)) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-concierge-bell"></i></div>
            <div class="stat-title">SERVICIOS CONTRATADOS</div>
            <div class="stat-value"><?= htmlspecialchars($cantidad_servicios_solicitados) ?></div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Mis Reservas -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bed" style="margin-right: 8px; color:#3b82f6;"></i> Mis Reservas</h3>
                <a href="gestion_reservas.php">Ver todas <i class="fas fa-arrow-right"></i></a>
            </div>
            <table class="reservas-table">
                <thead>
                    <tr>
                        <th>Habitación</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimas_reservas)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">No tienes reservas recientes.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ultimas_reservas as $reserva): ?>
                            <tr>
                                <td><?= htmlspecialchars($reserva->habitacion_numero) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($reserva->fecha_inicio))) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($reserva->fecha_fin))) ?></td>
                                <td>
                                    <?php
                                    $estado_class = '';
                                    $icon = 'fa-clock';
                                    $estado = strtolower($reserva->estado);
                                    if ($estado == 'confirmada') {
                                        $estado_class = 'status-confirmada';
                                        $icon = 'fa-check-circle';
                                    } elseif ($estado == 'pendiente') {
                                        $estado_class = 'status-pendiente';
                                        $icon = 'fa-clock';
                                    } elseif ($estado == 'check-in') {
                                        $estado_class = 'status-checkin';
                                        $icon = 'fa-key';
                                    } elseif ($estado == 'cancelada') {
                                        $estado_class = 'status-cancelada';
                                        $icon = 'fa-times-circle';
                                    } elseif ($estado == 'finalizada') {
                                        $estado_class = 'status-finalizada';
                                        $icon = 'fa-flag-checkered';
                                    }
                                    ?>
                                    <span class="status-reserva <?= $estado_class ?>"><i class="fas <?= $icon ?>"></i> <?= htmlspecialchars(ucfirst($reserva->estado)) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Servicios Contratados -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-concierge-bell" style="margin-right: 8px; color:#3b82f6;"></i> Servicios Contratados</h3>
                <a href="gestion_reservas.php">Ver detalles <i class="fas fa-arrow-right"></i></a>
            </div>
            <table class="servicios-table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Cant.</th>
                        <th>Fecha</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimos_servicios)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">Sin servicios recientes.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ultimos_servicios as $servicio): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($servicio->servicio_nombre) ?></strong></td>
                                <td><?= htmlspecialchars($servicio->cantidad) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($servicio->fecha_contratacion))) ?></td>
                                <td style="text-align: right; font-weight: 700;">$<?= htmlspecialchars(number_format($servicio->servicio_precio * $servicio->cantidad, 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Gráfica de Gastos Mensuales -->
        <div class="card full-width">
            <div class="card-header">
                <h3><i class="fas fa-chart-line" style="margin-right: 8px; color:#3b82f6;"></i> Historial de Gastos</h3>
                <span style="font-size: 0.7rem; color:#5b6e8c;">Últimos 6 meses</span>
            </div>
            <canvas id="expensesChart" height="200"></canvas>
        </div>

        <!-- Acciones Rápidas -->
        <div class="card full-width">
            <div class="card-header">
                <h3><i class="fas fa-bolt" style="margin-right: 8px; color:#3b82f6;"></i> Acciones Rápidas</h3>
            </div>
            <div class="quick-actions">
                <a href="#" class="action-btn"><i class="fas fa-calendar-plus"></i> Nueva Reserva</a>
                <a href="#" class="action-btn"><i class="fas fa-concierge-bell"></i> Contratar Servicio</a>
                <a href="#" class="action-btn"><i class="fas fa-credit-card"></i> Ver Facturación</a>
                <a href="#" class="action-btn"><i class="fas fa-headset"></i> Soporte</a>
            </div>
        </div>
    </div> <!-- /dashboard-grid -->
</div> <!-- /main-container -->

<script src="<?= $base_url ?>app/js/cliente/dash_cliente.js"></script>
<?php include_once '../templates/footer.php'; ?>