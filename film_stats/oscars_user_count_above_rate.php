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
        <label>Prediction rate above (%): <input type="number" id="oscars-user-count-above-rate-input" value="<?php echo esc_attr($default); ?>" min="0" max="100" style="width:60px"></label>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-above-rate-input');
        var result = document.getElementById('oscars-user-count-above-rate-result');
        var percent = document.getElementById('oscars-user-count-above-rate-percent');
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
                        var data = el.textContent.split('|');
                        result.textContent = data[0] || '0';
                        percent.textContent = (data.length > 1 ? data[1] : '');
                    }
                }
            };
            xhr.send('oscars_user_count_above_rate=' + encodeURIComponent(input.value) + '&oscars_user_count_above_rate_ajax=1');
        }
        input.addEventListener('input', updateCount);
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
    $total = 0;
    foreach ($users as $user) {
        if (isset($user['correct-prediction-rate']) && is_numeric($user['correct-prediction-rate'])) {
            $total++;
            if ((100 * floatval($user['correct-prediction-rate'])) > $threshold) {
                $count++;
            }
        }
    }
    $percent = $total > 0 ? round(100 * $count / $total, 1) : 0;
    return $count . '|' . ($total > 0 ? $percent . '% of users' : '');
}