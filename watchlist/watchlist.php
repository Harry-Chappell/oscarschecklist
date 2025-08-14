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

    $user_id   = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";

    if ( ! file_exists( $file_path ) ) {
        return '<p>No user data found.</p>';
    }

    $data = json_decode( file_get_contents( $file_path ), true );

    // Define settings and their default states
    $settings = [
        'auto_remove_watched' => [
            'label'   => 'Automatically remove watched films',
            'default' => true,
        ],
        'this_page_only' => [
            'label'   => 'Show films from this page only',
            'default' => false,
        ],
        'compact_view' => [
            'label'   => 'Compact view',
            'default' => false,
        ],
    ];

    // Build list of classes from active settings
    $settings_classes = [];
    foreach ( $settings as $key => $meta ) {
        if ( ! isset( $data[ $key ] ) ) {
            $data[ $key ] = $meta['default']; // apply default if missing
        }
        if ( $data[ $key ] ) {
            $settings_classes[] = $key; // add active setting as a class
        }
    }

    // Join classes into string
    $settings_class_str = ! empty( $settings_classes ) ? ' ' . implode( ' ', $settings_classes ) : '';

    $output  = '<div class="watchlist-cntr' . esc_attr( $settings_class_str ) . '">';
    $output .= '<h2>Your Watchlist</h2>';

    // Settings UI
    $output .= '<div class="watchlist-settings"><h3>Settings</h3><ul>';
    foreach ( $settings as $key => $meta ) {
        $checked = $data[ $key ] ? 'checked' : '';
        $output .= '<li>
            <label>
                <input type="checkbox" class="watchlist-setting-toggle" 
                       data-setting="' . esc_attr($key) . '" ' . $checked . '> 
                ' . esc_html($meta['label']) . '
            </label>
        </li>';
    }
    $output .= '</ul></div>';

    // If no watchlist, show empty message
    if ( empty( $data['watchlist'] ) || ! is_array( $data['watchlist'] ) ) {
        $output .= '<p>Your watchlist is empty.</p></div>';
        return $output;
    }

    // Build array of watched IDs for lookup
    $watched_ids = [];
    if ( ! empty( $data['watched'] ) && is_array( $data['watched'] ) ) {
        foreach ( $data['watched'] as $watched_item ) {
            if ( isset( $watched_item['film-id'] ) ) {
                $watched_ids[] = (int) $watched_item['film-id'];
            }
        }
    }

    $output .= '<ul class="watchlist">';

    foreach ( $data['watchlist'] as $film_id ) {
        $film_term = get_term( $film_id, 'films' );
        if ( ! $film_term || is_wp_error( $film_term ) ) continue;

        $poster = get_field( 'poster', 'films_' . $film_id );
        $poster_html = $poster ? '<img src="' . esc_url( $poster ) . '" alt="' . esc_attr( $film_term->name ) . '">' : '';

        $is_watched = in_array( (int) $film_id, $watched_ids, true );

        $svg_icon_check = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"></path></svg>';
        $svg_icon_remove = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M96 320C96 302.3 110.3 288 128 288L512 288C529.7 288 544 302.3 544 320C544 337.7 529.7 352 512 352L128 352C110.3 352 96 337.7 96 320z"></path></svg>';

        if ( $is_watched ) {
            $buttons  = '<button title="Watched" class="mark-as-unwatched-button" data-film-id="' . esc_attr( $film_id ) . '" data-action="unwatched">' . $svg_icon_check . '</button>';
        } else {
            $buttons  = '<button title="Watched" class="mark-as-watched-button" data-film-id="' . esc_attr( $film_id ) . '" data-action="watched">' . $svg_icon_check . '</button>';
        }

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

add_action('wp_ajax_oscars_update_setting', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in.');
    }

    $user_id = get_current_user_id();
    $setting = isset($_POST['setting']) ? sanitize_text_field($_POST['setting']) : '';
    $value   = isset($_POST['value']) ? filter_var($_POST['value'], FILTER_VALIDATE_BOOLEAN) : false;

    if (!$setting) {
        wp_send_json_error('No setting provided.');
    }

    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    if (!file_exists($file_path)) {
        wp_send_json_error('User data not found.');
    }

    $json = json_decode(file_get_contents($file_path), true);
    if (!is_array($json)) $json = [];

    $json[$setting] = $value;
    $json['last-updated'] = date('Y-m-d');

    file_put_contents($file_path, wp_json_encode($json, JSON_PRETTY_PRINT));

    wp_send_json_success(['setting' => $setting, 'value' => $value]);
});