<?php

/**
 * Shortcode: oscars_active_users_barchart
 * Displays a bar chart of number of users last active in each of the last N intervals (user-configurable).
 * Uses Chart.js (loads from CDN if not present).
 * Now with input fields for interval and timeframe, AJAX-powered.
 */
function oscars_active_users_barchart_shortcode($atts = []) {
    $atts = shortcode_atts([
        'timeframe' => 7,
        'interval' => 'day'
    ], $atts);
    $uid = uniqid('oscars-active-users-barchart-');
    $today = new DateTime();
    $start = new DateTime('2025-06-16');
    $interval_days = $start->diff($today)->days;
    $timeframe = isset($atts['timeframe']) && $atts['timeframe'] !== '' ? intval($atts['timeframe']) : $interval_days;
    if ($timeframe < 1) $timeframe = 1;
    $interval = in_array(strtolower($atts['interval']), ['day','week','month','year']) ? strtolower($atts['interval']) : 'day';
    ob_start();
    ?>
    <div id="<?php echo $uid; ?>-controls" class="oscars-active-users-barchart-controls" style="margin-bottom:1em">
        <h2>
            Users watched in the last
            <input type="number" id="<?php echo $uid; ?>-timeframe" value="<?php echo esc_attr($timeframe); ?>" min="1" max="365" style="width:60px">
            <span>Days</span>
        </h2>
    </div>
    <div style="width:100%;">
        <canvas id="<?php echo $uid; ?>" style="display:block;max-height:400px;height:400px;"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        (function() {
            var uid = <?php echo json_encode($uid); ?>;
            var ctx = document.getElementById(uid).getContext('2d');
            var chart = null;
            function fetchAndRenderChart() {
                var timeframe = document.getElementById(uid+'-timeframe').value;
                var data = new FormData();
                data.append('action', 'oscars_active_users_barchart_ajax');
                data.append('timeframe', timeframe);
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                })
                .then(response => response.json())
                .then(json => {
                    if (json.success) {
                        if (chart) chart.destroy();
                        chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: json.data.labels,
                                datasets: [{
                                    label: 'Watches',
                                    data: json.data.bins,
                                    backgroundColor: '#c7a34f',
                                    borderColor: '#987e40',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { display: false },
                                    title: { display: false }
                                },
                                scales: {
                                    x: { display: false },
                                    y: { display: false, beginAtZero: true }
                                }
                            }
                        });
                    }
                });
            }
            document.getElementById(uid+'-timeframe').addEventListener('input', fetchAndRenderChart);
            fetchAndRenderChart();
        })();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_active_users_barchart', 'oscars_active_users_barchart_shortcode');

// AJAX handler for oscars_active_users_barchart
add_action('wp_ajax_oscars_active_users_barchart_ajax', function() {
    $timeframe = isset($_POST['timeframe']) ? intval($_POST['timeframe']) : 7;
    if ($timeframe < 1) $timeframe = 7;
    $watched_path = ABSPATH . 'wp-content/uploads/watched_by_day.json';
    if (!file_exists($watched_path)) {
        wp_send_json_error('No watched_by_day data found.');
    }
    $json = file_get_contents($watched_path);
    $watched = json_decode($json, true);
    if (!$watched || !is_array($watched)) {
        wp_send_json_error('watched_by_day data is invalid.');
    }
    // Determine if we are on a film taxonomy term
    $queried_object = get_queried_object();
    $film_id = null;
    if (isset($queried_object->taxonomy) && $queried_object->taxonomy === 'films' && isset($queried_object->term_id)) {
        $film_id = (string)$queried_object->term_id;
    }
    $data = [];
    if ($film_id && isset($watched['films'][$film_id])) {
        $data = $watched['films'][$film_id];
    } else {
        $data = $watched['days'];
    }
    // Sort by date descending
    krsort($data);
    $labels = [];
    $bins = [];
    $i = 0;
    foreach ($data as $date => $count) {
        if ($i >= $timeframe) break;
        $labels[] = $date;
        $bins[] = $count;
        $i++;
    }
    $labels = array_reverse($labels);
    $bins = array_reverse($bins);
    wp_send_json_success(['labels' => $labels, 'bins' => $bins]);
});
add_action('wp_ajax_nopriv_oscars_active_users_barchart_ajax', function() {
    $timeframe = isset($_POST['timeframe']) ? intval($_POST['timeframe']) : 7;
    if ($timeframe < 1) $timeframe = 7;
    $watched_path = ABSPATH . 'wp-content/uploads/watched_by_day.json';
    if (!file_exists($watched_path)) {
        wp_send_json_error('No watched_by_day data found.');
    }
    $json = file_get_contents($watched_path);
    $watched = json_decode($json, true);
    if (!$watched || !is_array($watched)) {
        wp_send_json_error('watched_by_day data is invalid.');
    }
    $queried_object = get_queried_object();
    $film_id = null;
    if (isset($queried_object->taxonomy) && $queried_object->taxonomy === 'films' && isset($queried_object->term_id)) {
        $film_id = (string)$queried_object->term_id;
    }
    $data = [];
    if ($film_id && isset($watched['films'][$film_id])) {
        $data = $watched['films'][$film_id];
    } else {
        $data = $watched['days'];
    }
    krsort($data);
    $labels = [];
    $bins = [];
    $i = 0;
    foreach ($data as $date => $count) {
        if ($i >= $timeframe) break;
        $labels[] = $date;
        $bins[] = $count;
        $i++;
    }
    $labels = array_reverse($labels);
    $bins = array_reverse($bins);
    wp_send_json_success(['labels' => $labels, 'bins' => $bins]);
});