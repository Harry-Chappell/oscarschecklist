<?php
if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'my-style', get_stylesheet_directory_uri() . '/style.css', array(), date('ymdhis'), 'all' ); // Inside a child theme
    wp_enqueue_script( 'my-scripts', get_stylesheet_directory_uri() . '/scripts.js', array(), date('ymdhis'), true ); // Inside a child theme, load in footer
    wp_localize_script('my-scripts', 'OscarsChecklist', [
        'userId' => get_current_user_id()
    ]);
});

// Remove jQuery Migrate
add_action( 'wp_default_scripts', function( $scripts ) {
    if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
        $script = $scripts->registered['jquery'];
        if ( $script->deps ) {
            $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
        }
    }
});

add_action('pmxi_saved_post', 'wp_all_import_post_saved', 10, 1);

function wp_all_import_post_saved($id) {
    do_action('search_filter_update_post_cache', $id);
}




// Disable core update emails
add_filter( 'auto_core_update_send_email', '__return_false' );

// Disable plugin update emails
add_filter( 'auto_plugin_update_send_email', '__return_false' );

// Disable theme update emails
add_filter( 'auto_theme_update_send_email', '__return_false' );





// show admin bar only for admins
if (!current_user_can('manage_options')) {
    add_filter('show_admin_bar', '__return_false');
}
// show admin bar only for admins and editors
if (!current_user_can('edit_posts')) {
    add_filter('show_admin_bar', '__return_false');
}



function get_user_meta_json_path($user_id) {
    $upload_dir = wp_upload_dir();
    $user_dir   = $upload_dir['basedir'] . '/user_meta';
    if ( ! file_exists( $user_dir ) ) {
        wp_mkdir_p( $user_dir );
    }
    return $user_dir . "/user_{$user_id}.json";
}
function get_user_pred_fav_json_path($user_id) {
    $upload_dir = wp_upload_dir();
    $user_dir   = $upload_dir['basedir'] . '/user_meta';
    if ( ! file_exists( $user_dir ) ) {
        wp_mkdir_p( $user_dir );
    }
    return $user_dir . "/user_{$user_id}_pred_fav.json";
}

function load_user_meta_json($user_id) {
    $file_path = get_user_meta_json_path($user_id);
    $user = get_userdata($user_id);
    $username = $user ? $user->user_login : '';
    if (!file_exists($file_path)) {
        return [
            'watched' => [],
            'watchlist' => [],
            'favourite-categories' => [],
            'hidden-categories' => [],
            'public' => false,
            'username' => $username,
            'last-updated' => '',
            'total-watched' => 0,
            'this_page_only' => false,
            'auto_remove_watched' => true,
            'compact_view' => false,
        ];
    }
    $json = file_get_contents($file_path);
    $data = json_decode($json, true) ?: [];
    // Ensure username and public are always present
    $updated = false;
    if (!isset($data['username']) || $data['username'] === '') {
        $data['username'] = $username;
        $updated = true;
    }
    if (!isset($data['public'])) {
        $data['public'] = false;
        $updated = true;
    }
    if ($updated) {
        file_put_contents($file_path, wp_json_encode($data));
    }
    return $data + [
        'watched' => [],
        'watchlist' => [],
        'favourite-categories' => [],
        'hidden-categories' => [],
        'public' => false,
        'username' => $username,
        'last-updated' => '',
        'total-watched' => 0,
        'this_page_only' => false,
        'auto_remove_watched' => true,
        'compact_view' => false,
    ];
}

function load_user_pred_fav_json($user_id) {
    $file_path = get_user_pred_fav_json_path($user_id);
    $user = get_userdata($user_id);
    $username = $user ? $user->user_login : '';
    if (!file_exists($file_path)) {
        return [
            'username' => $username,
            'favourites' => [],
            'predictions' => [],
            'correct-predictions' => "",
            'incorrect-predictions' => "",
            'correct-prediction-rate' => "",
        ];
    }
    $json = file_get_contents($file_path);
    $data = json_decode($json, true) ?: [];
    // Ensure username is always present
    if (!isset($data['username']) || $data['username'] === '') {
        $data['username'] = $username;
        file_put_contents($file_path, wp_json_encode($data));
    }
    return $data + [
        'username' => $username,
        'favourites' => [],
        'predictions' => [],
        'correct-predictions' => "",
        'incorrect-predictions' => "",
        'correct-prediction-rate' => "",
    ];
}

function save_user_meta_json($user_id, $data) {
    $file_path = get_user_meta_json_path($user_id);
    file_put_contents($file_path, wp_json_encode($data));
}
function save_user_pred_fav_json($user_id, $data) {
    $file_path = get_user_pred_fav_json_path($user_id);
    // Only include specific fields for pred_fav files
    $filtered_data = array_intersect_key($data, array_flip([
        'username',
        'correct-predictions',
        'incorrect-predictions',
        'correct-prediction-rate',
        'predictions',
        'favourites'
    ]));
    file_put_contents($file_path, wp_json_encode($filtered_data));
}

function oscars_update_film_stats_json($film_id, $action) {
    $output_path = ABSPATH . 'wp-content/uploads/films_stats.json';
    if (!file_exists($output_path)) return;
    $json = file_get_contents($output_path);
    $films = json_decode($json, true);
    if (!$films || !is_array($films)) return;
    foreach ($films as &$film) {
        if (isset($film['film-id']) && $film['film-id'] == $film_id) {
            if (!isset($film['watched-count'])) $film['watched-count'] = 0;
            if ($action === 'watched') {
                $film['watched-count']++;
            } elseif ($action === 'unwatched' && $film['watched-count'] > 0) {
                $film['watched-count']--;
            }
            break;
        }
    }
    file_put_contents($output_path, json_encode($films));
}

function markAsWatched() {
    $post_id = $_POST['watched_post_id'] ?? null;
    $action = $_POST['watched_action'] ?? null;

    if (!$post_id || !is_user_logged_in() || !$action) {
        return;
    }

    $user_id = get_current_user_id();
    $json = load_user_meta_json($user_id);
    $changed = false;

    if ($action === 'watched') {
        // Only add if not already present
        $already = false;
        foreach ($json['watched'] as $film) {
            if ($film['film-id'] == $post_id) {
                $already = true;
                break;
            }
        }
        if (!$already) {
            // Use get_term to fetch the film term by ID
            $film_term = get_term((int)$post_id, 'films');
            $film_name = ($film_term && !is_wp_error($film_term)) ? $film_term->name : '';
            $film_slug = ($film_term && !is_wp_error($film_term)) ? $film_term->slug : '';
            $film_url = ($film_term && !is_wp_error($film_term)) ? get_term_link($film_term) : '';
            $film_url = $film_slug;
            $film_year = null;

            // Get year from first nomination post (if any)
            $nomination_query = new WP_Query([
                'post_type' => 'nominations',
                'posts_per_page' => 1,
                'tax_query' => [[
                    'taxonomy' => 'films',
                    'field'    => 'term_id',
                    'terms'    => (int)$post_id,
                ]],
                'orderby' => 'date',
                'order'   => 'ASC',
            ]);
            if ($nomination_query->have_posts()) {
                $nomination = $nomination_query->posts[0];
                $film_year = (int) date('Y', strtotime($nomination->post_date));
            }
            wp_reset_postdata();

            $entry = [
                'film-id' => (int)$post_id,
                'film-name' => $film_name,
                'film-year' => $film_year,
                'film-url' => $film_url,
                'watched-date' => date('Y-m-d'),
            ];
            $json['watched'][] = $entry;
            $changed = true;

            update_watched_by_day_json($post_id);
        }
    } elseif ($action === 'unwatched') {
        $before = count($json['watched']);
        $json['watched'] = array_values(array_filter($json['watched'], function($film) use ($post_id) {
            return $film['film-id'] != $post_id;
        }));
        if (count($json['watched']) < $before) {
            $changed = true;
        }
    }
    // Update stats and last-updated
    $json['total-watched'] = count($json['watched']);
    $json['last-updated'] = date('Y-m-d');
    save_user_meta_json($user_id, $json);
    if ($changed) {
        oscars_update_film_stats_json($post_id, $action);
    }
}
add_action('init', 'markAsWatched', 30);

function update_watched_by_day_json($film_id) {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/watched_by_day.json';
    $today = date('Y-m-d');

    // Load or initialize data
    if (file_exists($file_path)) {
        $json = file_get_contents($file_path);
        $data = json_decode($json, true);
        if (!is_array($data)) $data = [];
    } else {
        $data = [];
    }

    // Ensure structure
    if (!isset($data['days'])) $data['days'] = [];
    if (!isset($data['films'])) $data['films'] = [];

    // Increment total watched for today
    if (!isset($data['days'][$today])) {
        $data['days'][$today] = 1;
    } else {
        $data['days'][$today]++;
    }

    // Increment watched for this film for today
    if (!isset($data['films'][$film_id])) $data['films'][$film_id] = [];
    if (!isset($data['films'][$film_id][$today])) {
        $data['films'][$film_id][$today] = 1;
    } else {
        $data['films'][$film_id][$today]++;
    }

    // Save back to file
    file_put_contents($file_path, json_encode($data));
}

function markAsFav()
{
    $fav_nom_id = $_POST['fav_nom_id'] ?? null;
    $fav_action = $_POST['fav_action'] ?? null;

    if (!$fav_nom_id || !is_user_logged_in() || !$fav_action) {
        return;
    }

    $user_id = get_current_user_id();
    $json = load_user_pred_fav_json($user_id);

    if ($fav_action === 'fav') {
        if (!in_array((int)$fav_nom_id, $json['favourites'])) {
            $json['favourites'][] = (int)$fav_nom_id;
        }
    } elseif ($fav_action === 'unfav') {
        $json['favourites'] = array_values(array_filter($json['favourites'], function($id) use ($fav_nom_id) {
            return $id != $fav_nom_id;
        }));
    }
    save_user_pred_fav_json($user_id, $json);
}
add_action('init', 'markAsFav');

function markAspredict()
{
    $predict_nom_id = $_POST['predict_nom_id'] ?? null;
    $predict_action = $_POST['predict_action'] ?? null;

    if (!$predict_nom_id || !is_user_logged_in() || !$predict_action) {
        return;
    }

    $user_id = get_current_user_id();
    $json = load_user_pred_fav_json($user_id);

    if ($predict_action === 'predict') {
        if (!in_array((int)$predict_nom_id, $json['predictions'])) {
            $json['predictions'][] = (int)$predict_nom_id;
        }
    } elseif ($predict_action === 'unpredict') {
        $json['predictions'] = array_values(array_filter($json['predictions'], function($id) use ($predict_nom_id) {
            return $id != $predict_nom_id;
        }));
    }
    save_user_pred_fav_json($user_id, $json);
}
add_action('init', 'markAspredict');




require_once get_stylesheet_directory() . '/nominations_by_year.php';

add_shortcode('show_nominations', 'show_nominations_by_year_shortcode');




            
require_once get_stylesheet_directory() . '/nominations_by_cat.php';

add_shortcode('show_nominations_by_cat', 'show_nominations_by_cat_shortcode');

            



require_once get_stylesheet_directory() . '/nominations_for_scoreboard.php';

add_shortcode('show_scoreboard_nominations', 'show_scoreboard_nominations_shortcode');





require_once get_stylesheet_directory() . '/scores_for_scoreboard.php';

add_shortcode('show_scoreboard_scores', 'show_scoreboard_scores_shortcode');



function remove_term_button_shortcode() {
    // Only proceed if the button is clicked
    if (isset($_POST['remove_term_button'])) {
        // Query posts of custom post type 'nominations' with term ID 51843
        $args = array(
            'post_type' => 'nominations',
            'tax_query' => array(
                array(
                    'taxonomy' => 'award-categories', // Taxonomy name
                    'field'    => 'term_id',
                    'terms'    => 51843, // Term ID
                ),
            ),
            'posts_per_page' => -1, // Get all matching posts
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            echo '<p>Found ' . esc_html($query->post_count) . ' posts with term ID 51843.</p>';

            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                // Debugging: Output the post ID and title
                echo '<p>Processing Post: ' . esc_html(get_the_title()) . ' (ID: ' . esc_html($post_id) . ')</p>';

                // Remove the term with ID 51843 from the post
                $result = wp_remove_object_terms($post_id, 51843, 'award-categories'); // Remove term

                // Debugging: Output the result of term removal
                if ($result) {
                    echo '<p>Term 51843 removed from Post ID: ' . esc_html($post_id) . '</p>';
                } else {
                    echo '<p>Failed to remove term from Post ID: ' . esc_html($post_id) . '</p>';
                }
            }
            wp_reset_postdata();
        } else {
            echo '<p>No posts found with term ID 51843.</p>';
        }
    }

    // Output the button
    return '<form method="post">
                <input type="submit" name="remove_term_button" value="Remove Term 51843" />
            </form>';
}
add_shortcode('remove_term_button', 'remove_term_button_shortcode');




function circular_progress_bar($atts) {
    $count = $atts['count'];
    $output = "";
    
    $output .= '<div class="circular-progress-bar ' . $count . '" id="' . $count . '-progress">';
    $output .= '<span class="progress"></span>';
    $output .= '<span class="total"></span>';
    $output .= '</div>';
    return $output;
}

add_shortcode('circularprogressbar', 'circular_progress_bar');


function show_friends() {
    print_r($friend_watched_films);
}
add_shortcode('show_friends', 'show_friends');


// Core function to generate friends list HTML
function get_friends_list_html() {
    if ( ! is_user_logged_in() ) {
        return '';
    }

    $current_user_id = get_current_user_id();
    $friends = friends_get_friend_user_ids( $current_user_id );
    $output = '';
    if ($friends) {
        $output .= '<div id="friends-list"><ul>';

        $output .= '<li class="friend-item active" onclick="updateTOC(null)" title="' . wp_get_current_user()->display_name . '" style="--user-id:' . $current_user_id . '"><img src="' . get_avatar_url( get_current_user_id(), ['size' => 32] ) . '"><p>You</p></li>';
        foreach ( $friends as $friend_id ) {
            $friend_user = get_userdata( $friend_id );
            $avatar_url = get_avatar_url( $friend_id, ['size' => 32] );
            $first_name = $friend_user->user_firstname;
            $last_name = $friend_user->user_lastname;
            $user_login = $friend_user->user_login;
            $user_email = $friend_user->user_email;
            
            // Calculate lengths
            $id_length = strlen($friend_id);
            $login_length = strlen($user_login);
            $first_name_length = strlen($first_name);
            $last_name_length = strlen($last_name);
            $email_length = strlen($user_email);
            
            // Calculate product of lengths
            $randomcolornum = $id_length * $login_length * $first_name_length * $last_name_length * $email_length;
            
            // Get the last 3 digits of the calculated number
            $randomcolornum = substr($randomcolornum, -3);
            
            $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

            $output .= '<li class="friend-item" onclick="updateTOC(' . $friend_id . ')" title="' . $friend_user->display_name . '" style="--randomcolornum:' . $randomcolornum . '">';
            $output .= '<div class="friend-initials" title="' . $friend_user->display_name . '">' . $initials . '</div>';
            $output .= '<img src="' . $avatar_url . '" alt="' . $friend_user->display_name . '" title="' . $friend_user->display_name . '" class="friend-avatar">';
            $output .= '</li>';
        }

        $output .= '</ul></div>';
    } else {
        $output .= '<a class="add-friends-link" href="https://oscarschecklist.com/members/"><p>Add friends to compare</p><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M502.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l370.7 0-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l128-128z"/></svg></a>';
    }
    return $output;
}

// AJAX handler for loading friends list
function ajax_get_friends_list() {
    echo get_friends_list_html();
    wp_die();
}
add_action('wp_ajax_get_friends_list', 'ajax_get_friends_list');
add_action('wp_ajax_nopriv_get_friends_list', 'ajax_get_friends_list');

// Shortcode that loads friends list via AJAX
function get_friends_list() {
    if ( ! is_user_logged_in() ) {
        return '';
    }
    
    // Return placeholder that will be replaced by AJAX call
    $ajax_url = admin_url('admin-ajax.php');
    return '<div id="friends-list-container" class="loading"></div>
    <script>
    (function() {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "' . esc_js($ajax_url) . '", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                var container = document.getElementById("friends-list-container");
                if (container) {
                    container.innerHTML = xhr.responseText;
                    container.classList.remove("loading");
                    
                    // Dispatch event to notify that friends list has loaded
                    var event = new CustomEvent("friendsListLoaded");
                    document.dispatchEvent(event);
                }
            }
        };
        xhr.send("action=get_friends_list");
    })();
    </script>';
}
add_shortcode( 'getfriendslist', 'get_friends_list' );


function oscars_year_dropdown_shortcode() {
    $output = '<select id="oscarsYearDropdown">';
    $output .= '<option value="" selected disabled>Select Year</option>'; // Add this line
    for ($year = 2024; $year >= 1929; $year--) {
        $output .= '<option value="' . $year . '">' . $year . '</option>';
    }
    $output .= '</select>';
    $output .= <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('oscarsYearDropdown').addEventListener('change', function() {
        var year = this.value;
        window.location.href = 'https://oscarschecklist.com/years/nominations-' + year + '/';
    });
});
</script>
EOT;
    return $output;
}
add_shortcode('oscars_year_dropdown', 'oscars_year_dropdown_shortcode');




add_action( 'litespeed_esi_load-my_esi_block', 'my_esi_block_esi_load' );

function my_esi_block_esi_load()
{
do_action( 'litespeed_control_set_ttl', 300 );
#do_action( 'litespeed_control_set_nocache' );
echo "Hello world".rand (1,99999);
}




function oscar_nominations_navigation() {
    global $post;

    // Get the slug of the current page
    $slug = $post->post_name;

    // Extract the year from the slug (assuming the format is always "nominations-YEAR")
    if (preg_match('/\d{4}/', $slug, $matches)) {
        $current_year = intval($matches[0]);
    } else {
        return ''; // If no year found, return nothing
    }

    // Define the minimum and maximum years for navigation
    $min_year = 1929;
    $max_year = date('Y');

    // Generate the previous and next years
    $prev_year = $current_year - 1;
    $next_year = $current_year + 1;

    // Start building the HTML output
    $output = '<div class="nominations-navigation">';

    // Add the previous link if it's within the allowed range
    if ($prev_year >= $min_year) {
        $prev_link = "https://oscarschecklist.com/years/nominations-$prev_year/";
        $output .= '<a title="Nominations ' . $prev_year . '" href="' . esc_url($prev_link) . '" class="nominations-nav-button">← ' . $prev_year . '</a>';
    }

    // Add the year dropdown in place of the title
    $output .= '<select id="oscarsYearDropdown">';
    $output .= '<option value="" selected disabled>' . $current_year . '</option>';
    for ($year = $max_year; $year >= $min_year; $year--) {
        $output .= '<option value="' . $year . '">' . $year . '</option>';
    }
    $output .= '</select>';

    // Add the next link if it's within the allowed range
    if ($next_year <= $max_year) {
        $next_link = "https://oscarschecklist.com/years/nominations-$next_year/";
        $output .= '<a title="Nominations ' . $next_year . '" href="' . esc_url($next_link) . '" class="nominations-nav-button">' . $next_year . ' →</a>';
    }

    $output .= '</div>';

    // Add the JavaScript for dropdown functionality
    $output .= <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('oscarsYearDropdown').addEventListener('change', function() {
        var year = this.value;
        window.location.href = 'https://oscarschecklist.com/years/nominations-' + year + '/';
    });
});
</script>
EOT;

    return $output;
}

// Register the shortcode
add_shortcode('oscar_navigation', 'oscar_nominations_navigation');

function unique_films_shortcode($atts) {
    // Get the 'year' from ACF (Advanced Custom Fields)
    $year = get_field('year');

    // Initialize output variable
    $output = '';

    // Array to track films already listed
    $films_displayed = array();

    // Set up the query to get nominations for the specified year
    $args = array(
        'post_type' => 'nominations',
        'posts_per_page' => -1, // Get all nominations
        'date_query' => array(
            array(
                'year' => $year, // Filter by year
            ),
        ),
    );

    // Execute the query
    $nominations_query = new WP_Query($args);

    if ($nominations_query->have_posts()) {
        $output .= '<div class="awards-category">';
        $output .= '<div class="category-title"><h2>All Films</h2><div class="circular-progress-container"><div class="circular-progress"></div><span class="progress"></span><span class="total"></span></div></div>';
        $output .= '<ul class="nominations-list">';

        // Loop through each nomination
        while ($nominations_query->have_posts()) {
            $nominations_query->the_post();

            // Get the associated film taxonomy terms
            $films = get_the_terms(get_the_ID(), 'films');

            if ($films && !is_wp_error($films)) {
                foreach ($films as $film) {
                    $film_id = $film->term_id;

                    // Initialize categories array for this film
                    if (!isset($films_displayed[$film_id])) {
                        $films_displayed[$film_id] = array(
                            'name' => $film->name,
                            'link' => get_term_link($film),
                            'image' => get_field('poster', 'films_' . $film_id), // Get the featured image
                            'categories' => array(),
                            'winner_count' => 0,
                            'total_nominations' => 0,
                            'film_id' => $film->term_id,
                        );
                    }

                    // Get the award categories of the nomination
                    $categories = get_the_terms(get_the_ID(), 'award-categories');

                    if ($categories && !is_wp_error($categories)) {
                        foreach ($categories as $category) {
                            $category_name = esc_html($category->name);

                            // Check if this nomination has the 'winner' term
                            $is_winner = has_term('winner', 'award-categories', get_the_ID());

                            // Increase the winner count if this is a winning nomination
                            if ($is_winner) {
                                $films_displayed[$film_id]['winner_count']++;
                            }

                            // Add or update the category in the film's categories array
                            if (!isset($films_displayed[$film_id]['categories'][$category_name])) {
                                $films_displayed[$film_id]['categories'][$category_name] = array(
                                    'count' => 1,
                                    'is_winner' => $is_winner,
                                );
                            } else {
                                $films_displayed[$film_id]['categories'][$category_name]['count']++;
                            }

                            // Increment the total nominations for this film
                            $films_displayed[$film_id]['total_nominations']++;
                        }
                    }
                }
            }
        }

        // Sort the films by winner count and then by total nominations
        usort($films_displayed, function($a, $b) {
            if ($a['winner_count'] === $b['winner_count']) {
                return $b['total_nominations'] - $a['total_nominations'];
            }
            return $b['winner_count'] - $a['winner_count'];
        });

        // Output the films and their categories
        foreach ($films_displayed as $film_id => $film) {

            if (is_user_logged_in()) {
                // $post_id = $film->term_id;
                // Original line to get user's watched status
                $user_watched = get_user_meta(get_current_user_id(), 'watched_' . esc_attr($film['film_id']), true);
            }

            // Add the film ID as a class
            $output .= '<li class="nomination-item film-id-' . esc_attr($film['film_id']);
            if (is_user_logged_in() && $user_watched) {
                $output .= ' watched ';
            }
            $output .= '">';
            // Display the film's featured image if it exists
            if ($film['image']) {
                $output .= '<div class="film-image"><img src="' . esc_url($film['image']) . '" alt="' . esc_attr($film['name']) . '"></div>';
            }

            // Display the film name with a link
            $output .= '<h3><a href="' . esc_url($film['link']) . '">' . esc_html($film['name']) . '</a></h3>';

            $output .= '<ul class="nomination-categories">';
            foreach ($film['categories'] as $category_name => $category_info) {
                // Generate class names based on the category name
                $class = sanitize_title($category_name);
                $class .= $category_info['is_winner'] ? ' win' : '';

                $count_suffix = $category_info['count'] > 1 ? ' * ' . $category_info['count'] : '';
                $output .= '<li class="' . esc_attr($class) . '">' . esc_html($category_name) . $count_suffix . '</li>';
            }
            $output .= '</ul>';

            if (is_user_logged_in()) {
                if ($user_watched) {
                    $output .= '<form class="mark-as-unwatched">';
                    $output .= '<input type="hidden" name="watched_post_id" value="' . esc_attr($film['film_id']) . '">';
                    $output .= '<input type="hidden" name="watched_action" value="unwatched">';
                    $output .= '<button class="mark-as-watched-button" type="button">Watched</button>';
                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>';
                    $output .= '</form>';
                } else {
                    $output .= '<form class="mark-as-watched">';
                    $output .= '<input type="hidden" name="watched_post_id" value="' . esc_attr($film['film_id']) . '">';
                    $output .= '<input type="hidden" name="watched_action" value="watched">';
                    $output .= '<button class="mark-as-unwatched-button" type="button">Unwatched</button>';
                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>';
                    $output .= '</form>';
                }
            } else {
                $output .= '<form class="mark-as-watched">';
                $output .= '<input type="hidden" name="watched_post_id" value="' . $film->term_id . '">';
                $output .= '<input type="hidden" name="watched_action" value="watched">';
                $output .= '<button class="mark-as-unwatched-button" type="button">Unwatched</button>';
                $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>';
                $output .= '</form>';
            }

            $output .= '</li>';
        }

        $output .= '</ul>';
        $output .= '</div>';
    } else {
        // If no nominations are found for that year
        $output .= '<p>No nominations found for the year ' . esc_html($year) . '.</p>';
    }

    // Reset post data
    wp_reset_postdata();

    return $output;
}

add_shortcode('unique_films', 'unique_films_shortcode');

function custom_login_redirect_for_login_page($redirect_to, $request, $user) {
    // Check if the user is logged in
    if (isset($user->roles) && is_array($user->roles)) {
        // If the user has the 'subscriber' role
        if (in_array('subscriber', $user->roles)) {
            // Check if the login request came from /wp-login.php
            if (strpos($_SERVER['REQUEST_URI'], '/wp-login.php') !== false) {
                return home_url();
            }
        }
    }
    // Return the default redirect for all other users
    return $redirect_to;
}
add_filter('login_redirect', 'custom_login_redirect_for_login_page', 10, 3);

function wpb_login_logo() { 
?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(https://oscarschecklist.com/wp-content/uploads/2024/08/oscars-checklist-logo.png);
        height:100px;
        width:300px;
        background-size: 300px 100px;
        background-repeat: no-repeat;
        padding-bottom: 10px;
        background-size: contain;
        }

        body {
            background-color: white !important;
        }
        
        @media (prefers-color-scheme: dark) {
            body {
                background-color: black !important;
            }
            div#login *:not(input, button) {
                color: white !important;
            }
            div#login button span {
                color: #c59d3f !important;
            }
            div#login div#login-message {
                /* color: black !important; */
            }
            div#login .notice p {
                color: #3c434a !important;
            }
        }

        div#login {
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-flow: column;
            align-items: center;
            justify-content: center;
        }

        div#login > *:last-child {
            margin-bottom: 200px;
        }

        div#login form {
            border: none;
            box-shadow: none;
            padding: 25px 5px;
            margin: 0;
            background: inherit;
        }

        div#login form .input, 
        div#login form input[type=checkbox], 
        div#login input[type=text] {
            border-radius: 0;
            border-color: #e6e6e6;
            box-shadow: none;
            padding: 5px 15px 6px;
            font-size: 16px;
        }

        div#login form .input:focus, 
        div#login form input[type=checkbox]:focus, 
        div#login input[type=text]:focus {
            border-color: #c59d3f;
        }

        .wp-core-ui #login .button,
        .wp-core-ui #login .button-secondary {
            border-radius: 0;
            color: #c59d3f;
            border-color: #c59d3f;
        }

        .wp-core-ui #login .button-primary {
            background: #c59d3f;
            color: black;
            border: none;
            border-radius: 0;
            padding: 0 20px;
        }
        
        .wp-core-ui #login .button-primary:hover {
            background: #9a7721;
        }

        body.login .message,
        body.login .notice,
        body.login .success {
            border-left: 3px solid #c59d3f;
            background-color: #fbfbfb;
            box-shadow: none;
        }

    </style>
<?php 
						  }
add_action( 'login_enqueue_scripts', 'wpb_login_logo' );

function wpb_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'wpb_login_logo_url' );
  
function wpb_login_logo_url_title() {
    return 'Your Site Name and Info';
}
add_filter( 'login_headertext', 'wpb_login_logo_url_title' );

add_filter( 'login_display_language_dropdown', '__return_false' );


// define the bp_core_signup_user callback 
function ac_bp_core_signup_user( $user_id ) { 
    // make action magic happen here...
    $fullname = bp_get_member_profile_data( array( 'field' => 'Name', 'user_id' => $user_id ) );
   //split the full name at the first space
   $name_parts = explode(" ", $fullname, 2);

   //set the firstname and lastname for WordPress based on the BP name
   //firstname
   if( isset($name_parts[0]) )
        update_user_meta( $user_id, 'first_name', $name_parts[0] );

   //lastname
   if( isset($name_parts[1]) )
       update_user_meta( $user_id, 'last_name', $name_parts[1] );

   //not needed for an action, but I always like to return something
   return $fullname;
}

// BuddyPress save first name last name to WP profile on signup 
add_action( 'bp_core_signup_user', 'ac_bp_core_signup_user', 10, 1 );

add_filter( 'sanitize_user', 'remove_whitespace_from_username', 10, 3 );
function remove_whitespace_from_username( $username, $raw_username, $strict  ){
    $username = preg_replace('/\s+/', ' ', $username);
    return $username;
}



function custom_toggle_buttons_shortcode() {
    $show_unique = "";
    $hide_watched = "";
    $winners_only = "";

    $show_unique_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M48 256C48 141.1 141.1 48 256 48c63.1 0 119.6 28.1 157.8 72.5c8.6 10.1 23.8 11.2 33.8 2.6s11.2-23.8 2.6-33.8C403.3 34.6 333.7 0 256 0C114.6 0 0 114.6 0 256l0 40c0 13.3 10.7 24 24 24s24-10.7 24-24l0-40zm458.5-52.9c-2.7-13-15.5-21.3-28.4-18.5s-21.3 15.5-18.5 28.4c2.9 13.9 4.5 28.3 4.5 43.1l0 40c0 13.3 10.7 24 24 24s24-10.7 24-24l0-40c0-18.1-1.9-35.8-5.5-52.9zM256 80c-19 0-37.4 3-54.5 8.6c-15.2 5-18.7 23.7-8.3 35.9c7.1 8.3 18.8 10.8 29.4 7.9c10.6-2.9 21.8-4.4 33.4-4.4c70.7 0 128 57.3 128 128l0 24.9c0 25.2-1.5 50.3-4.4 75.3c-1.7 14.6 9.4 27.8 24.2 27.8c11.8 0 21.9-8.6 23.3-20.3c3.3-27.4 5-55 5-82.7l0-24.9c0-97.2-78.8-176-176-176zM150.7 148.7c-9.1-10.6-25.3-11.4-33.9-.4C93.7 178 80 215.4 80 256l0 24.9c0 24.2-2.6 48.4-7.8 71.9C68.8 368.4 80.1 384 96.1 384c10.5 0 19.9-7 22.2-17.3c6.4-28.1 9.7-56.8 9.7-85.8l0-24.9c0-27.2 8.5-52.4 22.9-73.1c7.2-10.4 8-24.6-.2-34.2zM256 160c-53 0-96 43-96 96l0 24.9c0 35.9-4.6 71.5-13.8 106.1c-3.8 14.3 6.7 29 21.5 29c9.5 0 17.9-6.2 20.4-15.4c10.5-39 15.9-79.2 15.9-119.7l0-24.9c0-28.7 23.3-52 52-52s52 23.3 52 52l0 24.9c0 36.3-3.5 72.4-10.4 107.9c-2.7 13.9 7.7 27.2 21.8 27.2c10.2 0 19-7 21-17c7.7-38.8 11.6-78.3 11.6-118.1l0-24.9c0-53-43-96-96-96zm24 96c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 24.9c0 59.9-11 119.3-32.5 175.2l-5.9 15.3c-4.8 12.4 1.4 26.3 13.8 31s26.3-1.4 31-13.8l5.9-15.3C267.9 411.9 280 346.7 280 280.9l0-24.9z"/></svg>';
    $hide_watched_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L504.5 470.8C507.2 468.4 509.9 466 512.5 463.6C559.3 420.1 590.6 368.2 605.5 332.5C608.8 324.6 608.8 315.8 605.5 307.9C590.6 272.2 559.3 220.2 512.5 176.8C465.4 133.1 400.7 96.2 319.9 96.2C263.1 96.2 214.3 114.4 173.9 140.4L73 39.1zM236.5 202.7C260 185.9 288.9 176 320 176C399.5 176 464 240.5 464 320C464 351.1 454.1 379.9 437.3 403.5L402.6 368.8C415.3 347.4 419.6 321.1 412.7 295.1C399 243.9 346.3 213.5 295.1 227.2C286.5 229.5 278.4 232.9 271.1 237.2L236.4 202.5zM357.3 459.1C345.4 462.3 332.9 464 320 464C240.5 464 176 399.5 176 320C176 307.1 177.7 294.6 180.9 282.7L101.4 203.2C68.8 240 46.4 279 34.5 307.7C31.2 315.6 31.2 324.4 34.5 332.3C49.4 368 80.7 420 127.5 463.4C174.6 507.1 239.3 544 320.1 544C357.4 544 391.3 536.1 421.6 523.4L357.4 459.2z"/></svg>';
    $winners_only_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M400 0L176 0c-26.5 0-48.1 21.8-47.1 48.2c.2 5.3 .4 10.6 .7 15.8L24 64C10.7 64 0 74.7 0 88c0 92.6 33.5 157 78.5 200.7c44.3 43.1 98.3 64.8 138.1 75.8c23.4 6.5 39.4 26 39.4 45.6c0 20.9-17 37.9-37.9 37.9L192 448c-17.7 0-32 14.3-32 32s14.3 32 32 32l192 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-26.1 0C337 448 320 431 320 410.1c0-19.6 15.9-39.2 39.4-45.6c39.9-11 93.9-32.7 138.2-75.8C542.5 245 576 180.6 576 88c0-13.3-10.7-24-24-24L446.4 64c.3-5.2 .5-10.4 .7-15.8C448.1 21.8 426.5 0 400 0zM48.9 112l84.4 0c9.1 90.1 29.2 150.3 51.9 190.6c-24.9-11-50.8-26.5-73.2-48.3c-32-31.1-58-76-63-142.3zM464.1 254.3c-22.4 21.8-48.3 37.3-73.2 48.3c22.7-40.3 42.8-100.5 51.9-190.6l84.4 0c-5.1 66.3-31.1 111.2-63 142.3z"/></svg>';
    $progress_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M96 96C113.7 96 128 110.3 128 128L128 464C128 472.8 135.2 480 144 480L544 480C561.7 480 576 494.3 576 512C576 529.7 561.7 544 544 544L144 544C99.8 544 64 508.2 64 464L64 128C64 110.3 78.3 96 96 96zM208 288C225.7 288 240 302.3 240 320L240 384C240 401.7 225.7 416 208 416C190.3 416 176 401.7 176 384L176 320C176 302.3 190.3 288 208 288zM352 224L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 224C288 206.3 302.3 192 320 192C337.7 192 352 206.3 352 224zM432 256C449.7 256 464 270.3 464 288L464 384C464 401.7 449.7 416 432 416C414.3 416 400 401.7 400 384L400 288C400 270.3 414.3 256 432 256zM576 160L576 384C576 401.7 561.7 416 544 416C526.3 416 512 401.7 512 384L512 160C512 142.3 526.3 128 544 128C561.7 128 576 142.3 576 160z"/></svg>';
    $watchlist_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M112 208C138.5 208 160 186.5 160 160C160 133.5 138.5 112 112 112C85.5 112 64 133.5 64 160C64 186.5 85.5 208 112 208zM256 128C238.3 128 224 142.3 224 160C224 177.7 238.3 192 256 192L544 192C561.7 192 576 177.7 576 160C576 142.3 561.7 128 544 128L256 128zM256 288C238.3 288 224 302.3 224 320C224 337.7 238.3 352 256 352L544 352C561.7 352 576 337.7 576 320C576 302.3 561.7 288 544 288L256 288zM256 448C238.3 448 224 462.3 224 480C224 497.7 238.3 512 256 512L544 512C561.7 512 576 497.7 576 480C576 462.3 561.7 448 544 448L256 448zM112 528C138.5 528 160 506.5 160 480C160 453.5 138.5 432 112 432C85.5 432 64 453.5 64 480C64 506.5 85.5 528 112 528zM160 320C160 293.5 138.5 272 112 272C85.5 272 64 293.5 64 320C64 346.5 85.5 368 112 368C138.5 368 160 346.5 160 320z"/></svg>';

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();

        // Get user preferences for each option
        $show_unique = get_user_meta($user_id, 'show_unique_films', true);
        $hide_watched = get_user_meta($user_id, 'hide_watched_films', true);
        $winners_only = get_user_meta($user_id, 'winners_only', true);
    }

    // Determine button texts based on user preferences
    $show_unique_text = $show_unique ? 'Show Unique' : 'Show Unique';
    $hide_watched_text = $hide_watched ? 'Show Watched' : 'Hide Watched';
    $winners_only_text = $winners_only ? 'Winners Only' : 'Winners Only';
    $predicted_text = 'Hide Predicted';

    // Generate the buttons' HTML with <span> to hold the text
    $output = '<button id="toggle-unique-films" class="toggle-unique-films-button">' . $show_unique_icon . '<span>' . $show_unique_text . '</span></button>';
    $output .= '<button id="toggle-hide-watched" class="toggle-hide-watched-button">' . $hide_watched_icon . '<span>' . $hide_watched_text . '</span></button>';
    $output .= '<button id="toggle-winners-only" class="toggle-winners-only-button">' . $winners_only_icon . '<span>' . $winners_only_text . '</span></button>';
    $output .= '<button id="toggle-predicted" class="toggle-predicted-button">' . $winners_only_icon . '<span>' . $predicted_text . '</span></button>';
    $output .= '<button id="toggle-progress" class="progress-toggle">' . $progress_icon . '<span>Progress</span></button>';
    $output .= '<button id="toggle-watchlist" class="watchlist-toggle">' . $watchlist_icon . '<span>Watchlist</span></button>';

    // Output the script to handle the button clicks
    $output .= '
    <script type="text/javascript">

        document.addEventListener("DOMContentLoaded", function() {
            var body = document.body;

            document.getElementById("toggle-predicted").addEventListener("click", function() {
                body.classList.toggle("hide-predicted");
            });

            // Initial class application based on user preferences
            if (' . json_encode($show_unique) . ') { body.classList.add("show-unique-films"); }
            if (' . json_encode($hide_watched) . ') { body.classList.add("hide-watched-films"); }
            if (' . json_encode($winners_only) . ') { body.classList.add("winners-only"); }

            // Handle "Show Unique Films" button click
            document.getElementById("toggle-unique-films").addEventListener("click", function() {
                body.classList.toggle("show-unique-films");
                savePreference("show_unique_films", body.classList.contains("show-unique-films"), this, "Show Unique", "Show Unique");
            });

            // Handle "Hide Watched Films" button click
            document.getElementById("toggle-hide-watched").addEventListener("click", function() {
                body.classList.toggle("hide-watched-films");
                savePreference("hide_watched_films", body.classList.contains("hide-watched-films"), this, "Hide Watched", "Hide Watched");
            });

            // Handle "Winners Only" button click
            document.getElementById("toggle-winners-only").addEventListener("click", function() {
                body.classList.toggle("winners-only");
                savePreference("winners_only", body.classList.contains("winners-only"), this, "Winners Only", "Winners Only");
            });

            // Function to save user preference via AJAX
            function savePreference(metaKey, state, button, enableText, disableText) {
                button.querySelector("span").textContent = state ? disableText : enableText;

                var formData = new FormData();
                formData.append("action", "save_toggle_preference");
                formData.append("meta_key", metaKey);
                formData.append("state", state);

                fetch("' . admin_url('admin-ajax.php') . '", {
                    method: "POST",
                    credentials: "same-origin",
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.text();
                })
                .catch(error => {
                    console.error("Error:", error);
                });
            }
        });
    </script>';

    return $output;
}

add_shortcode('custom_toggle_buttons', 'custom_toggle_buttons_shortcode');

function save_toggle_preference() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();

        // Get the preference key and state from the AJAX request
        $meta_key = isset($_POST['meta_key']) ? sanitize_text_field($_POST['meta_key']) : '';
        $state = isset($_POST['state']) ? filter_var($_POST['state'], FILTER_VALIDATE_BOOLEAN) : false;

        // Save the preference in user meta
        if ($meta_key) {
            update_user_meta($user_id, $meta_key, $state);
            echo 'Preference saved.';
        } else {
            echo 'Invalid request.';
        }
    }

    wp_die();
}
add_action('wp_ajax_save_toggle_preference', 'save_toggle_preference');

function add_custom_body_classes($classes) {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();

        // Add user ID class
        $classes[] = 'user-id-' . $user_id;

        // Get user preferences
        $show_unique = get_user_meta($user_id, 'show_unique_films', true);
        $hide_watched = get_user_meta($user_id, 'hide_watched_films', true);
        $winners_only = get_user_meta($user_id, 'winners_only', true);

        // Add classes based on user preferences
        if ($show_unique) { $classes[] = 'show-unique-films'; }
        if ($hide_watched) { $classes[] = 'hide-watched-films'; }
        if ($winners_only) { $classes[] = 'winners-only'; }
    }

    return $classes;
}
add_filter('body_class', 'add_custom_body_classes');


function add_custom_js_to_footer() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            const usernameInput = document.getElementById('signup_username');
            
            if (usernameInput) {
                // Create a notification element
                const notification = document.createElement('div');
                notification.classList.add('space-message'); // Add the class 'space-message'
                notification.style.display = 'none'; // Initially hidden
                notification.textContent = 'Spaces are not allowed in usernames. They have been replaced with hyphens.';
                usernameInput.parentNode.appendChild(notification);
                
                usernameInput.addEventListener('input', function(event) {
                    const originalValue = this.value;
                    this.value = this.value.replace(/\s+/g, '-');
                    
                    // If a space was replaced, show the notification
                    if (originalValue !== this.value) {
                        notification.style.display = 'block'; // Show the message
                    }
                });
            }
        });
    </script>
    <?php
}
add_action('wp_footer', 'add_custom_js_to_footer');



require_once get_stylesheet_directory() . '/nominees_nominations.php';


remove_filter( 'wp_mail_content_type', 'pmpro_wp_mail_content_type' );




function custom_redirect_requests_to_friends_requests() {
    // Check if the user is logged in
    if (is_user_logged_in()) {
        // Get the current logged-in user information
        $current_user = wp_get_current_user();

        // Get the current URL path, ensuring it's clean
        $current_url_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // Check if the user is on the exact /requests page
        if ($current_url_path === 'requests') {
            // Construct the URL to redirect to the user's friend requests page
            $redirect_url = home_url('/members/' . $current_user->user_login . '/friends/requests/');

            // Perform the redirect
            wp_redirect($redirect_url);
            exit;
        }
    }
}
add_action('template_redirect', 'custom_redirect_requests_to_friends_requests');


function add_badge_to_requests_menu_item($items, $args) {
    // Check if the user is logged in
    if (is_user_logged_in() && function_exists('bp_friend_get_total_requests_count')) {
        // Get the current logged-in user's pending friend requests count
        $pending_requests = bp_friend_get_total_requests_count(bp_loggedin_user_id());

        // Only proceed if there are pending requests
        if ($pending_requests > 0) {
            // Loop through all menu items
            foreach ($items as $item) {
                // Check if the menu item contains "Requests" as the title
                if (trim($item->title) === 'Requests') {
                    // Append the badge with the number of pending requests
                    $item->title .= ' <span class="badge">' . $pending_requests . '</span>';
                }
            }
        }
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'add_badge_to_requests_menu_item', 10, 2);




// $taxonomies = ['films', 'nominations']; // Your taxonomies
// Exclude 'recipes' from search results and show 'recipe-categories' taxonomy terms instead
function modify_search_query($query) {
    // Ensure this runs only on the main search query in the frontend
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        // Exclude all post types from the search
        $query->set('post_type', 'none');
    }
}

add_action('pre_get_posts', 'modify_search_query');
// Add taxonomy terms to search results
function add_taxonomy_terms_to_search($results, $query) {
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $search_term = $query->get('s'); // Get the search keyword

        // Define taxonomies to include in search
        $taxonomies = array('films', 'nominees', 'languages', 'countries', 'distributors-production-comps'  );

        foreach ($taxonomies as $taxonomy) {
            // Find terms in the current taxonomy that match the search term
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'name__like' => $search_term,
                'hide_empty' => false,
            ));

            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $result = (object) array(
                        'ID' => $term->term_id,
                        'post_title' => $term->name,
                        'post_type' => $taxonomy, // Use taxonomy name as a virtual post type
                    );
                    $results[] = $result;
                }
            }
        }
    }
    return $results;
}
add_filter('posts_results', 'add_taxonomy_terms_to_search', 10, 2);


// Customize search template display for taxonomy terms
function load_custom_search_template($template) {
    if (is_search()) {
        // Check if a custom search template exists
        $custom_template = locate_template('custom-search.php');
        if ($custom_template) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter('template_include', 'load_custom_search_template');









require_once get_stylesheet_directory() . '/scoreboard_admin.php';


function enqueue_page_scoreboard_styles() {
    if ((basename(get_page_template()) == 'page-scoreboard.php')) {
        wp_enqueue_style('page-scoreboard-style', get_stylesheet_directory_uri() . '/page-scoreboard.css', array(), null);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_page_scoreboard_styles');





function winner_class_updater_script() {
    ob_start();
    ?>
    <script>
    (function () {
        let refreshInterval = 10000;
        let intervalId;
    
        // console.log("Winner updater script initialized.");
    
        function fetchAndUpdate() {
            // console.log("Fetching data from server...");
            fetch("https://results.oscarschecklist.com/serve-results.php")
                .then(response => response.json())
                .then(data => {
                    // console.log("Data received:", data);
    
                    // Remove 'Winner' class from all nominations before applying updates
                    document.querySelectorAll(".nomination.Winner").forEach(nomination => {
                        nomination.classList.remove("Winner");
                    });
    
                    // Apply 'Winner' class if 'wI' exists
                    if (data.wI) {
                        let winningNomination = document.getElementById(`nomination-${data.wI}`);
                        if (winningNomination) {
                            // console.log("Applying 'Winner' class to nomination ID:", data.wI);
                            winningNomination.classList.add("winner");
                        } else {
                            console.warn("No matching nomination found for ID:", data.wI);
                        }
                    }
    
                    // Update refresh interval if 'rI' value is different
                    if (data.rI && data.rI * 1000 !== refreshInterval) {
                        // console.log("Updating refresh interval to:", data.rI * 1000, "ms");
                        refreshInterval = data.rI * 1000;
                        clearInterval(intervalId);
                        intervalId = setInterval(fetchAndUpdate, refreshInterval);
                    }
                })
                .catch(error => console.error("Error fetching results:", error));
        }
    
        fetchAndUpdate();
        intervalId = setInterval(fetchAndUpdate, refreshInterval);
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('winner_updater', 'winner_class_updater_script');




function bp_auto_activate_account( $user_id ) {
    bp_core_activate_signup( $user_id );
}
add_action( 'bp_core_signup_user', 'bp_auto_activate_account' );

function auto_login_new_user( $user_id ) {
    // wp_set_auth_cookie( $user_id, true );
    wp_redirect( home_url('/login/') ); // Change '/login/' to your actual login page URL
    exit;
}
add_action( 'bp_core_signup_user', 'auto_login_new_user' );;



function is_safari_desktop() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    if (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Chrome') === false && strpos($user_agent, 'Mobile') === false) {
        return true;
    }
    return false;
}
function add_safari_desktop_class($classes) {
    if (is_safari_desktop()) {
        $classes[] = 'safari-desktop';
    }
    return $classes;
}
add_filter('body_class', 'add_safari_desktop_class');






function prediction_count_shortcode() {
    ob_start();
    ?>
    <div id="prediction-counter">Loading predictions...</div>

    <script>
    function updatePredictionCount() {
        let lists = document.querySelectorAll("ul.nominations-list");
        let unpredictedCount = 0;

        lists.forEach(ul => {
            let hasPrediction = ul.querySelector("li.predict") !== null;
            if (!hasPrediction) {
                unpredictedCount++;
            }
        });

        let counterElement = document.getElementById("prediction-counter");

        if (unpredictedCount === 1) {
            counterElement.textContent = "You have 1 category left to predict!";
            counterElement.classList.remove("all-predictions-made");
        } else if (unpredictedCount > 1) {
            counterElement.textContent = `You have ${unpredictedCount} categories left to predict!`;
            counterElement.classList.remove("all-predictions-made");
        } else {
            counterElement.textContent = "You've cast all your predictions!";
            counterElement.classList.add("all-predictions-made");
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        updatePredictionCount(); // Run on page load

        // Observe changes in .nominations-list elements
        let observer = new MutationObserver(updatePredictionCount);
        document.querySelectorAll("ul.nominations-list").forEach(ul => {
            observer.observe(ul, { childList: true, subtree: true, attributes: true });
        });

        // Listen for button clicks and force an update
        document.body.addEventListener("click", function(event) {
            if (event.target.classList.contains("mark-as-unpredict-button") || 
                event.target.classList.contains("mark-as-predict-button")) {
                setTimeout(updatePredictionCount, 50); // Small delay to allow class changes
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('prediction_count', 'prediction_count_shortcode');



// require_once get_stylesheet_directory() . '/update_meta.php';
require_once get_stylesheet_directory() . '/film_stats.php';






function aggregate_watched_dates_to_by_day_json() {
    if ( ! current_user_can('manage_options') ) {
        return;
    }

    $upload_dir = wp_upload_dir();
    $user_meta_dir = $upload_dir['basedir'] . '/user_meta';
    $watched_by_day_path = $upload_dir['basedir'] . '/watched_by_day.json';

    // Load or initialize watched_by_day data
    if (file_exists($watched_by_day_path)) {
        $json = file_get_contents($watched_by_day_path);
        $data = json_decode($json, true);
        if (!is_array($data)) $data = [];
    } else {
        $data = [];
    }
    if (!isset($data['days'])) $data['days'] = [];
    if (!isset($data['films'])) $data['films'] = [];

    // Loop through all user json files
    foreach (glob($user_meta_dir . '/user_*.json') as $user_file) {
        $user_json = file_get_contents($user_file);
        $user_data = json_decode($user_json, true);
        if (!isset($user_data['watched']) || !is_array($user_data['watched'])) continue;

        foreach ($user_data['watched'] as $film) {
            if (!empty($film['watched-date']) && !empty($film['film-id'])) {
                $date = $film['watched-date'];
                $film_id = $film['film-id'];

                // Increment total watched for this date
                if (!isset($data['days'][$date])) {
                    $data['days'][$date] = 1;
                } else {
                    $data['days'][$date]++;
                }

                // Increment watched for this film for this date
                if (!isset($data['films'][$film_id])) $data['films'][$film_id] = [];
                if (!isset($data['films'][$film_id][$date])) {
                    $data['films'][$film_id][$date] = 1;
                } else {
                    $data['films'][$film_id][$date]++;
                }
            }
        }
    }

    // Save back to file
    file_put_contents($watched_by_day_path, json_encode($data));
}

function watched_by_day_admin_button() {
    if (!current_user_can('manage_options')) return '';
    $nonce = wp_create_nonce('aggregate_watched_dates');
    return '
        <div style="margin-bottom:1em;">
            <button id="aggregate-watched-dates" style="padding:8px 16px;font-size:16px;">Aggregate Watched Dates</button>
            <span id="aggregate-watched-dates-status"></span>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var btn = document.getElementById("aggregate-watched-dates");
            if(btn){
                btn.addEventListener("click", function() {
                    btn.disabled = true;
                    document.getElementById("aggregate-watched-dates-status").textContent = "Processing...";
                    fetch("' . admin_url('admin-ajax.php') . '?action=aggregate_watched_dates&nonce=' . $nonce . '")
                        .then(r => r.text())
                        .then(txt => {
                            document.getElementById("aggregate-watched-dates-status").textContent = txt;
                            btn.disabled = false;
                        });
                });
            }
        });
        </script>
    ';
}
add_shortcode('watched_by_day_admin_button', 'watched_by_day_admin_button');

// AJAX handler
add_action('wp_ajax_aggregate_watched_dates', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'aggregate_watched_dates')) {
        wp_die('Invalid nonce');
    }
    aggregate_watched_dates_to_by_day_json();
    wp_die('Aggregation complete!');
});

// Schedule daily cron job for watched dates aggregation
add_action('wp', function() {
    if (!wp_next_scheduled('daily_aggregate_watched_dates')) {
        wp_schedule_event(time(), 'daily', 'daily_aggregate_watched_dates');
    }
});

// Hook the aggregation function to the cron event
add_action('daily_aggregate_watched_dates', 'aggregate_watched_dates_to_by_day_json');


require_once get_stylesheet_directory() . '/watchlist/watchlist.php';

// === AJAX: Get user data as JSON ===
add_action('wp_ajax_get_user_data', 'oscars_get_user_data');
add_action('wp_ajax_nopriv_get_user_data', 'oscars_get_user_data');
function oscars_get_user_data() {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if (!$user_id) {
        wp_send_json_error(['error' => 'No user_id provided']);
    }
    $file = get_user_meta_json_path($user_id);
    if (!file_exists($file)) {
        wp_send_json_error(['error' => 'User file not found']);
    }
    $json = file_get_contents($file);
    wp_send_json(json_decode($json, true));
}

// === AJAX: Initialize user data file ===
add_action('wp_ajax_init_user_data', 'oscars_init_user_data');
add_action('wp_ajax_nopriv_init_user_data', 'oscars_init_user_data');
function oscars_init_user_data() {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if (!$user_id) {
        wp_send_json_error(['error' => 'No user_id provided']);
    }
    
    $file = get_user_meta_json_path($user_id);
    $pred_fav_file = get_user_pred_fav_json_path($user_id);
    
    // If file already exists, return it
    if (file_exists($file)) {
        $json = file_get_contents($file);
        wp_send_json_success(json_decode($json, true));
        return;
    }
    
    // Create new user data file
    $user = get_userdata($user_id);
    $username = $user ? $user->user_login : '';
    $data = [
        'watched' => [],
        'watchlist' => [],
        'favourite-categories' => [],
        'hidden-categories' => [],
        'public' => false,
        'username' => $username,
        'total-watched' => 0,
        'last-updated' => date('Y-m-d'),
        'this_page_only' => true,
        'auto_remove_watched' => true,
        'compact_view' => true,
    ];
    
    // Also create pred_fav file if it doesn't exist
    if (!file_exists($pred_fav_file)) {
        $pred_fav_data = [
            'username' => $username,
            'favourites' => [],
            'predictions' => [],
            'correct-predictions' => "",
            'incorrect-predictions' => "",
            'correct-prediction-rate' => "",
        ];
        file_put_contents($pred_fav_file, wp_json_encode($pred_fav_data));
    }
    
    file_put_contents($file, wp_json_encode($data));
    wp_send_json_success($data);
}

// === AJAX: Update category favourite/hidden ===
add_action('wp_ajax_update_category', 'oscars_update_category');
add_action('wp_ajax_nopriv_update_category', 'oscars_update_category');
function oscars_update_category() {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $category_slug = isset($_POST['category_slug']) ? sanitize_text_field($_POST['category_slug']) : '';
    $action = isset($_POST['category_action']) ? sanitize_text_field($_POST['category_action']) : '';
    $is_adding = isset($_POST['is_adding']) ? filter_var($_POST['is_adding'], FILTER_VALIDATE_BOOLEAN) : false;
    if (!$user_id || !$category_slug || !in_array($action, ['favourite', 'hidden-category'])) {
        wp_send_json_error(['error' => 'Invalid parameters']);
    }
    $file = get_user_meta_json_path($user_id);
    if (!file_exists($file)) {
        wp_send_json_error(['error' => 'User file not found']);
    }
    $data = json_decode(file_get_contents($file), true);
    $key = $action === 'favourite' ? 'favourite-categories' : 'hidden-categories';
    if (!isset($data[$key])) $data[$key] = [];
    if ($is_adding) {
        if (!in_array($category_slug, $data[$key])) {
            $data[$key][] = $category_slug;
        }
    } else {
        $data[$key] = array_values(array_diff($data[$key], [$category_slug]));
    }
    file_put_contents($file, json_encode($data));
    wp_send_json_success(['data' => $data]);
}

