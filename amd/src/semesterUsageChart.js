define(['jquery', 'local_cas_help_links/Chart'], function($, c) {
    return {
        initialise: function ($weeks, $userTotals, $clickTotals) {
            var ctx = $("#chart");
            var chart = new Chart(ctx, {
                type: "line",
                data: {
                    labels: $weeks,
                    datasets: [
                        {
                            label: "Total Students",
                            lineTension: 0.25,
                            data: $userTotals,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)'
                        },
                        {
                            label: "Total Clicks",
                            lineTension: 0.25,
                            data: $clickTotals,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)'
                        }
                    ]
                },
                options: {
                    responsive: false
                }
            });
        }
    };
});