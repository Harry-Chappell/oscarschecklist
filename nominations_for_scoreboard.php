<?php 
function show_scoreboard_nominations_shortcode($atts) {
    // Get Year from custom field
    $year = get_field('year');

    global $post;

    if (!$post) {
        return "No post found.";  // Return early if $post is null
    }

    // Get current user's friends
    $friend_ids = friends_get_friend_user_ids(bp_loggedin_user_id());
    array_unshift($friend_ids, bp_loggedin_user_id());
    
    // Initialize an empty array to store watched films by friends
    $friend_watched_films = array();

    // Loop through each friend's ID
    foreach ($friend_ids as $friend_id) {
        // Get watched films of the friend
        $friend_watched = get_user_meta($friend_id, 'watched_' . $post->ID, true);
        // Add watched films to the array
        if ($friend_watched) {
            $friend_watched_films[$friend_id] = $post->ID;
        }
    }

    
    $awardCategories = get_terms(array(
        'taxonomy' => 'award-categories',
        'hide_empty' => false,
    ));
    
    // Check if any awardCategories were found
    if (!empty($awardCategories) && !is_wp_error($awardCategories)) {
        $output = '';

        // Loop through each term
        foreach ($awardCategories as $awardCategory) {

            $args = array(
                'post_type' => 'nominations',
                'order' => 'ASC',
                'orderby' => 'name',
                'date_query' => array(
                    array(
                        'year' => $year,
                    ),
                ),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'award-categories',
                        'field' => 'slug',
                        'terms' => $awardCategory->slug,
                    ),
                ),
                'hide_empty' => false,
            );



            // Run the query
            $posts_query = new WP_Query($args);

            $has_winner = false; // Initialize to false for this category
        
            if ($posts_query->have_posts()) {
                while ($posts_query->have_posts()) {
                    $posts_query->the_post();
        
                    // Check if the current post has the "winner" category
                    if (has_term('winner', 'award-categories', get_the_ID())) {
                        $has_winner = true; // Mark as having a winner
                        continue; // Exit the loop early
                    }
                }
        
                wp_reset_postdata(); // Reset after each query
            }

            if ($posts_query->have_posts()) {
                

                $is_song = false;
                // Decide how to display certain categories                
                if ($awardCategory->name == "Actor in a Leading Role"
                    || $awardCategory->name == "Actor in a Supporting Role"
                    || $awardCategory->name == "Actress in a Leading Role"
                    || $awardCategory->name == "Actress in a Supporting Role"
                ) {
                    $nominee_visibility = "prominent";
                } elseif ($awardCategory->name == "Directing"
                    || $awardCategory->name == "Music (Original Song)"
                    || $awardCategory->name == "Music (Original Score)"
                ) {
                    $nominee_visibility = "shown";
                } elseif ($awardCategory->name == "Winner") {
                    continue;
                } else {
                    $nominee_visibility = "hidden";
                }


                if ($awardCategory->name == "Music (Original Song)") {
                    $is_song = true;
                }

                // Output posts
                $output .= '<div class="awards-category category-' . $awardCategory->slug . '" data-category-id="' . $awardCategory->term_id . '">';
                $output .= '<div class="category-title"><h2>' . $awardCategory->name . '</h2>';
                $output .= '</div>';
                $output .= '<ul class="nominations-list category-' . $awardCategory->slug . ' nominee_visibility-' . $nominee_visibility . '">';
                if ($posts_query->have_posts()) {
                    while ($posts_query->have_posts()) {
                        $posts_query->the_post();


                        if (is_user_logged_in()) {
                            $nomination_id = get_the_ID();
                            $user_fav = get_user_meta(get_current_user_id(), 'fav_' . $nomination_id, true);
                            $user_predict = get_user_meta(get_current_user_id(), 'predict_' . $nomination_id, true);
                        
                            // Arrays to store friends who favorited or predicted
                            $friends_fav = [];
                            $friends_predict = [];
                        
                            // Loop through each friend to check their preferences
                            foreach ($friend_ids as $friend_id) {
                                $friend_data = get_userdata($friend_id);
                                if ($friend_data) {
                                    $friend_username = $friend_data->user_login;
                                    $friend_first_name = get_user_meta($friend_id, 'first_name', true);
                                    $friend_last_name = get_user_meta($friend_id, 'last_name', true);
                                    $friend_email = $friend_data->user_email;
                                    $friend_avatar = get_avatar($friend_id, 96); // 96px avatar size

                                    // Calculate lengths
                                    $id_length = strlen($friend_id);
                                    $login_length = strlen($friend_username);
                                    $first_name_length = strlen($friend_first_name);
                                    $last_name_length = strlen($friend_last_name);
                                    $email_length = strlen($friend_email);

                                    if ($last_name_length == 0) {
                                        $last_name_length = 1;
                                    }
                                    // Calculate product of lengths and get last 3 digits
                                    $randomcolornum = $id_length * $login_length * $first_name_length * $last_name_length * $email_length;
                                    $randomcolornum = substr($randomcolornum, -3);

                                    // Get initials
                                    $initials = strtoupper(substr($friend_first_name, 0, 1) . substr($friend_last_name, 0, 1));
                        
                                    if (get_user_meta($friend_id, 'fav_' . $nomination_id, true)) {
                                        $friends_fav[] = [
                                            'id' => $friend_id,
                                            'username' => $friend_username,
                                            'first_name' => $friend_first_name,
                                            'last_name' => $friend_last_name,
                                            'email' => $friend_email,
                                            'avatar' => $friend_avatar,
                                            'initials' => $initials,
                                            'rand_color' => $randomcolornum
                                        ];
                                    }
                                    if (get_user_meta($friend_id, 'predict_' . $nomination_id, true)) {
                                        $friends_predict[] = [
                                            'id' => $friend_id,
                                            'username' => $friend_username,
                                            'first_name' => $friend_first_name,
                                            'last_name' => $friend_last_name,
                                            'email' => $friend_email,
                                            'avatar' => $friend_avatar,
                                            'initials' => $initials,
                                            'rand_color' => $randomcolornum
                                        ];
                                    }
                                }
                            }
                        }

                        $films = get_the_terms(get_the_ID(), 'films');
                        if (is_array($films) || is_object($films)) {
                        foreach ($films as $film) {
                            $nominees = get_the_terms(get_the_ID(), 'nominees');
                            $categories = get_the_terms(get_the_ID(), 'award-categories');

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

                            $output .= '<li id="nomination-' . esc_attr($nomination_id) . '" data-film-id="' . $film->term_id . '" data-nomination-id="' . esc_attr($nomination_id) . '" class="nomination ' . $winner . ' ';

                            if (is_user_logged_in() && $user_fav) {
                                $output .= 'fav ';
                            }
                            if (is_user_logged_in() && $user_predict) {
                                $output .= 'predict ';
                            }

                            $output .= ' film-id-' . $film->term_id . ' ';
                            if ($awardCategory->name == "Winner") {
                                $output .= 'winner ';
                            }
                            if ($is_song == true) {
                                $output .= ' song ';
                            }
                            $output .= '">';
                            
                            $output .= '<div class="nom-info">';


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

                                if ($poster) {
                                    $output .= '<img src="' . $poster . '" alt="' . $film->name . '">';                     
                                } else {
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM48 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 112l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16L64 96c-8.8 0-16 7.2-16 16zM416 96c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM160 128l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32L192 96c-17.7 0-32 14.3-32 32zm32 160c-17.7 0-32 14.3-32 32l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32l-128 0z"/></svg>';
                                }
                            $output .= '</span>';

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
                                                $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>';
                                            }
                                        $output .= '</a>';
                                    }
                                }
                                $output .= '</ul>';
                            }

                            $output .= '</div>';

                            if (!empty($friends_predict) || !empty($friends_fav)) {
                                $output .= '<div class="friends">';
                                if (!empty($friends_predict)) {
                                    $output .= '<div class="predictions">';
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M400 0L176 0c-26.5 0-48.1 21.8-47.1 48.2c.2 5.3 .4 10.6 .7 15.8L24 64C10.7 64 0 74.7 0 88c0 92.6 33.5 157 78.5 200.7c44.3 43.1 98.3 64.8 138.1 75.8c23.4 6.5 39.4 26 39.4 45.6c0 20.9-17 37.9-37.9 37.9L192 448c-17.7 0-32 14.3-32 32s14.3 32 32 32l192 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-26.1 0C337 448 320 431 320 410.1c0-19.6 15.9-39.2 39.4-45.6c39.9-11 93.9-32.7 138.2-75.8C542.5 245 576 180.6 576 88c0-13.3-10.7-24-24-24L446.4 64c.3-5.2 .5-10.4 .7-15.8C448.1 21.8 426.5 0 400 0zM48.9 112l84.4 0c9.1 90.1 29.2 150.3 51.9 190.6c-24.9-11-50.8-26.5-73.2-48.3c-32-31.1-58-76-63-142.3zM464.1 254.3c-22.4 21.8-48.3 37.3-73.2 48.3c22.7-40.3 42.8-100.5 51.9-190.6l84.4 0c-5.1 66.3-31.1 111.2-63 142.3z"/></svg>';

                                    foreach ($friends_predict as $friend) {
                                        $initials = strtoupper(substr($friend['first_name'], 0, 1) . substr($friend['last_name'], 0, 1));

                                        $output .= '<span class="friend" style="--randomcolornum:' . $friend['rand_color'] . '" data-friend-id="' . $friend['id'] . '">';
                                        $output .= $friend['avatar']; // Display avatar image
                                        $output .= '<div class="friend-initials">' . $initials . '</div>';
                                        $output .= '<a href="' . esc_url(bp_core_get_userlink($friend['id'], false, true)) . '">' . esc_html($friend['username']) . '</a>';
                                        $output .= '</span> ';
                                    }
                                    $output .= '</div>';
                                }
                                
                                if (!empty($friends_fav)) {
                                    $output .= '<div class="favorites">';
                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M47.6 300.4L228.3 469.1c7.5 7 17.4 10.9 27.7 10.9s20.2-3.9 27.7-10.9L464.4 300.4c30.4-28.3 47.6-68 47.6-109.5v-5.8c0-69.9-50.5-129.5-119.4-141C347 36.5 300.6 51.4 268 84L256 96 244 84c-32.6-32.6-79-47.5-124.6-39.9C50.5 55.6 0 115.2 0 185.1v5.8c0 41.5 17.2 81.2 47.6 109.5z"/></svg>';
                                    
                                    foreach ($friends_fav as $friend) {
                                        $initials = strtoupper(substr($friend['first_name'], 0, 1) . substr($friend['last_name'], 0, 1));

                                        $output .= '<span class="friend" style="--randomcolornum:' . $friend['rand_color'] . '" data-friend-id="' . $friend['id'] . '">';
                                        $output .= $friend['avatar']; // Display avatar image
                                        $output .= '<div class="friend-initials">' . $initials . '</div>';
                                        $output .= '<a href="' . esc_url(bp_core_get_userlink($friend['id'], false, true)) . '">' . esc_html($friend['username']) . '</a>';
                                        $output .= '</span> ';
                                    }
                                    $output .= '</div>';
                                }
                                $output .= '</div>';
                            }


                            $output .= '</li>';
                        }
                    }
                    }
                } else {
                    $output .= '<li>No posts found</li>';
                }
                $output .= '</ul>';
                $output .= '</div>';
            }
            wp_reset_postdata();
        }
        return $output;
    }
}

?>