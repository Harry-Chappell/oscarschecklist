<?php

/**
 * Shortcode: oscars_user_count_above_watched
 * Shows a number input and displays the count of users who have watched more than X films (AJAX, live update).
 */
function oscars_user_count_above_watched_shortcode() {
    ob_start();
    ?>
    <div id="oscars-user-count-above-watched-wrap">
        <label>Films watched more than: <input type="number" id="oscars-user-count-above-watched-input" value="0" min="0" style="width:60px"></label>
        <span id="oscars-user-count-above-watched-result"></span>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-above-watched-input');
        var result = document.getElementById('oscars-user-count-above-watched-result');
        function updateCount() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-above-watched-ajax');
                    if (el) result.textContent = el.textContent;
                }
            };
            xhr.send('oscars_user_count_above_watched=' + encodeURIComponent(input.value) + '&oscars_user_count_above_watched_ajax=1');
        }
        input.addEventListener('input', updateCount);
        updateCount();
    });
    </script>
    <span id="oscars-user-count-above-watched-ajax" style="display:none"><?php echo oscars_user_count_above_watched_inner(); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_above_watched', 'oscars_user_count_above_watched_shortcode');

function oscars_user_count_above_watched_inner() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $threshold = isset($_POST['oscars_user_count_above_watched']) ? intval($_POST['oscars_user_count_above_watched']) : 0;
    if (!file_exists($output_path)) return '0';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return '0';
    $count = 0;
    foreach ($users as $user) {
        if (isset($user['total-watched']) && intval($user['total-watched']) > $threshold) {
            $count++;
        }
    }
    return $count;
}