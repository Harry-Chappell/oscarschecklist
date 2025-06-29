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
    // Calculate default timeframe as days between June 16, 2025 and today
    $today = new DateTime();
    $start = new DateTime('2025-06-16');
    $interval_days = $start->diff($today)->days;
    $timeframe = isset($atts['timeframe']) && $atts['timeframe'] !== '' ? intval($atts['timeframe']) : $interval_days;
    if ($timeframe < 1) $timeframe = 1;
    $interval = in_array(strtolower($atts['interval']), ['day','week','month','year']) ? strtolower($atts['interval']) : 'day';
    ob_start();
    ?>
    <div id="oscars-active-users-barchart-controls" style="margin-bottom:1em">
        <h2>
            Users who were most recently active in the last
            <input type="number" id="oscars-active-users-timeframe" value="<?php echo esc_attr($timeframe); ?>" min="1" max="365" style="width:60px">
            <select id="oscars-active-users-interval">
                <option value="day"<?php if($interval==='day') echo ' selected'; ?>>Days</option>
                <option value="week"<?php if($interval==='week') echo ' selected'; ?>>Weeks</option>
                <option value="month"<?php if($interval==='month') echo ' selected'; ?>>Months</option>
                <option value="year"<?php if($interval==='year') echo ' selected'; ?>>Years</option>
            </select>.
        </h2>
    </div>
    <div style="width:100%;">
        <canvas id="oscars-active-users-barchart" style="display:block;max-height:400px;height:400px;"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('oscars-active-users-barchart').getContext('2d');
        var chart = null;
        function fetchAndRenderChart() {
            var interval = document.getElementById('oscars-active-users-interval').value;
            var timeframe = document.getElementById('oscars-active-users-timeframe').value;
            var data = new FormData();
            data.append('action', 'oscars_active_users_barchart_ajax');
            data.append('interval', interval);
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
                                label: 'Users last active',
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
                                title: { display: false, text: 'User Last Active by ' + interval.charAt(0).toUpperCase() + interval.slice(1) + ' (past ' + timeframe + ')'}
                            },
                            scales: {
                                x: { display: false, title: { display: false, text: interval.charAt(0).toUpperCase() + interval.slice(1) + 's Ago' }, ticks: { maxTicksLimit: 13 } },
                                y: { display: false, beginAtZero: true, title: { display: false, text: 'Number of Users' } }
                            }
                        }
                    });
                }
            });
        }
        document.getElementById('oscars-active-users-interval').addEventListener('change', fetchAndRenderChart);
        document.getElementById('oscars-active-users-timeframe').addEventListener('input', fetchAndRenderChart);
        fetchAndRenderChart();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_active_users_barchart', 'oscars_active_users_barchart_shortcode');

// AJAX handler for oscars_active_users_barchart
add_action('wp_ajax_oscars_active_users_barchart_ajax', function() {
    $interval = isset($_POST['interval']) ? strtolower($_POST['interval']) : 'day';
    $timeframe = isset($_POST['timeframe']) ? intval($_POST['timeframe']) : 7;
    if (!in_array($interval, ['day', 'week', 'month', 'year'])) $interval = 'day';
    if ($timeframe < 1) $timeframe = 7;
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    if (!file_exists($output_path)) {
        wp_send_json_error('No user stats data found.');
    }
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) {
        wp_send_json_error('User stats data is invalid.');
    }
    $now = strtotime('today');
    $bins = array_fill(0, $timeframe + 1, 0);
    foreach ($users as $user) {
        if (!empty($user['last-updated'])) {
            $last = strtotime($user['last-updated']);
            if ($last) {
                switch ($interval) {
                    case 'day':
                        $diff = floor(($now - $last) / 86400);
                        break;
                    case 'week':
                        $diff = floor(($now - $last) / (7 * 86400));
                        break;
                    case 'month':
                        $now_y = (int)date('Y', $now);
                        $now_m = (int)date('n', $now);
                        $last_y = (int)date('Y', $last);
                        $last_m = (int)date('n', $last);
                        $diff = ($now_y - $last_y) * 12 + ($now_m - $last_m);
                        break;
                    case 'year':
                        $diff = (int)date('Y', $now) - (int)date('Y', $last);
                        break;
                    default:
                        $diff = floor(($now - $last) / 86400);
                }
                if ($diff >= 0 && $diff <= $timeframe) {
                    $bins[$diff]++;
                }
            }
        }
    }
    $labels = [];
    for ($i = $timeframe; $i >= 0; $i--) {
        switch ($interval) {
            case 'day':
                $labels[] = $i . 'd ago';
                break;
            case 'week':
                $labels[] = $i . 'w ago';
                break;
            case 'month':
                $labels[] = $i . 'mo ago';
                break;
            case 'year':
                $labels[] = $i . 'y ago';
                break;
        }
    }
    $bins = array_reverse($bins);
    // $labels = array_reverse($labels);
    wp_send_json_success(['labels' => $labels, 'bins' => $bins]);
});
add_action('wp_ajax_nopriv_oscars_active_users_barchart_ajax', function() {
    // Duplicate the same handler for non-logged-in users
    $interval = isset($_POST['interval']) ? strtolower($_POST['interval']) : 'day';
    $timeframe = isset($_POST['timeframe']) ? intval($_POST['timeframe']) : 7;
    if (!in_array($interval, ['day', 'week', 'month', 'year'])) $interval = 'day';
    if ($timeframe < 1) $timeframe = 7;
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    if (!file_exists($output_path)) {
        wp_send_json_error('No user stats data found.');
    }
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) {
        wp_send_json_error('User stats data is invalid.');
    }
    $now = strtotime('today');
    $bins = array_fill(0, $timeframe + 1, 0);
    foreach ($users as $user) {
        if (!empty($user['last-updated'])) {
            $last = strtotime($user['last-updated']);
            if ($last) {
                switch ($interval) {
                    case 'day':
                        $diff = floor(($now - $last) / 86400);
                        break;
                    case 'week':
                        $diff = floor(($now - $last) / (7 * 86400));
                        break;
                    case 'month':
                        $now_y = (int)date('Y', $now);
                        $now_m = (int)date('n', $now);
                        $last_y = (int)date('Y', $last);
                        $last_m = (int)date('n', $last);
                        $diff = ($now_y - $last_y) * 12 + ($now_m - $last_m);
                        break;
                    case 'year':
                        $diff = (int)date('Y', $now) - (int)date('Y', $last);
                        break;
                    default:
                        $diff = floor(($now - $last) / 86400);
                }
                if ($diff >= 0 && $diff <= $timeframe) {
                    $bins[$diff]++;
                }
            }
        }
    }
    $labels = [];
    for ($i = $timeframe; $i >= 0; $i--) {
        switch ($interval) {
            case 'day':
                $labels[] = $i . 'd ago';
                break;
            case 'week':
                $labels[] = $i . 'w ago';
                break;
            case 'month':
                $labels[] = $i . 'mo ago';
                break;
            case 'year':
                $labels[] = $i . 'y ago';
                break;
        }
    }
    $bins = array_reverse($bins);
    wp_send_json_success(['labels' => $labels, 'bins' => $bins]);
});