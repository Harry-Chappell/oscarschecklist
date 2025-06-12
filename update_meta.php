<?php


function show_current_user_meta_data_raw() {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $user_meta = get_user_meta( $current_user->ID );

        ob_start();
        echo '<h2>User Meta for ' . esc_html( $current_user->display_name ) . '</h2>';
        echo '<pre>';
        print_r( $user_meta );
        echo '</pre>';
        return ob_get_clean();
    } else {
        return '<p>No user is logged in.</p>';
    }
}
add_shortcode( 'user_meta_raw', 'show_current_user_meta_data_raw' );


function enqueue_user_meta_transform_script() {
    wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
    wp_enqueue_script(
        'user-meta-transform',
        get_stylesheet_directory_uri() . '/user-meta-transform.js',
        array('chart-js'),
        null,
        true
    );
    wp_localize_script('user-meta-transform', 'userMetaAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('user_meta_nonce'),
        'user_id'  => get_current_user_id(),
    ]);
}
add_action( 'wp_enqueue_scripts', 'enqueue_user_meta_transform_script' );


function user_meta_transform_button_shortcode() {
    if ( !is_user_logged_in() ) return '<p>Please log in to view your meta data.</p>';

    $html = '
        <div><button id="refresh-all-users-meta" style="padding:10px 20px;cursor:pointer;margin-top:10px;">Refresh All Users Meta</button></div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var allBtn = document.getElementById("refresh-all-users-meta");
            if (allBtn) {
                allBtn.addEventListener("click", function() {
                    allBtn.disabled = true;
                    allBtn.textContent = "Refreshing all users...";
                    fetch(ajaxurl, {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: new URLSearchParams({
                            action: "refresh_all_users_meta",
                            nonce: userMetaAjax.nonce
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        allBtn.disabled = false;
                        allBtn.textContent = "Refresh All Users Meta";
                        alert(data.success ? data.data : (data.data || "Unknown error"));
                    })
                    .catch(() => {
                        allBtn.disabled = false;
                        allBtn.textContent = "Refresh All Users Meta";
                        alert("Error refreshing all users meta");
                    });
                });
            }
        });
        </script>';
    return $html;
}
add_shortcode( 'transform_user_meta_button', 'user_meta_transform_button_shortcode' );


// Save transformed user meta to JSON file
function ajax_transform_user_meta_to_json() {
    check_ajax_referer( 'user_meta_nonce', 'nonce' );

    if ( !is_user_logged_in() ) {
        wp_send_json_error( 'User not logged in.' );
    }

    $user_id = get_current_user_id();
    $meta = get_user_meta( $user_id );
    $user = get_userdata($user_id);
    $username = $user ? $user->user_login : '';

    // Build $transformed in the new order
    $transformed = [
        'username'       => $username,
        'public'         => false,
        'last-updated'   => '',
        'total-watched'  => 0, // will set below
        'correct-predictions'   => '',
        'incorrect-predictions'   => '',
        'correct-prediction-rate'   => '',
        'predictions'    => [],
        'favourites'     => [],
        'watched'        => [],
    ];

    foreach ( $meta as $key => $value_array ) {
        if ( preg_match( '/^(watched|fav|predict)_(\d+)$/', $key, $matches ) ) {
            $type = $matches[1];
            $id = (int) $matches[2];

            // Only process if value is "y"
            if ( !isset($value_array[0]) || $value_array[0] !== 'y' ) {
                continue;
            }

            if ( $type === 'watched' ) {
                $film_name = '';
                $film_year = null;

                // Get the film name from the term
                $film_term = get_term( $id, 'films' );
                
                // Debugging: Check if term is valid
                if ( $film_term && ! is_wp_error( $film_term ) ) {
                    $film_name = $film_term->name;
                    $film_slug = $film_term->slug; // <-- add this line
                    // Debugging: Output term name
                    error_log("Found Film Term: " . $film_name);
                } else {
                    $film_slug = null; // fallback if not found
                    error_log("No Film Term found for ID: " . $id);
                }

                // Find a nomination that has this film term
                $nomination_query = new WP_Query([
                    'post_type' => 'nominations',
                    'posts_per_page' => 1,
                    'tax_query' => [[
                        'taxonomy' => 'films',
                        'field'    => 'term_id',
                        'terms'    => $id,
                    ]],
                    'orderby' => 'date',
                    'order' => 'ASC',
                ]);

                // Check if nominations are found
                if ( $nomination_query->have_posts() ) {
                    $nomination = $nomination_query->posts[0];
                    $film_year = (int) date( 'Y', strtotime( $nomination->post_date ) );
                    
                    // Debugging: Log nomination info
                    error_log("Found Nomination: " . $nomination->post_title . " Year: " . $film_year);
                } else {
                    error_log("No nominations found for Film Term ID: " . $id);
                }

                wp_reset_postdata();

                // Debugging: Check the data before pushing to the transformed array
                error_log("Adding to watched array: Film ID: $id, Name: $film_name, Year: $film_year");

                $film_url = ( $film_term && ! is_wp_error( $film_term ) ) ? get_term_link( $film_term ) : '';
// error_log("Film URL: " . $film_url);
                if ( !empty( $film_name ) && !empty( $film_year ) ) {
                    $transformed['watched'][] = [
                        'film-id'   => $id,
                        'film-name' => $film_name,
                        'film-year' => $film_year,
                        'film-url'  => $film_slug,
                    ];
                }
            } elseif ( $type === 'fav' ) {
                $transformed['favourites'][] = $id;
            } elseif ( $type === 'predict' ) {
                $transformed['predictions'][] = $id;
            }

            // Debugging: Log the transformed array before saving
            error_log("Transformed data: " . wp_json_encode($transformed));
        } 
    }
    $transformed['total-watched'] = count($transformed['watched']);
    // $transformed['last-updated'] = date('Y-m-d');

    $upload_dir = wp_upload_dir();
    $user_dir   = $upload_dir['basedir'] . '/user_meta';
    $file_path  = $user_dir . "/user_{$user_id}.json";

    if ( ! file_exists( $user_dir ) ) {
        wp_mkdir_p( $user_dir );
    }

    file_put_contents( $file_path, wp_json_encode( $transformed, JSON_PRETTY_PRINT ) );

    // Create predictions & favourites only JSON
    $pred_fav = [
        'username'    => $username,
        'predictions' => $transformed['predictions'],
        'favourites'  => $transformed['favourites'],
    ];
    $pred_fav_path = $user_dir . "/user_{$user_id}_pred_fav.json";
    file_put_contents( $pred_fav_path, wp_json_encode( $pred_fav, JSON_PRETTY_PRINT ) );

    $file_size = filesize( $file_path );

    wp_send_json_success([
        'message'   => 'User meta transformed and saved to JSON.',
        'file_url'  => $upload_dir['baseurl'] . "/user_meta/user_{$user_id}.json",
        'file_size' => size_format( $file_size )
    ]);
}
add_action( 'wp_ajax_transform_user_meta', 'ajax_transform_user_meta_to_json' );


// Read and analyze transformed JSON for stats
function ajax_count_user_meta_stats_from_json() {
    check_ajax_referer( 'user_meta_nonce', 'nonce' );

    if ( !is_user_logged_in() ) {
        wp_send_json_error( 'User not logged in.' );
    }

    $user_id = get_current_user_id();
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . "/user_meta/user_{$user_id}.json";

    if ( ! file_exists( $file_path ) ) {
        wp_send_json_error( 'Transformed JSON not found. Please transform first.' );
    }

    $data = json_decode( file_get_contents( $file_path ), true );

    $watched_by_year = [];
    $current_year = (int) date('Y');
    for ( $y = 1929; $y <= $current_year; $y++ ) {
        $watched_by_year[ $y ] = 0;
    }

    if ( ! empty( $data['watched'] ) ) {
        foreach ( $data['watched'] as $film_data ) {
            if ( isset( $film_data['film-year'] ) ) {
                $year = (int) $film_data['film-year'];
                if ( isset( $watched_by_year[ $year ] ) ) {
                    $watched_by_year[ $year ]++;
                }
            }
        }
    }

    $result = [
        'watchedCount'    => count( $data['watched'] ?? [] ),
        'favouritesCount' => count( $data['favourites'] ?? [] ),
        'predictionsCount'=> count( $data['predictions'] ?? [] ),
        'watchedByYear'   => $watched_by_year,
        'watched'         => $data['watched'] ?? []
    ];

    wp_send_json_success( $result );
}
add_action( 'wp_ajax_count_user_meta_stats', 'ajax_count_user_meta_stats_from_json' );



function debug_log_time($label, $start_time) {
    $end_time = microtime(true);
    $elapsed = round(($end_time - $start_time) * 1000, 2); // ms
    error_log("[$label] took {$elapsed}ms");
    return $end_time;
}

function regenerate_user_json($user_id) {
    $meta = get_user_meta( $user_id );
    $user = get_userdata($user_id);
    $username = $user ? $user->user_login : '';

    $transformed = [
        'username'       => $username,
        'public'         => false,
        'last-updated'   => '',
        'total-watched'  => 0, // will set below
        'correct-predictions'   => '',
        'incorrect-predictions'   => '',
        'correct-prediction-rate'   => '',
        'predictions'    => [],
        'favourites'     => [],
        'watched'        => [],
    ];
    foreach ( $meta as $key => $value_array ) {
        if ( preg_match( '/^(watched|fav|predict)_(\d+)$/', $key, $matches ) ) {
            $type = $matches[1];
            $id = (int) $matches[2];
            if ( !isset($value_array[0]) || $value_array[0] !== 'y' ) {
                continue;
            }
            if ( $type === 'watched' ) {
                $film_name = '';
                $film_year = null;
                $film_term = get_term( $id, 'films' );
                if ( $film_term && ! is_wp_error( $film_term ) ) {
                    $film_name = $film_term->name;
                    $film_slug = $film_term->slug;
                } else {
                    $film_slug = null;
                }
                $nomination_query = new WP_Query([
                    'post_type' => 'nominations',
                    'posts_per_page' => 1,
                    'tax_query' => [[
                        'taxonomy' => 'films',
                        'field'    => 'term_id',
                        'terms'    => $id,
                    ]],
                    'orderby' => 'date',
                    'order' => 'ASC',
                ]);
                if ( $nomination_query->have_posts() ) {
                    $nomination = $nomination_query->posts[0];
                    $film_year = (int) date( 'Y', strtotime( $nomination->post_date ) );
                }
                wp_reset_postdata();
                if ( !empty( $film_name ) && !empty( $film_year ) ) {
                    $transformed['watched'][] = [
                        'film-id'   => $id,
                        'film-name' => $film_name,
                        'film-year' => $film_year,
                        'film-url'  => $film_slug,
                    ];
                }
            } elseif ( $type === 'fav' ) {
                $transformed['favourites'][] = $id;
            } elseif ( $type === 'predict' ) {
                $transformed['predictions'][] = $id;
            }
        }
    }
    $transformed['total-watched'] = count($transformed['watched']);
    // $transformed['last-updated'] = date('Y-m-d');

    $upload_dir = wp_upload_dir();
    $user_dir   = $upload_dir['basedir'] . '/user_meta';
    $file_path  = $user_dir . "/user_{$user_id}.json";

    if ( ! file_exists( $user_dir ) ) {
        wp_mkdir_p( $user_dir );
    }

    file_put_contents( $file_path, wp_json_encode( $transformed, JSON_PRETTY_PRINT ) );

    // Create predictions & favourites only JSON
    $pred_fav = [
        'username'    => $username,
        'predictions' => $transformed['predictions'],
        'favourites'  => $transformed['favourites'],
    ];
    $pred_fav_path = $user_dir . "/user_{$user_id}_pred_fav.json";
    file_put_contents( $pred_fav_path, wp_json_encode( $pred_fav, JSON_PRETTY_PRINT ) );
}

function ajax_refresh_all_users_meta() {
    check_ajax_referer( 'user_meta_nonce', 'nonce' );
    $users = get_users([ 'fields' => 'ID' ]);
    foreach ($users as $user_id) {
        regenerate_user_json($user_id);
    }
    wp_send_json_success('All user meta JSON files have been refreshed.');
}
add_action('wp_ajax_refresh_all_users_meta', 'ajax_refresh_all_users_meta');
