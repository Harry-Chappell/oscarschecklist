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
    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';    if (!$film_id) {
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
    if ( $action_type === 'add' ) {
        if ( ! in_array( $film_id, $json['watchlist'], true ) ) {
            $json['watchlist'][] = $film_id;
            $changed = true;
        }
    } elseif ( $action_type === 'remove' ) {
        $before = count($json['watchlist']);
        $json['watchlist'] = array_values(array_filter($json['watchlist'], function($id) use ($film_id) {
            return $id != $film_id;
        }));
        if ( count($json['watchlist']) < $before ) {
            $changed = true;
        }
    }
    $json['last-updated'] = date('Y-m-d');
    if ($changed) {
        file_put_contents($file_path, wp_json_encode($json, JSON_PRETTY_PRINT));
    }
    wp_send_json_success(['watchlist' => $json['watchlist']]);
});





function oscars_watchlist_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>Please log in to view your watchlist.</p>';
    }

    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";

    if ( ! file_exists( $file_path ) ) {
        return '<p>No user data found.</p>';
    }

    $data = json_decode( file_get_contents( $file_path ), true );

    if ( ! $data || ! isset( $data['watchlist'] ) || ! is_array( $data['watchlist'] ) || empty( $data['watchlist'] ) ) {
        return '<p>Your watchlist is empty.</p>';
    }

    // Build array of watched film IDs for quick lookup
    $watched_ids = [];
    if ( isset( $data['watched'] ) && is_array( $data['watched'] ) ) {
        foreach ( $data['watched'] as $watched_item ) {
            if ( isset( $watched_item['film-id'] ) ) {
                $watched_ids[] = (int) $watched_item['film-id'];
            }
        }
    }

    // SVG icon markup
    $svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"></path></svg>';

    $output  = '<div class="watchlist-cntr">';
    $output .= '<h2>Your Watchlist</h2>';
    $output .= '<ul class="watchlist">';

    foreach ( $data['watchlist'] as $film_id ) {
        $film_term = get_term( $film_id, 'films' );

        if ( ! $film_term || is_wp_error( $film_term ) ) {
            continue;
        }

        // Get ACF poster
        $poster = get_field( 'poster', 'films_' . $film_id );
        $poster_html = $poster ? '<img src="' . esc_url( $poster ) . '" alt="' . esc_attr( $film_term->name ) . '">' : '';

        // Watched status
        $is_watched = in_array( (int) $film_id, $watched_ids, true );

        // SVG icons
        $svg_icon_check = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"></path></svg>';
        $svg_icon_remove = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M312 375c9.4-9.4 9.4-24.6 0-33.9L201 230.1 312 119c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0L167 196.1 56.9 85c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9L133.1 230.1 23 341.1c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0L167 264.1l110.1 110.1c9.4 9.4 24.6 9.4 33.9 0z"/></svg>';

        if ( $is_watched ) {
            $buttons  = '<button title="Watched" class="mark-as-unwatched-button" data-film-id="' . esc_attr( $film_id ) . '" data-action="unwatched">' . $svg_icon_check . '</button>';
        } else {
            $buttons  = '<button title="Watched" class="mark-as-watched-button" data-film-id="' . esc_attr( $film_id ) . '" data-action="watched">' . $svg_icon_check . '</button>';
        }

        // Remove button
        $buttons .= '<button title="Remove from Watchlist" class="remove-from-watchlist-button" data-film-id="' . esc_attr( $film_id ) . '" data-action="remove">' . $svg_icon_remove . '</button>';

        $output .= '<li class="' . ( $is_watched ? 'watched' : 'unwatched' ) . '" data-film-id="' . esc_attr( $film_id ) . '">';
        $output .= $poster_html;
        $output .= '<span class="film-title">' . esc_html( $film_term->name ) . '</span>';
        $output .= $buttons;
        $output .= '</li>';
    }

    $output .= '</ul></div>';

    return $output;
}
add_shortcode( 'watchlist', 'oscars_watchlist_shortcode' );