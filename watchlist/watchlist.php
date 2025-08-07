<?php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'oscars-watchlist',
        get_stylesheet_directory_uri() . '/watchlist/watchlist.js',
        array('jquery'),
        filemtime(get_stylesheet_directory() . '/watchlist/watchlist.js'),
        true
    );
});

add_action('wp_ajax_oscars_update_watchlist', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in.');
    }
    $user_id = get_current_user_id();
    $film_id = isset($_POST['film_id']) ? intval($_POST['film_id']) : 0;
    $add = isset($_POST['add']) ? intval($_POST['add']) : 0;
    if (!$film_id) {
        wp_send_json_error('No film ID.');
    }

    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . "/user_meta/user_{$user_id}.json";
    if (!file_exists($file_path)) {
        // Create a new file if missing
        $user = get_userdata($user_id);
        $username = $user ? $user->user_login : '';
        $json = [
            'watched' => [],
            'favourites' => [],
            'predictions' => [],
            'watchlist' => [],
            'favourite-categories' => [],
            'hidden-categories' => [],
            'correct-predictions' => "",
            'incorrect-predictions' => "",
            'correct-prediction-rate' => "",
            'public' => false,
            'username' => $username,
            'total-watched' => 0,
            'last-updated' => date('Y-m-d'),
        ];
    } else {
        $json = json_decode(file_get_contents($file_path), true);
        if (!is_array($json)) $json = [];
        if (!isset($json['watchlist']) || !is_array($json['watchlist'])) {
            $json['watchlist'] = [];
        }
    }

    $changed = false;
    if ($add) {
        if (!in_array($film_id, $json['watchlist'])) {
            $json['watchlist'][] = $film_id;
            $changed = true;
        }
    } else {
        $before = count($json['watchlist']);
        $json['watchlist'] = array_values(array_filter($json['watchlist'], function($id) use ($film_id) {
            return $id != $film_id;
        }));
        if (count($json['watchlist']) < $before) {
            $changed = true;
        }
    }
    $json['last-updated'] = date('Y-m-d');
    if ($changed) {
        file_put_contents($file_path, wp_json_encode($json, JSON_PRETTY_PRINT));
    }
    wp_send_json_success(['watchlist' => $json['watchlist']]);
});


/**
 * Shortcode: watchlist
 * Outputs the user's watchlist.
 */
function oscars_watchlist_shortcode() {
    if ( current_user_can( 'administrator' ) ) {
        $output = '<div class="watchlist-cntr">';
        $output .= '<h2>Your Watchlist</h2>';
        if (!is_user_logged_in()) {
            $output .= '<p>Please log in to view your watchlist.</p>';
        } else {
            $output .= '<p>Welcome to your watchlist.</p>';
            $user_id = get_current_user_id();
            $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
            if (!file_exists($file_path)) { '<p>No user data found.</p>'; }
            $data = json_decode(file_get_contents($file_path), true);
            if (!$data || !isset($data['watchlist']) || !is_array($data['watchlist'])) {
                $output .= '<p>Your watchlist is empty.</p>';
            }
            $watchlist = $data['watchlist'];
            if (empty($watchlist)) {
                $output .= '<p>Your watchlist is empty</p>';
            } else {
                $output .= '<p>Your watch list is not empty.</p>';
                $output .= '<ul class="watchlist">';
                foreach ($watchlist as $film_id) {
                    $film = get_post($film_id);
                    if ($film) {
                        $output .= '<li>';
                        $output .= '<span class="film-title">' . esc_html($film->post_title) . '</span>';
                        $output .= '<button class="mark-as-watched-button" data-film-id="' . esc_attr($film_id) . '">Mark as Watched</button>';
                        $output .= '<button class="mark-as-unwatched-button" data-film-id="' . esc_attr($film_id) . '">Mark as Unwatched</button>';
                        $output .= '</li>';
                    }
                }
                $output .= '</ul>';
            }
        }
        // print_r($watchlist);
        $output .= '</div>';
        return $output;
    }
}
add_shortcode('watchlist', 'oscars_watchlist_shortcode');