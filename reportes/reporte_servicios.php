<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: ../acceso_denegado.php"); exit; }
require_once '../vendor/autoload.php';
require_once '../conexion/bd.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$sql = "SELECT s.nombre, s.precio, COUNT(rs.id) as cantidad_solicitudes, COALESCE(SUM(rs.subtotal),0) as ingresos_generados 
        FROM servicios s 
        LEFT JOIN reserva_servicio rs ON s.id = rs.servicio_id 
        GROUP BY s.id, s.nombre, s.precio 
        ORDER BY cantidad_solicitudes DESC";
$stmt = $conexion->query($sql);
$servicios = $stmt->fetchAll(PDO::FETCH_OBJ);

$html = '
<style>
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; }
    .header { text-align: center; border-bottom: 2px solid #8b5cf6; padding-bottom: 15px; margin-bottom: 20px; }
    .header h1 { color: #4c1d95; margin: 0; font-size: 24px; }
    .header p { color: #64748b; margin: 5px 0 0 0; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
    th { background-color: #8b5cf6; color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) { background-color: #f5f3ff; }
    .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 30px; text-align: center; color: #94a3b8; font-size: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
</style>
<div class="header">
    <h1>Reporte de Servicios y Consumos</h1>
    <p>Generado el: ' . date('d/m/Y H:i:s') . ' | Hotel Horizon</p>
</div>
<table>
    <thead><tr><th>Servicio</th><th>Precio Unitario</th><th>Solicitudes Históricas</th><th>Ingresos Generados</th></tr></thead>
    <tbody>';

$totalGenerado = 0;
foreach($servicios as $s) {
    $totalGenerado += $s->ingresos_generados;
    $html .= '<tr>
        <td><strong>' . htmlspecialchars($s->nombre) . '</strong></td>
        <td>$' . number_format($s->precio, 2) . '</td>
        <td>' . $s->cantidad_solicitudes . ' veces</td>
        <td>$' . number_format($s->ingresos_generados, 2) . '</td>
    </tr>';
}
$html .= '</tbody></table>
<div style="margin-top: 20px; text-align: right; font-size: 16px; background:#f5f3ff; padding: 10px; border:1px solid #ddd6fe; border-radius:8px;">
    <strong>Ingresos Totales por Servicios: </strong> <span style="color:#6d28d9; font-weight:900;">$' . number_format($totalGenerado, 2) . '</span>
</div>
<div class="footer">Página <span class="pagenum"></span> - Sistema de Gestión Hotelera</div>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Servicios_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
