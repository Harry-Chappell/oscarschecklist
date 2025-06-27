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
        <span id="oscars-user-count-above-rate-result" class="large"></span>
        <span id="oscars-user-count-above-rate-percent" class="small"></span>
        <label>Have a prediction rate above <input type="number" id="oscars-user-count-above-rate-input" value="<?php echo esc_attr($default); ?>" min="0" max="100" style="width:60px"> (%)</label>
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
                        if (data.length > 1 && !chartDataCache) {
                            var chartData = JSON.parse(data[1]);
                            chartDataCache = chartData;
                            renderChart(chartDataCache);
                        }
                    }
                }
            };
            xhr.send('oscars_user_count_above_rate=' + encodeURIComponent(input.value) + '&oscars_user_count_above_rate_ajax=1');
        }
        function renderChart(chartData) {
            var labels = [
                '0-4', '5-9', '10-14', '15-19', '20-24', '25-29', '30-34', '35-39', '40-44', '45-49',
                '50-54', '55-59', '60-64', '65-69', '70-74', '75-79', '80-84', '85-89', '90-94', '95-100'
            ];
            if (chartInstance) chartInstance.destroy();
            chartInstance = new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '',
                        data: chartData,
                        borderColor: 'rgba(199,163,78,0.5)',
                        backgroundColor: 'rgba(199,163,78,0.1)',
                        fill: true,
                        pointRadius: 0,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    }
                }
            });
        }
        input.addEventListener('input', function() {
            // Only update the result and percent, not the chart
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
                    }
                }
            };
            xhr.send('oscars_user_count_above_rate=' + encodeURIComponent(input.value) + '&oscars_user_count_above_rate_ajax=1');
        });
        // Initial load: get everything and render chart
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
    return $count . '|' . ($total > 0 ? $percent . '% of users' : '') . '||' . json_encode($bins);
}