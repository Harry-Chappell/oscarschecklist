<?php

/**
 * Shortcode: oscars_active_users_barchart
 * Displays a bar chart of number of users last active in each of the last N intervals (user-configurable).
 * Uses Chart.js (loads from CDN if not present).
 * Now with input fields for interval and timeframe, AJAX-powered.
 */
function oscars_active_users_barchart_shortcode($atts = []) {
    $atts = shortcode_atts([
        'timeframe' => '',
        'interval' => 'day',
        'film_id' => ''
    ], $atts);
    $uid = uniqid('oscars-active-users-barchart-');
    $interval = in_array(strtolower($atts['interval']), ['day','week','month','year']) ? strtolower($atts['interval']) : 'day';
    
    // Calculate default timeframe from June 16, 2025 to today
    $today = new DateTime();
    $start = new DateTime('2025-06-16');
    $diff_days = $start->diff($today)->days;
    
    // Convert days to appropriate interval units
    switch ($interval) {
        case 'week':
            $default_timeframe = ceil($diff_days / 7);
            break;
        case 'month':
            $default_timeframe = $start->diff($today)->m + ($start->diff($today)->y * 12);
            break;
        case 'year':
            $default_timeframe = max(1, $start->diff($today)->y);
            break;
        default: // day
            $default_timeframe = $diff_days;
            break;
    }
    
    $timeframe = isset($atts['timeframe']) && $atts['timeframe'] !== '' ? intval($atts['timeframe']) : $default_timeframe;
    if ($timeframe < 1) $timeframe = 1;
    $film_id = $atts['film_id'];
    ob_start();
    ?>
    <div id="<?php echo $uid; ?>-controls" class="oscars-active-users-barchart-controls" style="margin-bottom:1em">
        <h2>
            Users watched in the last
            <input type="number" id="<?php echo $uid; ?>-timeframe" value="<?php echo esc_attr($timeframe); ?>" min="1" max="365" style="width:60px">
            <span><?php echo ucfirst($interval) . 's'; ?></span>
        </h2>
    </div>
    <div class="oscars-active-users-barchart-wrapper" style="width:100%;">
        <canvas id="<?php echo $uid; ?>" class="oscars-active-users-barchart-canvas"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        (function() {
            var uid = <?php echo json_encode($uid); ?>;
            var ctx = document.getElementById(uid).getContext('2d');
            var chart = null;
            var film_id = <?php echo json_encode($film_id); ?>;
            var interval = <?php echo json_encode($interval); ?>;
            function fetchAndRenderChart() {
                var timeframe = document.getElementById(uid+'-timeframe').value;
                var data = new FormData();
                data.append('action', 'oscars_active_users_barchart_ajax');
                data.append('timeframe', timeframe);
                data.append('interval', interval);
                data.append('film_id', film_id);
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
                                maintainAspectRatio: false,
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
    $interval = isset($_POST['interval']) && in_array($_POST['interval'], ['day','week','month','year']) ? $_POST['interval'] : 'day';
    $watched_path = ABSPATH . 'wp-content/uploads/watched_by_day.json';
    if (!file_exists($watched_path)) {
        wp_send_json_error('No watched_by_day data found.');
    }
    $json = file_get_contents($watched_path);
    $watched = json_decode($json, true);
    if (!$watched || !is_array($watched)) {
        wp_send_json_error('watched_by_day data is invalid.');
    }
    $film_id = isset($_POST['film_id']) && $_POST['film_id'] !== '' ? (string)$_POST['film_id'] : null;
    $data = [];
    if ($film_id && isset($watched['films'][$film_id])) {
        $data = $watched['films'][$film_id];
    } else {
        $data = $watched['days'];
    }
    
    // Generate intervals based on interval type
    $all_intervals = [];
    $interval_map = [];
    if (!empty($data)) {
        $dates = array_keys($data);
        rsort($dates); // newest first
        $latest = new DateTime($dates[0]);
        
        for ($i = 0; $i < $timeframe; $i++) {
            $d = clone $latest;
            switch ($interval) {
                case 'week':
                    $d->modify("-$i weeks");
                    $label = $d->format('Y-\WW');
                    break;
                case 'month':
                    $d->modify("-$i months");
                    $label = $d->format('Y-m');
                    break;
                case 'year':
                    $d->modify("-$i years");
                    $label = $d->format('Y');
                    break;
                default: // day
                    $d->modify("-$i days");
                    $label = $d->format('Y-m-d');
                    break;
            }
            $all_intervals[] = $label;
        }
        $all_intervals = array_reverse($all_intervals); // oldest first
        
        // Map each date in data to its interval
        foreach ($data as $date => $count) {
            $dt = new DateTime($date);
            switch ($interval) {
                case 'week':
                    $interval_key = $dt->format('Y-\WW');
                    break;
                case 'month':
                    $interval_key = $dt->format('Y-m');
                    break;
                case 'year':
                    $interval_key = $dt->format('Y');
                    break;
                default: // day
                    $interval_key = $dt->format('Y-m-d');
                    break;
            }
            if (!isset($interval_map[$interval_key])) {
                $interval_map[$interval_key] = 0;
            }
            $interval_map[$interval_key] += $count;
        }
    }
    
    $labels = [];
    $bins = [];
    foreach ($all_intervals as $interval_label) {
        $labels[] = $interval_label;
        $bins[] = isset($interval_map[$interval_label]) ? $interval_map[$interval_label] : 0;
    }
    wp_send_json_success(['labels' => $labels, 'bins' => $bins]);
});
add_action('wp_ajax_nopriv_oscars_active_users_barchart_ajax', function() {
    $timeframe = isset($_POST['timeframe']) ? intval($_POST['timeframe']) : 7;
    if ($timeframe < 1) $timeframe = 7;
    $interval = isset($_POST['interval']) && in_array($_POST['interval'], ['day','week','month','year']) ? $_POST['interval'] : 'day';
    $watched_path = ABSPATH . 'wp-content/uploads/watched_by_day.json';
    if (!file_exists($watched_path)) {
        wp_send_json_error('No watched_by_day data found.');
    }
    $json = file_get_contents($watched_path);
    $watched = json_decode($json, true);
    if (!$watched || !is_array($watched)) {
        wp_send_json_error('watched_by_day data is invalid.');
    }
    $film_id = isset($_POST['film_id']) && $_POST['film_id'] !== '' ? (string)$_POST['film_id'] : null;
    $data = [];
    if ($film_id && isset($watched['films'][$film_id])) {
        $data = $watched['films'][$film_id];
    } else {
        $data = $watched['days'];
    }
    
    // Generate intervals based on interval type
    $all_intervals = [];
    $interval_map = [];
    if (!empty($data)) {
        $dates = array_keys($data);
        rsort($dates); // newest first
        $latest = new DateTime($dates[0]);
        
        for ($i = 0; $i < $timeframe; $i++) {
            $d = clone $latest;
            switch ($interval) {
                case 'week':
                    $d->modify("-$i weeks");
                    $label = $d->format('Y-\WW');
                    break;
                case 'month':
                    $d->modify("-$i months");
                    $label = $d->format('Y-m');
                    break;
                case 'year':
                    $d->modify("-$i years");
                    $label = $d->format('Y');
                    break;
                default: // day
                    $d->modify("-$i days");
                    $label = $d->format('Y-m-d');
                    break;
            }
            $all_intervals[] = $label;
        }
        $all_intervals = array_reverse($all_intervals); // oldest first
        
        // Map each date in data to its interval
        foreach ($data as $date => $count) {
            $dt = new DateTime($date);
            switch ($interval) {
                case 'week':
                    $interval_key = $dt->format('Y-\WW');
                    break;
                case 'month':
                    $interval_key = $dt->format('Y-m');
                    break;
                case 'year':
                    $interval_key = $dt->format('Y');
                    break;
                default: // day
                    $interval_key = $dt->format('Y-m-d');
                    break;
            }
            if (!isset($interval_map[$interval_key])) {
                $interval_map[$interval_key] = 0;
            }
            $interval_map[$interval_key] += $count;
        }
    }
    
    $labels = [];
    $bins = [];
    foreach ($all_intervals as $interval_label) {
        $labels[] = $interval_label;
        $bins[] = isset($interval_map[$interval_label]) ? $interval_map[$interval_label] : 0;
    }
    wp_send_json_success(['labels' => $labels, 'bins' => $bins]);
});