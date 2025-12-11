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
            'watchlist' => [],
            'favourite-categories' => [],
            'hidden-categories' => [],
            'public' => false,
            'username' => $username,
            'total-watched' => 0,
            'last-updated' => date('Y-m-d'),
            'this_page_only' => false,
            'auto_remove_watched' => true,
            'compact_view' => false,
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
        // New format: array of objects with film-id, order, film-name, film-year, film-url
        $already_in = false;
        foreach ($json['watchlist'] as $item) {
            if (isset($item['film-id']) && intval($item['film-id']) === $film_id) {
                $already_in = true;
                break;
            }
        }
        if (!$already_in) {
            $order = count($json['watchlist']) + 1;
            
            // Fetch film details from the post/term
            $film_term = get_term($film_id, 'films');
            $film_name = '';
            $film_url = '';
            
            if ($film_term && !is_wp_error($film_term)) {
                $film_name = $film_term->name;
                $film_url = $film_term->slug;
            }
            
            $json['watchlist'][] = [ 
                'film-id' => $film_id, 
                'order' => $order,
                'film-name' => $film_name,
                'film-url' => $film_url
            ];
            $changed = true;
        }
    } elseif ( $action_type === 'remove' ) {
        $before = count($json['watchlist']);
        $json['watchlist'] = array_values(array_filter($json['watchlist'], function($item) use ($film_id) {
            return !(isset($item['film-id']) && intval($item['film-id']) === $film_id);
        }));
        if ( count($json['watchlist']) < $before ) {
            // Reindex order fields
            $json['watchlist'] = array_values($json['watchlist']);
            foreach ($json['watchlist'] as $i => &$item) {
                $item['order'] = $i + 1;
            }
            unset($item);
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

    // Initialize data with defaults
    $data = null;
    if ( file_exists( $file_path ) ) {
        $data = json_decode( file_get_contents( $file_path ), true );
    }

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
    // COMMENTED OUT - Classes now added by JavaScript for caching compatibility
    // $settings_classes = [];
    // foreach ( $settings as $key => $meta ) {
    //     if ( ! isset( $data[ $key ] ) ) {
    //         $data[ $key ] = $meta['default']; // apply default if missing
    //     }
    //     if ( $data[ $key ] ) {
    //         $settings_classes[] = $key; // add active setting as a class
    //     }
    // }

    // Join classes into string
    // $settings_class_str = ! empty( $settings_classes ) ? ' ' . implode( ' ', $settings_classes ) : '';

    // Classes are now added by JavaScript after loading user data from cache
    $output  = '<div class="watchlist-cntr">';
    $output .= '<h2>Your Watchlist</h2>';

    // Settings UI
    $output .= '<details class="watchlist-settings"><summary>';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M259.1 73.5C262.1 58.7 275.2 48 290.4 48L350.2 48C365.4 48 378.5 58.7 381.5 73.5L396 143.5C410.1 149.5 423.3 157.2 435.3 166.3L503.1 143.8C517.5 139 533.3 145 540.9 158.2L570.8 210C578.4 223.2 575.7 239.8 564.3 249.9L511 297.3C511.9 304.7 512.3 312.3 512.3 320C512.3 327.7 511.8 335.3 511 342.7L564.4 390.2C575.8 400.3 578.4 417 570.9 430.1L541 481.9C533.4 495 517.6 501.1 503.2 496.3L435.4 473.8C423.3 482.9 410.1 490.5 396.1 496.6L381.7 566.5C378.6 581.4 365.5 592 350.4 592L290.6 592C275.4 592 262.3 581.3 259.3 566.5L244.9 496.6C230.8 490.6 217.7 482.9 205.6 473.8L137.5 496.3C123.1 501.1 107.3 495.1 99.7 481.9L69.8 430.1C62.2 416.9 64.9 400.3 76.3 390.2L129.7 342.7C128.8 335.3 128.4 327.7 128.4 320C128.4 312.3 128.9 304.7 129.7 297.3L76.3 249.8C64.9 239.7 62.3 223 69.8 209.9L99.7 158.1C107.3 144.9 123.1 138.9 137.5 143.7L205.3 166.2C217.4 157.1 230.6 149.5 244.6 143.4L259.1 73.5zM320.3 400C364.5 399.8 400.2 363.9 400 319.7C399.8 275.5 363.9 239.8 319.7 240C275.5 240.2 239.8 276.1 240 320.3C240.2 364.5 276.1 400.2 320.3 400z"/></svg>';
    $output .= '</summary><h3>Watchlist Settings</h3><ul>';
    foreach ( $settings as $key => $meta ) {
        // Use default value if data doesn't exist or key is not set
        $is_checked = ( $data && isset( $data[ $key ] ) ) ? $data[ $key ] : $meta['default'];
        $checked = $is_checked ? 'checked' : '';
        $output .= '<li>
            <label>
                <input type="checkbox" id="check-' . esc_attr($key) . '" class="watchlist-setting-toggle" 
                       data-setting="' . esc_attr($key) . '" ' . $checked . '> 
                ' . esc_html($meta['label']) . '
            </label>
        </li>';
    }
    $output .= '</ul></details>';

    $output .= '<div class="watchlist-setting-labels">';

    $output .= '<label id="label-auto_remove_watched" title="Auto Remove" for="check-auto_remove_watched">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M160 96C124.7 96 96 124.7 96 160L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 160C544 124.7 515.3 96 480 96L160 96zM232 296L408 296C421.3 296 432 306.7 432 320C432 333.3 421.3 344 408 344L232 344C218.7 344 208 333.3 208 320C208 306.7 218.7 296 232 296z"/></svg>';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M160 144C151.2 144 144 151.2 144 160L144 480C144 488.8 151.2 496 160 496L480 496C488.8 496 496 488.8 496 480L496 160C496 151.2 488.8 144 480 144L160 144zM96 160C96 124.7 124.7 96 160 96L480 96C515.3 96 544 124.7 544 160L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 160zM232 296L408 296C421.3 296 432 306.7 432 320C432 333.3 421.3 344 408 344L232 344C218.7 344 208 333.3 208 320C208 306.7 218.7 296 232 296z"/></svg>';
    $output .= '</label>';

    $output .= '<label id="label-this_page_only" title="Show..." for="check-this_page_only">';
    $output .= '<span>This Page Only</span>';
    $output .= '<span>All Films</span>';
    $output .= '</label>';

    $output .= '<label id="label-compact_view" title="Compact View" for="check-compact_view">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M503.5 71C512.9 61.6 528.1 61.6 537.4 71L569.4 103C578.8 112.4 578.8 127.6 569.4 136.9L482.4 223.9L521.4 262.9C528.3 269.8 530.3 280.1 526.6 289.1C522.9 298.1 514.2 304 504.5 304L360.5 304C347.2 304 336.5 293.3 336.5 280L336.5 136C336.5 126.3 342.3 117.5 351.3 113.8C360.3 110.1 370.6 112.1 377.5 119L416.5 158L503.5 71zM136.5 336L280.5 336C293.8 336 304.5 346.7 304.5 360L304.5 504C304.5 513.7 298.7 522.5 289.7 526.2C280.7 529.9 270.4 527.9 263.5 521L224.5 482L137.5 569C128.1 578.4 112.9 578.4 103.6 569L71.6 537C62.2 527.6 62.2 512.4 71.6 503.1L158.6 416.1L119.6 377.1C112.7 370.2 110.7 359.9 114.4 350.9C118.1 341.9 126.8 336 136.5 336z"/></svg>';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M408 64L552 64C565.3 64 576 74.7 576 88L576 232C576 241.7 570.2 250.5 561.2 254.2C552.2 257.9 541.9 255.9 535 249L496 210L409 297C399.6 306.4 384.4 306.4 375.1 297L343.1 265C333.7 255.6 333.7 240.4 343.1 231.1L430.1 144.1L391.1 105.1C384.2 98.2 382.2 87.9 385.9 78.9C389.6 69.9 398.3 64 408 64zM232 576L88 576C74.7 576 64 565.3 64 552L64 408C64 398.3 69.8 389.5 78.8 385.8C87.8 382.1 98.1 384.2 105 391L144 430L231 343C240.4 333.6 255.6 333.6 264.9 343L296.9 375C306.3 384.4 306.3 399.6 296.9 408.9L209.9 495.9L248.9 534.9C255.8 541.8 257.8 552.1 254.1 561.1C250.4 570.1 241.7 576 232 576z"/></svg>';
    $output .= '</label>';

    $output .= '</div>';
    

    // JavaScript will populate the watchlist dynamically
    $output .= '<!-- Watchlist items will be populated by JavaScript --></div>';
    
    // // If no watchlist, show empty message
    // if ( empty( $data['watchlist'] ) || ! is_array( $data['watchlist'] ) ) {
    //     $output .= '<p class="empty-message">Your watchlist is empty.</p></div>';
    //     return $output;
    // }

    // // Build array of watched IDs for lookup
    // $watched_ids = [];
    // if ( ! empty( $data['watched'] ) && is_array( $data['watched'] ) ) {
    //     foreach ( $data['watched'] as $watched_item ) {
    //         if ( isset( $watched_item['film-id'] ) ) {
    //             $watched_ids[] = (int) $watched_item['film-id'];
    //         }
    //     }
    // }

    // $output .= '<ul class="watchlist">';

    // foreach ( $data['watchlist'] as $watchlist_item ) {
    //     // New format: $watchlist_item is an array with 'film-id' and 'order'
    //     $film_id = isset($watchlist_item['film-id']) ? (int)$watchlist_item['film-id'] : null;
    //     $order = isset($watchlist_item['order']) && $watchlist_item['order'] !== null ? (int)$watchlist_item['order'] : '';
    //     if (!$film_id) continue;
    //     $film_term = get_term( $film_id, 'films' );
    //     if ( ! $film_term || is_wp_error( $film_term ) ) continue;

    //     $poster = get_field( 'poster', 'films_' . $film_id );
    //     $poster_html = $poster ? '<img src="' . esc_url( $poster ) . '" alt="' . esc_attr( $film_term->name ) . '">' : '';

    //     $is_watched = in_array( (int) $film_id, $watched_ids, true );

    //     $svg_icon_check = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M528 320C528 205.1 434.9 112 320 112C205.1 112 112 205.1 112 320C112 434.9 205.1 528 320 528C434.9 528 528 434.9 528 320zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320z"/></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M320 112C434.9 112 528 205.1 528 320C528 434.9 434.9 528 320 528C205.1 528 112 434.9 112 320C112 205.1 205.1 112 320 112zM320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM404.4 276.7C411.4 265.5 408 250.7 396.8 243.6C385.6 236.5 370.8 240 363.7 251.2L302.3 349.5L275.3 313.5C267.3 302.9 252.3 300.7 241.7 308.7C231.1 316.7 228.9 331.7 236.9 342.3L284.9 406.3C289.6 412.6 297.2 416.2 305.1 415.9C313 415.6 320.2 411.4 324.4 404.6L404.4 276.6z"/></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM404.4 276.7L324.4 404.7C320.2 411.4 313 415.6 305.1 416C297.2 416.4 289.6 412.8 284.9 406.4L236.9 342.4C228.9 331.8 231.1 316.8 241.7 308.8C252.3 300.8 267.3 303 275.3 313.6L302.3 349.6L363.7 251.3C370.7 240.1 385.5 236.6 396.8 243.7C408.1 250.8 411.5 265.5 404.4 276.8z"/></svg>';
    //     $svg_icon_remove = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"/></svg>';

    //     if ( $is_watched ) {
    //         $buttons  = '<button title="Watched" class="mark-as-unwatched-button" data-film-id="' . esc_attr( $film_id ) . '" data-action="unwatched">' . $svg_icon_check . '</button>';
    //     } else {
    //         $buttons  = '<button title="Watched" class="mark-as-watched-button" data-film-id="' . esc_attr( $film_id ) . '" data-action="watched">' . $svg_icon_check . '</button>';
    //     }

    //     $buttons .= '<button title="Remove from Watchlist" class="remove-from-watchlist-button" data-film-id="' . esc_attr( $film_id ) . '" data-action="remove">' . $svg_icon_remove . '</button>';

    //     $li_style = $order !== '' ? ' style="--order: ' . esc_attr($order) . ';"' : '';
    //     $output .= '<li class="' . ( $is_watched ? 'watched' : 'unwatched' ) . '" data-film-id="' . esc_attr( $film_id ) . '"' . $li_style . '>';
    //     $output .= $poster_html;
    //     $term_link = get_term_link( $film_term );
    //     if ( is_wp_error( $term_link ) ) {
    //         $term_link = '#';
    //     }
    //     $output .= '<a class="film-title" href="' . esc_url( $term_link ) . '">' . esc_html( $film_term->name ) . '</a>';
    //     $output .= $buttons;
    //     $output .= '</li>';
    // }

    // $output .= '</ul></div>';
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

add_action('wp_ajax_oscars_reorder_watchlist', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in.');
    }
    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    if (!file_exists($file_path)) {
        wp_send_json_error('User data not found.');
    }
    $json = json_decode(file_get_contents($file_path), true);
    if (!is_array($json) || !isset($json['watchlist']) || !is_array($json['watchlist'])) {
        wp_send_json_error('Invalid user data.');
    }
    $new_order = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : [];
    if (empty($new_order)) {
        wp_send_json_error('No order provided.');
    }
    // Reorder watchlist based on new_order (array of film-ids)
    $film_map = [];
    foreach ($json['watchlist'] as $item) {
        if (isset($item['film-id'])) {
            $film_map[$item['film-id']] = $item;
        }
    }
    $reordered = [];
    foreach ($new_order as $i => $film_id) {
        $film_id = (int)$film_id;
        if (isset($film_map[$film_id])) {
            $item = $film_map[$film_id];
            $item['order'] = $i + 1;
            $reordered[] = $item;
        }
    }
    $json['watchlist'] = $reordered;
    $json['last-updated'] = date('Y-m-d');
    file_put_contents($file_path, wp_json_encode($json, JSON_PRETTY_PRINT));
    wp_send_json_success(['watchlist' => $json['watchlist']]);
});