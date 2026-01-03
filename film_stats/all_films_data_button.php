<?php

/**
 * Compile all films data and save as JSON in user_meta folder.
 * Provides a shortcode button to trigger the generation.
 */
function oscars_compile_all_films_data() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }

    // Get all films from the custom taxonomy
    $films = get_terms([
        'taxonomy' => 'films',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if (empty($films) || is_wp_error($films)) {
        return 'No films found.';
    }

    $films_data = [];

    // Build film data array
    foreach ($films as $film) {
        $films_data[] = [
            'film-id' => $film->term_id,
            'film-name' => $film->name,
            'film-url' => $film->slug,
        ];
    }

    // Prepare output data structure
    $output_data = [
        'films' => $films_data
    ];

    // Save JSON to user_meta folder
    $output_path = ABSPATH . 'wp-content/uploads/user_meta/all_films_data.json';
    
    // Ensure directory exists
    $user_meta_dir = dirname($output_path);
    if (!is_dir($user_meta_dir)) {
        mkdir($user_meta_dir, 0755, true);
    }

    // Write JSON file
    $result = file_put_contents($output_path, json_encode($output_data, JSON_PRETTY_PRINT));

    if ($result === false) {
        return 'Error: Failed to save JSON file.';
    }

    return 'All films data JSON generated successfully! (' . count($films_data) . ' films)';
}

/**
 * Shortcode to display button for generating all films data JSON.
 * Usage: [all_films_data_button]
 */
function oscars_all_films_data_button_shortcode() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }

    $output = '';

    // Check if form was submitted
    if (isset($_POST['oscars_all_films_data_generate'])) {
        $output .= '<div class="oscars-message">' . oscars_compile_all_films_data() . '</div>';
    }

    // Display button form
    $output .= '<form method="post">';
    $output .= '<button type="submit" name="oscars_all_films_data_generate" class="button button-primary">Generate All Films Data JSON</button>';
    $output .= '</form>';

    return $output;
}

add_shortcode('all_films_data_button', 'oscars_all_films_data_button_shortcode');
