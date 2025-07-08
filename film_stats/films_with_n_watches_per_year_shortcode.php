<?php
/**
 * Shortcode to output the number of films per year with exactly N watches (default N=0).
 * Usage: [films_with_n_watches_per_year]
 */
function oscars_films_with_n_watches_per_year_shortcode($atts = array()) {
    $atts = shortcode_atts([
        'showfilms' => 'false',
    ], $atts, 'films_with_n_watches_per_year');
    $show_films = ($atts['showfilms'] === 'true');

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
    $default_n = 0;
    $selected_n = $default_n;
    if (isset($_POST['films_with_n_watches_n']) && is_numeric($_POST['films_with_n_watches_n'])) {
        $selected_n = intval($_POST['films_with_n_watches_n']);
    } elseif (isset($_GET['films_with_n_watches_n']) && is_numeric($_GET['films_with_n_watches_n'])) {
        $selected_n = intval($_GET['films_with_n_watches_n']);
    }
    // Count films with exactly N watches per year
    $films_with_n_watches_per_year = array();
    for ($year = $start_year; $year <= $end_year; $year++) {
        $films_with_n_watches_per_year[$year] = 0;
    }
    foreach ($films as $film) {
        if (isset($film['film-year']) && isset($film['watched-count'])) {
            $film_year = intval($film['film-year']);
            $watched_count = intval($film['watched-count']);
            if ($film_year >= $start_year && $film_year <= $end_year && $watched_count === $selected_n) {
                $films_with_n_watches_per_year[$film_year]++;
            }
        }
    }
    // Collect films per year if needed
    $films_list_per_year = array();
    if ($show_films) {
        for ($year = $start_year; $year <= $end_year; $year++) {
            $films_list_per_year[$year] = array();
        }
        foreach ($films as $film) {
            if (isset($film['film-year']) && isset($film['watched-count'])) {
                $film_year = intval($film['film-year']);
                $watched_count = intval($film['watched-count']);
                if ($film_year >= $start_year && $film_year <= $end_year && $watched_count === $selected_n) {
                    $title = isset($film['film-name']) ? $film['film-name'] : (isset($film['film-title']) ? $film['film-title'] : (isset($film['title']) ? $film['title'] : 'Untitled'));
                    $url = isset($film['film-url']) ? $film['film-url'] : (isset($film['url']) ? $film['url'] : '#');
                    $films_list_per_year[$film_year][] = array(
                        'title' => $title,
                        'url' => $url
                    );
                }
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
    }
    $uid = uniqid('films_with_n_watches_');
    ob_start();
    ?>
    <h2>Films Per Year with Exactly <span id="<?php echo $uid; ?>-n-label"><?php echo esc_html($selected_n); ?></span> Watches</h2>
    <div style="max-width:100%; max-height:300px; overflow:auto;"><canvas id="<?php echo $uid; ?>" style="max-height:300px;width:100%;height:300px;"></canvas></div>
    <form id="<?php echo $uid; ?>-form" style="margin-bottom:1em;" onsubmit="return false;">
        <label>Films that have been watched <input type="number" name="films_with_n_watches_n" id="<?php echo $uid; ?>-n" value="<?php echo esc_attr($selected_n); ?>" min="0" style="width:80px"> times: <span id="<?php echo $uid; ?>-total-label"></span> (out of <?php echo count($films); ?> films)</label>
    </form>
    <?php if ($show_films): ?>
    <?php
        // Get all decades from 1920s to current decade
        $first_decade = 1920;
        $last_decade = floor($end_year / 10) * 10;
        $all_decades = range($last_decade, $first_decade, -10); // Most recent first
    ?>
    <div id="<?php echo $uid; ?>-decade-tabs" class="decade-tabs" style="margin-top:1em;">
        <div class="tab-buttons">
            <?php foreach (
                $all_decades as $i => $decade):
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
                // Close all tabs
                btns.forEach(function(b) { b.classList.remove('active'); });
                tabs.forEach(function(t) { t.classList.remove('active'); });
                // If not already active, open it
                if (!isActive) {
                    btn.classList.add('active');
                    if (tab) tab.classList.add('active');
                }
                // If already active, leave all closed
            });
        });
    })();
    </script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript">
    window.addEventListener('DOMContentLoaded', function() {
    (function(){
        var years = <?php echo json_encode(array_values(array_keys($films_with_n_watches_per_year))); ?>;
        var counts = <?php echo json_encode(array_values($films_with_n_watches_per_year)); ?>;
        var defaultN = <?php echo $default_n; ?>;
        var uid = "<?php echo $uid; ?>";
        var chart;
        var ctx = document.getElementById(uid).getContext("2d");
        var nInput = document.getElementById(uid + "-n");
        var nLabel = document.getElementById(uid + "-n-label");
        var totalLabel = document.getElementById(uid + "-total-label");
        function updateChart(newCounts) {
            chart.data.datasets[0].data = newCounts;
            chart.update();
            updateTotal(newCounts);
        }
        function updateTotal(countsArr) {
            var total = countsArr.reduce(function(a, b) { return a + b; }, 0);
            totalLabel.textContent = total;
        }
        chart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: years,
                datasets: [{
                    label: "Films with N Watches",
                    data: counts,
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
                },
                scales: {
                    x: { title: { display: true, text: "Year" }, ticks: { maxTicksLimit: 20 } },
                    y: { beginAtZero: true, title: { display: true, text: "Films" } }
                }
            }
        });
        // Initial total
        updateTotal(counts);
        nInput.addEventListener('input', function() {
            var n = parseInt(nInput.value);
            if (isNaN(n) || n < 0) return;
            nLabel.textContent = n;
            var formData = new FormData();
            formData.append('films_with_n_watches_n', n);
            formData.append('showfilms', <?php echo $show_films ? '"true"' : '"false"'; ?>);
            fetch(window.location.pathname + window.location.search, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                var temp = document.createElement('div');
                temp.innerHTML = html;
                var scriptTag = temp.querySelector('script[type="application/json"][data-uid^="films_with_n_watches_"]');
                if (scriptTag) {
                    var newCounts = JSON.parse(scriptTag.textContent);
                    updateChart(newCounts);
                }
                // Update films tab container if present
                var newTabs = temp.querySelector('div[id$="-decade-tabs"]');
                var oldTabs = document.getElementById(uid + '-decade-tabs');
                if (newTabs && oldTabs) {
                    // Get new UID from the newTabs id
                    var newUid = newTabs.id.replace(/-decade-tabs$/, '');
                    oldTabs.parentNode.replaceChild(newTabs, oldTabs);
                    // Update uid variable for future updates
                    uid = newUid;
                    // Re-initialize tab JS for the new tab container
                    (function(){
                        var tabContainer = document.getElementById(uid + '-decade-tabs');
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
                }
            });
        });
    })();
    });
    </script>
    <script type="application/json" data-uid="<?php echo $uid; ?>"><?php echo json_encode(array_values($films_with_n_watches_per_year)); ?></script>
    <?php
    return ob_get_clean();
}
add_shortcode('films_with_n_watches_per_year', 'oscars_films_with_n_watches_per_year_shortcode');
