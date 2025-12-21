<?php 


function show_nominations_by_cat_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'category' => '', // Award category slug
        ),
        $atts,
        'show_nominations' // Add this third parameter to specify the shortcode
    );

    $has_winner = '';
    $category_slug = $atts['category'];
    global $post;


    // Get current user's friends
    $friend_ids = friends_get_friend_user_ids(bp_loggedin_user_id());

    // Initialize an empty array to store watched films by friends
    $friend_watched_films = array();

    // Loop through each friend's ID
    foreach ($friend_ids as $friend_id) {
        $json_path = ABSPATH . 'wp-content/uploads/user_meta/user_' . $friend_id . '.json';
        $friend_watched = false;
        if (file_exists($json_path)) {
            $json_data = file_get_contents($json_path);
            $user_meta = json_decode($json_data, true);
            if (isset($user_meta['watched']) && is_array($user_meta['watched'])) {
                foreach ($user_meta['watched'] as $watched_film) {
                    if (isset($watched_film['film-id']) && $watched_film['film-id'] == $post->ID) {
                        $friend_watched = true;
                        break;
                    }
                }
            }
        }
        if ($friend_watched) {
            $friend_watched_films[$friend_id] = $post->ID;
        }
    }

    // Now $friend_watched_films array contains watched films by each friend

    

    // Initialize the output
    $output = '';

    // Query for nominations grouped by year
    $args = array(
        'post_type' => 'nominations',
        'posts_per_page' => -1, // Get all posts
        'order' => 'DESC',
        'orderby' => 'date',
        'tax_query' => array(
            array(
                'taxonomy' => 'award-categories',
                'field' => 'slug',
                'terms' => $category_slug,
            ),
        ),
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {

        $is_song = false;
        // Decide how to display certain categories                
        if ($category_slug == "actor-in-a-leading-role"
            || $category_slug == "actor-in-a-supporting-role"
            || $category_slug == "actress-in-a-leading-role"
            || $category_slug == "actress-in-a-supporting-role"
        ) {
            $nominee_visibility = "prominent";
        } elseif ($category_slug == "directing"
            || $category_slug == "music-original-song"
            || $category_slug == "music-original-score"
        ) {
            $nominee_visibility = "shown";
            // } elseif ($category_slug == "winner") {
                // continue;
        } else {
            $nominee_visibility = "hidden";
        }
            
        if ($category_slug == "music-original-song") {
            $is_song = true;
        }

        $current_year = '';

        while ($query->have_posts()) {
            $query->the_post();
            $post_year = get_the_date('Y');

            // Start a new year section if the year has changed
            if ($post_year !== $current_year) {
                if ($current_year !== '') {
                    $output .= '</ul></div>'; // Close the previous year's list
                }

                $current_year = $post_year;
                // $output .= '<h2>' . $current_year . '</h2>';
                // $output .= '<ul class=" nominee_visibility-' . $nominee_visibility  . '">';

                $output .= '<div class="awards-category category-' . $category_slug . '">';
                $output .= '<div class="category-title"><h2>' . $current_year . '</h2>';
                $output .= '<a class="category-link" title="' . $current_year . '" href="https://oscarschecklist.com/nominations-' . $current_year . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M502.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l370.7 0-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l128-128z"/></svg></a>';
                $output .= '<div class="circular-progress-container"><div class="circular-progress"></div><span class="progress"></span><span class="total"></span></div></div>';
                $output .= '<ul class="nominations-list year-' . $current_year . ' nominee_visibility-' . $nominee_visibility . '">';
            }

            // Output the nomination title
            // $output .= '<li class="">' . get_the_title() . '</li>';
            $films = get_the_terms(get_the_ID(), 'films');
                if (is_array($films) || is_object($films)) {
                        foreach ($films as $film) {
                            $nominees = get_the_terms(get_the_ID(), 'nominees');
                            $categories = get_the_terms(get_the_ID(), 'award-categories');



                            if (is_user_logged_in()) {
                                $nomination_id = get_the_ID();
                                $user_id = get_current_user_id();
                                $pred_fav_path = ABSPATH . 'wp-content/uploads/user_meta/user_' . $user_id . '_pred_fav.json';
                                $user_fav = false;
                                $user_predict = false;
                                if (file_exists($pred_fav_path)) {
                                    $pred_fav_data = file_get_contents($pred_fav_path);
                                    $pred_fav_meta = json_decode($pred_fav_data, true);
                                    // Favourites
                                    if (isset($pred_fav_meta['favourites']) && is_array($pred_fav_meta['favourites'])) {
                                        $user_fav = in_array($nomination_id, $pred_fav_meta['favourites']);
                                    }
                                    // Predictions
                                    if (isset($pred_fav_meta['predictions']) && is_array($pred_fav_meta['predictions'])) {
                                        $user_predict = in_array($nomination_id, $pred_fav_meta['predictions']);
                                    }
                                }
                            }
                            if (is_user_logged_in()) {
                                $post_id = $film->term_id;
                                $user_id = get_current_user_id();
                                $json_path = ABSPATH . 'wp-content/uploads/user_meta/user_' . $user_id . '.json';
                                $user_watched = false;
                                if (file_exists($json_path)) {
                                    $json_data = file_get_contents($json_path);
                                    $user_meta = json_decode($json_data, true);
                                    if (isset($user_meta['watched']) && is_array($user_meta['watched'])) {
                                        foreach ($user_meta['watched'] as $watched_film) {
                                            if (isset($watched_film['film-id']) && $watched_film['film-id'] == $post_id) {
                                                $user_watched = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            $winner = '';
                            if ($categories && !is_wp_error($categories)) {
                                foreach ($categories as $category) {
                                    if ( $category->slug == "winner" ) {
                                        $winner = "winner";
                                    }
                                }
                            }

                            // Get the post ID
                            $nomination_id = get_the_ID();

                            $output .= '<li id="nomination-' . esc_attr($nomination_id) . '" class="' . $winner . ' ';
                            // if (is_user_logged_in() && $user_watched) {
                            //     $output .= 'watched ';
                            // }
                            // if (is_user_logged_in() && $user_fav) {
                            //     $output .= 'fav ';
                            // }
                            // if (is_user_logged_in() && $user_predict) {
                            //     $output .= 'predict ';
                            // }
                            // Watchlist class is added by JavaScript to avoid caching issues
                            // $output .= do_shortcode('[esi watched ttl="3" film-id="' . $film->term_id . '"]');

                            $output .= ' film-id-' . $film->term_id . ' ';
                            if ($category_slug == "winner") {
                                $output .= 'winner ';
                                // echo apply_filters( 'litespeed_esi_url', 'my_esi_block', 'Custom ESI block' );
                            }
                            $output .= '" data-film-id="' . $film->term_id . '">';

                            if ($is_song == true) {
                                $output .= '<h3 class="song-name">' . get_the_title() . '</h3>';
                            }

                            if ($nominee_visibility == "prominent") {
                                $output .= '<a class="film-name" href="' . get_term_link($film) . '"><p>' . $film->name . '</p></a>';
                            } elseif ($is_song == true) {
                                $output .= '<a class="film-name" href="' . get_term_link($film) . '"><h4>' . $film->name . '</h4></a>';
                            } else {
                                $output .= '<a class="film-name" href="' . get_term_link($film) . '"><h3>' . $film->name . '</h3></a>';
                            }
                            $output .= '<span class="film-poster">';
                                $poster = get_field('poster', "films_" . $film->term_id);

                                if (is_array($poster) && isset($poster['id'])) {
                                    $output .= wp_get_attachment_image($poster['id'], 'medium');
                                // } else {
                                    // Optionally handle the case where there's no valid poster
                                    // For example, you might set a default image or leave $output unchanged
                                    // $output .= '<img src="' . get_template_directory_uri() . '/images/default-poster.jpg" alt="Default Poster">';
                                }    
                                if ($poster) {
                                    $output .= '<img src="' . $poster . '" alt="' . $film->name . '">';                     
                                } else {
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM48 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 112l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16L64 96c-8.8 0-16 7.2-16 16zM416 96c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM160 128l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32L192 96c-17.7 0-32 14.3-32 32zm32 160c-17.7 0-32 14.3-32 32l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32l-128 0z"/></svg>';
                                }
                            $output .= '</span>';

                            $output .= '<div class="buttons-cntr">';
                                if (is_user_logged_in()) {
                                    $film_id = $film->term_id;
                                    $watched_button_class = $user_watched ? 'mark-as-unwatched-button' : 'mark-as-watched-button';
                                    $watched_action = $user_watched ? 'unwatched' : 'watched';
                                    $output .= '<button title="Watched" class="' . $watched_button_class . '" data-film-id="' . $film_id . '" data-action="' . $watched_action . '">';
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>';
                                    $output .= '</button>';
                                    
                                    
                                    $fav_button_class = $user_fav ? 'mark-as-unfav-button' : 'mark-as-fav-button';
                                    $fav_action = $user_fav ? 'unfav' : 'fav';
                                    $output .= '<button title="Favourite" class="' . $fav_button_class . '" data-nomination-id="' . $nomination_id . '" data-action="' . $fav_action . '">';
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M47.6 300.4L228.3 469.1c7.5 7 17.4 10.9 27.7 10.9s20.2-3.9 27.7-10.9L464.4 300.4c30.4-28.3 47.6-68 47.6-109.5v-5.8c0-69.9-50.5-129.5-119.4-141C347 36.5 300.6 51.4 268 84L256 96 244 84c-32.6-32.6-79-47.5-124.6-39.9C50.5 55.6 0 115.2 0 185.1v5.8c0 41.5 17.2 81.2 47.6 109.5z"/></svg>';
                                    $output .= '</button>';

                                    // Watchlist button - always start as "add to watchlist", JavaScript will update based on user data
                                    $output .= '<button title="Watchlist" class="mark-as-watchlist-button" data-film-id="' . $film_id . '" data-action="watchlist">';
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M96 320C96 302.3 110.3 288 128 288L512 288C529.7 288 544 302.3 544 320C544 337.7 529.7 352 512 352L128 352C110.3 352 96 337.7 96 320z"/></svg>';
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z"/></svg>';
                                    $output .= '</button>';
                                    
                                    if (!$has_winner) {
                                        $predict_button_class = $user_predict ? 'mark-as-unpredict-button' : 'mark-as-predict-button';
                                        $predict_action = $user_predict ? 'unpredict' : 'predict';
                                        $output .= '<button title="Prediction" class="' . $predict_button_class . '" data-nomination-id="' . $nomination_id . '" data-action="' . $predict_action . '">';
                                        $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M400 0L176 0c-26.5 0-48.1 21.8-47.1 48.2c.2 5.3 .4 10.6 .7 15.8L24 64C10.7 64 0 74.7 0 88c0 92.6 33.5 157 78.5 200.7c44.3 43.1 98.3 64.8 138.1 75.8c23.4 6.5 39.4 26 39.4 45.6c0 20.9-17 37.9-37.9 37.9L192 448c-17.7 0-32 14.3-32 32s14.3 32 32 32l192 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-26.1 0C337 448 320 431 320 410.1c0-19.6 15.9-39.2 39.4-45.6c39.9-11 93.9-32.7 138.2-75.8C542.5 245 576 180.6 576 88c0-13.3-10.7-24-24-24L446.4 64c.3-5.2 .5-10.4 .7-15.8C448.1 21.8 426.5 0 400 0zM48.9 112l84.4 0c9.1 90.1 29.2 150.3 51.9 190.6c-24.9-11-50.8-26.5-73.2-48.3c-32-31.1-58-76-63-142.3zM464.1 254.3c-22.4 21.8-48.3 37.3-73.2 48.3c22.7-40.3 42.8-100.5 51.9-190.6l84.4 0c-5.1 66.3-31.1 111.2-63 142.3z"/></svg>';
                                        $output .= '</button>';
                                    }
                                } else {
                                    $film_id = $film->term_id;
                                    $watched_button_class = 'mark-as-watched-button';
                                    $button_text = 'Unwatched';
                                    $watched_action = 'watched';
                                    $post_year = get_the_date('Y');
                                    
                                    $output .= '<button class="' . $watched_button_class . '" data-film-id="' . $film_id . '" data-action="' . $watched_action . '" data-film-name="' . esc_attr($film->name) . '" data-film-slug="' . esc_attr($film->slug) . '" data-film-year="' . esc_attr($post_year) . '">';
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>';
                                    $output .= '</button>';
                                    
                                    
                                    $fav_button_class = 'mark-as-fav-button';
                                    $fav_button_text = 'Mark as Favourite';
                                    $fav_action = 'fav';
                                    
                                    $output .= '<button title="Favourite" class="' . $fav_button_class . '" data-nomination-id="' . $nomination_id . '" data-action="' . $fav_action . '">';
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M47.6 300.4L228.3 469.1c7.5 7 17.4 10.9 27.7 10.9s20.2-3.9 27.7-10.9L464.4 300.4c30.4-28.3 47.6-68 47.6-109.5v-5.8c0-69.9-50.5-129.5-119.4-141C347 36.5 300.6 51.4 268 84L256 96 244 84c-32.6-32.6-79-47.5-124.6-39.9C50.5 55.6 0 115.2 0 185.1v5.8c0 41.5 17.2 81.2 47.6 109.5z"/></svg>';
                                    $output .= '</button>';
                                    
                                    if (!$has_winner) {
                                        $predict_button_class = 'mark-as-predict-button';
                                        $predict_button_text = 'Predict';
                                        $predict_action = 'predict';
                                        
                                        $output .= '<button title="Prediction" class="' . $predict_button_class . '" data-nomination-id="' . $nomination_id . '" data-action="' . $predict_action . '">';
                                        $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M400 0L176 0c-26.5 0-48.1 21.8-47.1 48.2c.2 5.3 .4 10.6 .7 15.8L24 64C10.7 64 0 74.7 0 88c0 92.6 33.5 157 78.5 200.7c44.3 43.1 98.3 64.8 138.1 75.8c23.4 6.5 39.4 26 39.4 45.6c0 20.9-17 37.9-37.9 37.9L192 448c-17.7 0-32 14.3-32 32s14.3 32 32 32l192 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-26.1 0C337 448 320 431 320 410.1c0-19.6 15.9-39.2 39.4-45.6c39.9-11 93.9-32.7 138.2-75.8C542.5 245 576 180.6 576 88c0-13.3-10.7-24-24-24L446.4 64c.3-5.2 .5-10.4 .7-15.8C448.1 21.8 426.5 0 400 0zM48.9 112l84.4 0c9.1 90.1 29.2 150.3 51.9 190.6c-24.9-11-50.8-26.5-73.2-48.3c-32-31.1-58-76-63-142.3zM464.1 254.3c-22.4 21.8-48.3 37.3-73.2 48.3c22.7-40.3 42.8-100.5 51.9-190.6l84.4 0c-5.1 66.3-31.1 111.2-63 142.3z"/></svg>';
                                        $output .= '</button>';
                                    }
                                }
                                $output .= '</div>';

                            if ($nominee_visibility == "hidden") {
                                $output .= '<details class="nominees"><summary>Nominees</summary>';
                                $output .= '<ul class="nominees-name">';
                                if (is_array($nominees)) {
                                    foreach ($nominees as $nominee) {
                                        $output .= '<li><a class="nominee-name" href="' . get_term_link($nominee) . '">' . $nominee->name . '</a></li>';
                                    }
                                }
                                $output .= '</ul></details>';
                            }

                            $output .= '<ul class="nominees-name">';
                            if (is_array($nominees)) {
                                foreach ($nominees as $nominee) {
                                    if ($nominee_visibility == "prominent") {
                                        $output .= '<li><a class="nominee-name" href="' . get_term_link($nominee) . '"><h3>' . $nominee->name . '</h3></a></li>';
                                    } elseif  ($nominee_visibility == "shown") {
                                        $output .= '<li><a class="nominee-name" href="' . get_term_link($nominee) . '">' . $nominee->name . '</a></li>';
                                    }
                                }
                            }
                            $output .= '</ul>';

                            if ($nominee_visibility != "hidden") {
                                $output .= '<ul class="nominees-photo">';
                                if (is_array($nominees)) {
                                    foreach ($nominees as $nominee) {
                                        $output .= '<a class="nominee-photo" href="' . get_term_link($nominee) . '">';
                                            $photo = get_field('photo', "nominees_" . $nominee->term_id);

                                            if (is_array($photo) && isset($photo['id'])) {
                                                $output .= wp_get_attachment_image($photo['id'], 'medium');
                                            } else {
                                                // Optionally handle the case where there's no valid photo
                                                // For example, you might set a default image or leave $output unchanged
                                                $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>';
                                            }
                                        $output .= '</a>';
                                    }
                                }
                                $output .= '</ul>';
                            }

                            $output .= '<div class="friends-watched">';
                            // Get watched films by friends for this specific film
                            foreach ($friend_ids as $friend_id) {
                                $json_path = ABSPATH . 'wp-content/uploads/user_meta/user_' . $friend_id . '.json';
                                $friend_watched = false;
                                if (file_exists($json_path)) {
                                    $json_data = file_get_contents($json_path);
                                    $user_meta = json_decode($json_data, true);
                                    if (isset($user_meta['watched']) && is_array($user_meta['watched'])) {
                                        foreach ($user_meta['watched'] as $watched_film) {
                                            if (isset($watched_film['film-id']) && $watched_film['film-id'] == $post_id) {
                                                $friend_watched = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                                if ($friend_watched) {
                                    $friend_user = get_userdata($friend_id);
                                    $friend_avatar = get_avatar($friend_id, 32); // Change size as needed
                                    $friend_profile_link = bp_members_get_user_url($friend_id);

                                    // Get user details to calculate random color number
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

                                    // Calculate product of lengths and get last 3 digits
                                    $randomcolornum = $id_length * $login_length * $first_name_length * $last_name_length * $email_length;
                                    $randomcolornum = substr($randomcolornum, -3);

                                    // Get initials
                                    $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
                                    
                                    // Output the avatar with the friend id and random color number in the class
                                    $output .= '<div class="friend-avatar friend-id-' . $friend_id . '" style="--randomcolornum:' . $randomcolornum . '">' . $friend_avatar . '<div class="friend-initials" title="' . $friend_user->display_name . '">' . $initials . '</div></div>';
                                }
                            }

                            $output .= '</li>';
                        }
                    }
        }

        $output .= '</ul>'; // Close the last year's list
    } else {
        $output = '<p>No nominations found for the specified category.</p>';
    }

    // Reset post data
    wp_reset_postdata();

    return $output;
}

?>