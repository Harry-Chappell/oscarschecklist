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
    ob_start();
    ?>
    <canvas id="<?php echo esc_attr($chart_id); ?>" width="1000" height="300"></canvas>
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
                        title: { display: true, text: 'Films Watched by Year' }
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
