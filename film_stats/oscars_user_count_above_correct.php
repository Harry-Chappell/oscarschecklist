<?php

/**
 * Shortcode: oscars_user_count_above_correct
 * Shows a number input and displays the count of users with more than X correct predictions (AJAX, live update).
 */
function oscars_user_count_above_correct_shortcode($atts = []) {
    $atts = shortcode_atts([
        'default' => 0
    ], $atts);
    $default = is_numeric($atts['default']) ? intval($atts['default']) : 0;
    ob_start();
    ?>
    <div id="oscars-user-count-above-correct-wrap">
        <label><h3>Have more than <input type="number" id="oscars-user-count-above-correct-input" value="<?php echo esc_attr($default); ?>" min="0" style="width:60px"> correct predictions</h3></label>
        <span id="oscars-user-count-above-correct-percent" class="small"></span>
        <span id="oscars-user-count-above-correct-result" class="large"></span>
        <canvas id="oscars-user-count-above-correct-chart" width="400" height="180" style="display:block;margin-top:1em;"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-above-correct-input');
        var result = document.getElementById('oscars-user-count-above-correct-result');
        var percent = document.getElementById('oscars-user-count-above-correct-percent');
        var chartCanvas = document.getElementById('oscars-user-count-above-correct-chart');
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
                    var el = doc.querySelector('#oscars-user-count-above-correct-ajax');
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
            xhr.send('oscars_user_count_above_correct=' + encodeURIComponent(input.value) + '&oscars_user_count_above_correct_ajax=1');
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
                        // borderColor: 'rgba(199,163,78,1)',
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
                                text: 'Number of Correct Predictions'
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
                    var el = doc.querySelector('#oscars-user-count-above-correct-ajax');
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
            xhr.send('oscars_user_count_above_correct=' + encodeURIComponent(input.value) + '&oscars_user_count_above_correct_ajax=1');
        });
        // Initial load
        updateCount();
    });
    </script>
    <span id="oscars-user-count-above-correct-ajax" style="display:none"><?php echo oscars_user_count_above_correct_inner(isset($default) ? $default : 0); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_above_correct', 'oscars_user_count_above_correct_shortcode');

function oscars_user_count_above_correct_inner($default = 0) {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $threshold = isset($_POST['oscars_user_count_above_correct']) ? intval($_POST['oscars_user_count_above_correct']) : intval($default);
    if (!file_exists($output_path)) return '0|';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return '0|';
    $count = 0;
    $max = 0;
    foreach ($users as $user) {
        if (isset($user['correct-predictions']) && is_numeric($user['correct-predictions'])) {
            $val = intval($user['correct-predictions']);
            if ($val > $max) $max = $val;
        }
    }
    $bins = array_fill(0, $max + 1, 0);
    $labels = [];
    for ($i = 0; $i <= $max; $i++) {
        $labels[] = (string)$i;
    }
    $total = get_site_user_count();
    foreach ($users as $user) {
        if (isset($user['correct-predictions']) && is_numeric($user['correct-predictions'])) {
            $val = intval($user['correct-predictions']);
            if ($val >= 0 && $val <= $max) {
                $bins[$val]++;
            }
            if ($val > $threshold) {
                $count++;
            }
        }
    }
    $percent = $total > 0 ? round(100 * $count / $total, 1) : 0;
    $chartPayload = json_encode([ 'bins' => $bins, 'labels' => $labels ]);
    return $count . '|' . ($total > 0 ? $percent . '% of users' : '') . '||' . $chartPayload;
}