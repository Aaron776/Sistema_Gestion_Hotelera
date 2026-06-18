document.addEventListener('DOMContentLoaded', function() {
    const editUserForm = document.getElementById('editUserForm');
    const submitBtn = document.getElementById('submitBtn');

    // Efecto de carga al guardar cambios
    if (editUserForm) {
        editUserForm.addEventListener('submit', function() {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando cambios...';
            }
        });
    }
});