<?php
get_header(); ?>

<div class="search-results">
    <div class="search-hero">
        <h1>Search Results for "<?php echo get_search_query(); ?>"</h1>
    </div>
    <?php
    // Initialize an empty array to group results by taxonomy
    $grouped_results = array();

    // Loop through posts and group them by their taxonomy
    if (have_posts()) {
        while (have_posts()) {
            the_post();

            $term_id = get_the_ID(); // Get term ID
            $taxonomy = get_post_type(); // Use virtual post_type as the taxonomy name

            if (taxonomy_exists($taxonomy)) {
                // Add the current post to the corresponding taxonomy group
                if (!isset($grouped_results[$taxonomy])) {
                    $grouped_results[$taxonomy] = array();
                }
                $grouped_results[$taxonomy][] = array(
                    'term_id' => $term_id,
                    'title' => get_the_title(),
                    'link' => get_term_link($term_id, $taxonomy),
                );
            }
        }
    }

    // Display grouped results with custom templates
    foreach ($grouped_results as $taxonomy => $terms) {
        $taxonomy_object = get_taxonomy($taxonomy);
        $taxonomy_label = $taxonomy_object->labels->singular_name; // Get taxonomy label
        ?>
        <div class="results-cntr">
            <h2><?php echo esc_html($taxonomy_label); ?></h2>

            <?php if ($taxonomy === 'films') { ?>
                <ul>
                    <?php foreach ($terms as $term) { ?>
                        <li>
                            <a href="<?php echo esc_url($term['link']); ?>">
                                <?php echo esc_html($term['title']); ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } elseif ($taxonomy === 'nominees') { ?>
                <ul>
                    <?php foreach ($terms as $term) { ?>
                        <li>
                            <a href="<?php echo esc_url($term['link']); ?>">
                                <?php echo esc_html($term['title']); ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <ul>
                    <?php foreach ($terms as $term) { ?>
                        <li>
                            <a href="<?php echo esc_url($term['link']); ?>">
                                <?php echo esc_html($term['title']); ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
        <?php
    }
    ?>

    <?php if (empty($grouped_results)) : ?>
        <p>No results found.</p>
    <?php endif; ?>
</div>

<?php
get_footer();