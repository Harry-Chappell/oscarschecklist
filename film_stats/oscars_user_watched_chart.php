<?php

/**
 * Shortcode: oscars_user_watched_chart
 * Outputs a bar chart of watched films by year for the current user.
 */
function oscars_user_watched_chart_shortcode() {
    if (!is_user_logged_in()) return '<p>Please log in to view your chart.</p>';
    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    if (!file_exists($file_path)) return '<p>No user data found.</p>';
    $data = json_decode(file_get_contents($file_path), true);
    if (!$data || empty($data['watched'])) return '<p>No watched films data found.</p>';
    $watched_by_year = [];
    // Find min/max year
    $min_year = 1929;
    $max_year = intval(date('Y'));
    // Count watched per year
    foreach ($data['watched'] as $film) {
        if (!empty($film['film-year'])) {
            $y = intval($film['film-year']);
            if (!isset($watched_by_year[$y])) $watched_by_year[$y] = 0;
            $watched_by_year[$y]++;
        }
    }
    // Fill in all years from 1929 to current year with 0 if missing
    for ($y = $min_year; $y <= $max_year; $y++) {
        if (!isset($watched_by_year[$y])) $watched_by_year[$y] = 0;
    }
    ksort($watched_by_year);
    $labels = array_keys($watched_by_year);
    $counts = array_values($watched_by_year);
    $chart_id = 'watchedchart';
    $films_list_per_year = array();
    for ($y = $min_year; $y <= $max_year; $y++) {
        $films_list_per_year[$y] = array();
    }
    foreach ($data['watched'] as $film) {
        if (!empty($film['film-year'])) {
            $y = intval($film['film-year']);
            $title = isset($film['film-name']) ? $film['film-name'] : (isset($film['film-title']) ? $film['film-title'] : (isset($film['title']) ? $film['title'] : 'Untitled'));
            $url = isset($film['film-url']) ? $film['film-url'] : (isset($film['url']) ? $film['url'] : '#');
            $films_list_per_year[$y][] = array('title' => $title, 'url' => $url);
        }
    }
    // Group by decade after populating per-year
    $films_list_per_decade = array();
    foreach ($films_list_per_year as $year => $films_in_year) {
        if (count($films_in_year) === 0) continue;
        $decade = floor($year / 10) * 10;
        if (!isset($films_list_per_decade[$decade])) $films_list_per_decade[$decade] = array();
        $films_list_per_decade[$decade][$year] = $films_in_year;
    }
    krsort($films_list_per_decade); // Most recent decade first
    $first_decade = 1920;
    $last_decade = floor($max_year / 10) * 10;
    $all_decades = range($last_decade, $first_decade, -10); // Most recent first
    $uid = 'watchedchart';
    ob_start();
    ?>
    <canvas id="<?php echo esc_attr($chart_id); ?>" width="1000" height="300"></canvas>
    <div id="<?php echo $uid; ?>-decade-tabs" class="decade-tabs" style="margin-top:1em;">
        <div class="tab-buttons">
            <?php foreach ($all_decades as $i => $decade):
                $years = isset($films_list_per_decade[$decade]) ? $films_list_per_decade[$decade] : array();
                $decade_count = 0;
                foreach ($years as $films_in_year) { $decade_count += count($films_in_year); }
            ?>
                <button type="button" class="tab-btn" data-tab="<?php echo $uid . '-tab-' . $decade; ?>"><?php echo $decade; ?>s (<?php echo $decade_count; ?>)</button>
            <?php endforeach; ?>
        </div>
        <div class="tab-contents">
            <?php foreach ($all_decades as $i => $decade):
                $years = isset($films_list_per_decade[$decade]) ? $films_list_per_decade[$decade] : array();
                $decade_years = range($decade + 9, $decade, -1); // Most recent year first
            ?>
            <div class="tab-content" id="<?php echo $uid . '-tab-' . $decade; ?>">
                <ul>
                <?php foreach ($decade_years as $year):
                    if ($year > $max_year) continue; // Skip future years
                    $films_in_year = isset($years[$year]) ? $years[$year] : array();
                    usort($films_in_year, function($a, $b) { return strcasecmp($a['title'], $b['title']); });
                ?>
                    <li><strong><?php echo $year; ?></strong> (<?php echo count($films_in_year); ?>):
                        <ul>
                        <?php foreach ($films_in_year as $film): ?>
                            <li><a href="<?php echo esc_url($film['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($film['title']); ?></a></li>
                        <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script type="text/javascript">
    (function(){
        var tabContainer = document.getElementById('<?php echo $uid; ?>-decade-tabs');
        if (!tabContainer) return;
        var btns = tabContainer.querySelectorAll('.tab-btn');
        var tabs = tabContainer.querySelectorAll('.tab-content');
        btns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tabId = btn.getAttribute('data-tab');
                var tab = document.getElementById(tabId);
                var isActive = btn.classList.contains('active');
                btns.forEach(function(b) { b.classList.remove('active'); });
                tabs.forEach(function(t) { t.classList.remove('active'); });
                if (!isActive) {
                    btn.classList.add('active');
                    if (tab) tab.classList.add('active');
                }
            });
        });
    })();
    </script>
    <script>
    (function(){
        function renderChart_<?php echo $chart_id; ?>() {
            var canvas = document.getElementById('<?php echo esc_js($chart_id); ?>');
            if (!canvas || canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
                setTimeout(renderChart_<?php echo $chart_id; ?>, 100);
                return;
            }
            if (typeof Chart === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = renderChart_<?php echo $chart_id; ?>;
                document.head.appendChild(script);
                return;
            }
            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Films watched',
                        data: <?php echo json_encode($counts); ?>,
                        backgroundColor: '#c7a34e',
                        borderColor: '#c7a34e',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: { title: { display: true, text: 'Year' } },
                        y: { beginAtZero: true, title: { display: true, text: 'Films Watched' } }
                    }
                }
            });
        }
        document.addEventListener('DOMContentLoaded', renderChart_<?php echo $chart_id; ?>);
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_watched_chart', 'oscars_user_watched_chart_shortcode');
