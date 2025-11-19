<?php

/**
 * Shortcode: oscars_user_count_above_watched
 * Shows a number input and displays the count of users who have watched more than X films (AJAX, live update).
 */
function oscars_user_count_above_watched_shortcode($atts = []) {
    $atts = shortcode_atts([
        'films' => 0
    ], $atts);
    $default_films = intval($atts['films']);
    ob_start();
    ?>
    <div id="oscars-user-count-above-watched-wrap" class="loading">
        <span class="large" id="oscars-user-count-above-watched-result"></span>
        <span class="small" id="oscars-user-count-above-watched-percent"></span>
        <label>Watched more than <input type="number" id="oscars-user-count-above-watched-input" value="<?php echo esc_attr($default_films); ?>" min="0"> films.</label>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-above-watched-input');
        var result = document.getElementById('oscars-user-count-above-watched-result');
        var percent = document.getElementById('oscars-user-count-above-watched-percent');
        var wrap = document.getElementById('oscars-user-count-above-watched-wrap');
        function updateCount() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-above-watched-ajax');
                    if (el) {
                        var val = el.textContent.split('/');
                        result.textContent = val[0];
                        if (val.length > 1 && parseInt(val[1]) > 0) {
                            var pct = (100 * parseInt(val[0]) / parseInt(val[1])).toFixed(1);
                            percent.textContent = pct + '%';
                        } else {
                            percent.textContent = '';
                        }
                        wrap.classList.remove('loading');
                    }
                }
            };
            xhr.send('oscars_user_count_above_watched=' + encodeURIComponent(input.value) + '&oscars_user_count_above_watched_ajax=1');
        }
        input.addEventListener('input', updateCount);
        updateCount();
    });
    </script>
    <span id="oscars-user-count-above-watched-ajax" style="display:none"><?php echo oscars_user_count_above_watched_inner(true); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_above_watched', 'oscars_user_count_above_watched_shortcode');

function oscars_user_count_above_watched_inner($show_total = false) {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $threshold = isset($_POST['oscars_user_count_above_watched']) ? intval($_POST['oscars_user_count_above_watched']) : 0;
    if (!file_exists($output_path)) return $show_total ? '0/0' : '0';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return $show_total ? '0/0' : '0';
    $count = 0;
    foreach ($users as $user) {
        if (isset($user['total-watched']) && intval($user['total-watched']) > $threshold) {
            $count++;
        }
    }
    $total = is_array($users) ? count($users) : 0;
    return $show_total ? ($count . '/' . $total) : $count;
}