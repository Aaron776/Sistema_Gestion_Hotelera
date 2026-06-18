document.addEventListener('DOMContentLoaded', function() {
    const clienteForm = document.getElementById('clienteForm');
    const submitBtn = document.getElementById('submitBtn');

    if (clienteForm) {
        clienteForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando Cambios...';
        });
    }
});