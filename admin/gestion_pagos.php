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

// Configuración del paginador
$resultados_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = 0;

try {
    // 1. Obtener total de registros
    $queryTotal = $conexion->prepare("SELECT COUNT(*) FROM pagos");
    $queryTotal->execute();
    $total_registros = $queryTotal->fetchColumn();

    $total_paginas = ceil($total_registros / $resultados_por_pagina);
    if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $resultados_por_pagina;

    // 2. Obtener datos con paginación real
    $sql = $conexion->prepare("SELECT 
            pagos.id as id_pago,
            pagos.reserva_id as reserva_id,
            recep.nombre as recepcionista,
            pagos.monto as monto,
            pagos.metodo as metodo_pago,
            pagos.estado as estado_pago,
            pagos.fecha as fecha_pago,
            pagos.comprobante_referencia as comprobante,
            cliente.nombre as cliente 
        FROM pagos 
        INNER JOIN reservas ON pagos.reserva_id = reservas.id 
        INNER JOIN usuarios AS recep ON pagos.usuario_id = recep.id 
        INNER JOIN usuarios AS cliente ON reservas.cliente_id = cliente.id 
        ORDER BY pagos.fecha DESC LIMIT :limit OFFSET :offset");
    $sql->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $pagos = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los pagos: " . $e->getMessage());
    $pagos = [];
    $total_registros = 0;
    $total_paginas = 0;
}

// Obtener el total de pago con estado pagado
try {
    $queryTotalPagado = $conexion->prepare("SELECT COUNT(*) FROM pagos WHERE estado = 'pagado'");
    $queryTotalPagado->execute();
    $total_pagado = $queryTotalPagado->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener el total de pagos: " . $e->getMessage());
    $total_pagado = 0;
}

// Obtener el total de pago con estado pendiente
try {
    $queryTotalPendiente = $conexion->prepare("SELECT COUNT(*) FROM pagos WHERE estado = 'pendiente'");
    $queryTotalPendiente->execute();
    $total_pendiente = $queryTotalPendiente->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener el total de pagos: " . $e->getMessage());
    $total_pendiente = 0;
}

// Obtener la suma total de la columna monto de los pagos en estado pagado
try {
    $queryTotalPagos = $conexion->prepare("SELECT SUM(monto) as total_pagado FROM pagos WHERE estado = 'pagado'");
    $queryTotalPagos->execute();
    $total_pagos = $queryTotalPagos->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener el total de pagos: " . $e->getMessage());
    $total_pagos = 0;
}

// Obtener la suma total de la columna monto de los pagos en estado pagado del mes actual
try {
    $queryTotalPagosMes = $conexion->prepare("SELECT SUM(monto) as total_pagado FROM pagos WHERE estado = 'pagado' AND fecha >= DATE_TRUNC('month', CURRENT_DATE) AND fecha < (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month')");
    $queryTotalPagosMes->execute();
    $total_pagos_mes = $queryTotalPagosMes->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener el total de pagos: " . $e->getMessage());
    $total_pagos_mes = 0;
}
include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/gestion_pagos.css">

<div class="content-wrapper">
    <!-- Tarjetas de resumen -->
    <div class="dashboard-cards" style="padding: 0 0 30px 0;">
        <div class="stat-card">
            <div class="card-icon" style="background: #eef2ff; color: #3b82f6;"><i class="fas fa-chart-line"></i></div>
            <div class="stat-title">TOTAL RECAUDADO</div>
            <div class="stat-value">$<?php echo htmlspecialchars(number_format($total_pagos, 2)); ?></div>
            <div class="stat-trend">Todos los pagos registrados</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-title">PAGOS COMPLETADOS</div>
            <div class="stat-value"><?php echo htmlspecialchars($total_pagado); ?></div>
            <div class="stat-trend">Pagos exitosos</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fffbeb; color: #d97706;"><i class="fas fa-clock"></i></div>
            <div class="stat-title">PAGOS PENDIENTES</div>
            <div class="stat-value"><?php echo htmlspecialchars($total_pendiente); ?></div>
            <div class="stat-trend">Aguardando confirmación</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #eef2ff; color: #3b82f6;"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-title">ESTE MES</div>
            <div class="stat-value">$<?php echo htmlspecialchars(number_format($total_pagos_mes, 2)); ?></div>
            <div class="stat-trend">Pagos del mes actual</div>
        </div>
    </div>

    <!-- Tabla de pagos -->
    <div class="table-container">
        <table class="payments-table" id="paymentsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Reserva</th>
                    <th>Recepcionista</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Método</th>
                    <th>Comprobante</th>
                    <th>Fecha de Pago</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($pagos)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <i class="fas fa-money-check-alt" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <span style="font-weight: 500; font-size: 0.95rem;">No se encontraron pagos registrados</span>
                                <small style="color: #94a3b8;">Los cobros realizados a los huéspedes aparecerán en esta lista.</small>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pagos as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item->id_pago); ?></td>
                            <td>RES-<?php echo htmlspecialchars($item->reserva_id); ?> - <?php echo htmlspecialchars($item->cliente); ?></td>
                            <td><?php echo htmlspecialchars($item->recepcionista); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format($item->monto, 2)); ?></td>
                            <td>
                                <?php if (strtolower($item->estado_pago) == 'pagado'): ?>
                                    <span class="status-badge status-pagado"><i class="fas fa-check-circle"></i> Pagado</span>
                                <?php elseif (strtolower($item->estado_pago) == 'pendiente'): ?>
                                    <span class="status-badge status-pendiente"><i class="fas fa-clock"></i> Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (strtolower($item->metodo_pago) == 'efectivo'): ?>
                                    <span class="method-badge method-efectivo"><i class="fas fa-money-bill-wave"></i> Efectivo</span>
                                <?php elseif (strtolower($item->metodo_pago) == 'tarjeta'): ?>
                                    <span class="method-badge method-tarjeta"><i class="fas fa-credit-card"></i> Tarjeta</span>
                                <?php elseif (strtolower($item->metodo_pago) == 'transferencia'): ?>
                                    <span class="method-badge method-transferencia"><i class="fas fa-university"></i> Transferencia</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item->comprobante)): ?>
                                    <a href="../app/comprobantes_pagos/<?php echo htmlspecialchars($item->comprobante); ?>" target="_blank" class="btn-comprobante">
                                        <i class="fas fa-file-invoice"></i> Ver Comprobante
                                    </a>
                                <?php else: ?>
                                    <span class="comprobante-vacio">Sin comprobante</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($item->fecha_pago))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_registros > 0): ?>
            <!-- Paginador UI -->
            <div class="paginador-container">
                <div class="paginador-nav">
                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=<?= $pagina - 1 ?>" class="paginador-btn"><i class="fas fa-chevron-left"></i> </a>
                    <?php else: ?>
                        <span class="paginador-btn disabled"><i class="fas fa-chevron-left"></i> </span>
                    <?php endif; ?>

                    <?php
                    $rango = 2;
                    $inicio = max(1, $pagina - $rango);
                    $fin = min($total_paginas, $pagina + $rango);

                    if ($inicio > 1): ?>
                        <a href="?pagina=1" class="paginador-btn">1</a>
                        <?php if ($inicio > 2): ?>
                            <span class="paginador-dots">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                        <?php if ($i == $pagina): ?>
                            <span class="paginador-btn active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?pagina=<?= $i ?>" class="paginador-btn"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($fin < $total_paginas): ?>
                        <?php if ($fin < $total_paginas - 1): ?>
                            <span class="paginador-dots">...</span>
                        <?php endif; ?>
                        <a href="?pagina=<?= $total_paginas ?>" class="paginador-btn"><?= $total_paginas ?></a>
                    <?php endif; ?>

                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?pagina=<?= $pagina + 1 ?>" class="paginador-btn"> <i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="paginador-btn disabled"> <i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>

                <div class="paginador-info">
                    Mostrando <?= ($total_registros > 0 ? $offset + 1 : 0) ?> a <?= min($offset + $resultados_por_pagina, $total_registros) ?> de <?= $total_registros ?> pagos
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once '../templates/footer.php'; ?>