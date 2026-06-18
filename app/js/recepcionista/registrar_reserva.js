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

    // Hacer el campo de noches readonly ya que se calcula automáticamente
    nochesInput.setAttribute('readonly', 'readonly');
    nochesInput.classList.add('readonly-field');

    let fpInicio, fpFin;

    // Inicializar flatpickr si está disponible en el proyecto
    if (typeof flatpickr !== 'undefined') {
        fpInicio = flatpickr("#fechaInicio", {
            locale: "es",
            dateFormat: "Y-m-d", // Formato SQL estándar
            minDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                if (fpFin) {
                    fpFin.set('minDate', dateStr);
                }
                calculateTotal();
            }
        });

        fpFin = flatpickr("#fechaFin", {
            locale: "es",
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function() {
                calculateTotal();
            }
        });
    } else {
        // Fallback si no hay flatpickr
        fechaInicioInput.type = 'date';
        fechaFinInput.type = 'date';
        fechaInicioInput.addEventListener('input', calculateTotal);
        fechaFinInput.addEventListener('input', calculateTotal);
    }

    habitacionSelect.addEventListener('change', calculateTotal);

    function calculateTotal() {
        const habitacionOption = habitacionSelect.options[habitacionSelect.selectedIndex];
        if (!habitacionOption || !habitacionOption.value) {
            hideSummary();
            return;
        }

        const precioNoche = parseFloat(habitacionOption.getAttribute('data-precio') || 0);

        let fechaInicioVal = fechaInicioInput.value;
        let fechaFinVal = fechaFinInput.value;

        if (!fechaInicioVal || !fechaFinVal) {
            hideSummary();
            return;
        }

        // Calcular la diferencia en días
        const start = new Date(fechaInicioVal);
        const end = new Date(fechaFinVal);

        if (isNaN(start.getTime()) || isNaN(end.getTime())) return;

        // Calcular la diferencia ignorando horas para evitar bugs por cambios de horario
        const diffTime = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate()) - Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays <= 0) {
            hideSummary();
            return;
        }

        // Rellenar campos
        nochesInput.value = diffDays;

        const total = precioNoche * diffDays;
        precioTotalInput.value = total.toFixed(2);

        // Actualizar UI del Resumen Visual
        precioNocheDisplay.textContent = `$${precioNoche.toFixed(2)}`;
        nochesDisplay.textContent = diffDays;
        totalDisplay.textContent = `$${total.toFixed(2)}`;

        // Mostrar resumen visual
        summaryBox.style.display = 'block';
    }

    function hideSummary() {
        summaryBox.style.display = 'none';
        precioTotalInput.value = '';
        nochesInput.value = '';
    }
});