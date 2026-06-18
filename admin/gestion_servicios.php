<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';


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
    $queryTotal = $conexion->prepare("SELECT COUNT(*) FROM servicios");
    $queryTotal->execute();
    $total_registros = $queryTotal->fetchColumn();

    $total_paginas = ceil($total_registros / $resultados_por_pagina);
    if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $resultados_por_pagina;

    // 2. Obtener datos con paginación real
    $sql = $conexion->prepare("SELECT id as id_servicio,nombre,precio FROM servicios ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $sql->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $servicios = $sql->fetchAll(PDO::FETCH_OBJ);

    // --- ESTADÍSTICAS PARA LAS TARJETAS ---
    // Promedio de precios
    $qAvg = $conexion->prepare("SELECT AVG(precio) FROM servicios");
    $qAvg->execute();
    $avg_precio = (float)$qAvg->fetchColumn();

    // Servicio con mayor precio
    $qMax = $conexion->prepare("SELECT nombre, precio FROM servicios ORDER BY precio DESC LIMIT 1");
    $qMax->execute();
    $max_servicio = $qMax->fetch(PDO::FETCH_OBJ);

    // Servicio con menor precio
    $qMin = $conexion->prepare("SELECT nombre, precio FROM servicios ORDER BY precio ASC LIMIT 1");
    $qMin->execute();
    $min_servicio = $qMin->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los servicios: " . $e->getMessage());
    $servicios = [];
    $total_registros = 0;
    $total_paginas = 0;
    $avg_precio = 0;
    $max_servicio = null;
    $min_servicio = null;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/gestion_servicios.css">

<div class="content-wrapper">
    <div class="btn-add-container">
        <a href="agregar_servicio.php" class="btn-add"><i class="fas fa-plus-circle"></i> Nuevo Servicio</a>
    </div>

    <!-- Tarjetas de Estadísticas de Servicios -->
    <div class="dashboard-cards" style="padding: 0 0 30px 0;">
        <div class="stat-card">
            <div class="card-icon" style="background: #eef2ff; color: #3b82f6;"><i class="fas fa-utensils"></i></div>
            <div class="stat-title">TOTAL SERVICIOS</div>
            <div class="stat-value"><?= $total_registros ?></div>
            <div class="stat-trend">Catálogo activo</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-title">PRECIO PROMEDIO</div>
            <div class="stat-value">$<?= number_format($avg_precio, 2) ?></div>
            <div class="stat-trend">Costo por servicio</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fff1f2; color: #e11d48;"><i class="fas fa-arrow-up"></i></div>
            <div class="stat-title">MÁS COSTOSO</div>
            <div class="stat-value" style="font-size: 1.4rem;"><?= $max_servicio ? htmlspecialchars($max_servicio->nombre) : 'N/A' ?></div>
            <div class="stat-trend">$<?= $max_servicio ? number_format($max_servicio->precio, 2) : '0.00' ?> (USD)</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fffbe6; color: #d97706;"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-title">MÁS ECONÓMICO</div>
            <div class="stat-value" style="font-size: 1.4rem;"><?= $min_servicio ? htmlspecialchars($min_servicio->nombre) : 'N/A' ?></div>
            <div class="stat-trend">$<?= $min_servicio ? number_format($min_servicio->precio, 2) : '0.00' ?> (USD)</div>
        </div>
    </div>

    <div class="table-container">
        <?php if (isset($_SESSION['errores'])) : ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($_SESSION['errores'] as $error) : ?>
                        <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errores']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['exito'])) : ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars(is_array($_SESSION['exito']) ? $_SESSION['exito'][0] : $_SESSION['exito']); ?>
            </div>
            <?php unset($_SESSION['exito']); ?>
        <?php endif; ?>
        <table class="services-table" id="servicesTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre del Servicio</th>
                    <th>Precio (USD)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($servicios)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <i class="fas fa-box-open" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <span style="font-weight: 500; font-size: 0.95rem;">No se encontraron servicios registrados</span>
                                <small style="color: #94a3b8;">Haz clic en "Nuevo Servicio" para agregar uno al catálogo.</small>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($servicios as $item) { ?>
                        <tr>
                            <td><?= htmlspecialchars($item->id_servicio) ?></td>
                            <td class="service-name"><?= htmlspecialchars($item->nombre) ?></td>
                            <td class="service-price">$<?= htmlspecialchars(number_format($item->precio, 2)) ?></td>
                            <td class="action-buttons">
                                <a href="editar_servicio.php?id_servicio=<?= Crypto::encrypt($item->id_servicio) ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i></a>
                                <form id="formEliminar-<?= $item->id_servicio ?>" action="../controladores/admin/eliminar_servicio.php" method="POST">
                                    <input type="hidden" name="id_servicio" value="<?= Crypto::encrypt($item->id_servicio) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="button" class="btn-action btn-delete" title="Eliminar" onclick="confirmarEliminacion(<?= $item->id_servicio ?>, '<?= htmlspecialchars($item->nombre, ENT_QUOTES) ?>')"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
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
                    Mostrando <?= ($total_registros > 0 ? $offset + 1 : 0) ?> a <?= min($offset + $resultados_por_pagina, $total_registros) ?> de <?= $total_registros ?> servicios
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/gestion_servicios.js"></script>
<?php include_once '../templates/footer.php'; ?>