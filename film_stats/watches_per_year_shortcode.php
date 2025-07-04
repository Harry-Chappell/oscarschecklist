<?php
/**
 * Shortcode to output the total number of watches per year from 1927 to the current year.
 * Usage: [watches_per_year]
 */
function oscars_watches_per_year_shortcode() {
    $output_path = ABSPATH . 'wp-content/uploads/films_stats.json';
    if (!file_exists($output_path)) {
        return '<p>Stats file not found.</p>';
    }
    $json = file_get_contents($output_path);
    $films = json_decode($json, true);
    if (!$films || !is_array($films)) {
        return '<p>Stats file is invalid.</p>';
    }
    $start_year = 1929;
    $end_year = intval(date('Y'));
    $watches_per_year = array();
    // Initialize all years to 0
    for ($year = $start_year; $year <= $end_year; $year++) {
        $watches_per_year[$year] = 0;
    }
    // Sum watched-counts per year
    foreach ($films as $film) {
        if (isset($film['film-year']) && isset($film['watched-count'])) {
            $film_year = intval($film['film-year']);
            if ($film_year >= $start_year && $film_year <= $end_year) {
                $watches_per_year[$film_year] += intval($film['watched-count']);
            }
        }
    }
    // Handle input for start and end year
    $default_start_year = 1929;
    $default_end_year = $end_year;
    $selected_start_year = $default_start_year;
    $selected_end_year = $default_end_year;
    if (isset($_POST['watches_per_year_start']) && is_numeric($_POST['watches_per_year_start'])) {
        $selected_start_year = max($default_start_year, intval($_POST['watches_per_year_start']));
    }
    if (isset($_POST['watches_per_year_end']) && is_numeric($_POST['watches_per_year_end'])) {
        $selected_end_year = min($default_end_year, intval($_POST['watches_per_year_end']));
    }
    // Filter years for chart
    $filtered_years = array();
    $filtered_counts = array();
    foreach ($watches_per_year as $year => $count) {
        if ($year >= $selected_start_year && $year <= $selected_end_year) {
            $filtered_years[] = $year;
            $filtered_counts[] = $count;
        }
    }
    // Find min/max watched years
    $range_counts = array();
    foreach ($watches_per_year as $year => $count) {
        if ($year >= $selected_start_year && $year <= $selected_end_year) {
            $range_counts[$year] = $count;
        }
    }
    $min_years = array_keys($range_counts, min($range_counts));
    $max_years = array_keys($range_counts, max($range_counts));
    $min_count = min($range_counts);
    $max_count = max($range_counts);
    $uid = uniqid('watches_per_year_');
    ob_start();
    ?>
    <h2>Total Watches Per Year</h2>
    <form id="<?php echo $uid; ?>-form" style="margin-bottom:1em;" onsubmit="return false;">
        <label>From <input type="number" name="watches_per_year_start" id="<?php echo $uid; ?>-start" value="<?php echo esc_attr($selected_start_year); ?>" min="<?php echo $default_start_year; ?>" max="<?php echo $default_end_year; ?>" style="width:80px"></label>
        <label>to <input type="number" name="watches_per_year_end" id="<?php echo $uid; ?>-end" value="<?php echo esc_attr($selected_end_year); ?>" min="<?php echo $default_start_year; ?>" max="<?php echo $default_end_year; ?>" style="width:80px"></label>
    </form>
    <div style="max-width:100%; max-height:600px; overflow:auto;"><canvas id="<?php echo $uid; ?>" style="max-height:600px;width:100%;height:400px;"></canvas></div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript">
    window.addEventListener('DOMContentLoaded', function() {
    (function(){
        var years = <?php echo json_encode(array_values(array_keys($watches_per_year))); ?>;
        var counts = <?php echo json_encode(array_values($watches_per_year)); ?>;
        var defaultStart = <?php echo $default_start_year; ?>;
        var defaultEnd = <?php echo $default_end_year; ?>;
        var uid = "<?php echo $uid; ?>";
        var chart;
        var ctx = document.getElementById(uid).getContext("2d");
        var startInput = document.getElementById(uid + "-start");
        var endInput = document.getElementById(uid + "-end");
        function getFilteredData(start, end) {
            var filteredYears = [], filteredCounts = [];
            for (var i = 0; i < years.length; i++) {
                var y = parseInt(years[i]);
                if (y >= start && y <= end) {
                    filteredYears.push(years[i]);
                    filteredCounts.push(counts[i]);
                }
            }
            return {years: filteredYears, counts: filteredCounts};
        }
        function getMinMax(filteredCounts, filteredYears) {
            if(filteredCounts.length === 0) return {min: 0, max: 0, minYears: [], maxYears: []};
            var min = Math.min.apply(null, filteredCounts);
            var max = Math.max.apply(null, filteredCounts);
            var minYears = [], maxYears = [];
            for (var i = 0; i < filteredCounts.length; i++) {
                if (filteredCounts[i] === min) minYears.push(filteredYears[i]);
                if (filteredCounts[i] === max) maxYears.push(filteredYears[i]);
            }
            return {min, max, minYears, maxYears};
        }
        function updateChart() {
            var start = parseInt(startInput.value);
            var end = parseInt(endInput.value);
            if (isNaN(start) || isNaN(end) || start > end) return;
            var data = getFilteredData(start, end);
            chart.data.labels = data.years;
            chart.data.datasets[0].data = data.counts;
            chart.options.plugins.title.text = "Total Watches Per Year (" + start + "–" + end + ")";
            chart.update();
            // Update min/max display
            var minmax = getMinMax(data.counts, data.years);
            var minText = (minmax.minYears.length && minmax.min !== minmax.max) ? (minmax.minYears.join(', ') + ' (' + minmax.min + ' watches)') : 'N/A';
            var maxText = (minmax.maxYears.length && minmax.max !== 0) ? (minmax.maxYears.join(', ') + ' (' + minmax.max + ' watches)') : 'N/A';
            document.getElementById(uid + '-minmax').innerHTML =
                '<strong>Most watched year(s):</strong> ' + maxText + '<br>' +
                '<strong>Least watched year(s):</strong> ' + minText;
        }
        chart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: getFilteredData(defaultStart, defaultEnd).years,
                datasets: [{
                    label: "Total Watches",
                    data: getFilteredData(defaultStart, defaultEnd).counts,
                    backgroundColor: "#c7a34f",
                    borderColor: "#c7a34f",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    // title: { display: true, text: "Total Watches Per Year (" + defaultStart + "–" + defaultEnd + ")" }
                },
                scales: {
                    x: { title: { display: true, text: "Year" }, ticks: { maxTicksLimit: 20 } },
                    y: { beginAtZero: true, title: { display: true, text: "Watches" } }
                }
            }
            });
        
        startInput.addEventListener('input', updateChart);
        endInput.addEventListener('input', updateChart);
        startInput.addEventListener('change', updateChart);
        endInput.addEventListener('change', updateChart);
        // Initial min/max display
        updateChart();
    })();
    });
    </script>
    <div id="<?php echo $uid; ?>-minmax" style="margin-top:1em;"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('watches_per_year', 'oscars_watches_per_year_shortcode');
