<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // 1. Obtener total de registros
    $stmt_total = $conexion->prepare("SELECT COUNT(*) FROM pagos");
    $stmt_total->execute();
    $total_registros = $stmt_total->fetchColumn();

    $resultados_por_pagina = 10;
    $total_paginas = ceil($total_registros / $resultados_por_pagina);

    // 2. Obtener página actual
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina < 1) $pagina = 1;
    if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $resultados_por_pagina;

    // 3. Obtener pagos con paginación real y aliases de tabla correctos, junto con detalles de la reserva y cliente
    $sql = $conexion->prepare('SELECT u_cliente.nombre AS cliente, u_cliente.cedula AS cliente_cedula, u_cliente.telefono AS cliente_telefono, u_cliente.email AS cliente_email, pagos.id AS id_pago, reservas.id AS id_reserva, reservas.fecha_inicio, reservas.fecha_fin, reservas.total AS reserva_total, reservas.estado AS reserva_estado, COALESCE(u_recepcionista.nombre, \'Online/Cliente\') AS recepcionista, pagos.metodo AS metodo, pagos.monto AS monto, pagos.fecha AS fecha_pago, pagos.estado AS estado, pagos.comprobante_referencia AS comprobante FROM pagos JOIN reservas ON pagos.reserva_id = reservas.id JOIN usuarios u_cliente ON reservas.cliente_id = u_cliente.id LEFT JOIN usuarios u_recepcionista ON pagos.usuario_id = u_recepcionista.id ORDER BY pagos.fecha DESC LIMIT :limit OFFSET :offset');
    $sql->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $pagos = $sql->fetchAll(PDO::FETCH_OBJ);

    // --- ESTADÍSTICAS PARA LAS TARJETAS ---
    // Total Recaudado (solo los pagados)
    $qTotal = $conexion->prepare("SELECT SUM(monto) FROM pagos WHERE estado = 'pagado'");
    $qTotal->execute();
    $stat_total = $qTotal->fetchColumn() ?: 0.00;

    // Pagos Hoy (solo los pagados de la fecha actual)
    $qHoy = $conexion->prepare("SELECT SUM(monto) FROM pagos WHERE estado = 'pagado' AND DATE(fecha) = CURRENT_DATE");
    $qHoy->execute();
    $stat_hoy = $qHoy->fetchColumn() ?: 0.00;

    // Pagos Pendientes
    $qPendientes = $conexion->prepare("SELECT COUNT(*) FROM pagos WHERE estado = 'pendiente'");
    $qPendientes->execute();
    $stat_pendientes = $qPendientes->fetchColumn() ?: 0;
} catch (PDOException $e) {
    error_log("Error al obtener los pagos: " . $e->getMessage());
    $pagos = [];
    $total_registros = 0;
    $total_paginas = 0;
    $stat_total = 0.00;
    $stat_hoy = 0.00;
    $stat_pendientes = 0;
}

include_once '../templates/header.php';
?>
<style>
    /* Estilos exclusivos para la gestión de pagos */
    .content-wrapper {
        padding: 30px;
    }

    /* Tarjetas superiores de resumen de recaudación */
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 24px;
        margin-bottom: 30px;
    }

    .summary-card {
        background: white;
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        border: 1px solid #eff3f8;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.1);
    }

    .summary-card .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
    }

    .summary-card .card-info {
        flex-grow: 1;
    }

    .summary-card h4 {
        font-size: 0.8rem;
        color: #5b6e8c;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .summary-card .value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }

    /* Contenedor y diseño de la tabla de pagos */
    .table-container {
        background: white;
        border-radius: 28px;
        padding: 20px;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.02);
        border: 1px solid #eff3f8;
        overflow-x: auto;
    }

    .pagos-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    .pagos-table thead th {
        padding: 16px 12px;
        font-weight: 600;
        color: #1e293b;
        border-bottom: 2px solid #e2e8f0;
        font-size: 0.85rem;
        text-align: left;
    }

    .pagos-table tbody td {
        padding: 14px 12px;
        vertical-align: middle;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }

    .pagos-table tbody tr:hover {
        background: #f8fafc;
    }

    /* Etiquetas de Estado de Pago */
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 40px;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-pagado {
        background: #dcfce7;
        color: #15803d;
    }

    .status-pendiente {
        background: #fef3c7;
        color: #b45309;
    }

    .status-cancelado {
        background: #fee2e2;
        color: #b91c1c;
    }

    /* Etiquetas de Método de Pago */
    .method-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 40px;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .method-efectivo {
        background: #e0f2fe;
        color: #0369a1;
    }

    .method-tarjeta {
        background: #e9d5ff;
        color: #6b21a5;
    }

    .method-transferencia {
        background: #dbeafe;
        color: #1e40af;
    }

    /* Botón para abrir la ventana de detalles */
    .btn-detail {
        background: #eef2ff;
        border: none;
        padding: 6px 16px;
        border-radius: 40px;
        color: #3b82f6;
        font-weight: 600;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-detail:hover {
        background: #3b82f6;
        color: white;
        transform: scale(1.02);
    }

    /* Estilos para el Modal de Detalle de Pago */
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
    }

    .modal-content {
        background: white;
        max-width: 500px;
        width: 90%;
        border-radius: 32px;
        padding: 32px;
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

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-header h3 {
        font-size: 1.3rem;
        font-weight: 700;
    }

    .close-modal {
        font-size: 1.5rem;
        cursor: pointer;
        color: #94a3b8;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .detail-label {
        font-weight: 600;
        color: #5b6e8c;
    }

    .detail-value {
        color: #1e293b;
    }

    /* Adaptabilidad de las tarjetas de resumen */

    @media (max-width: 768px) {
        .table-container {
            padding: 12px;
        }

        .content-wrapper {
            padding: 20px;
        }

        .summary-cards {
            grid-template-columns: 1fr 1fr;
        }
    }

    /* Paginación */
    .paginator-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 25px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
    }

    .paginator-info {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }

    .paginator-buttons {
        display: flex;
        gap: 6px;
    }

    .paginator-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 35px;
        height: 35px;
        padding: 0 10px;
        border-radius: 8px;
        background: white;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .paginator-btn:hover:not(.disabled):not(.active) {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    .paginator-btn.active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
    }

    .paginator-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>
<div class="content-wrapper">
    <!-- Tarjetas de resumen -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="card-info">
                <h4>Total Recaudado</h4>
                <div class="value" id="totalAmount">$<?= number_format($stat_total, 2, '.', ',') ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="card-icon" style="background: #eef2ff; color: #3b82f6;"><i class="fas fa-calendar-day"></i></div>
            <div class="card-info">
                <h4>Pagos Hoy</h4>
                <div class="value" id="todayAmount">$<?= number_format($stat_hoy, 2, '.', ',') ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="card-icon" style="background: #fffbeb; color: #d97706;"><i class="fas fa-clock"></i></div>
            <div class="card-info">
                <h4>Pagos Pendientes</h4>
                <div class="value" id="pendingCount"><?= $stat_pendientes ?></div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="pagos-table" id="pagosTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Reserva</th>
                    <th>Recepcionista</th>
                    <th>Método de Pago</th>
                    <th>Monto Total</th>
                    <th>Estado</th>
                    <th>Comprobante</th>
                    <th>Fecha de Pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($pagos as $item) : ?>
                    <tr>
                        <td><?= htmlspecialchars($item->id_pago) ?></td>
                        <td>RES-<?php echo htmlspecialchars($item->id_reserva); ?> - <?= htmlspecialchars($item->cliente) ?></td>
                        <td><?php echo htmlspecialchars($item->recepcionista); ?></td>
                        <td>
                            <?php
                            $config_metodos = [
                                'efectivo' => ['label' => 'Efectivo', 'class' => 'method-efectivo', 'icon' => 'fa-money-bill-wave'],
                                'tarjeta' => ['label' => 'Tarjeta', 'class' => 'method-tarjeta', 'icon' => 'fa-credit-card'],
                                'transferencia' => ['label' => 'Transferencia', 'class' => 'method-transferencia', 'icon' => 'fa-university']
                            ];
                            $m = $config_metodos[$item->metodo] ?? ['label' => $item->metodo, 'class' => '', 'icon' => 'fa-wallet'];
                            ?>
                            <span class="method-badge <?= $m['class'] ?>">
                                <i class="fas <?= $m['icon'] ?>"></i> <?= $m['label'] ?>
                            </span>
                        </td>
                        <td>$<?= htmlspecialchars(number_format($item->monto, 2, ',', '.')) ?></td>
                        <td>
                            <?php
                            $config_status = [
                                'pagado' => ['label' => 'Pagado', 'class' => 'status-pagado', 'icon' => 'fa-check-circle'],
                                'pendiente' => ['label' => 'Pendiente', 'class' => 'status-pendiente', 'icon' => 'fa-clock'],
                                'cancelado' => ['label' => 'Cancelado', 'class' => 'status-cancelado', 'icon' => 'fa-times-circle']
                            ];
                            $s = $config_status[$item->estado] ?? ['label' => $item->estado, 'class' => '', 'icon' => 'fa-info-circle'];
                            ?>
                            <span class="status-badge <?= $s['class'] ?>">
                                <i class="fas <?= $s['icon'] ?>"></i> <?= $s['label'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($item->comprobante != ""): ?>
                                <a href="../app/comprobantes_pagos/<?= htmlspecialchars($item->comprobante) ?>" target="_blank" style="text-decoration: none; color: #3b82f6;">
                                    <i class="fas fa-file-invoice"></i> Ver Archivo
                                </a>
                            <?php else: ?>
                                <span>Sin Comprobante</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('d-m-Y H:i A', strtotime($item->fecha_pago))) ?></td>
                        <td>
                            <button class="btn-detail" onclick="verDetalle(<?= $item->id_pago ?>)">
                                <i class="fas fa-info-circle"></i> Detalle
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Paginación -->
        <div class="paginator-container">
            <div class="paginator-buttons">
                <a href="?pagina=1" class="paginator-btn <?= ($pagina <= 1) ? 'disabled' : '' ?>" title="Primera página">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?pagina=<?= max(1, $pagina - 1) ?>" class="paginator-btn <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                    <i class="fas fa-angle-left"></i> Anterior
                </a>

                <?php
                $rango = 1;
                $pag_inicio = max(1, $pagina - $rango);
                $pag_fin = min($total_paginas, $pagina + $rango);

                if ($pag_inicio > 1): ?>
                    <a href="?pagina=1" class="paginator-btn">1</a>
                    <?php if ($pag_inicio > 2): ?>
                        <span class="paginator-btn disabled">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $pag_inicio; $i <= $pag_fin; $i++): ?>
                    <a href="?pagina=<?= $i ?>" class="paginator-btn <?= ($pagina == $i) ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($pag_fin < $total_paginas): ?>
                    <?php if ($pag_fin < $total_paginas - 1): ?>
                        <span class="paginator-btn disabled">...</span>
                    <?php endif; ?>
                    <a href="?pagina=<?= $total_paginas ?>" class="paginator-btn"><?= $total_paginas ?></a>
                <?php endif; ?>

                <a href="?pagina=<?= min($total_paginas, $pagina + 1) ?>" class="paginator-btn <?= ($pagina >= $total_paginas || $total_paginas <= 1) ? 'disabled' : '' ?>">
                    Siguiente <i class="fas fa-angle-right"></i>
                </a>
                <a href="?pagina=<?= $total_paginas ?>" class="paginator-btn <?= ($pagina >= $total_paginas || $total_paginas <= 1) ? 'disabled' : '' ?>" title="Última página">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </div>
            <div class="paginator-info">
                <?php
                $inicio_reg = $total_registros > 0 ? ($offset + 1) : 0;
                $fin_reg = min($offset + $resultados_por_pagina, $total_registros);
                ?>
                Mostrando <?= $inicio_reg ?> a <?= $fin_reg ?> de <?= $total_registros ?> resultados
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalle de Pago y Reserva -->
<div id="pagoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-file-invoice-dollar" style="color: #3b82f6; margin-right: 8px;"></i> Detalles Completos</h3>
            <span class="close-modal" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">

            <!-- Barra de Resumen Superior -->
            <div class="modal-info-summary">
                <div>
                    <div style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Referencia Reserva</div>
                    <div id="modalReserva" style="font-size: 1.1rem; font-weight: 800; color: #1e293b;">-</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Recibo de Pago</div>
                    <div id="modalIdPago" style="font-size: 1.1rem; font-weight: 800; color: #1e293b;">-</div>
                </div>
            </div>

            <!-- Columna Izquierda: Huésped y Reserva -->
            <div class="modal-col">
                <h4 class="modal-subtitle"><i class="fas fa-user-circle" style="color: #3b82f6;"></i> Información del Huésped</h4>
                <div class="detail-row">
                    <span class="detail-label">Nombre:</span>
                    <span class="detail-value" id="modalCliente" style="font-weight: 700;">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cédula:</span>
                    <span class="detail-value" id="modalCedula">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Teléfono:</span>
                    <span class="detail-value" id="modalTelefono">-</span>
                </div>

                <h4 class="modal-subtitle" style="margin-top: 24px;"><i class="fas fa-calendar-alt" style="color: #8b5cf6;"></i> Detalles de la Estancia</h4>
                <div class="detail-row" style="border: none;">
                    <span class="detail-label">Fechas:</span>
                    <span class="detail-value"><span id="modalCheckin">-</span> al <span id="modalCheckout">-</span></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value" id="modalReservaEstado">-</span>
                </div>
            </div>

            <!-- Columna Derecha: Detalles del Pago -->
            <div class="modal-col">
                <h4 class="modal-subtitle"><i class="fas fa-receipt" style="color: #10b981;"></i> Detalles del Cobro</h4>
                <div class="detail-row">
                    <span class="detail-label">Fecha:</span>
                    <span class="detail-value" id="modalFecha">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Atendido por:</span>
                    <span class="detail-value" id="modalRecepcionista">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Método:</span>
                    <span class="detail-value" id="modalMetodo">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Comprobante:</span>
                    <span class="detail-value" id="modalComprobante">-</span>
                </div>
                <div class="detail-row total-highlight">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <span class="detail-label" style="font-size: 0.9rem;">Monto Total</span>
                        <span id="modalEstado">-</span>
                    </div>
                    <div id="modalMonto" style="font-size: 1.8rem; font-weight: 800; color: #10b981;">-</div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 5px;">Total de la reserva: <span id="modalReservaTotal">-</span></div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const pagosData = <?= json_encode($pagos) ?>;
</script>
<script src="<?= $base_url ?>app/js/recepcionista/gestion_pagos.js"></script>
<?php include_once '../templates/footer.php'; ?>