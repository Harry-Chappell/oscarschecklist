<?php
/**
 * Shortcode: oscars_user_count_active_days
 * Shows a number input and displays the count of users active in the last X days (AJAX, live update).
 */
function oscars_user_count_active_days_shortcode() {
    ob_start();
    ?>
    <div id="oscars-user-count-active-days-wrap">
        <span class="large" id="oscars-user-count-active-days-result"></span>
        <label>Active in last <input type="number" id="oscars-user-count-active-days-input" value="7" min="1"> days:</label>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-active-days-input');
        var result = document.getElementById('oscars-user-count-active-days-result');
        function updateCount() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-active-days-ajax');
                    if (el) result.textContent = el.textContent;
                }
            };
            xhr.send('oscars_user_count_active_days=' + encodeURIComponent(input.value) + '&oscars_user_count_active_days_ajax=1');
        }
        input.addEventListener('input', updateCount);
        updateCount();
    });
    </script>
    <span id="oscars-user-count-active-days-ajax" style="display:none"><?php echo oscars_user_count_active_days_inner(); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_active_days', 'oscars_user_count_active_days_shortcode');

function oscars_user_count_active_days_inner() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $days = isset($_POST['oscars_user_count_active_days']) ? intval($_POST['oscars_user_count_active_days']) : 7;
    if (!file_exists($output_path)) return '0';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return '0';
    $count = 0;
    $now = time();
    foreach ($users as $user) {
        if (!empty($user['last-updated'])) {
            $last = strtotime($user['last-updated']);
            if ($last && ($now - $last) <= ($days * 86400)) {
                $count++;
            }
        }
    }
    return $count;
}