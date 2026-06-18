document.addEventListener('DOMContentLoaded', function() {
    const habitacionSelect = document.getElementById('habitacion');
    const fechaInicioInput = document.getElementById('fechaInicio');
    const fechaFinInput = document.getElementById('fechaFin');
    const nochesInput = document.getElementById('noches');
    const precioTotalInput = document.getElementById('precioTotal');
    const summaryBox = document.getElementById('summaryBox');

    const precioNocheDisplay = document.getElementById('precioNocheDisplay');
    const nochesDisplay = document.getElementById('nochesDisplay');
    const totalDisplay = document.getElementById('totalDisplay');

    let fpInicio, fpFin;

    if (typeof flatpickr !== 'undefined') {
        fpInicio = flatpickr("#fechaInicio", {
            locale: "es",
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function(selectedDates, dateStr) {
                if (fpFin) fpFin.set('minDate', dateStr);
                calculateTotal();
            }
        });

        fpFin = flatpickr("#fechaFin", {
            locale: "es",
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: calculateTotal
        });
    }

    habitacionSelect.addEventListener('change', calculateTotal);

    function calculateTotal() {
        const habitacionOption = habitacionSelect.options[habitacionSelect.selectedIndex];
        if (!habitacionOption || !habitacionOption.value) return;

        const precioNoche = parseFloat(habitacionOption.getAttribute('data-precio') || 0);
        const start = new Date(fechaInicioInput.value);
        const end = new Date(fechaFinInput.value);

        if (isNaN(start.getTime()) || isNaN(end.getTime())) return;

        const diffTime = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate()) - Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays <= 0) {
            summaryBox.style.display = 'none';
            return;
        }

        nochesInput.value = diffDays;
        const total = precioNoche * diffDays;
        precioTotalInput.value = total.toFixed(2);

        precioNocheDisplay.textContent = `$${precioNoche.toFixed(2)}`;
        nochesDisplay.textContent = diffDays;
        totalDisplay.textContent = `$${total.toFixed(2)}`;
        summaryBox.style.display = 'block';
    }

    // Ejecutar cálculo inicial para mostrar datos guardados
    calculateTotal();
});