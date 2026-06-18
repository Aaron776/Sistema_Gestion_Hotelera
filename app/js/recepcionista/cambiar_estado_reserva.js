document.addEventListener('DOMContentLoaded', function() {
    const estadoForm = document.getElementById('estadoForm');
    const submitBtn = document.getElementById('submitBtn');

    if (estadoForm) {
        estadoForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        });
    }
});