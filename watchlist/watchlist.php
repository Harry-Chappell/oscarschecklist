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
    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
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
    if ( $action_type === 'add' ) {
        // New format: array of objects with film-id and order (order is null for now)
        $already_in = false;
        foreach ($json['watchlist'] as $item) {
            if (isset($item['film-id']) && intval($item['film-id']) === $film_id) {
                $already_in = true;
                break;
            }
        }
        if (!$already_in) {
            $json['watchlist'][] = [ 'film-id' => $film_id, 'order' => null ];
            $changed = true;
        }
    } elseif ( $action_type === 'remove' ) {
        $before = count($json['watchlist']);
        $json['watchlist'] = array_values(array_filter($json['watchlist'], function($item) use ($film_id) {
            return !(isset($item['film-id']) && intval($item['film-id']) === $film_id);
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
            'default' => true,
        ],
        'compact_view' => [
            'label'   => 'Compact view',
            'default' => true,
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
    $output .= '<details class="watchlist-settings"><summary>';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M259.1 73.5C262.1 58.7 275.2 48 290.4 48L350.2 48C365.4 48 378.5 58.7 381.5 73.5L396 143.5C410.1 149.5 423.3 157.2 435.3 166.3L503.1 143.8C517.5 139 533.3 145 540.9 158.2L570.8 210C578.4 223.2 575.7 239.8 564.3 249.9L511 297.3C511.9 304.7 512.3 312.3 512.3 320C512.3 327.7 511.8 335.3 511 342.7L564.4 390.2C575.8 400.3 578.4 417 570.9 430.1L541 481.9C533.4 495 517.6 501.1 503.2 496.3L435.4 473.8C423.3 482.9 410.1 490.5 396.1 496.6L381.7 566.5C378.6 581.4 365.5 592 350.4 592L290.6 592C275.4 592 262.3 581.3 259.3 566.5L244.9 496.6C230.8 490.6 217.7 482.9 205.6 473.8L137.5 496.3C123.1 501.1 107.3 495.1 99.7 481.9L69.8 430.1C62.2 416.9 64.9 400.3 76.3 390.2L129.7 342.7C128.8 335.3 128.4 327.7 128.4 320C128.4 312.3 128.9 304.7 129.7 297.3L76.3 249.8C64.9 239.7 62.3 223 69.8 209.9L99.7 158.1C107.3 144.9 123.1 138.9 137.5 143.7L205.3 166.2C217.4 157.1 230.6 149.5 244.6 143.4L259.1 73.5zM320.3 400C364.5 399.8 400.2 363.9 400 319.7C399.8 275.5 363.9 239.8 319.7 240C275.5 240.2 239.8 276.1 240 320.3C240.2 364.5 276.1 400.2 320.3 400z"/></svg>';
    $output .= '</summary><h3>Watchlist Settings</h3><ul>';
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
    $output .= '</ul></details>';

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

    foreach ( $data['watchlist'] as $watchlist_item ) {
        // New format: $watchlist_item is an array with 'film-id' and 'order'
        $film_id = isset($watchlist_item['film-id']) ? (int)$watchlist_item['film-id'] : null;
        if (!$film_id) continue;
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