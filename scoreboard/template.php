<div id="main-scoreboard-container">
    <header>
        <h1>Oscars Scoreboard</h1>
    </header>
    <section id="friends" class="border-r p-20">
        <ul id="friends-list">
            <?php
            if (is_user_logged_in()) {
                $current_user_id = get_current_user_id();
                $friends = friends_get_friend_user_ids($current_user_id);
                
                // Add current user first
                $current_user = wp_get_current_user();
                $current_avatar_url = get_avatar_url($current_user_id, ['size' => 64]);
                
                echo '<li class="friend-item" data-user-id="' . esc_attr($current_user_id) . '">';
                echo '<div class="friend-photo">';
                echo '<img src="' . esc_url($current_avatar_url) . '" alt="' . esc_attr($current_user->display_name) . '">';
                echo '</div>';
                echo '<div class="friend-info">';
                echo '<span class="friend-name">' . esc_html($current_user->display_name) . '</span>';
                echo '<span class="file-size" data-user-id="' . esc_attr($current_user_id) . '">Loading...</span>';
                echo '</div>';
                echo '</li>';
                
                // Add friends
                if ($friends) {
                    foreach ($friends as $friend_id) {
                        $friend_user = get_userdata($friend_id);
                        if (!$friend_user) continue;
                        
                        $avatar_url = get_avatar_url($friend_id, ['size' => 64]);
                        $first_name = $friend_user->user_firstname;
                        $last_name = $friend_user->user_lastname;
                        $user_login = $friend_user->user_login;
                        $user_email = $friend_user->user_email;
                        
                        // Generate color from hash
                        $hash_string = $friend_id . $user_login . $first_name . $last_name . $user_email;
                        $hash = crc32($hash_string);
                        $randomcolornum = abs($hash) % 1000;
                        
                        // Get initials
                        $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
                        
                        echo '<li class="friend-item" data-user-id="' . esc_attr($friend_id) . '" style="--randomcolornum:' . $randomcolornum . '">';
                        echo '<div class="friend-photo">';
                        
                        // Check if avatar is default Gravatar
                        $is_default_avatar = strpos($avatar_url, 'd=mm') !== false || strpos($avatar_url, 'd=blank') !== false;
                        
                        if ($is_default_avatar) {
                            // Show initials with color
                            echo '<div class="friend-initials">' . esc_html($initials) . '</div>';
                        } else {
                            // Show avatar image
                            echo '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($friend_user->display_name) . '">';
                        }
                        
                        echo '</div>';
                        echo '<div class="friend-info">';
                        echo '<span class="friend-name">' . esc_html($friend_user->display_name) . '</span>';
                        echo '<span class="file-size" data-user-id="' . esc_attr($friend_id) . '">Loading...</span>';
                        echo '</div>';
                        echo '</li>';
                    }
                }
            }
            ?>
        </ul>
    </section>
    <section id="films" class="border-r p-20">
        <ul id="films-list">
            <?php
            $year = 2026;

            // Get all nominations for this year
            $args = array(
                'post_type' => 'nominations',
                'posts_per_page' => -1,
                'date_query' => array(
                    array(
                        'year' => $year,
                    ),
                ),
            );

            $nominations_query = new WP_Query($args);
            
            // Group nominations by film
            $films_data = array();
            
            if ($nominations_query->have_posts()) {
                while ($nominations_query->have_posts()) {
                    $nominations_query->the_post();
                    
                    $films = get_the_terms(get_the_ID(), 'films');
                    $nomination_id = get_the_ID();
                    $categories = get_the_terms(get_the_ID(), 'award-categories');
                    
                    if ($films && !is_wp_error($films)) {
                        foreach ($films as $film) {
                            $film_id = $film->term_id;
                            
                            // Initialize film in array if not exists
                            if (!isset($films_data[$film_id])) {
                                $films_data[$film_id] = array(
                                    'name' => $film->name,
                                    'term_id' => $film->term_id,
                                    'nominations' => array(),
                                );
                            }
                            
                            // Add nomination data
                            $category_name = '';
                            if ($categories && !is_wp_error($categories)) {
                                foreach ($categories as $category) {
                                    if ($category->slug != 'winner') {
                                        $category_name = $category->name;
                                        break;
                                    }
                                }
                            }
                            
                            $films_data[$film_id]['nominations'][] = array(
                                'nomination_id' => $nomination_id,
                                'category_name' => $category_name,
                            );
                        }
                    }
                }
                wp_reset_postdata();
            }
            
            // Sort films by nomination count (descending), then alphabetically
            usort($films_data, function($a, $b) {
                $count_diff = count($b['nominations']) - count($a['nominations']);
                if ($count_diff !== 0) {
                    return $count_diff;
                }
                return strcmp($a['name'], $b['name']);
            });
            
            // Output films
            foreach ($films_data as $film_data) {
                $nomination_count = count($film_data['nominations']);
                echo '<li class="film-item" data-film-id="' . esc_attr($film_data['term_id']) . '" data-nomination-count="' . esc_attr($nomination_count) . '">';
                
                echo '<div class="film-content">';
                
                // Film poster
                echo '<span class="film-poster">';
                $poster = get_field('poster', 'films_' . $film_data['term_id']);
                if ($poster) {
                    echo '<img src="' . esc_url($poster) . '" alt="' . esc_attr($film_data['name']) . '">';
                } else {
                    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM48 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 112l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16L64 96c-8.8 0-16 7.2-16 16zM416 96c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM160 128l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32L192 96c-17.7 0-32 14.3-32 32zm32 160c-17.7 0-32 14.3-32 32l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32l-128 0z"/></svg>';
                }
                echo '</span>';
                
                // Film name and nomination count
                echo '<div class="film-info">';
                echo '<h3 class="film-name">' . esc_html($film_data['name']) . '</h3>';
                echo '<span class="nomination-count">' . esc_html($nomination_count) . ' nomination' . ($nomination_count !== 1 ? 's' : '') . '</span>';
                echo '</div>';
                
                echo '</div>'; // .film-content
                
                // Nominations (trophy icons)
                echo '<div class="nominations-icons">';
                foreach ($film_data['nominations'] as $nomination) {
                    echo '<span class="trophy-icon" data-nomination-id="' . esc_attr($nomination['nomination_id']) . '">';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M400 0L176 0c-26.5 0-48.1 21.8-47.1 48.2c.2 5.3 .4 10.6 .7 15.8L24 64C10.7 64 0 74.7 0 88c0 92.6 33.5 157 78.5 200.7c44.3 43.1 98.3 64.8 138.1 75.8c23.4 6.5 39.4 26 39.4 45.6c0 20.9-17 37.9-37.9 37.9L192 448c-17.7 0-32 14.3-32 32s14.3 32 32 32l192 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-26.1 0C337 448 320 431 320 410.1c0-19.6 15.9-39.2 39.4-45.6c39.9-11 93.9-32.7 138.2-75.8C542.5 245 576 180.6 576 88c0-13.3-10.7-24-24-24L446.4 64c.3-5.2 .5-10.4 .7-15.8C448.1 21.8 426.5 0 400 0zM48.9 112l84.4 0c9.1 90.1 29.2 150.3 51.9 190.6c-24.9-11-50.8-26.5-73.2-48.3c-32-31.1-58-76-63-142.3zM464.1 254.3c-22.4 21.8-48.3 37.3-73.2 48.3c22.7-40.3 42.8-100.5 51.9-190.6l84.4 0c-5.1 66.3-31.1 111.2-63 142.3z"/></svg>';
                    echo '<span class="category-tooltip">' . esc_html($nomination['category_name']) . '</span>';
                    echo '</span>';
                }
                echo '</div>'; // .nominations-icons
                
                echo '</li>';
            }
            ?>
        </ul>
    </section>
    <section id="categories" class="border-r p-20">
        <?php 

            $year = 2026;

            global $post;

            if (!$post) {
                echo "No post found.";  // Return early if $post is null
                return;
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
                            // || $awardCategory->name == "Writing (Adapted)"
                            // || $awardCategory->name == "Writing (Original)"
                        ) {
                            $nominee_visibility = "shown";
                        // } elseif ($awardCategory->name == "Music (Original Song)") {
                        //     $is_song = true;
                        } elseif ($awardCategory->name == "Winner") {
                            continue;
                        } else {
                            $nominee_visibility = "hidden";
                        }


                        if ($awardCategory->name == "Music (Original Song)") {
                            $is_song = true;
                        }

                        // Output posts
                        $output .= '<div class="awards-category category-' . $awardCategory->slug . '" data-category-slug="' . $awardCategory->slug . '" >';
                        $output .= '<div class="category-title"><h2>' . $awardCategory->name . '</h2></div>';

                        $output .= '<ul class="nominations-list category-' . $awardCategory->slug . ' nominee_visibility-' . $nominee_visibility . '">';
                        if ($posts_query->have_posts()) {
                            while ($posts_query->have_posts()) {
                                $posts_query->the_post();
                                

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

                                    $output .= '<li id="nomination-' . esc_attr($nomination_id) . '" class="' . $winner . ' ';

                                    $output .= ' film-id-' . $film->term_id . ' ';
                                    if ($awardCategory->name == "Winner") {
                                        $output .= 'winner ';
                                    }
                                    if ($is_song == true) {
                                        $output .= ' song ';
                                    }
                                    $output .= '" data-film-id="' . $film->term_id . '">';

                                        $output .= '<div class="left-section">';
                                            $output .= '<span class="film-poster">';
                                                $poster = get_field('poster', "films_" . $film->term_id);

                                                if ($poster) {
                                                    $output .= '<img src="' . $poster . '" alt="' . $film->name . '">';                     
                                                } else {
                                                    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM48 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 112l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16L64 96c-8.8 0-16 7.2-16 16zM416 96c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM160 128l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32L192 96c-17.7 0-32 14.3-32 32zm32 160c-17.7 0-32 14.3-32 32l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32l-128 0z"/></svg>';
                                                }
                                            $output .= '</span>';

                                            if ($nominee_visibility == "prominent") {
                                                if (is_array($nominees)) {
                                                    foreach ($nominees as $nominee) {
                                                        $output .= '<span class="nominee-photo">';
                                                            $photo = get_field('photo', "nominees_" . $nominee->term_id);

                                                            if (is_array($photo) && isset($photo['id'])) {
                                                                $output .= wp_get_attachment_image($photo['id'], 'medium');
                                                            } else {
                                                                // Optionally handle the case where there's no valid photo
                                                                // For example, you might set a default image or leave $output unchanged
                                                                $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>';
                                                            }
                                                        $output .= '</span>';
                                                    }
                                                }
                                            }
                                        $output .= '</div>'; // .left-section

                                        $output .= '<div class="right-section">';
                                            if ($is_song == true) {
                                                $output .= '<h3 class="song-name">' . get_the_title() . '</h3>';
                                            }

                                            if ($nominee_visibility == "prominent") {
                                                $output .= '<span class="film-name"><p>' . $film->name . '</p></span>';
                                            } elseif ($is_song == true) {
                                                $output .= '<span class="film-name"><h4>' . $film->name . '</h4></span>';
                                            } else {
                                                $output .= '<span class="film-name"><h3>' . $film->name . '</h3></span>';
                                            }

                                            if ($is_song != true) {
                                                $output .= '<ul class="nominees-name">';
                                                if (is_array($nominees)) {
                                                    foreach ($nominees as $nominee) {
                                                        if ($nominee_visibility == "prominent") {
                                                            $output .= '<li><span class="nominee-name"><h3>' . $nominee->name . '</h3></span></li>';
                                                        } elseif  ($nominee_visibility == "shown") {
                                                            $output .= '<li><span class="nominee-name">' . $nominee->name . '</span></li>';
                                                        }
                                                    }
                                                }
                                                $output .= '</ul>';
                                            }

                                            $output .= '<div class="prediction-favourites">';
                                                $output .= '<div class="pred-fav-btns">';
                                                    $output .= '<button class="pred-btn" data-nomination-id="' . esc_attr($nomination_id) . '" data-category-slug="' . esc_attr($awardCategory->slug) . '" aria-label="Mark as prediction">';
                                                        $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M400 0L176 0c-26.5 0-48.1 21.8-47.1 48.2c.2 5.3 .4 10.6 .7 15.8L24 64C10.7 64 0 74.7 0 88c0 92.6 33.5 157 78.5 200.7c44.3 43.1 98.3 64.8 138.1 75.8c23.4 6.5 39.4 26 39.4 45.6c0 20.9-17 37.9-37.9 37.9L192 448c-17.7 0-32 14.3-32 32s14.3 32 32 32l192 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-26.1 0C337 448 320 431 320 410.1c0-19.6 15.9-39.2 39.4-45.6c39.9-11 93.9-32.7 138.2-75.8C542.5 245 576 180.6 576 88c0-13.3-10.7-24-24-24L446.4 64c.3-5.2 .5-10.4 .7-15.8C448.1 21.8 426.5 0 400 0zM48.9 112l84.4 0c9.1 90.1 29.2 150.3 51.9 190.6c-24.9-11-50.8-26.5-73.2-48.3c-32-31.1-58-76-63-142.3zM464.1 254.3c-22.4 21.8-48.3 37.3-73.2 48.3c22.7-40.3 42.8-100.5 51.9-190.6l84.4 0c-5.1 66.3-31.1 111.2-63 142.3z"/></svg>';
                                                    $output .= '</button>';
                                                    $output .= '<button class="fav-btn" data-nomination-id="' . esc_attr($nomination_id) . '" data-category-slug="' . esc_attr($awardCategory->slug) . '" aria-label="Mark as favourite">';
                                                        $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M47.6 300.4L228.3 469.1c7.5 7 17.4 10.9 27.7 10.9s20.2-3.9 27.7-10.9L464.4 300.4c30.4-28.3 47.6-68 47.6-109.5v-5.8c0-69.9-50.5-129.5-119.4-141C347 36.5 300.6 51.4 268 84L256 96 244 84c-32.6-32.6-79-47.5-124.6-39.9C50.5 55.6 0 115.2 0 185.1v5.8c0 41.5 17.2 81.2 47.6 109.5z"/></svg>';
                                                    $output .= '</button>';
                                                $output .= '</div>';
                                                $output .= '<div class="predictions"></div>';
                                                $output .= '<div class="favourites"></div>';
                                            $output .= '</div>'; // .prediction-favourites
                                        $output .= '</div>'; // .right-section

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
                echo $output;
            }
        ?>
    </section>
    <footer>
        <div id="countdown-display">Next reload in: <span id="countdown">30</span>s</div>
        <div id="progress-bar-container">
            <div id="progress-bar"></div>
        </div>
        <ul id="notices-list"></ul>
    </footer>
</div>
