<?php
/**
 * Shortcode to output the number of films per year with exactly N watches (default N=0).
 * Usage: [films_with_n_watches_per_year]
 */
function oscars_films_with_n_watches_per_year_shortcode() {
    $output_path = ABSPATH . 'wp-content/uploads/films_stats.json';
    if (!file_exists($output_path)) {
        return '<p>Stats file not found.</p>';
    }
    $json = file_get_contents($output_path);
    $films = json_decode($json, true);
    if (!$films || !is_array($films)) {
        return '<p>Stats file is invalid.</p>';
    }
    $start_year = 1929;
    $end_year = intval(date('Y'));
    $default_n = 0;
    $selected_n = $default_n;
    if (isset($_POST['films_with_n_watches_n']) && is_numeric($_POST['films_with_n_watches_n'])) {
        $selected_n = intval($_POST['films_with_n_watches_n']);
    } elseif (isset($_GET['films_with_n_watches_n']) && is_numeric($_GET['films_with_n_watches_n'])) {
        $selected_n = intval($_GET['films_with_n_watches_n']);
    }
    // Count films with exactly N watches per year
    $films_with_n_watches_per_year = array();
    for ($year = $start_year; $year <= $end_year; $year++) {
        $films_with_n_watches_per_year[$year] = 0;
    }
    foreach ($films as $film) {
        if (isset($film['film-year']) && isset($film['watched-count'])) {
            $film_year = intval($film['film-year']);
            $watched_count = intval($film['watched-count']);
            if ($film_year >= $start_year && $film_year <= $end_year && $watched_count === $selected_n) {
                $films_with_n_watches_per_year[$film_year]++;
            }
        }
    }
    $uid = uniqid('films_with_n_watches_');
    ob_start();
    ?>
    <h2>Films Per Year with Exactly <span id="<?php echo $uid; ?>-n-label"><?php echo esc_html($selected_n); ?></span> Watches</h2>
    <form id="<?php echo $uid; ?>-form" style="margin-bottom:1em;" onsubmit="return false;">
        <label>Films that have been watched <input type="number" name="films_with_n_watches_n" id="<?php echo $uid; ?>-n" value="<?php echo esc_attr($selected_n); ?>" min="0" style="width:80px"> times: <span id="<?php echo $uid; ?>-total-label"></span></label>
    </form>
    <div style="max-width:100%; max-height:400px; overflow:auto;"><canvas id="<?php echo $uid; ?>" style="max-height:400px;width:100%;height:400px;"></canvas></div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript">
    window.addEventListener('DOMContentLoaded', function() {
    (function(){
        var years = <?php echo json_encode(array_values(array_keys($films_with_n_watches_per_year))); ?>;
        var counts = <?php echo json_encode(array_values($films_with_n_watches_per_year)); ?>;
        var defaultN = <?php echo $default_n; ?>;
        var uid = "<?php echo $uid; ?>";
        var chart;
        var ctx = document.getElementById(uid).getContext("2d");
        var nInput = document.getElementById(uid + "-n");
        var nLabel = document.getElementById(uid + "-n-label");
        var totalLabel = document.getElementById(uid + "-total-label");
        function updateChart(newCounts) {
            chart.data.datasets[0].data = newCounts;
            chart.update();
            updateTotal(newCounts);
        }
        function updateTotal(countsArr) {
            var total = countsArr.reduce(function(a, b) { return a + b; }, 0);
            totalLabel.textContent = total;
        }
        chart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: years,
                datasets: [{
                    label: "Films with N Watches",
                    data: counts,
                    backgroundColor: "#c7a34f",
                    borderColor: "#c7a34f",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: { title: { display: true, text: "Year" }, ticks: { maxTicksLimit: 20 } },
                    y: { beginAtZero: true, title: { display: true, text: "Films" } }
                }
            }
        });
        // Initial total
        updateTotal(counts);
        nInput.addEventListener('input', function() {
            var n = parseInt(nInput.value);
            if (isNaN(n) || n < 0) return;
            nLabel.textContent = n;
            // AJAX to reload just the chart data, like watches_per_year_shortcode.php
            var formData = new FormData();
            formData.append('films_with_n_watches_n', n);
            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                var temp = document.createElement('div');
                temp.innerHTML = html;
                var scriptTag = temp.querySelector('script[type="application/json"][data-uid^="films_with_n_watches_"]');
                if (scriptTag) {
                    var newCounts = JSON.parse(scriptTag.textContent);
                    updateChart(newCounts);
                }
            });
        });
    })();
    });
    </script>
    <script type="application/json" data-uid="<?php echo $uid; ?>"><?php echo json_encode(array_values($films_with_n_watches_per_year)); ?></script>
    <?php
    return ob_get_clean();
}
add_shortcode('films_with_n_watches_per_year', 'oscars_films_with_n_watches_per_year_shortcode');
