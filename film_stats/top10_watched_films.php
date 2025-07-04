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
    if ($atts['year'] === 'current' ) {
        $atts['year'] = date('Y'); // Default to current year if not provided
    }
    $has_exact_year = ($atts['year'] !== '' && is_numeric($atts['year']));
    
    // Calculate default end year as the year from 7 months ago
    $seven_months_ago = (new DateTime())->modify('-3 months');
    $default_end_year = intval($seven_months_ago->format('Y'));
    $selected_year = isset($atts['year']) && is_numeric($atts['year']) ? intval($atts['year']) : $default_end_year;
    if (isset($_POST['oscars_watched_films_year']) && is_numeric($_POST['oscars_watched_films_year'])) {
        $selected_year = intval($_POST['oscars_watched_films_year']);
    }
    $label = $has_exact_year ? 'from' : 'from 1929 to';
    $uid = uniqid('oscars_watched_films_');
    // Allow AJAX to pass the UID so the response div matches the JS selector
    if (isset($_POST['oscars_watched_films_uid']) && preg_match('/^oscars_watched_films_[a-zA-Z0-9]+$/', $_POST['oscars_watched_films_uid'])) {
        $uid = $_POST['oscars_watched_films_uid'];
    }
    $start_year = 1929;
    if (isset($_POST['oscars_watched_films_start_year']) && is_numeric($_POST['oscars_watched_films_start_year'])) {
        $start_year = intval($_POST['oscars_watched_films_start_year']);
    }
    ob_start();
    ?>
    <h3>Top <?php echo $top ?></h3>
    <h2>Most Watched Films</h2>
    <div id="<?php echo $uid; ?>-filter-wrap">
        <label><h3>
        <?php if (!$has_exact_year): ?>
            from <input type="number" id="<?php echo $uid; ?>-start-year-input" value="<?php echo esc_attr($start_year); ?>" min="1929" max="<?php echo esc_attr($selected_year); ?>" style="width:80px"> to 
        <?php else: ?>
            from
        <?php endif; ?>
        <input type="number" id="<?php echo $uid; ?>-year-input" value="<?php echo esc_attr($selected_year); ?>" min="1929" max="<?php echo esc_attr(date('Y')); ?>" style="width:80px">
        </h3></label>
    </div>
    <div id="<?php echo $uid; ?>-list-wrap"><?php echo oscars_watched_films_list_inner($top, $selected_year, $has_exact_year, $uid, $start_year); ?></div>
    <div id="<?php echo $uid; ?>-ajax" style="display:none"><?php echo oscars_watched_films_list_inner($top, $selected_year, $has_exact_year, $uid, $start_year); ?></div>
    <script>
    (function() {
        var yearInput = document.getElementById('<?php echo $uid; ?>-year-input');
        <?php if (!$has_exact_year): ?>
        var startYearInput = document.getElementById('<?php echo $uid; ?>-start-year-input');
        <?php endif; ?>
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
            xhr.send('oscars_watched_films_top=<?php echo $top; ?>&oscars_watched_films_year=' + encodeURIComponent(yearInput.value)
                <?php if (!$has_exact_year): ?>
                + '&oscars_watched_films_start_year=' + encodeURIComponent(startYearInput.value)
                <?php endif; ?>
                + '&oscars_watched_films_exact=<?php echo $has_exact_year ? '1' : '0'; ?>&oscars_watched_films_uid=<?php echo $uid; ?>&oscars_watched_films_ajax=1');
        }
        yearInput.addEventListener('input', updateList);
        yearInput.addEventListener('change', updateList);
        <?php if (!$has_exact_year): ?>
        startYearInput.addEventListener('input', updateList);
        startYearInput.addEventListener('change', updateList);
        <?php endif; ?>
        updateList();
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('watched_films', 'oscars_watched_films_list_shortcode');

function oscars_watched_films_list_inner($top = 10, $year = null, $exact = false, $uid = null, $start_year = 1929) {
    $output_path = ABSPATH . 'wp-content/uploads/films_stats.json';
    if (isset($_POST['oscars_watched_films_top']) && is_numeric($_POST['oscars_watched_films_top'])) {
        $top = intval($_POST['oscars_watched_films_top']);
    }
    if (isset($_POST['oscars_watched_films_year']) && is_numeric($_POST['oscars_watched_films_year'])) {
        $year = intval($_POST['oscars_watched_films_year']);
    }
    if (isset($_POST['oscars_watched_films_start_year']) && is_numeric($_POST['oscars_watched_films_start_year'])) {
        $start_year = intval($_POST['oscars_watched_films_start_year']);
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
    if (!$start_year) {
        $start_year = 1929;
    }
    $json = file_get_contents($output_path);
    $films = json_decode($json, true);
    if (!$films || !is_array($films)) {
        return '<p>Stats file is invalid.</p>';
    }
    $watched_films = array_filter($films, function($film) use ($year, $exact, $start_year) {
        if (!isset($film['watched-count']) || $film['watched-count'] <= 0 || !isset($film['film-year'])) return false;
        $film_year = intval($film['film-year']);
        return $exact ? ($film_year === $year) : ($film_year >= $start_year && $film_year <= $year);
    });
    // Sort by watched-count descending
    usort($watched_films, function($a, $b) {
        return $b['watched-count'] <=> $a['watched-count'];
    });
    $watched_films = array_slice($watched_films, 0, $top);
    if (empty($watched_films)) {
        return '<p>No films have been watched yet.</p>';
    }
    $output .= '<ul class="leaderboard oscars-watched-films-list">';
    $rank = 1;
    $suffixes = function($n) {
        if ($n % 10 == 1 && $n % 100 != 11) return 'st';
        if ($n % 10 == 2 && $n % 100 != 12) return 'nd';
        if ($n % 10 == 3 && $n % 100 != 13) return 'rd';
        return 'th';
    };
    foreach ($watched_films as $film) {
        $output .= '<li>';
        $output .= '<span class="rank">' . $rank . '<sup>' . $suffixes($rank) . '</sup></span>';
        $output .= '<span class="film-title"><a href="' . esc_url($film['film-url']) . '" target="_blank">' . esc_html($film['film-name']) . '</a></span>';
        $output .= '<span class="stat-value">' . intval($film['watched-count']) . '</span>';
        $output .= '</li>';
        $rank++;
    }
    $output .= '</ul>';
    if ($uid) $output .= '</div>';
    return $output;
}

