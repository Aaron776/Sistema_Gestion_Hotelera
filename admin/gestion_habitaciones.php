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
    $queryTotal = $conexion->prepare("SELECT COUNT(*) FROM habitaciones");
    $queryTotal->execute();
    $total_registros = $queryTotal->fetchColumn();

    $total_paginas = ceil($total_registros / $resultados_por_pagina);
    if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $resultados_por_pagina;

    // 2. Obtener datos con paginación real
    $sql = $conexion->prepare("SELECT id as id_habitacion,numero,precio,tipo,capacidad,estado FROM habitaciones ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $sql->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $habitaciones = $sql->fetchAll(PDO::FETCH_OBJ);

    // --- ESTADÍSTICAS PARA LAS TARJETAS ---
    $qDisp = $conexion->prepare("SELECT COUNT(*) FROM habitaciones WHERE estado='disponible'");
    $qDisp->execute();
    $total_disponibles = $qDisp->fetchColumn();

    $qOcup = $conexion->prepare("SELECT COUNT(*) FROM habitaciones WHERE estado='ocupada'");
    $qOcup->execute();
    $total_ocupadas = $qOcup->fetchColumn();

    $qMant = $conexion->prepare("SELECT COUNT(*) FROM habitaciones WHERE estado='mantenimiento'");
    $qMant->execute();
    $total_mantenimiento = $qMant->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener los usuarios: " . $e->getMessage());
    $habitaciones = [];
    $total_registros = 0;
    $total_paginas = 0;
    $total_disponibles = 0;
    $total_ocupadas = 0;
    $total_mantenimiento = 0;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/gestion_habitaciones.css">

<div class="content-wrapper">
    <div class="section-container">
        <div class="section-header">
            <h2><i class="fas fa-bed"></i> Gestión de Habitaciones</h2>
            <p>Administra las habitaciones del hotel: tipos, precios, capacidad y estado actual.</p>
        </div>
        <a href="agregar_habitacion.php" class="btn-add" id="btnAgregarHabitacion"><i class="fas fa-plus-circle"></i> Nueva Habitación</a>
    </div>

    <!-- Tarjetas de Estadísticas de Inventario -->
    <div class="dashboard-cards" style="padding: 0 0 30px 0;">
        <div class="stat-card">
            <div class="card-icon" style="background: #eef2ff; color: #3b82f6;"><i class="fas fa-door-open"></i></div>
            <div class="stat-title">TOTAL HABITACIONES</div>
            <div class="stat-value"><?= $total_registros ?></div>
            <div class="stat-trend">Capacidad total</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-title">DISPONIBLES</div>
            <div class="stat-value"><?= $total_disponibles ?></div>
            <div class="stat-trend">Listas para reservar</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fff1f2; color: #e11d48;"><i class="fas fa-user-friends"></i></div>
            <div class="stat-title">OCUPADAS</div>
            <div class="stat-value"><?= $total_ocupadas ?></div>
            <div class="stat-trend">Huéspedes alojados</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fffbe6; color: #d97706;"><i class="fas fa-tools"></i></div>
            <div class="stat-title">EN MANTENIMIENTO</div>
            <div class="stat-value"><?= $total_mantenimiento ?></div>
            <div class="stat-trend">Fuera de servicio</div>
        </div>
    </div>

    <div class="table-container" style="margin-bottom: 40px;">
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
        <table id="roomsTable" class="display responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Número</th>
                    <th>Precio (USD)</th>
                    <th>Tipo</th>
                    <th>Capacidad</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($habitaciones)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <i class="fas fa-bed" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <span style="font-weight: 500; font-size: 0.95rem;">No se encontraron habitaciones registradas</span>
                                <small style="color: #94a3b8;">Haz clic en "Nueva Habitación" para agregar una al inventario.</small>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($habitaciones as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item->id_habitacion); ?></td>
                            <td><strong><?php echo htmlspecialchars($item->numero); ?></strong></td>
                            <td>$<?php echo htmlspecialchars(number_format($item->precio, 2)); ?></td>
                            <td>
                                <?php
                                $tipo = strtolower($item->tipo);
                                $iconos_tipo = [
                                    'simple'       => 'fa-user',
                                    'doble'        => 'fa-users',
                                    'matrimonial'  => 'fa-heart',
                                    'suite'        => 'fa-gem',
                                    'presidencial' => 'fa-crown'
                                ];
                                $icono_t = $iconos_tipo[$tipo] ?? 'fa-bed';
                                ?>
                                <span class="badge-tipo tipo-<?= $tipo ?>"><i class="fas <?= $icono_t ?>"></i> <?php echo htmlspecialchars(ucfirst($item->tipo)); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($item->capacidad); ?> Personas</td>
                            <td>
                                <?php
                                $estado = strtolower($item->estado);
                                $iconos_estado = [
                                    'disponible'    => 'fa-check-circle',
                                    'mantenimiento' => 'fa-tools',
                                    'ocupada'       => 'fa-door-closed'
                                ];
                                $icono_e = $iconos_estado[$estado] ?? 'fa-info-circle';
                                ?>
                                <span class="badge-estado-hab estado-<?= $estado ?>"><i class="fas <?= $icono_e ?>"></i> <?php echo htmlspecialchars(ucfirst($item->estado)); ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="editar_habitacion.php?id_habitacion=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_habitacion))); ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>

                                    <a href="gestion_mantenimientos_habitacion.php?id_habitacion=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_habitacion))); ?>" class="btn-action btn-maintenance" title="Ver mantenimiento de la habitación"><i class="fas fa-tools"></i></a>

                                    <form id="formEliminar-<?= $item->id_habitacion ?>" action="../controladores/admin/eliminar_habitacion.php" method="post" style="display:inline;">
                                        <input type="hidden" name="id_habitacion" value="<?php echo htmlspecialchars(Crypto::encrypt($item->id_habitacion)); ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <button type="button" class="btn-action btn-delete" title="Eliminar" onclick="confirmarEliminacion(<?= $item->id_habitacion ?>, '<?= htmlspecialchars($item->numero, ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_paginas > 0): ?>
            <div class="paginador-container">
                <div class="paginador-nav">
                    <a href="?pagina=<?= max(1, $pagina - 1) ?>" class="paginador-btn <?= ($pagina <= 1) ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?pagina=<?= $i ?>" class="paginador-btn <?= ($pagina == $i) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?pagina=<?= min($total_paginas, $pagina + 1) ?>" class="paginador-btn <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="paginador-info">
                    Mostrando <?= min($total_registros, $offset + 1) ?> a <?= min($total_registros, $offset + count($habitaciones)) ?> de <?= $total_registros ?> resultados
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 20px; color: #94a3b8; font-size: 0.9rem;">
                No se encontraron habitaciones registradas.
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/gestion_habitaciones.js"></script>
<?php include_once '../templates/footer.php'; ?>