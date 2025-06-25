<?php
/**
 * Shortcode: oscars_user_count_active_days
 * Shows a number input and displays the count of users active in the last X days (AJAX, live update).
 */
function oscars_user_count_active_days_shortcode($atts = []) {
    $atts = shortcode_atts([
        'timeframe' => 0,
        'interval' =>  'day'
    ], $atts);
    $default_timeframe = intval($atts['timeframe']);
    $interval = in_array(strtolower($atts['interval']), ['day','week','month','year']) ? strtolower($atts['interval']) : 'day';
    ob_start();
    ?>
    <div id="oscars-user-count-active-days-wrap">
        <span class="large" id="oscars-user-count-active-days-result"></span>
        <span class="small" id="oscars-user-count-active-days-percent"></span>
        <label>Active in last <input type="number" id="oscars-user-count-active-days-input" value="<?php echo esc_attr($default_timeframe); ?>" min="1">
            <select id="oscars-count-active-users-interval">
                <option value="day"<?php if($interval==='day') echo ' selected'; ?>>Days</option>
                <option value="week"<?php if($interval==='week') echo ' selected'; ?>>Weeks</option>
                <option value="month"<?php if($interval==='month') echo ' selected'; ?>>Months</option>
                <option value="year"<?php if($interval==='year') echo ' selected'; ?>>Years</option>
            </select>
        </label>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-active-days-input');
        var intervalSelect = document.getElementById('oscars-count-active-users-interval');
        var result = document.getElementById('oscars-user-count-active-days-result');
        var percent = document.getElementById('oscars-user-count-active-days-percent');
        function updateCount() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-active-days-ajax');
                    if (el) {
                        var val = el.textContent.split('/');
                        result.textContent = val[0];
                        if (val.length > 1 && parseInt(val[1]) > 0) {
                            var pct = (100 * parseInt(val[0]) / parseInt(val[1])).toFixed(1);
                            percent.textContent = pct + '%';
                        } else {
                            percent.textContent = '';
                        }
                    }
                }
            };
            xhr.send('oscars_user_count_active_days=' + encodeURIComponent(input.value) + '&interval=' + encodeURIComponent(intervalSelect.value) + '&oscars_user_count_active_days_ajax=1');
        }
        input.addEventListener('input', updateCount);
        intervalSelect.addEventListener('change', updateCount);
        updateCount();
    });
    </script>
    <span id="oscars-user-count-active-days-ajax" style="display:none"><?php echo oscars_user_count_active_days_inner(true); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_active_days', 'oscars_user_count_active_days_shortcode');

function oscars_user_count_active_days_inner($show_total = false) {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $timeframe = isset($_POST['oscars_user_count_active_days']) ? intval($_POST['oscars_user_count_active_days']) : 7;
    $interval = isset($_POST['interval']) && in_array(strtolower($_POST['interval']), ['day','week','month','year']) ? strtolower($_POST['interval']) : 'day';
    if (!file_exists($output_path)) return $show_total ? '0/0' : '0';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return $show_total ? '0/0' : '0';
    $count = 0;
    $now = time();
    foreach ($users as $user) {
        if (!empty($user['last-updated'])) {
            $last = strtotime($user['last-updated']);
            if ($last) {
                switch ($interval) {
                    case 'day':
                        $diff = ($now - $last) / 86400;
                        break;
                    case 'week':
                        $diff = ($now - $last) / (7 * 86400);
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
                        $diff = ($now - $last) / 86400;
                }
                if ($diff >= 0 && $diff < $timeframe) {
                    $count++;
                }
            }
        }
    }
    $total = is_array($users) ? count($users) : 0;
    return $show_total ? ($count . '/' . $total) : $count;
}