function renderCharts() {
    // Get the date range from the report
    const startDate = '<?php echo $current_report["start_date"] ?? date("Y-m-d", strtotime("-30 days")); ?>';
    const endDate = '<?php echo $current_report["end_date"] ?? date("Y-m-d"); ?>';
    
    // Function to fetch and render a chart
    function renderChart(chartId, chartType, chartOptions = {}) {
        fetch(`charts.php?chart=${chartType}&start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById(chartId).getContext('2d');
                
                // Default options
                const defaults = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (chartType === 'salesChart' || chartType === 'terminalChart') {
                                        label += '₱' + context.raw.toLocaleString();
                                    } else {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (chartType === 'salesChart' || chartType === 'terminalChart') {
                                        return '₱' + value.toLocaleString();
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                };
                
                // Merge with custom options
                const options = {...defaults, ...chartOptions};
                
                // Create chart
                window[chartId] = new Chart(ctx, {
                    type: chartType.includes('Chart') ? 'bar' : 'doughnut', // Default types
                    data: {
                        labels: data.labels || [],
                        datasets: [{
                            label: chartType.replace('Chart', ''),
                            data: data.values || [],
                            backgroundColor: data.colors || [
                                'rgba(54, 162, 235, 0.6)',
                                'rgba(255, 99, 132, 0.6)',
                                'rgba(75, 192, 192, 0.6)',
                                'rgba(255, 159, 64, 0.6)',
                                'rgba(153, 102, 255, 0.6)'
                            ],
                            borderColor: data.colors || [
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(255, 159, 64, 1)',
                                'rgba(153, 102, 255, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: options
                });
            })
            .catch(error => console.error(`Error loading ${chartId} data:`, error));
    }
    
    // Render each chart with specific options
    renderChart('destinationsChart', 'destinationsChart', {
        plugins: {
            title: {
                display: true,
                text: 'Top Destinations by Ticket Count',
                font: { size: 16 }
            }
        }
    });
    
    renderChart('terminalChart', 'terminalChart', {
        plugins: {
            title: {
                display: true,
                text: 'Terminal Performance by Revenue',
                font: { size: 16 }
            }
        }
    });
    
    renderChart('dailyTrendChart', 'dailyTrendChart', {
        plugins: {
            title: {
                display: true,
                text: 'Daily Ticket Sales Trend',
                font: { size: 16 }
            }
        }
    });
    
    renderChart('statusChart', 'statusChart', {
        type: 'doughnut',
        plugins: {
            title: {
                display: true,
                text: 'Ticket Status Distribution',
                font: { size: 16 }
            }
        }
    });
    
    renderChart('salesChart', 'salesChart', {
        plugins: {
            title: {
                display: true,
                text: 'Daily Revenue Trend',
                font: { size: 16 }
            }
        }
    });
}
