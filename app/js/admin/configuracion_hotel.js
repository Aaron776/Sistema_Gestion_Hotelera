document.addEventListener('DOMContentLoaded', function() {
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');
    const previewImg = logoPreview.querySelector('img');

    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validar que realmente sea una imagen
                if (!file.type.startsWith('image/')) {
                    Swal.fire('Error', 'El archivo seleccionado no es una imagen válida.', 'error');
                    this.value = ''; // Limpiar el input
                    previewImg.src = '';
                    logoPreview.classList.remove('has-image');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    logoPreview.classList.add('has-image');
                }
                reader.readAsDataURL(file);
            } else {
                previewImg.src = '';
                logoPreview.classList.remove('has-image');
            }
        });
    }
});