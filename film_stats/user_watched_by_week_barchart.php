<?php 
/**
 * Shortcode: oscars_user_watched_by_week_barchart
 * Shows a bar chart of how many films the current user watched in each of the last 52 weeks.
 * Uses Chart.js (loads from CDN if not present).
 */
function oscars_user_watched_by_week_barchart_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to see your watched films by interval.</p>';
    }
    ob_start();
    ?>
    <h2>You and Your Friends' Films Watched</h2>
    <div id="oscars-user-watched-by-interval-controls" style="margin-bottom:1em">
        <label>Interval:
            <select id="oscars-user-watched-interval">
                <option value="day" selected>Day</option>
                <option value="week">Week</option>
                <option value="month">Month</option>
                <option value="year">Year</option>
            </select>
        </label>
        <label style="margin-left:1em;">Timeframe:
            <input type="number" id="oscars-user-watched-timeframe" value="<?php echo max(1, floor((strtotime('today')-strtotime(date('Y') . '-06-16'))/86400)); ?>" min="1" max="365" style="width:60px">
        </label>
    </div>
    <canvas id="oscars-user-watched-by-interval-barchart" width="1000" height="300"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('oscars-user-watched-by-interval-barchart').getContext('2d');
        var chart = null;
        function fetchAndRenderChart() {
            var interval = document.getElementById('oscars-user-watched-interval').value;
            var timeframe = document.getElementById('oscars-user-watched-timeframe').value;
            var data = new FormData();
            data.append('action', 'oscars_user_watched_by_interval_barchart_ajax');
            data.append('interval', interval);
            data.append('timeframe', timeframe);
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
            .then(response => response.json())
            .then(json => {
                console.log('AJAX response:', json);
                if (json.success) {
                    if (chart) chart.destroy();
                    // Prepare datasets: current user and friends
                    var datasets = [];
                    if (json.data.friends && Array.isArray(json.data.friends)) {
                        console.log('Friends data:', json.data.friends);
                        var friendColors = [
                            '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f', '#edc949', '#af7aa1', '#ff9da7', '#9c755f', '#bab0ab'
                        ];
                        json.data.friends.forEach(function(friend, idx) {
                            datasets.push({
                                label: friend.display_name || 'Friend ' + (idx+1),
                                data: friend.bins,
                                fill: true,
                                backgroundColor: friendColors[idx % friendColors.length] + '22', // 13% opacity
                                borderColor: friendColors[idx % friendColors.length],
                                borderWidth: 2,
                                pointRadius: 0,
                                tension: 0.3
                            });
                        });
                    } else {
                        console.log('No friends data found in AJAX response.');
                    }
                    // Current user on top
                    datasets.push({
                        label: 'You',
                        data: json.data.bins,
                        fill: true,
                        backgroundColor: 'rgba(199, 163, 79, 0.3)',
                        borderColor: '#c7a34f',
                        borderWidth: 3,
                        pointRadius: 2,
                        tension: 0.3
                    });
                    chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: json.data.labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: true },
                                // title: { display: true, text: 'Your Films Watched by ' + interval.charAt(0).toUpperCase() + interval.slice(1) + ' (past ' + timeframe + ')'}
                            },
                            elements: {
                                line: { borderJoinStyle: 'round' }
                            },
                            scales: {
                                x: { title: { display: true, text: interval.charAt(0).toUpperCase() + interval.slice(1) + 's Ago' }, ticks: { maxTicksLimit: 13 } },
                                y: { beginAtZero: true, title: { display: true, text: 'Films Watched' } }
                            }
                        }
                    });
                } else {
                    console.log('AJAX error:', json);
                }
            });
        }
        document.getElementById('oscars-user-watched-interval').addEventListener('change', fetchAndRenderChart);
        document.getElementById('oscars-user-watched-timeframe').addEventListener('input', fetchAndRenderChart);
        fetchAndRenderChart();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('user_watched_by_week_barchart', 'oscars_user_watched_by_week_barchart_shortcode');

// AJAX handler for user watched by interval barchart
add_action('wp_ajax_oscars_user_watched_by_interval_barchart_ajax', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in.');
    }
    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    if (!file_exists($file_path)) {
        wp_send_json_error('No data found for current user.');
    }
    $json = file_get_contents($file_path);
    $data = json_decode($json, true);
    if (!$data || empty($data['watched']) || !is_array($data['watched'])) {
        wp_send_json_error('No watched films data found.');
    }
    $interval = isset($_POST['interval']) ? strtolower($_POST['interval']) : 'week';
    $timeframe = isset($_POST['timeframe']) ? intval($_POST['timeframe']) : 52;
    if (!in_array($interval, ['day', 'week', 'month', 'year'])) $interval = 'week';
    if ($timeframe < 1) $timeframe = 52;
    $now = strtotime('today');
    $bins = array_fill(0, $timeframe + 1, 0);
    foreach ($data['watched'] as $film) {
        if (!empty($film['watched-date'])) {
            $watched = strtotime($film['watched-date']);
            if ($watched) {
                switch ($interval) {
                    case 'day':
                        $diff = floor(($now - $watched) / 86400);
                        break;
                    case 'week':
                        $diff = floor(($now - $watched) / (7 * 86400));
                        break;
                    case 'month':
                        $now_y = (int)date('Y', $now);
                        $now_m = (int)date('n', $now);
                        $watched_y = (int)date('Y', $watched);
                        $watched_m = (int)date('n', $watched);
                        $diff = ($now_y - $watched_y) * 12 + ($now_m - $watched_m);
                        break;
                    case 'year':
                        $diff = (int)date('Y', $now) - (int)date('Y', $watched);
                        break;
                    default:
                        $diff = floor(($now - $watched) / (7 * 86400));
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
    // --- BuddyPress friends ---
    $friends_data = [];
    if (function_exists('friends_get_friend_user_ids')) {
        $friend_ids = friends_get_friend_user_ids($user_id);
        foreach ($friend_ids as $fid) {
            $fpath = wp_upload_dir()['basedir'] . "/user_meta/user_{$fid}.json";
            if (!file_exists($fpath)) {
                continue;
            }
            $fjson = file_get_contents($fpath);
            $fdata = json_decode($fjson, true);
            if (!$fdata || empty($fdata['watched']) || !is_array($fdata['watched'])) {
                continue;
            }
            $fbins = array_fill(0, $timeframe + 1, 0);
            foreach ($fdata['watched'] as $film) {
                if (!empty($film['watched-date'])) {
                    $watched = strtotime($film['watched-date']);
                    if ($watched) {
                        switch ($interval) {
                            case 'day':
                                $diff = floor(($now - $watched) / 86400);
                                break;
                            case 'week':
                                $diff = floor(($now - $watched) / (7 * 86400));
                                break;
                            case 'month':
                                $now_y = (int)date('Y', $now);
                                $now_m = (int)date('n', $now);
                                $watched_y = (int)date('Y', $watched);
                                $watched_m = (int)date('n', $watched);
                                $diff = ($now_y - $watched_y) * 12 + ($now_m - $watched_m);
                                break;
                            case 'year':
                                $diff = (int)date('Y', $now) - (int)date('Y', $watched);
                                break;
                            default:
                                $diff = floor(($now - $watched) / (7 * 86400));
                        }
                        if ($diff >= 0 && $diff <= $timeframe) {
                            $fbins[$diff]++;
                        }
                    }
                }
            }
            $friend_user = get_userdata($fid);
            $display_name = $friend_user ? $friend_user->user_login : '';
            $friends_data[] = [
                'id' => $fid,
                'display_name' => $display_name,
                'bins' => array_reverse($fbins)
            ];
        }
    }
    wp_send_json_success(['labels' => $labels, 'bins' => $bins, 'friends' => $friends_data]);
});