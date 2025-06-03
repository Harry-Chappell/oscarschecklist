<?php

// Shortcode to display the winner submission form with reset buttons and AJAX functionality
function winner_submission_form_shortcode() {
    // Path to the existing JSON file
    $json_url = 'https://results.oscarschecklist.com/results.txt';
    $json_data = [];

    // Fetch existing data from JSON file
    $response = wp_remote_get($json_url);
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $json_data = json_decode(wp_remote_retrieve_body($response), true);
    }

    // Get the ceremony date from the current page (or dynamically if needed)
    $ceremony_date = get_field('ceremony_date');

    // Ensure the ceremony_date is valid
    if (!$ceremony_date) {
        return '<p>Ceremony date not set for this page.</p>';
    }

    // Convert ceremony_date to a format suitable for comparison (if necessary)
    $ceremony_date_formatted = date('Y-m-d', strtotime($ceremony_date));

    // Query nominations with the specified ceremony_date as the post date
    $posts_with_ceremony_date = get_posts([
        'post_type'      => 'nominations',
        'posts_per_page' => -1,
        'date_query'     => [
            [
                'after'     => $ceremony_date_formatted . ' 00:00:00',
                'before'    => $ceremony_date_formatted . ' 23:59:59',
                'inclusive' => true,
            ],
        ],
    ]);

    // Extract category IDs from the fetched posts
    $category_ids = [];
    foreach ($posts_with_ceremony_date as $post) {
        $post_categories = wp_get_post_terms($post->ID, 'award-categories', ['fields' => 'ids']);
        $category_ids = array_merge($category_ids, $post_categories);
    }
    $category_ids = array_unique($category_ids); // Remove duplicates

    // Fetch only the categories with the filtered IDs
    $categories = get_terms([
        'taxonomy'   => 'award-categories',
        'hide_empty' => false,
        'include'    => $category_ids,
    ]);

    // Fetch all nominations for the dropdown
    $nominations = get_posts([
        'post_type'      => 'nominations',
        'posts_per_page' => -1,
    ]);

    // Get the current server time
    $server_time = current_time('Y-m-d H:i:s');

    ob_start(); // Start output buffering
    ?>

    <!-- <p>Current Server Time: <?php echo esc_html($server_time); ?></p> -->
    <!-- <p>Ceremony Date: <?php echo esc_html($ceremony_date); ?></p> -->
    
    <form id="winner-submission-form" action="" method="POST">
    <input type="hidden" name="action" value="update_winners_file_frontend">

    <div class="active-category">
        <!-- Active Category -->
        <label for="active-category">Active Category:</label>
        <select id="active-category" name="active_category">
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo esc_attr($category->term_id); ?>" 
                    <?php selected($json_data['aC'] ?? '', $category->term_id); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="reset-field" data-field="active-category">Reset</button>
    </div>

    <div class="winner">
        <!-- Winner -->
        <label for="winner">Winner ID:</label>
        <input type="text" id="winner" name="winner" 
            value="<?php echo esc_attr($json_data['wI'] ?? ''); ?>" />
        <button type="button" class="reset-field" data-field="winner">Reset</button>
    </div>

    <div class="overall-state">
        <!-- Overall State -->
        <label for="overall-state">Overall State:</label>
        <select id="overall-state" name="overall_state">
            <option value="not-started" <?php selected($json_data['oS'] ?? '', 'not-started'); ?>>Not Started</option>
            <option value="showtime" <?php selected($json_data['oS'] ?? '', 'showtime'); ?>>Showtime</option>
            <option value="awarding" <?php selected($json_data['oS'] ?? '', 'awarding'); ?>>Awarding</option>
            <option value="finished" <?php selected($json_data['oS'] ?? '', 'finished'); ?>>Finished</option>
        </select>
        <button type="button" class="reset-field" data-field="overall-state">Reset</button>
    </div>

    <div class="page-refresh">
        <!-- Page Refresh -->
        <label for="page-refresh">Refresh Time:</label>
        <input type="time" step="10" id="page-refresh" name="page_refresh" 
            value="<?php echo esc_attr($json_data['pR'] ?? ''); ?>">
        <button type="button" class="reset-field" data-field="page-refresh">Reset</button>
    </div>

    <div class="send-message">
        <!-- Send Message -->
        <label for="send-message">Send Message:</label>
        <input type="text" id="send-message" name="send_message" 
            value="<?php echo esc_attr($json_data['sM']['text'] ?? ''); ?>">
        <button type="button" class="reset-field" data-field="send-message">Reset</button>
    </div>

    <div class="message-time">
        <label for="message-time">Message Time:</label>
        <input type="time" step="10" id="message-time" name="message_time" 
            value="<?php echo esc_attr($json_data['sM']['time'] ?? ''); ?>">
        <button type="button" class="reset-field" data-field="message-time">Reset</button>
    </div>

    <div class="refresh-interval">
        <!-- Change Refresh Interval -->
        <label for="refresh-interval">Refresh Interval:</label>
        <input type="number" id="refresh-interval" name="refresh_interval" min="1" 
            value="<?php echo esc_attr($json_data['rI'] ?? ''); ?>">
        <button type="button" class="reset-field" data-field="refresh-interval">Reset</button>
    </div>

    <button type="submit">Submit</button>
</form>



    <!-- JSON Data Display Section -->
    <details>
        <summary>
            <p>JSON</p>
        </summary>
        <pre id="json-data-display"></pre>
        <!-- <form id="reset-form" method="post">
            <button type="submit" name="reset_data" value="true">Reset Active Category & Winner</button>
        </form> -->
    </details>




    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Fetch and display current JSON data from the serve-results.php endpoint
            function fetchJSONData() {
                fetch('https://results.oscarschecklist.com/serve-results.php')
                    .then(response => response.json())
                    .then(data => {
                        // Display the JSON data in a formatted way
                        const jsonDisplay = document.getElementById('json-data-display');
                        jsonDisplay.textContent = JSON.stringify(data, null, 2);
                    })
                    .catch(error => {
                        console.error('Error fetching JSON data:', error);
                    });
            }

            // Fetch initial data when the page loads
            function updateFormWithExistingData() {
                fetch('https://results.oscarschecklist.com/serve-results.php')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('active-category').value = data.aC || '';
                        document.getElementById('winner').value = data.wI[0] || '';
                        document.getElementById('overall-state').value = data.oS || '';
                        document.getElementById('page-refresh').value = data.pR || '';
                        document.getElementById('send-message').value = data.sM.text || '';
                        document.getElementById('message-time').value = data.sM.time || '';
                        document.getElementById('refresh-interval').value = data.rI || '';
                    })
                    .catch(error => {
                        console.error('Error fetching initial JSON data:', error);
                    });
            }

            // Fetch initial data when the page loads
            updateFormWithExistingData();

            // Set interval for refreshing JSON data based on rI field
            let rI = parseInt(document.getElementById('refresh-interval').value) || 60; // Default to 60 seconds if empty
            setInterval(fetchJSONData, rI * 1000); // Refresh every 'rI' seconds
        });
    </script>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            // Reset field to blank when clicking "Reset" button
            const resetButtons = document.querySelectorAll('.reset-field');
            resetButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const fieldId = button.getAttribute('data-field');
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.value = '';
                    }
                });
            });

            // AJAX form submission
            const form = document.getElementById('winner-submission-form');
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(form);

                // Send form data via AJAX
                fetch('<?php echo esc_url(admin_url('admin-post.php')); ?>', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    console.log('AJAX Response:', data); // Log the response for debugging
                    if (data.success) {
                        // alert('Data submitted successfully');
                        // Update the form with the new data from the server
                        updateFormWithNewData(data.data);
                    } else {
                        alert('Error submitting data');
                        console.error('Error details:', data); // Log error details
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error submitting data');
                });
            });

            // Function to update the form with new data from the server
            function updateFormWithNewData(data) {
                document.getElementById('active-category').value = data.aC || '';
                document.getElementById('winner').value = data.wI[0] || '';
                document.getElementById('overall-state').value = data.oS || '';
                document.getElementById('page-refresh').value = data.pR || '';
                document.getElementById('send-message').value = data.sM.text || '';
                document.getElementById('message-time').value = data.sM.time || '';
                document.getElementById('refresh-interval').value = data.rI || '';
            }

            // Function to fetch JSON and update the active category and winner
            function updateaCAndWinner() {
                fetch('https://results.oscarschecklist.com/serve-results.php')
                    .then(response => response.json())
                    .then(data => {
                        const aC = data.aC || null; // Expecting a single value or null
                        const winnerID = data.wI || null; // Expecting a single value or null

                        // If no active category, remove 'active' and 'passed' classes from all .category elements
                        if (!aC) {
                            document.querySelectorAll('.category').forEach(el => {
                                el.classList.remove('active', 'passed');
                            });
                        } else {
                            document.querySelectorAll('.category').forEach(el => {
                                const categoryId = parseInt(el.getAttribute('data-category-id'));

                                if (categoryId === aC) {
                                    // Add 'active' class to the active category
                                    el.classList.add('active');
                                    el.classList.remove('passed');
                                } else {
                                    // Remove 'active' and 'passed' classes from other categories
                                    el.classList.remove('active', 'passed');
                                }
                            });
                        }

                        // Now loop through all nominations and apply 'winner' class to the winner
                        document.querySelectorAll('.nomination').forEach(nomination => {
                            const nominationId = nomination.getAttribute('data-nomination-id');

                            // If the nomination ID matches the winner ID, add the 'winner' class
                            if (winnerID && nominationId == winnerID) {
                                nomination.classList.add('winner');
                            } else {
                                nomination.classList.remove('winner');
                            }
                        });
                    })
                    .catch(error => console.error('Error fetching active category and winner data from JSON:', error));
            }

            // Run the function when the page loads
            updateaCAndWinner();

            // Refresh the active category and winner every 1 second
            setInterval(updateaCAndWinner, 1000);
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Select all nomination elements
            const nominations = document.querySelectorAll('.nomination');

            nominations.forEach(nomination => {
                nomination.addEventListener('click', function () {
                    // Get the nomination ID
                    const nominationId = this.getAttribute('data-nomination-id');

                    // Update the winner input field
                    const winnerField = document.getElementById('winner');
                    if (winnerField) {
                        winnerField.value = nominationId;
                    }

                    // Remove 'selected' class from all siblings
                    nominations.forEach(nom => nom.classList.remove('selected'));

                    // Add 'selected' class to the clicked nomination
                    this.classList.add('selected');
                });
            });
        });


        // // Handle reset form submission via AJAX
        // document.getElementById('reset-form').addEventListener('submit', function(e) {
        //     e.preventDefault(); // Prevent the form from submitting the traditional way

        //     // Send the reset request via AJAX
        //     fetch('/wp-admin/admin-post.php?action=reset_winners_file_frontend', {
        //         method: 'POST',
        //         body: new URLSearchParams(new FormData(this)),  // Send form data
        //     })
        //     .then(response => response.json())
        //     .then(data => {
        //         if (data.success) {
        //             console.log('Active Category and Winner ID have been reset.');

        //             // Reset the active category dropdown
        //             document.getElementById('active-category').value = '';

        //             // Reset the winner input field
        //             document.getElementById('winner').value = '';

        //             // Optionally refresh categories and winners
        //             updateaCAndWinner();
        //         } else {
        //             console.log('Failed to reset data.');
        //         }
        //     })
        //     .catch(error => {
        //         console.error('Error resetting data:', error);
        //     });
        // });

    </script>

    <style>
        div:has(>#winner-submission-form) {
            display: flex;
            flex-direction: column;
            padding-bottom: 100px;
        }
        div:has(>#winner-submission-form) .category:not(.active) {
            display: none;
        }
        div:has(>#winner-submission-form) .category.active {
            order: -1;
        }
        div:has(>#winner-submission-form) .nomination {
            background: var(--theme-palette-color-7);
            display: flex;
            padding: 5px 10px;
            flex-direction: column;
            gap: 5px;
        }
        div:has(>#winner-submission-form) .nomination.selected {
            background: var(--theme-palette-color-6);
        }
        div:has(>#winner-submission-form) .nomination.winner {
            background: var(--theme-palette-color-1);
            color: black
        }
        div:has(>#winner-submission-form) .nomination:hover {
            scale: 1.05;
            cursor: pointer;
        }
        div:has(>#winner-submission-form) p {
            margin: 0;
        }
        .nominations {
            display: flex;
            gap: 10px;
            flex-flow: row wrap;
        }
        .category {
            border: 1px solid var(--theme-palette-color-6);
            margin: 25px 0 0;
            padding: 25px 2vw 20px;
            border-radius: 3px;
            position: relative;
        }
        .category h2 {
            background: var(--theme-palette-color-8);
            display: inline;
            padding: 0 10px;
            position: absolute;
            inset: 0 auto auto 10px;
            transform: translateY(-50%);
            font-size: 18px;
        }
        form#winner-submission-form {
            background: var(--theme-palette-color-7);
            padding: 25px 2vw;
            border: 1px solid var(--theme-palette-color-6);
            display: flex;
            flex-flow: row wrap;
            gap: 0 20px;
            justify-content: space-between;
            /* order: -2; */
            margin-top: 20px;
        }
        form#winner-submission-form div {
            display: flex;
            flex-flow: row wrap;
            margin-bottom: 10px;
            width: 100%;
            max-width: calc(50% - 10px);
        }
        form#winner-submission-form div.active-category {
            max-width: 100%;
        }
        form#winner-submission-form label {
            width: 100%;
            margin: 0;
            padding: 0 10px;
            font-size: 13px;
        }
        form#winner-submission-form input, form#winner-submission-form select {
            width: calc(100% - 50px);
        }
        button.reset-field {
            width: 40px;
            margin-left: 10px;
            border-radius: 3px;
            font-size: 11px;
            background: var(--theme-palette-color-6);
            color: var(--theme-palette-color-4);
        }
        form#winner-submission-form input, form#winner-submission-form select {
            width: calc(100% - 50px);
            border: 1px solid var(--theme-palette-color-6);
            background: var(--theme-palette-color-8);
            height: 25px;
            font-size: 12px;
        }
        
        form#winner-submission-form>button {
            position: fixed;
            inset: auto 50% 10px;
            transform: translateX(-50%);
            height: 50px;
            background: var(--theme-palette-color-1);
            color: white;
            font-weight: bold;
            font-size: 17px;
            width: calc(100vw - 20px);
            max-width: 500px;
            z-index: 10;
            box-shadow: 0 0 50px var(--theme-palette-color-8), 0 0 50px var(--theme-palette-color-8), 0 0 50px var(--theme-palette-color-8), 0 0 50px var(--theme-palette-color-8), 0 0 50px var(--theme-palette-color-8), 0 0 50px var(--theme-palette-color-8), 0 0 50px var(--theme-palette-color-8), 0 0 50px var(--theme-palette-color-8);
        }
        summary {
            display: flex;
        }
        details h2 {
            font-size: 18px;
            padding: 0 20px;
            margin: 20px 0 0 !important;
        }
        pre {
            overflow: auto;
            padding: 2vw;
            font-size: 10px;
        }
        /* form#reset-form button {
            background: none;
            color: red;
            border: 2px solid;
            padding: 3px 10px 0;
            margin: auto;
            display: flex;
        } */

    </style>

    <?php
    return ob_get_clean(); // Return the buffered content
}

// Register the shortcode
add_shortcode('winner_submission_form', 'winner_submission_form_shortcode');

// Handle form submission via AJAX
function handle_form_submission() {
    // Path to the secure endpoint
    $secure_url = 'https://results.oscarschecklist.com/serve-results.php';

    // Fetch existing data from the JSON file
    $json_url = 'https://results.oscarschecklist.com/results.txt';
    $response = wp_remote_get($json_url);
    $existing_data = (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200)
        ? json_decode(wp_remote_retrieve_body($response), true)
        : [];

    // Sanitize inputs
    $new_active_category = isset($_POST['active_category']) ? intval($_POST['active_category']) : null;
    $winner_id = isset($_POST['winner']) ? intval($_POST['winner']) : null;
    $overall_state = isset($_POST['overall_state']) ? sanitize_text_field($_POST['overall_state']) : ($existing_data['oS'] ?? '');
    $page_refresh = isset($_POST['page_refresh']) ? sanitize_text_field($_POST['page_refresh']) : ($existing_data['pR'] ?? '');
    $send_message = isset($_POST['send_message']) ? sanitize_text_field($_POST['send_message']) : ($existing_data['sM']['text'] ?? '');
    $message_time = isset($_POST['message_time']) ? sanitize_text_field($_POST['message_time']) : ($existing_data['sM']['time'] ?? '');
    $refresh_interval = isset($_POST['refresh_interval']) ? intval($_POST['refresh_interval']) : ($existing_data['rI'] ?? 0);

    // If winner_id is provided and the post exists, assign the "Winner" term
    if ($winner_id && get_post_status($winner_id)) {
        // Get the existing categories of the post
        $existing_categories = wp_get_post_terms($winner_id, 'award-categories', ['fields' => 'ids']);

        // The term ID for "Winner"
        $winner_term_id = 40989; // Replace with the actual term ID if different

        // Check if "Winner" term is already assigned
        if (!in_array($winner_term_id, $existing_categories)) {
            // Add "Winner" term to the existing categories
            $existing_categories[] = $winner_term_id;
        }

        // Assign the updated list of categories (including "Winner") to the post
        wp_set_post_terms($winner_id, $existing_categories, 'award-categories');
    }

    // Update the JSON data with the new values
    $updated_data = array_merge($existing_data, [
        'aC'   => $new_active_category, // Store as single value
        'wI'        => $winner_id, // Store as single value
        'oS'     => $overall_state,
        'pR'      => $page_refresh,
        'sM'      => [
            'text' => $send_message,
            'time' => $message_time,
        ],
        'rI'  => $refresh_interval,
    ]);

    // Send updated data to the secure endpoint
    $response = wp_remote_post($secure_url, [
        'method'    => 'POST',
        'headers'   => ['Content-Type' => 'application/json'],
        'body'      => json_encode($updated_data, JSON_PRETTY_PRINT),
        'timeout'   => 15,
    ]);

    // Return the updated data to be used by AJAX
    wp_send_json_success($updated_data);
}
add_action('admin_post_update_winners_file_frontend', 'handle_form_submission');
add_action('admin_post_nopriv_update_winners_file_frontend', 'handle_form_submission');


// // Handle reset form submission via AJAX
// function handle_reset_submission() {
//     // Path to the secure endpoint
//     $secure_url = 'https://results.oscarschecklist.com/serve-results.php';

//     // Fetch existing data from the JSON file
//     $json_url = 'https://results.oscarschecklist.com/results.txt';
//     $response = wp_remote_get($json_url);
//     $existing_data = (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200)
//         ? json_decode(wp_remote_retrieve_body($response), true)
//         : [];

//     // Reset aC and wI
//     $existing_data['aC'] = null;
//     $existing_data['wI'] = null;

//     // Update the JSON data with the reset values
//     $updated_data = array_merge($existing_data, [
//         'aC'   => null,  // Clear aC
//         'wI'        => null,  // Clear wI
//     ]);

//     // Send updated data to the secure endpoint
//     $response = wp_remote_post($secure_url, [
//         'method'    => 'POST',
//         'headers'   => ['Content-Type' => 'application/json'],
//         'body'      => json_encode($updated_data, JSON_PRETTY_PRINT),
//         'timeout'   => 15,
//     ]);

//     // Return the updated data to be used by AJAX
//     wp_send_json_success($updated_data);
// }
// add_action('admin_post_reset_winners_file_frontend', 'handle_reset_submission');
// add_action('admin_post_nopriv_reset_winners_file_frontend', 'handle_reset_submission');




// Shortcode to display nominations for the scoreboard
function nominations_for_scoreboard_shortcode() {
    // Get the ceremony date from the current page's ACF field
    $ceremony_date = get_field('ceremony_date');
    if (!$ceremony_date) {
        return '<p>No ceremony date set for this page.</p>';
    }

    // Convert the ceremony date to Year-Month-Day format for querying
    $formatted_ceremony_date = date('Y-m-d', strtotime($ceremony_date));

    // Fetch nominations with the matching post date
    $nominations = get_posts([
        'post_type'      => 'nominations',
        'posts_per_page' => -1,
        'date_query'     => [
            [
                'after'     => $formatted_ceremony_date . ' 00:00:00',
                'before'    => $formatted_ceremony_date . ' 23:59:59',
                'inclusive' => true,
            ],
        ],
        'tax_query' => [
            [
                'taxonomy' => 'award-categories',
                'field'    => 'term_id',
                'operator' => 'EXISTS',
            ],
        ],
    ]);

    // Check if any nominations are found
    if (empty($nominations)) {
        return '<p>No nominations found for the specified ceremony date.</p>';
    }

    // Group nominations by category
    $categories = [];
    foreach ($nominations as $nomination) {
        $category_terms = wp_get_post_terms($nomination->ID, 'award-categories');
        foreach ($category_terms as $category) {
            $categories[$category->term_id]['title'] = $category->name;
            $categories[$category->term_id]['nominations'][] = $nomination;
        }
    }

    // Start output buffering
    ob_start();

    foreach ($categories as $category_id => $category_data) {
        ?>
        <div class="category" data-category-id="<?php echo esc_attr($category_id); ?>">
            <h2><?php echo esc_html($category_data['title']); ?></h2>
            <div class="nominations">
                <?php foreach ($category_data['nominations'] as $nomination): ?>
                    <div class="nomination" data-nomination-id="<?php echo esc_attr($nomination->ID); ?>">
                    <?php
                    // Get the award category ID (assumed to be available)
                    // $award_category_id = get_queried_object_id(); // You can replace this with the correct method to get the category ID

                    // Get the single term from the 'films' taxonomy
                    $film_terms = wp_get_post_terms($nomination->ID, 'films');
                    if (!empty($film_terms)) {
                        $film_term = $film_terms[0]; // Get the first (and only) term
                        echo '<p class="film-title">' . esc_html($film_term->name) . '</p>';
                    }

                    // Check if the award category ID matches the specified ones
                    if (in_array($category_id, [41054, 41055, 41056, 41057, 41058])) {
                        // Get the single term from the 'nominees' taxonomy
                        $nominee_terms = wp_get_post_terms($nomination->ID, 'nominees');
                        if (!empty($nominee_terms)) {
                            $nominee_term = $nominee_terms[0]; // Get the first (and only) term
                            echo '<p class="nominee-name">' . esc_html($nominee_term->name) . '</p>';
                        }
                    } elseif ($category_id == 41095) {
                        // If the award category ID is 41095, show the nomination title
                        echo '<p class="nomination-title">' . esc_html(get_the_title($nomination)) . '</p>';
                    }
                    ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    return ob_get_clean();
}
add_shortcode('nominations_for_scoreboard', 'nominations_for_scoreboard_shortcode');