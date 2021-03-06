define(['local_cas_help_links/Chart'], function(c) {
    return {
        initialise: function (weeks, userTotals, clickTotals) {
            var ctx = document.getElementById("chart");
            var chart = new c(ctx, {
                type: "line",
                data: {
                    labels: weeks,
                    datasets: [
                        {
                            label: "Total Students",
                            lineTension: 0.25,
                            data: userTotals,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)'
                        },
                        {
                            label: "Total Clicks",
                            lineTension: 0.25,
                            data: clickTotals,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)'
                        }
                    ]
                },
                options: {
                    legend:{display: true,labels:{fontSize:15}},
                    scales:{xAxes:[{ticks:{fontSize:15}}],yAxes:[{ticks:{fontSize:15}}]},
                    responsive: true,
                    maintainAspectRatio: true,
                }
            });
        }
    };
});
