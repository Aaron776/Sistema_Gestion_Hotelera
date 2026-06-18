document.addEventListener('DOMContentLoaded', function() {
    const servicioSelect = document.getElementById('servicioSelect');
    const cantidadInput = document.getElementById('cantidad');
    const precioDisplay = document.getElementById('precioUnitarioDisplay');
    const totalDisplay = document.getElementById('totalDisplay');

    function actualizarPrecios() {
        const selected = servicioSelect.options[servicioSelect.selectedIndex];
        if (!selected || !selected.value) {
            precioDisplay.textContent = '$0.00';
            totalDisplay.textContent = '$0.00';
            return;
        }

        const precioUnitario = parseFloat(selected.dataset.precio) || 0;
        const cantidad = parseInt(cantidadInput.value) || 1;
        const total = precioUnitario * cantidad;

        precioDisplay.textContent = `$${precioUnitario.toFixed(2)}`;
        totalDisplay.textContent = `$${total.toFixed(2)}`;
    }

    servicioSelect.addEventListener('change', actualizarPrecios);
    cantidadInput.addEventListener('input', actualizarPrecios);
});