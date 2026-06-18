<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: ../acceso_denegado.php"); exit; }
require_once '../vendor/autoload.php';
require_once '../conexion/bd.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$sql = "SELECT numero, tipo, precio, estado FROM habitaciones ORDER BY numero ASC";
$stmt = $conexion->query($sql);
$habitaciones = $stmt->fetchAll(PDO::FETCH_OBJ);

$html = '
<style>
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; }
    .header { text-align: center; border-bottom: 2px solid #3b82f6; padding-bottom: 15px; margin-bottom: 20px; }
    .header h1 { color: #1e293b; margin: 0; font-size: 24px; }
    .header p { color: #64748b; margin: 5px 0 0 0; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
    th { background-color: #3b82f6; color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) { background-color: #f8fafc; }
    .status-badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-weight: bold; font-size: 10px; }
    .status-disponible { color: #15803d; }
    .status-ocupada { color: #b45309; }
    .status-mantenimiento { color: #b91c1c; }
    .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 30px; text-align: center; color: #94a3b8; font-size: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
</style>
<div class="header">
    <h1>Reporte de Ocupación de Habitaciones</h1>
    <p>Generado el: ' . date('d/m/Y H:i:s') . ' | Hotel Horizon</p>
</div>
<table>
    <thead><tr><th>N° Habitación</th><th>Tipo</th><th>Precio/Noche</th><th>Estado</th></tr></thead>
    <tbody>';

$totalOcupadas = 0;
foreach($habitaciones as $h) {
    if($h->estado == 'ocupada') $totalOcupadas++;
    $html .= '<tr>
        <td><strong>' . $h->numero . '</strong></td>
        <td>' . ucfirst($h->tipo) . '</td>
        <td>$' . number_format($h->precio, 2) . '</td>
        <td><span class="status-badge status-' . strtolower($h->estado) . '">' . strtoupper($h->estado) . '</span></td>
    </tr>';
}
$html .= '</tbody></table>
<div style="margin-top: 20px; text-align: right; font-size: 14px;">
    <strong>Total Ocupadas: </strong> ' . $totalOcupadas . ' / ' . count($habitaciones) . '
</div>
<div class="footer">Página <span class="pagenum"></span> - Sistema de Gestión Hotelera</div>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Ocupacion_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
