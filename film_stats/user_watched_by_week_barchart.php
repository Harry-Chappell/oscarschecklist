<?php 
/**
 * Shortcode: oscars_user_watched_by_week_barchart
 * Shows a bar chart of how many films the current user watched in each of the last 52 weeks.
 * Uses Chart.js (loads from CDN if not present).
 */
function oscars_user_watched_by_week_barchart_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to see your watched films by week.</p>';
    }
    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    if (!file_exists($file_path)) {
        return '<p>No data found for current user.</p>';
    }
    $json = file_get_contents($file_path);
    $data = json_decode($json, true);
    if (!$data || empty($data['watched']) || !is_array($data['watched'])) {
        return '<p>No watched films data found.</p>';
    }
    $now = strtotime('today');
    $bins = array_fill(0, 53, 0); // 0 = this week, 52 = 52 weeks ago
    foreach ($data['watched'] as $film) {
        if (!empty($film['watched-date'])) {
            $watched = strtotime($film['watched-date']);
            if ($watched) {
                $weeks_ago = floor(($now - $watched) / (7 * 86400));
                if ($weeks_ago >= 0 && $weeks_ago <= 52) {
                    $bins[$weeks_ago]++;
                }
            }
        }
    }
    // Prepare labels: 0 (left) to 52 (right)
    $labels = [];
    for ($i = 0; $i <= 52; $i++) {
        $labels[] = $i . 'w ago';
    }
    // Only reverse bins so left is 0w ago, right is 52w ago
    $bins = array_reverse($bins);
    $labels = array_reverse($labels);
    ob_start();
    ?>
    <canvas id="oscars-user-watched-by-week-barchart" width="1000" height="300"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    function renderUserWatchedByWeekChart() {
        var canvas = document.getElementById('oscars-user-watched-by-week-barchart');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        // Wait until canvas is visible and has width
        if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
            setTimeout(renderUserWatchedByWeekChart, 100);
            return;
        }
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Films watched',
                    data: <?php echo json_encode($bins); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Your Films Watched by Week (past 52 weeks)' }
                },
                scales: {
                    x: { title: { display: true, text: 'Weeks Ago' }, ticks: { maxTicksLimit: 13 } },
                    y: { beginAtZero: true, title: { display: true, text: 'Films Watched' } }
                }
            }
        });
    }
    document.addEventListener('DOMContentLoaded', renderUserWatchedByWeekChart);
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('user_watched_by_week_barchart', 'oscars_user_watched_by_week_barchart_shortcode');