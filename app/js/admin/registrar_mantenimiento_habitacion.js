document.addEventListener('DOMContentLoaded', function() {
    const maintenanceForm = document.getElementById('maintenanceForm');
    const submitBtn = document.getElementById('submitBtn');

    if (maintenanceForm) {
        maintenanceForm.addEventListener('submit', function() {
            // Deshabilitar botón para evitar múltiples envíos
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            }
        });
    }

    console.log("Módulo de registro de mantenimiento inicializado.");
});