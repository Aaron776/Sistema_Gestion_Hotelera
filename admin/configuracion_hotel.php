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

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/configuracion_hotel.css">
<div class="configuracion-hotel">
    <!-- ===== SECCIÓN SUPERIOR: CUADRO DE INFORMACIÓN ===== -->
    <div class="card-informacion">
        <div class="card-header">
            <h3>Información Actual del Hotel</h3>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <!-- Columna Izquierda: Datos -->
                <div class="info-datos">
                    <div class="fila-info">
                        <span class="etiqueta">Nombre:</span>
                        <span class="valor destacado"><?= htmlspecialchars(ucfirst($info_hotel->nombre)) ?></span>
                    </div>
                    <div class="fila-info">
                        <span class="etiqueta">RUC:</span>
                        <span class="valor"><?= htmlspecialchars($info_hotel->ruc) ?></span>
                    </div>
                    <div class="fila-info">
                        <span class="etiqueta">Dirección:</span>
                        <span class="valor"><?= htmlspecialchars(ucfirst($info_hotel->direccion)) ?></span>
                    </div>
                    <div class="fila-info">
                        <span class="etiqueta">Teléfono:</span>
                        <span class="valor"><?= htmlspecialchars($info_hotel->telefono) ?></span>
                    </div>
                    <div class="fila-info">
                        <span class="etiqueta">Email:</span>
                        <span class="valor"><?= htmlspecialchars(strtolower($info_hotel->email)) ?></span>
                    </div>
                    <div class="fila-info">
                        <span class="etiqueta">Impuesto:</span>
                        <span class="valor"><span class="badge badge-info"><?= htmlspecialchars($info_hotel->porcentaje_impuesto) ?> %</span></span>
                    </div>
                    <div class="fila-info">
                        <span class="etiqueta">Moneda:</span>
                        <span class="valor"><?php $m = $info_hotel->moneda; ?>
                            <?php if ($m === 'USD'): ?>
                                <span class="badge" style="background:#e6f4ea;color:#137333"><i class="fas fa-dollar-sign"></i> USD</span>
                            <?php elseif ($m === 'EUR'): ?>
                                <span class="badge" style="background:#e8f4fd;color:#1a73e8"><i class="fas fa-euro-sign"></i> EUR</span>
                            <?php elseif ($m === 'PEN'): ?>
                                <span class="badge" style="background:#fef3c7;color:#b45309"><i class="fas fa-sun"></i> PEN</span>
                            <?php elseif ($m === 'MXN'): ?>
                                <span class="badge" style="background:#ecfdf5;color:#065f46"><i class="fas fa-money-bill-wave"></i> MXN</span>
                            <?php elseif ($m === 'COP'): ?>
                                <span class="badge" style="background:#fef2f2;color:#991b1b"><i class="fas fa-money-bill-wave"></i> COP</span>
                            <?php elseif ($m === 'CLP'): ?>
                                <span class="badge" style="background:#fdf2f8;color:#9d174d"><i class="fas fa-money-bill-wave"></i> CLP</span>
                            <?php elseif ($m === 'ARS'): ?>
                                <span class="badge" style="background:#e0f2fe;color:#075985"><i class="fas fa-money-bill-wave"></i> ARS</span>
                            <?php elseif ($m === 'BOB'): ?>
                                <span class="badge" style="background:#f0fdfa;color:#0f766e"><i class="fas fa-money-bill-wave"></i> BOB</span>
                            <?php elseif ($m === 'PYG'): ?>
                                <span class="badge" style="background:#ecfeff;color:#0e7490"><i class="fas fa-money-bill-wave"></i> PYG</span>
                            <?php elseif ($m === 'UYU'): ?>
                                <span class="badge" style="background:#fefce8;color:#a16207"><i class="fas fa-money-bill-wave"></i> UYU</span>
                            <?php else: ?>
                                <span class="badge badge-success"><?= htmlspecialchars($m) ?></span>
                            <?php endif; ?></span>
                    </div>
                </div>

                <!-- Columna Derecha: Logo -->
                <div class="info-logo">
                    <small>Logo Actual</small>
                    <img src="../app/logo/<?php echo $info_hotel->logo_url; ?>" alt="Logo del Hotel">
                </div>
            </div>
        </div>
    </div>

    <!-- ===== SECCIÓN INFERIOR: FORMULARIO ===== -->
    <div class="card-formulario">
        <div class="card-header">
            <h3>Editar Información del Hotel</h3>
        </div>
        <div class="card-body">
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
            <form action="../controladores/admin/configuracion_hotel.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_configuracion" value="<?= htmlspecialchars(Crypto::encrypt($info_hotel->id_configuracion)) ?>">
                <div class="form-grid">
                    <!-- Nombre -->
                    <div class="campo">
                        <label>Nombre del Hotel <span>*</span></label>
                        <input type="text" name="nombre" placeholder="Ej: Hotel Paraíso" value="<?= htmlspecialchars(ucfirst($info_hotel->nombre)) ?>">
                    </div>

                    <!-- RUC -->
                    <div class="campo">
                        <label>RUC <span>*</span></label>
                        <input type="text" name="ruc" placeholder="Ej: 1234567890001" value="<?php echo $info_hotel->ruc; ?>">
                    </div>

                    <!-- Dirección -->
                    <div class="campo">
                        <label>Dirección <span>*</span></label>
                        <input type="text" name="direccion" placeholder="Ej: Av. Principal 123" value="<?php echo $info_hotel->direccion; ?>">
                    </div>

                    <!-- Teléfono -->
                    <div class="campo">
                        <label>Teléfono <span>*</span></label>
                        <input type="text" name="telefono" placeholder="Ej: +593 99 999 9999" value="<?php echo $info_hotel->telefono; ?>">
                    </div>

                    <!-- Email -->
                    <div class="campo">
                        <label>Email <span>*</span></label>
                        <input type="email" name="email" placeholder="Ej: info@hotelparaiso.com" value="<?php echo $info_hotel->email; ?>">
                    </div>

                    <!-- Porcentaje Impuesto -->
                    <div class="campo">
                        <label>Impuesto (%) <span>*</span></label>
                        <div class="input-grupo">
                            <input type="number" name="porcentaje_impuesto" min="0" max="100" step="0.01" placeholder="12" value="<?php echo $info_hotel->porcentaje_impuesto; ?>">
                            <span class="input-adorno">%</span>
                        </div>
                    </div>

                    <!-- Moneda -->
                    <div class="campo">
                        <label>Moneda <span>*</span></label>
                        <select name="moneda">
                            <option value="">-- Seleccionar --</option>
                            <option value="USD" <?= $info_hotel->moneda === 'USD' ? 'selected' : '' ?>>USD - Dólar estadounidense</option>
                            <option value="EUR" <?= $info_hotel->moneda === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                            <option value="PEN" <?= $info_hotel->moneda === 'PEN' ? 'selected' : '' ?>>PEN - Sol peruano</option>
                            <option value="MXN" <?= $info_hotel->moneda === 'MXN' ? 'selected' : '' ?>>MXN - Peso mexicano</option>
                            <option value="COP" <?= $info_hotel->moneda === 'COP' ? 'selected' : '' ?>>COP - Peso colombiano</option>
                            <option value="CLP" <?= $info_hotel->moneda === 'CLP' ? 'selected' : '' ?>>CLP - Peso chileno</option>
                            <option value="ARS" <?= $info_hotel->moneda === 'ARS' ? 'selected' : '' ?>>ARS - Peso argentino</option>
                            <option value="BOB" <?= $info_hotel->moneda === 'BOB' ? 'selected' : '' ?>>BOB - Boliviano</option>
                            <option value="PYG" <?= $info_hotel->moneda === 'PYG' ? 'selected' : '' ?>>PYG - Guaraní paraguayo</option>
                            <option value="UYU" <?= $info_hotel->moneda === 'UYU' ? 'selected' : '' ?>>UYU - Peso uruguayo</option>
                        </select>
                    </div>

                    <!-- Logo -->
                    <div class="campo campo-completo">
                        <label>Logo del Hotel</label>
                        <input type="file" name="logo_url" id="logoInput" accept="image/png, image/jpeg, image/jpg, image/webp">
                        <small>Formatos: PNG, JPG, WEBP. Tamaño máximo: 2MB</small>
                        <div class="logo-preview" id="logoPreview">
                            <img src="" alt="Vista previa">
                            <span class="logo-preview-placeholder">Vista previa del logo</span>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="botones-form">
                    <button type="reset" class="btn-cancelar">Restablecer</button>
                    <button type="submit" class="btn-guardar">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script src="<?= $base_url ?>app/js/admin/configuracion_hotel.js"></script>
<?php include_once '../templates/footer.php'; ?>