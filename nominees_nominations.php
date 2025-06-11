<?php 

function nominees_nominations_function() {
    // Get the queried object (taxonomy term) from the current URL
    $queried_object = get_queried_object();

    // Ensure we have a valid term object and taxonomy
    if (!isset($queried_object->taxonomy) || !isset($queried_object->slug)) {
        return '<p>Invalid term or taxonomy.</p>';
    }

    // Set taxonomy and term_slug based on current URL
    $taxonomy = $queried_object->taxonomy;
    $term_slug = $queried_object->slug;

    // Query for all nominations linked to the taxonomy term
    $args = array(
        'post_type' => 'nominations',
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $term_slug,
            ),
        ),
    );

    $query = new WP_Query($args);

    // Start output buffering to return HTML content
    ob_start();

    // Get the current term object
    $term = get_term_by('slug', $term_slug, $taxonomy);

    // Output the term title
    if ($term) {
        // Example: Get and display custom fields for the term
        $poster = get_field('poster', $taxonomy . '_' . $term->term_id);
        $duration = get_field('duration', $taxonomy . '_' . $term->term_id);
        $wikipedia_film = get_field('wikipedia_film', $taxonomy . '_' . $term->term_id);
        $release_date = get_field('release_date', $taxonomy . '_' . $term->term_id);
        $budget = get_field('budget', $taxonomy . '_' . $term->term_id);
        $box_office = get_field('box_office', $taxonomy . '_' . $term->term_id);
        $production_distribution = get_field('production_distribution', $taxonomy . '_' . $term->term_id);
        $country = get_field('country', $taxonomy . '_' . $term->term_id);
        $language = get_field('language', $taxonomy . '_' . $term->term_id);
        $photo = get_field('photo', $taxonomy . '_' . $term->term_id);
        $birthday = get_field('birthday', $taxonomy . '_' . $term->term_id);
        $wikipedia_nominee = get_field('wikipedia_nominee', $taxonomy . '_' . $term->term_id);


        
        if ($poster) {
            echo '<img class="term-featured-image" src="' . esc_html($poster) . '" alt="' . esc_html($term->name) . ' poster">'; // Display custom field 1
        }
        if ($taxonomy == 'nominees' && $photo) {
            echo wp_get_attachment_image($photo['id'], 'medium');
        }
        echo '<h1>' . esc_html($term->name) . '</h1>'; // Display term title
    

        if (category_description() != "") {
            echo '<details class="description"><summary>Bio:</summary>';
            echo '<p>' . category_description() . '</p>'; // Display term description
            echo '</details>';
        }


        // Query nominations and count winners
        $total_nominations = $query->found_posts; // Total nominations on this page
        $winner_count = 0;

        // Loop through nominations to count winners
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $categories = wp_get_post_terms(get_the_ID(), 'award-categories');

                foreach ($categories as $category) {
                    if ($category->slug === 'winner') { // Adjust to match the actual winner slug
                        $winner_count++;
                        break; // Exit the loop for this post if it's marked as a winner
                    }
                }
            }
            wp_reset_postdata();
        }

        // Display the summary
        $non_wins_count = $total_nominations - $winner_count;

        echo '<div class="nominations-summary">';
        echo '<p><span class="wins">' . $winner_count . ' win';
        if ($winner_count != '1') { echo 's'; }
        echo '</span> from <span class="nominations">' . $total_nominations . ' nominations</span></p>';

        // Display icons
        echo '<div class="nominations-icons">';
        // $icon_win = '<svg class="icon win" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M..." /></svg>'; // Replace with your gold icon SVG
        // $icon_nomination = '<svg class="icon nomination" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M..." /></svg>'; // Replace with your neutral icon SVG
        
        // Output win icons
        for ($i = 0; $i < $winner_count; $i++) {
            echo '<div class="icon winner">' . file_get_contents("https://stage.oscarschecklist.com/wp-content/uploads/2025/01/trophy-solid.svg") . '</div>';
        }
        
        // Output non-win icons
        for ($i = 0; $i < $non_wins_count; $i++) {
            echo '<div class="icon nomination">' . file_get_contents("https://stage.oscarschecklist.com/wp-content/uploads/2025/01/trophy-solid.svg") . '</div>';
        }
        echo '</div>'; // Close nominations-icons
        echo '</div>'; // Close nominations-summary




        
        
        if ($taxonomy == 'films') {

            echo '<details class="info"><summary>Info</summary>';

            if ($wikipedia_film) {
                echo '<a href="' . esc_html($wikipedia_film) . '">Wikipedia</a>'; // Display custom field 1
            }
            if ($release_date) {
                echo '<p>Release Date: <strong>' . esc_html($release_date) . '</strong></p>'; // Display custom field 1
            }
            if ($duration) {
                echo '<p>Duration: <strong>' . esc_html($duration) . '</strong></p>'; // Display custom field 1
            }
            if ($budget) {
                echo '<p>Budget: <strong>' . esc_html($budget) . '</strong></p>'; // Display custom field 1
            }
            if ($box_office) {
                echo '<p>Box Office: <strong>' . esc_html($box_office) . '</strong></p>'; // Display custom field 1
            }
            if ($production_distribution) {
                echo '<p>Studios: <strong>' . esc_html($production_distribution) . '</strong></p>'; // Display custom field 1
            }
            if ($country) {
                echo '<p>Country: <strong>' . esc_html($country) . '</strong></p>'; // Display custom field 1
            }
            if ($language) {
                echo '<p>Language: <strong>' . esc_html($language) . '</strong></p>'; // Display custom field 1
            }
            
            echo "</details>";
        }
    }

    if ($query->have_posts()) {
        echo '<ul class="nominees-nominations-list">';

        while ($query->have_posts()) {
            $query->the_post();

            // Get associated film from the 'films' taxonomy (assuming there's only one film)
            $films = wp_get_post_terms(get_the_ID(), 'films');
            $film = !empty($films) ? $films[0] : null;

            // Get award categories (there can be multiple)
            $categories = wp_get_post_terms(get_the_ID(), 'award-categories');

            foreach ($categories as $category) {
                $is_song = false;
                // Decide how to display certain categories                
                if ($category->name == "Actor in a Leading Role"
                    || $category->name == "Actor in a Supporting Role"
                    || $category->name == "Actress in a Leading Role"
                    || $category->name == "Actress in a Supporting Role"
                ) {
                    $nominee_visibility = "prominent";
                } elseif ($category->name == "Directing"
                    || $category->name == "Music (Original Song)"
                    || $category->name == "Music (Original Score)"
                    // || $category->name == "Writing (Adapted)"
                    // || $category->name == "Writing (Original)"
                ) {
                    $nominee_visibility = "shown";
                // } elseif ($category->name == "Music (Original Song)") {
                //     $is_song = true;
                } elseif ($category->name == "Winner") {
                    continue;
                } else {
                    $nominee_visibility = "hidden";
                }

                if ($category->name == "Music (Original Song)") {
                    $is_song = true;
                }
            }

            if ($taxonomy == 'nominees') {
                $nominee_visibility = 'hidden';
            }
            
            // Get the nomination year (assuming it's a custom field)
            $year = get_the_date('Y'); // Get the year from the post's publish date
            
            echo '<li class="';
            foreach ($categories as $category) {
                echo ' ' . esc_html($category->slug);
            }
            echo ' nominee-visibility-' . $nominee_visibility;

            $user_watched = false;
            $post_id = ($film && isset($film->term_id)) ? $film->term_id : null;
            if (is_user_logged_in() && $post_id) {
                $user_id = get_current_user_id();
                $json_path = ABSPATH . 'wp-content/uploads/user_meta/user_' . $user_id . '.json';
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
                if ($user_watched) {
                    echo ' watched ';
                }
            }
            echo '">';

            // Display the poster for the associated film
            // if ($taxonomy != 'films') {
                echo '<span class="film-poster">';
                    $poster = get_field('poster', "films_" . $film->term_id);  
                    if ($poster) {
                        echo '<img src="' . $poster . '" alt="' . $film->name . '">';                     
                    } else {
                        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM48 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 112l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16L64 96c-8.8 0-16 7.2-16 16zM416 96c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM160 128l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32L192 96c-17.7 0-32 14.3-32 32zm32 160c-17.7 0-32 14.3-32 32l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32l-128 0z"/></svg>';
                    }
                echo '</span>';
            // }

            echo '<div class="info">';
            echo '<div class="year-category">';
            // Display the year with a link to the relevant year page
            if ($year) {
                $year_link = 'https://stage.oscarschecklist.com/years/nominations-' . $year . '/';
                echo '<a class="year" href="' . esc_url($year_link) . '">' . esc_html($year) . '</a><p> - </p>';
            }

            // Display categories without the "Category: " prefix and link them to their respective pages
            foreach ($categories as $category) {
                $category_link = 'https://stage.oscarschecklist.com/category-pages/' . $category->slug;
                echo '<a class="category ' . esc_html($category->name) . '" href="' . esc_url($category_link) . '">' . esc_html($category->name) . '</a>';
            }
            echo '</div>';
            
            echo '<div class="title">';
            // Display the film without the "Film: " prefix
            if ($taxonomy != 'films') {
                if ($film) {
                    $film_link = get_term_link($film); // Get the link to the film term archive
                    echo '<a class="film-title" href="' . esc_url($film_link) . '">' . esc_html($film->name) . '</a>';
                }
            }
            echo '</div>';
            
            // Get all nominees associated with the nomination
            if ($taxonomy != 'nominees') {
                $nominees = wp_get_post_terms(get_the_ID(), 'nominees');
                
                if (!empty($nominees) && is_array($nominees)) {
                    $openstate = '';
                    if ($taxonomy == 'films') { $openstate = 'open'; }
                    echo '<details class="nominees" ' . $openstate . '><summary>Nominees</summary>';
                    echo '<div class="nominees-name"><p>';                
                    foreach ($nominees as $nominee) {
                        // Get nominee link
                        $nominee_link = get_term_link($nominee);
                        
                        // Get nominee photo (ACF field "photo")
                        // $nominee_photo = get_field('photo', "nominees_" . $nominee->term_id);
                        
                        // Display nominee's name with a link
                        echo '<a href="' . esc_url($nominee_link) . '" title="' . esc_html($nominee->name) . '">' . esc_html($nominee->name) . '</a>';
                        
                        // Display nominee's photo if available
                        // if ($nominee_photo) {
                        //     echo '<img src="' . esc_url($nominee_photo['url']) . '" alt="' . esc_attr($nominee->name) . '" style="max-width:150px;">';
                        // } else {
                        //     // Fallback if no photo is available (optional)
                        //     echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!-- Fallback SVG here --></svg>';
                        // }
                    }
                    echo '</p></div></details>';
                }
            }
            echo '</div>';

            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $post_id = $film->term_id;
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
                $button_class = $user_watched ? 'mark-as-unwatched-button' : 'mark-as-watched-button';
                $button_text = $user_watched ? 'Watched' : 'Unwatched';
                $watched_action = $user_watched ? 'unwatched' : 'watched';
                echo '<button class="' . $button_class . '" data-film-id="' . $post_id . '" data-action="' . $watched_action . '">' . $button_text;
                echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>';
                echo '</button>';
            } else if ($post_id) {
                $button_class = 'mark-as-watched-button';
                $button_text = 'Unwatched';
                $watched_action = 'watched';
            
                echo '<button class="' . $button_class . '" data-film-id="' . $post_id . '" data-action="' . $watched_action . '">' . $button_text;
                echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg>';
                echo '</button>';
            }

            echo '</li>';
        }

        echo '</ul>';
    } else {
        echo '<p>No nominations found for this term.</p>';
    }

    wp_reset_postdata();

    // Return the output
    return ob_get_clean();
}

add_shortcode('nominees_nominations', 'nominees_nominations_function');