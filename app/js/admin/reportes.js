/**
 * Función para generar reportes en PDF
 * @param {string} tipo - El tipo de reporte a generar
 */
function generatePDF(tipo) {
    Swal.fire({
        title: 'Generando Reporte...',
        text: 'Por favor espere un momento.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
        timer: 1500,
        timerProgressBar: true
    }).then(() => {
        // Redirigir al controlador que genera el PDF (placeholder)
        // En un escenario real, esto enviaría los parámetros de filtrado necesarios
        window.location.href = `../reportes/reporte_${tipo}.php`;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("Módulo de reportes inicializado.");
});