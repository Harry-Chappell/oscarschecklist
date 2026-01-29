<div id="main-scoreboard-container" class="scoreboard-admin-container">
    <header>
        <h1>Oscars Scoreboard Admin</h1>
    </header>
    <section class="admin-controls">

        <div class="controller upcoming-categories-controller">
            <h2>Upcoming Categories</h2>
            <p>Select the current category being announced.</p>
            <?php
            // Query nominations for 2026
            $nominations_2026 = get_posts([
                'post_type'      => 'nominations',
                'posts_per_page' => -1,
                'date_query'     => [
                    [
                        'year' => 2026,
                    ],
                ],
            ]);

            // Extract unique category IDs from the nominations
            $category_ids = [];
            foreach ($nominations_2026 as $nomination) {
                $post_categories = wp_get_post_terms($nomination->ID, 'award-categories', ['fields' => 'ids']);
                $category_ids = array_merge($category_ids, $post_categories);
            }
            $category_ids = array_unique($category_ids);

            // Fetch the categories with those IDs
            $categories = get_terms([
                'taxonomy'   => 'award-categories',
                'hide_empty' => false,
                'include'    => $category_ids,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (!empty($categories) && !is_wp_error($categories)) :
            ?>
                <div id="category-control">
                    <label for="category-select">Category:</label>
                    <select id="category-select">
                        <option value="">-- Select a category --</option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>" data-slug="<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="set-category-btn">Activate</button>
                </div>
            <?php else : ?>
                <p>No categories found with nominations for 2026.</p>
            <?php endif; ?>
        </div>

        <div class="controller current-category-controller">
            <h2>Current Category</h2>
            <div id="current-category-container">
            <?php
            // Get all categories with 2026 nominations
            $nominations_2026 = get_posts([
                'post_type'      => 'nominations',
                'posts_per_page' => -1,
                'date_query'     => [
                    [
                        'year' => 2026,
                    ],
                ],
            ]);

            $category_ids = [];
            foreach ($nominations_2026 as $nomination) {
                $post_categories = wp_get_post_terms($nomination->ID, 'award-categories', ['fields' => 'ids']);
                $category_ids = array_merge($category_ids, $post_categories);
            }
            $category_ids = array_unique($category_ids);

            $categories = get_terms([
                'taxonomy'   => 'award-categories',
                'hide_empty' => false,
                'include'    => $category_ids,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);
            
            if (!empty($categories) && !is_wp_error($categories)) {
                foreach ($categories as $category) {
                    if ($category->slug === 'winner') continue;
                    
                    $current_category_name = $category->name;
                    $current_category_slug = $category->slug;
                        
                    $current_category_name = $category->name;
                    $current_category_slug = $category->slug;
                    
                    // Query nominations for this category in 2026
                    $args = array(
                        'post_type' => 'nominations',
                        'order' => 'ASC',
                        'orderby' => 'name',
                        'posts_per_page' => -1,
                        'date_query' => array(
                            array(
                                'year' => 2026,
                            ),
                        ),
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'award-categories',
                                'field' => 'slug',
                                'terms' => $current_category_slug,
                            ),
                        ),
                    );
                    
                    $nominations_query = new WP_Query($args);
                    
                    // Determine nominee visibility based on category
                    $nominee_visibility = "hidden";
                    if (in_array($current_category_name, ["Actor in a Leading Role", "Actor in a Supporting Role", "Actress in a Leading Role", "Actress in a Supporting Role"])) {
                        $nominee_visibility = "prominent";
                    } elseif (in_array($current_category_name, ["Directing", "Music (Original Song)", "Music (Original Score)"])) {
                        $nominee_visibility = "shown";
                    }
                    
                    $is_song = ($current_category_name == "Music (Original Song)");
                    
                    echo '<div class="current-category-display" data-category-slug="' . esc_attr($current_category_slug) . '" style="display: none;">';
                    echo '<h3 class="category-title">' . esc_html($current_category_name) . '</h3>';
                    
                    if ($nominations_query->have_posts()) {
                        echo '<ul class="nominations-list nominee_visibility-' . $nominee_visibility . '">';
                            
                            while ($nominations_query->have_posts()) {
                                $nominations_query->the_post();
                                
                                $films = get_the_terms(get_the_ID(), 'films');
                                if (is_array($films) || is_object($films)) {
                                    foreach ($films as $film) {
                                        $nominees = get_the_terms(get_the_ID(), 'nominees');
                                        $categories = get_the_terms(get_the_ID(), 'award-categories');
                                        
                                        $winner = '';
                                        if ($categories && !is_wp_error($categories)) {
                                            foreach ($categories as $category) {
                                                if ($category->slug == "winner") {
                                                    $winner = "winner";
                                                }
                                            }
                                        }
                                        
                                        $nomination_id = get_the_ID();
                                        
                                        echo '<li id="nomination-' . esc_attr($nomination_id) . '" class="' . $winner . ' film-id-' . $film->term_id;
                                        if ($is_song) {
                                            echo ' song';
                                        }
                                        echo '" data-film-id="' . $film->term_id . '">';
                                        
                                        echo '<div class="left-section">';
                                        echo '<span class="film-poster">';
                                        $poster = get_field('poster', "films_" . $film->term_id);
                                        
                                        if ($poster) {
                                            echo '<img src="' . esc_url($poster) . '" alt="' . esc_attr($film->name) . '">';
                                        } else {
                                            echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM48 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 112l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16L64 96c-8.8 0-16 7.2-16 16zM416 96c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM160 128l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32L192 96c-17.7 0-32 14.3-32 32zm32 160c-17.7 0-32 14.3-32 32l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32l-128 0z"/></svg>';
                                        }
                                        echo '</span>';
                                        
                                        if ($nominee_visibility == "prominent") {
                                            if (is_array($nominees)) {
                                                foreach ($nominees as $nominee) {
                                                    echo '<span class="nominee-photo">';
                                                    $photo = get_field('photo', "nominees_" . $nominee->term_id);
                                                    
                                                    if (is_array($photo) && isset($photo['id'])) {
                                                        echo wp_get_attachment_image($photo['id'], 'medium');
                                                    } else {
                                                        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>';
                                                    }
                                                    echo '</span>';
                                                }
                                            }
                                        }
                                        echo '</div>'; // .left-section
                                        
                                        echo '<div class="right-section">';
                                        if ($is_song) {
                                            echo '<h3 class="song-name">' . esc_html(get_the_title()) . '</h3>';
                                        }
                                        
                                        if ($nominee_visibility == "prominent") {
                                            echo '<span class="film-name"><p>' . esc_html($film->name) . '</p></span>';
                                        } elseif ($is_song) {
                                            echo '<span class="film-name"><h4>' . esc_html($film->name) . '</h4></span>';
                                        } else {
                                            echo '<span class="film-name"><h3>' . esc_html($film->name) . '</h3></span>';
                                        }
                                        
                                        if (!$is_song) {
                                            echo '<ul class="nominees-name">';
                                            if (is_array($nominees)) {
                                                foreach ($nominees as $nominee) {
                                                    if ($nominee_visibility == "prominent") {
                                                        echo '<li><span class="nominee-name"><h3>' . esc_html($nominee->name) . '</h3></span></li>';
                                                    } elseif ($nominee_visibility == "shown") {
                                                        echo '<li><span class="nominee-name">' . esc_html($nominee->name) . '</span></li>';
                                                    }
                                                }
                                            }
                                            echo '</ul>';
                                        }
                                        
                                        echo '</div>'; // .right-section
                                        
                                        echo '</li>';
                                    }
                                }
                            }
                            
                            echo '</ul>';
                        } else {
                            echo '<p>No nominations found for this category.</p>';
                        }
                        
                        echo '</div>'; // .current-category-display
                        
                        wp_reset_postdata();
                    }
                }
            ?>
            </div>
        </div>

        <div class="controller interval-control-controller">
            <h2>Interval Control</h2>
            <p>Set the intervals for how often the admin page and scoreboard reload.</p>
            <div id="interval-control">
                <div class="interval-setting">
                    <label for="admin-interval-input">Admin Reload Interval (seconds):</label>
                    <input type="number" id="admin-interval-input" min="1" step="1" value="5" />
                    <div id="admin-countdown-display">Next reload in: <span id="admin-countdown">5</span>s</div>
                </div>
                <div class="interval-setting">
                    <label for="scoreboard-interval-input">Scoreboard Reload Interval (seconds):</label>
                    <input type="number" id="scoreboard-interval-input" min="1" step="1" value="10" />
                </div>
            </div>
        </div>
        
        <div class="controller notice-posting-controller">
            <h2>Post a Notice</h2>
            <p>Post a new notice to be displayed on the scoreboard.</p>
            <form id="notice-form">
                <input type="text" id="notice-input" placeholder="Type your notice..." required />
                <button type="submit">Post</button>
            </form>
            
            <details id="admin-notices-section">
                <summary>Manage Current Notices (<span id="notice-count">0</span>)</summary>
                <div id="admin-notices-container">
                    <button id="clear-all-notices" class="danger-btn">Clear All Notices</button>
                    <ul id="admin-notices-list"></ul>
                </div>
            </details>
        </div>

        <div class="controller event-status-controller">
            <h2>Set Event Status</h2>
            <p></p>
        </div>

    </section>
    <footer>
        <div id="progress-bar-container">
            <div id="progress-bar"></div>
        </div>
    </footer>
</div>
