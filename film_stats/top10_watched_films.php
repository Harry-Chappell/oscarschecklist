<?php

/**
 * Shortcode to output watched films, ordered by watched count, with links. Supports [watched_films top="10"].
 * Includes a year input to filter films before a given year (default: next year).
 */
function oscars_watched_films_list_shortcode($atts = []) {
    $atts = shortcode_atts([
        'top' => 10,
        'year' => ''
    ], $atts);
    $top = is_numeric($atts['top']) && intval($atts['top']) > 0 ? intval($atts['top']) : 10;
    $has_exact_year = ($atts['year'] !== '' && is_numeric($atts['year']));
    $selected_year = $has_exact_year ? intval($atts['year']) : intval(date('Y'));
    $label = $has_exact_year ? 'Only show films from' : 'Only show films up to';
    $uid = uniqid('oscars_watched_films_');
    // Allow AJAX to pass the UID so the response div matches the JS selector
    if (isset($_POST['oscars_watched_films_uid']) && preg_match('/^oscars_watched_films_[a-zA-Z0-9]+$/', $_POST['oscars_watched_films_uid'])) {
        $uid = $_POST['oscars_watched_films_uid'];
    }
    ob_start();
    ?>
    <div id="<?php echo $uid; ?>-filter-wrap">
        <label><?php echo $label; ?> <input type="number" id="<?php echo $uid; ?>-year-input" value="<?php echo esc_attr($selected_year); ?>" min="1927" max="<?php echo esc_attr(date('Y')); ?>" style="width:80px"></label>
    </div>
    <div id="<?php echo $uid; ?>-list-wrap"><?php echo oscars_watched_films_list_inner($top, $selected_year, $has_exact_year, $uid); ?></div>
    <div id="<?php echo $uid; ?>-ajax" style="display:none"><?php echo oscars_watched_films_list_inner($top, $selected_year, $has_exact_year, $uid); ?></div>
    <script>
    (function() {
        var yearInput = document.getElementById('<?php echo $uid; ?>-year-input');
        var listWrap = document.getElementById('<?php echo $uid; ?>-list-wrap');
        function updateList() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#<?php echo $uid; ?>-ajax');
                    if (el) listWrap.innerHTML = el.innerHTML;
                }
            };
            xhr.send('oscars_watched_films_top=<?php echo $top; ?>&oscars_watched_films_year=' + encodeURIComponent(yearInput.value) + '&oscars_watched_films_exact=<?php echo $has_exact_year ? '1' : '0'; ?>&oscars_watched_films_uid=<?php echo $uid; ?>&oscars_watched_films_ajax=1');
        }
        yearInput.addEventListener('input', updateList);
        yearInput.addEventListener('change', updateList);
        updateList();
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('watched_films', 'oscars_watched_films_list_shortcode');

function oscars_watched_films_list_inner($top = 10, $year = null, $exact = false, $uid = null) {
    $output_path = ABSPATH . 'wp-content/uploads/films_stats.json';
    if (isset($_POST['oscars_watched_films_top']) && is_numeric($_POST['oscars_watched_films_top'])) {
        $top = intval($_POST['oscars_watched_films_top']);
    }
    if (isset($_POST['oscars_watched_films_year']) && is_numeric($_POST['oscars_watched_films_year'])) {
        $year = intval($_POST['oscars_watched_films_year']);
    }
    if (isset($_POST['oscars_watched_films_exact'])) {
        $exact = ($_POST['oscars_watched_films_exact'] == '1');
    }
    // Use the passed UID for the AJAX div if provided
    if ($uid === null && isset($_POST['oscars_watched_films_uid']) && preg_match('/^oscars_watched_films_[a-zA-Z0-9]+$/', $_POST['oscars_watched_films_uid'])) {
        $uid = $_POST['oscars_watched_films_uid'];
    }
    if ($uid) {
        $output = '<div id="' . $uid . '-ajax">';
    } else {
        $output = '';
    }
    if (!$year) {
        $year = date('Y');
    }
    if (!file_exists($output_path)) {
        return '<p>No stats file found. Please generate it first.</p>';
    }
    $json = file_get_contents($output_path);
    $films = json_decode($json, true);
    if (!$films || !is_array($films)) {
        return '<p>Stats file is invalid.</p>';
    }
    // Filter to only watched films for the exact year if $exact is true, otherwise up to and including the year
    $watched_films = array_filter($films, function($film) use ($year, $exact) {
        if (!isset($film['watched-count']) || $film['watched-count'] <= 0 || !isset($film['film-year'])) return false;
        $film_year = intval($film['film-year']);
        return $exact ? ($film_year === $year) : ($film_year <= $year);
    });
    // Sort by watched-count descending
    usort($watched_films, function($a, $b) {
        return $b['watched-count'] <=> $a['watched-count'];
    });
    $watched_films = array_slice($watched_films, 0, $top);
    if (empty($watched_films)) {
        return '<p>No films have been watched yet.</p>';
    }
    $output .= '<ul class="oscars-watched-films-list">';
    foreach ($watched_films as $film) {
        $output .= '<li>';
        $output .= '<a href="' . esc_url($film['film-url']) . '" target="_blank">' . esc_html($film['film-name']) . '</a>';
        $output .= ' <span>(' . intval($film['watched-count']) . ')</span>';
        $output .= '</li>';
    }
    $output .= '</ul>';
    if ($uid) $output .= '</div>';
    return $output;
}

