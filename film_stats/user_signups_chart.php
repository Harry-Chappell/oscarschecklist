<?php
/**
 * Shortcode to display user signups chart
 * Usage: [user_signups_chart]
 */
function oscars_user_signups_chart_shortcode($atts = array()) {
    $atts = shortcode_atts([
        'start' => '2024-10-01',
        'end' => date('Y-m-d'),
        'unit' => 'month'
    ], $atts, 'user_signups_chart');

    $upload_dir = wp_upload_dir();
    $json_file = $upload_dir['basedir'] . '/signup_dates.json';
    
    if (!file_exists($json_file)) {
        return '<p>Signup data not found.</p>';
    }
    
    $json_content = file_get_contents($json_file);
    $data = json_decode($json_content, true);
    
    if (!$data || !isset($data['signups']) || !is_array($data['signups'])) {
        return '<p>Invalid signup data.</p>';
    }
    
    $signups = $data['signups'];
    
    // Get current values from request or use defaults from attributes
    $start_date = isset($_POST['signup_start_date']) ? sanitize_text_field($_POST['signup_start_date']) : $atts['start'];
    $end_date = isset($_POST['signup_end_date']) ? sanitize_text_field($_POST['signup_end_date']) : $atts['end'];
    $unit = isset($_POST['signup_unit']) ? sanitize_text_field($_POST['signup_unit']) : $atts['unit'];
    
    // Validate unit
    $valid_units = ['minute', 'hour', 'day', 'week', 'month'];
    if (!in_array($unit, $valid_units)) {
        $unit = 'month';
    }
    
    $uid = uniqid('signup_chart_');
    
    ob_start();
    ?>
    <style>
    .signup-chart-controls {
        margin-bottom: 20px;
    }
    .signup-chart-controls label {
        display: inline-block;
        margin-right: 15px;
        margin-bottom: 10px;
    }
    .signup-chart-controls input,
    .signup-chart-controls select {
        margin-left: 5px;
        padding: 5px;
        min-width: 130px;
    }
    .signup-chart-controls button {
        padding: 8px 15px;
        background: #c7a34f;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        margin-left: 10px;
    }
    .signup-chart-controls button:hover {
        background: #b08d3f;
    }

    </style>
    
    <h2>User Signups Over Time</h2>
    
    <div class="signup-chart-controls">
        <form id="<?php echo $uid; ?>-form">
            <div style="margin-bottom: 10px;">
                <label>
                    Start Date:
                    <input type="date" name="signup_start_date" id="<?php echo $uid; ?>-start" value="<?php echo esc_attr($start_date); ?>">
                </label>
                <label>
                    End Date:
                    <input type="date" name="signup_end_date" id="<?php echo $uid; ?>-end" value="<?php echo esc_attr($end_date); ?>">
                </label>
                <label>
                    Time Unit:
                    <select name="signup_unit" id="<?php echo $uid; ?>-unit">
                        <option value="minute" <?php selected($unit, 'minute'); ?>>Minutes</option>
                        <option value="hour" <?php selected($unit, 'hour'); ?>>Hours</option>
                        <option value="day" <?php selected($unit, 'day'); ?>>Days</option>
                        <option value="week" <?php selected($unit, 'week'); ?>>Weeks</option>
                        <option value="month" <?php selected($unit, 'month'); ?>>Months</option>
                    </select>
                </label>
            </div>
        </form>
    </div>
    
    <div style="max-width:100%; max-height:200px; overflow:auto;">
        <canvas id="<?php echo $uid; ?>" style="max-height:200px;width:100%;height:200px;"></canvas>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script type="text/javascript">
    window.addEventListener('DOMContentLoaded', function() {
        (function(){
            var signupData = <?php echo json_encode($signups); ?>;
            var uid = "<?php echo $uid; ?>";
            var chart;
            var ctx = document.getElementById(uid).getContext("2d");
            var startInput = document.getElementById(uid + "-start");
            var endInput = document.getElementById(uid + "-end");
            var unitSelect = document.getElementById(uid + "-unit");
            
            function processData(start, end, unit) {
                var startDate = new Date(start + 'T00:00:00');
                var endDate = new Date(end + 'T23:59:59');
                
                // Filter signups within date range
                var filtered = signupData.filter(function(signup) {
                    var signupDate = new Date(signup.date);
                    return signupDate >= startDate && signupDate <= endDate;
                });
                
                if (filtered.length === 0) {
                    return { labels: [], data: [] };
                }
                
                // Group by time unit
                var grouped = {};
                
                filtered.forEach(function(signup) {
                    var date = new Date(signup.date);
                    var key;
                    
                    switch(unit) {
                        case 'minute':
                            key = date.getFullYear() + '-' + 
                                  String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                                  String(date.getDate()).padStart(2, '0') + ' ' + 
                                  String(date.getHours()).padStart(2, '0') + ':' + 
                                  String(date.getMinutes()).padStart(2, '0');
                            break;
                        case 'hour':
                            key = date.getFullYear() + '-' + 
                                  String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                                  String(date.getDate()).padStart(2, '0') + ' ' + 
                                  String(date.getHours()).padStart(2, '0') + ':00';
                            break;
                        case 'day':
                            key = date.getFullYear() + '-' + 
                                  String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                                  String(date.getDate()).padStart(2, '0');
                            break;
                        case 'week':
                            var weekStart = new Date(date);
                            weekStart.setDate(date.getDate() - date.getDay());
                            key = weekStart.getFullYear() + '-W' + 
                                  String(Math.ceil((weekStart - new Date(weekStart.getFullYear(), 0, 1)) / 604800000) + 1).padStart(2, '0');
                            break;
                        case 'month':
                            key = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
                            break;
                    }
                    
                    if (!grouped[key]) {
                        grouped[key] = 0;
                    }
                    grouped[key]++;
                });
                
                var labels = Object.keys(grouped).sort();
                var data = labels.map(function(label) {
                    return grouped[label];
                });
                
                return { labels: labels, data: data };
            }
            
            function createChart() {
                var processed = processData(startInput.value, endInput.value, unitSelect.value);
                
                if (chart) {
                    chart.destroy();
                }
                
                var chartConfig = {
                    type: 'bar',
                    data: {
                        labels: processed.labels,
                        datasets: [{
                            data: processed.data,
                            backgroundColor: '#c7a34f',
                            borderColor: '#c7a34f',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        return context[0].label;
                                    },
                                    label: function(context) {
                                        return context.parsed.y;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { 
                                display: false
                            },
                            y: { 
                                display: false
                            }
                        }
                    }
                }
                
                chart = new Chart(ctx, chartConfig);
            }
            
            // Initial chart
            createChart();
            
            // Update on input change
            startInput.addEventListener('change', function() {
                createChart();
            });
            endInput.addEventListener('change', function() {
                createChart();
            });
            unitSelect.addEventListener('change', function() {
                createChart();
            });
        })();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('user_signups_chart', 'oscars_user_signups_chart_shortcode');
