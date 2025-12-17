<?php

/**
 * Shortcode: oscars_user_count_above_rate
 * Shows a number input and displays the count of users with prediction rate above X% (AJAX, live update).
 */
function oscars_user_count_above_rate_shortcode($atts = []) {
    $atts = shortcode_atts([
        'default' => 0
    ], $atts);
    $default = is_numeric($atts['default']) ? floatval($atts['default']) : 0;
    ob_start();
    ?>
    <div id="oscars-user-count-above-rate-wrap">
        <label><h3>Have a prediction rate above <input type="number" id="oscars-user-count-above-rate-input" value="<?php echo esc_attr($default); ?>" min="0" max="100" style="width:60px"> (%)</h3></label>
        <span id="oscars-user-count-above-rate-percent" class="small"></span>
        <span id="oscars-user-count-above-rate-result" class="large"></span>
        <canvas id="oscars-user-count-above-rate-chart" width="400" height="180" style="display:block;margin-top:1em;"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-above-rate-input');
        var result = document.getElementById('oscars-user-count-above-rate-result');
        var percent = document.getElementById('oscars-user-count-above-rate-percent');
        var chartCanvas = document.getElementById('oscars-user-count-above-rate-chart');
        var chartInstance = null;
        var chartDataCache = null;
        var chartLabelsCache = null;
        function updateCount() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-above-rate-ajax');
                    if (el) {
                        var data = el.textContent.split('||');
                        var main = data[0].split('|');
                        result.textContent = main[0] || '0';
                        percent.textContent = (main.length > 1 ? main[1] : '');
                        if (data.length > 1) {
                            var chartPayload = JSON.parse(data[1]);
                            chartDataCache = chartPayload.bins;
                            chartLabelsCache = chartPayload.labels;
                            renderChart(chartDataCache, chartLabelsCache);
                        }
                    }
                }
            };
            xhr.send('oscars_user_count_above_rate=' + encodeURIComponent(input.value) + '&oscars_user_count_above_rate_ajax=1');
        }
        function renderChart(chartData, chartLabels) {
            if (chartInstance) chartInstance.destroy();
            chartInstance = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Number of Users',
                        data: chartData,
                        backgroundColor: 'rgba(199,163,78,0.75)',
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        x: {
                            display: false,
                            title: {
                                display: true,
                                text: 'Prediction Rate (%)'
                            },
                            grid: { display: false },
                            ticks: { display: true }
                        },
                        y: {
                            display: false,
                            title: {
                                display: true,
                                text: 'Number of Users'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        input.addEventListener('input', function() {
            // Only update the result and percent, and update chart
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-above-rate-ajax');
                    if (el) {
                        var data = el.textContent.split('||');
                        var main = data[0].split('|');
                        result.textContent = main[0] || '0';
                        percent.textContent = (main.length > 1 ? main[1] : '');
                        if (data.length > 1) {
                            var chartPayload = JSON.parse(data[1]);
                            chartDataCache = chartPayload.bins;
                            chartLabelsCache = chartPayload.labels;
                            renderChart(chartDataCache, chartLabelsCache);
                        }
                    }
                }
            };
            xhr.send('oscars_user_count_above_rate=' + encodeURIComponent(input.value) + '&oscars_user_count_above_rate_ajax=1');
        });
        // Initial load
        updateCount();
    });
    </script>
    <span id="oscars-user-count-above-rate-ajax" style="display:none"><?php echo oscars_user_count_above_rate_inner(isset($default) ? $default : 0); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_above_rate', 'oscars_user_count_above_rate_shortcode');

function oscars_user_count_above_rate_inner($default = 0) {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $threshold = isset($_POST['oscars_user_count_above_rate']) ? floatval($_POST['oscars_user_count_above_rate']) : floatval($default);
    if (!file_exists($output_path)) return '0|';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return '0|';
    $count = 0;
    $bins = array_fill(0, 20, 0); // 20 bins for 0-4, 5-9, ..., 95-100
    $labels = [];
    for ($i = 0; $i < 19; $i++) {
        $labels[] = ($i*5) . '-' . ($i*5+4) . '%';
    }
    $labels[] = '95-100%';
    $total = 0;
    foreach ($users as $user) {
        if (isset($user['correct-prediction-rate']) && is_numeric($user['correct-prediction-rate'])) {
            $rate = round(100 * floatval($user['correct-prediction-rate']));
            $total++;
            $bin = ($rate >= 100) ? 19 : intval($rate / 5);
            if ($bin >= 0 && $bin < 20) {
                $bins[$bin]++;
            }
            if ($rate > $threshold) {
                $count++;
            }
        }
    }
    $percent = $total > 0 ? round(100 * $count / $total, 1) : 0;
    $chartPayload = json_encode([ 'bins' => $bins, 'labels' => $labels ]);
    return $count . '|' . ($total > 0 ? $percent . '% of users' : '') . '||' . $chartPayload;
}