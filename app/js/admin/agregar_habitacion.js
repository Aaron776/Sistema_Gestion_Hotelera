document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const roomForm = document.getElementById('roomForm');
    const submitBtn = document.getElementById('submitBtn');

    if (roomForm) {
        roomForm.addEventListener('submit', function() {
            // Deshabilitar botón para evitar múltiples envíos
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            }
        });
    }

    // Nota: Las funciones de validación (validateRoomNumber, etc.) 
    // deben ser implementadas según la lógica de negocio requerida.
});