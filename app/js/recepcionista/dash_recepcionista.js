document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de ocupación por tipo
    const ctx = document.getElementById('ocupacionChart');
    if (ctx && typeof chartLabels !== 'undefined') {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ec4899'],
                    borderWidth: 0,
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
            }
        });
    }
    console.log("Dashboard recepcionista cargado con éxito.");
});