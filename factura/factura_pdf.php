<?php

/**
 * Generador de Factura PDF - Cliente
 * Usa DomPDF para generar la factura de una reserva finalizada.
 */
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Solo clientes autenticados
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../acceso_denegado.php");
    exit;
}

// 2. Validar y desencriptar el id de la reserva
if (!isset($_GET['id_reserva'])) {
    header("Location: ../cliente/gestion_reservas.php");
    exit;
}

$id_reserva = Crypto::decrypt(trim($_GET['id_reserva']));
$id_cliente = $_SESSION['id_usuario'];

if (empty($id_reserva) || !is_numeric($id_reserva) || $id_reserva <= 0) {
    header("Location: ../cliente/gestion_reservas.php");
    exit;
}

// 3. Obtener la información de la tabla FACTURA y validación de propiedad
try {
    $sql = $conexion->prepare("
        SELECT f.*, r.fecha_inicio, r.fecha_fin, u.email as cliente_email, u.telefono as cliente_telefono
        FROM facturas f
        INNER JOIN reservas r ON f.reserva_id = r.id
        INNER JOIN usuarios u ON r.cliente_id = u.id
        WHERE f.reserva_id = :id_reserva
          AND r.cliente_id = :id_cliente
        LIMIT 1
    ");
    $sql->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
    $sql->bindParam(':id_cliente', $id_cliente,  PDO::PARAM_INT);
    $sql->execute();
    $factura = $sql->fetch(PDO::FETCH_OBJ);

    if (!$factura) {
        $_SESSION['errores'] = ["No se encontró el registro de factura para esta reserva."];
        header("Location: ../cliente/gestion_reservas.php");
        exit;
    }

    $id_reserva = $factura->reserva_id;

    // Obtener detalles de la habitación
    $sql2 = $conexion->prepare("
        SELECT h.numero as hab_numero, h.tipo as hab_tipo, 
               h.precio AS hab_precio_noche, rh.precio AS precio_pactado
        FROM reserva_habitacion rh
        LEFT JOIN habitaciones h ON rh.habitacion_id = h.id
        WHERE rh.reserva_id = :id_reserva
        LIMIT 1
    ");
    $sql2->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
    $sql2->execute();
    $habitacion = $sql2->fetch(PDO::FETCH_OBJ);

    // Obtener servicios adicionales
    $sql3 = $conexion->prepare("
        SELECT s.nombre, rs.cantidad, rs.subtotal 
        FROM reserva_servicio rs 
        INNER JOIN servicios s ON rs.servicio_id = s.id 
        WHERE rs.reserva_id = :id_reserva
    ");
    $sql3->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
    $sql3->execute();
    $servicios = $sql3->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al generar factura PDF: " . $e->getMessage());
    $_SESSION['errores'] = ["Error al obtener los datos de la factura."];
    header("Location: ../cliente/gestion_reservas.php");
    exit;
}

// 4. Obtener información del hotel
$info_hotel = null;
try {
    $stmt = $conexion->query("SELECT * FROM configuracion_hotel LIMIT 1");
    $info_hotel = $stmt->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener info hotel en factura: " . $e->getMessage());
}

// 5. Calcular valores
$fecha_inicio  = new DateTime($factura->fecha_inicio);
$fecha_fin     = new DateTime($factura->fecha_fin);
$noches        = $fecha_inicio->diff($fecha_fin)->days;
$precio_estancia = $habitacion ? ($habitacion->precio_pactado * $noches) : 0;

$porcentaje_iva = $info_hotel ? (float)$info_hotel->porcentaje_impuesto : 12.00;
$moneda        = $info_hotel ? htmlspecialchars($info_hotel->moneda) : 'USD';
$numero_factura = $factura->numero_factura;

// 6. Construir el HTML de la factura
$hotel_nombre  = $info_hotel ? htmlspecialchars($info_hotel->nombre)   : 'Hotel Horizon';
$hotel_ruc     = $info_hotel ? htmlspecialchars($info_hotel->ruc)      : '-';
$hotel_dir     = $info_hotel ? htmlspecialchars($info_hotel->direccion) : '-';
$hotel_tel     = $info_hotel ? htmlspecialchars($info_hotel->telefono)  : '-';
$hotel_email   = $info_hotel ? htmlspecialchars($info_hotel->email)     : '-';

// Calcular IVA sobre el subtotal de la factura (que ya incluye habitación + servicios)
$subtotal_final = (float)$factura->subtotal;
$impuestos_final = (float)$factura->impuestos;
$total_final = (float)$factura->total;

// Limpiar cualquier salida previa (espacios en blanco o advertencias de PHP) que corrompa el flujo del PDF
if (ob_get_length()) {
    ob_end_clean();
}

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Helvetica", sans-serif; font-size: 12px; color: #1e293b; background: white; }

        .header { background: #0f172a; color: white; padding: 28px 32px; display: table; width: 100%; }
        .header-left  { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .hotel-name { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
        .hotel-sub  { font-size: 10px; color: #94a3b8; margin-top: 4px; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #3b82f6; }
        .invoice-sub   { font-size: 10px; color: #94a3b8; margin-top: 4px; }

        .body-wrap { padding: 28px 32px; }

        .info-section { display: table; width: 100%; margin-bottom: 24px; }
        .info-box { display: table-cell; width: 50%; vertical-align: top; }
        .info-box:last-child { text-align: right; }

        .label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; letter-spacing: 0.8px; }
        .value { font-size: 12px; color: #0f172a; margin-top: 2px; margin-bottom: 10px; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items thead th {
            background: #0f172a;
            color: white;
            padding: 10px 14px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border: none;
        }
        table.items thead th:last-child { text-align: right; }
        table.items tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 11px; color: #334155; }
        table.items tbody td:last-child { text-align: right; }
        table.items tbody tr:nth-child(even) { background: #fbfcfe; }

        .totals-wrap { text-align: right; }
        .totals-table { display: inline-table; min-width: 260px; }
        .totals-row { display: table-row; }
        .totals-label, .totals-val { display: table-cell; padding: 5px 10px; font-size: 12px; }
        .totals-label { text-align: left; color: #64748b; }
        .totals-val    { text-align: right; font-weight: bold; }
        .totals-total .totals-label { font-weight: bold; font-size: 14px; color: #0f172a; }
        .totals-total .totals-val   { font-size: 16px; color: #3b82f6; }
        .divider { border-top: 2px solid #e2e8f0; margin: 10px 0; }

        .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 32px; font-size: 10px; color: #64748b; text-align: center; margin-top: 30px; }

        .badge { display: inline-block; background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="hotel-name">' . $hotel_nombre . '</div>
        <div class="hotel-sub">RUC: ' . $hotel_ruc . ' &nbsp;|&nbsp; ' . $hotel_dir . '</div>
        <div class="hotel-sub">Tel: ' . $hotel_tel . ' &nbsp;|&nbsp; ' . $hotel_email . '</div>
    </div>
    <div class="header-right">
        <div class="invoice-title">FACTURA</div>
        <div class="invoice-sub">' . $numero_factura . '</div>
        <div class="invoice-sub" style="margin-top:6px;">Fecha Emisión: ' . date('d/m/Y', strtotime($factura->creado_en ?? 'now')) . '</div>
    </div>
</div>

<div class="body-wrap">
    <div class="info-section">
        <div class="info-box">
            <div class="label">Facturado a</div>
            <div class="value" style="font-size:14px; font-weight:bold;">' . htmlspecialchars($factura->cliente_nombre) . '</div>
            <div class="label">Identificación</div>
            <div class="value">' . htmlspecialchars($factura->cliente_documento) . '</div>
            <div class="label">Contacto</div>
            <div class="value">' . htmlspecialchars($factura->cliente_email) . ' / ' . htmlspecialchars($factura->cliente_telefono) . '</div>
            ' . ($factura->cliente_direccion ? '<div class="label">Dirección</div><div class="value">' . htmlspecialchars($factura->cliente_direccion) . '</div>' : '') . '
        </div>
        <div class="info-box">
            <div class="label">N° Reserva</div>
            <div class="value"># ' . htmlspecialchars($factura->reserva_id) . '</div>
            <div class="label">Estado</div>
            <div class="value"><span class="badge">Pagada</span></div>
            <div class="label">Check-in</div>
            <div class="value">' . $fecha_inicio->format('d/m/Y') . '</div>
            <div class="label">Check-out</div>
            <div class="value">' . $fecha_fin->format('d/m/Y') . '</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th width="45%">Descripción del Concepto</th>
                <th width="15%" style="text-align:center;">Cant.</th>
                <th width="20%" style="text-align:right;">Precio Un.</th>
                <th width="20%" style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><b>Alojamiento:</b> Hab. ' . htmlspecialchars($habitacion->hab_numero) . ' (' . htmlspecialchars($habitacion->hab_tipo) . ')</td>
                <td style="text-align:center;">' . $noches . ' noches</td>
                <td style="text-align:right;">' . $moneda . ' ' . number_format($habitacion->precio_pactado, 2) . '</td>
                <td style="text-align:right;">' . $moneda . ' ' . number_format($precio_estancia, 2) . '</td>
            </tr>';

if (!empty($servicios)) {
    foreach ($servicios as $s) {
        $html .= '
            <tr>
                <td><b>Servicio:</b> ' . htmlspecialchars(ucfirst($s->nombre)) . '</td>
                <td style="text-align:center;">' . $s->cantidad . '</td>
                <td style="text-align:right;">' . $moneda . ' ' . number_format($s->cantidad > 0 ? $s->subtotal / $s->cantidad : 0, 2) . '</td>
                <td style="text-align:right;">' . $moneda . ' ' . number_format($s->subtotal, 2) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>
    <div class="totals-wrap">
        <div class="totals-table">
            <div class="totals-row">
                <div class="totals-label">Subtotal</div>
                <div class="totals-val">' . $moneda . ' ' . number_format($subtotal_final, 2) . '</div>
            </div>
            <div class="totals-row">
                <div class="totals-label">IVA (' . $porcentaje_iva . '%)</div>
                <div class="totals-val">' . $moneda . ' ' . number_format($impuestos_final, 2) . '</div>
            </div>
            <div class="divider"></div>
            <div class="totals-row totals-total">
                <div class="totals-label">TOTAL</div>
                <div class="totals-val">' . $moneda . ' ' . number_format($total_final, 2) . '</div>
            </div>
        </div>
    </div>

</div>

<div class="footer">
    Gracias por elegir ' . $hotel_nombre . '. Esta factura fue generada automáticamente el ' . date('d/m/Y H:i') . '.
</div>

</body>
</html>';

// 7. Generar el PDF con DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Factura_' . $numero_factura . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
