<?php

/**
 * Backfill missing film-name and film-url data in user watchlists.
 * Uses the all_films_data.json file as the source to avoid database queries.
 */
function oscars_backfill_watchlist_data() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }

    // Load the films data JSON
    $films_json_path = ABSPATH . 'wp-content/uploads/user_meta/all_films_data.json';
    
    if (!file_exists($films_json_path)) {
        return 'Error: all_films_data.json not found. Please generate it first using the "Generate All Films Data JSON" button.';
    }

    $films_json = file_get_contents($films_json_path);
    $films_data = json_decode($films_json, true);

    if (empty($films_data['films'])) {
        return 'Error: No films data found in JSON file.';
    }

    // Create a lookup array indexed by film-id for fast access
    $films_lookup = [];
    foreach ($films_data['films'] as $film) {
        $films_lookup[$film['film-id']] = [
            'film-name' => $film['film-name'],
            'film-url' => $film['film-url']
        ];
    }

    // Process all user files
    $user_meta_dir = ABSPATH . 'wp-content/uploads/user_meta/';
    $user_files = glob($user_meta_dir . 'user_*.json');

    if (empty($user_files)) {
        return 'No user files found.';
    }

    $total_users_processed = 0;
    $total_users_updated = 0;
    $total_entries_updated = 0;
    $total_missing = 0;

    foreach ($user_files as $user_file) {
        // Extract user ID from filename
        preg_match('/user_(\d+)\.json$/', basename($user_file), $matches);
        $user_id = $matches[1] ?? 'unknown';

        // Load user data
        $user_json = file_get_contents($user_file);
        $user_data = json_decode($user_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Backfill watchlist: Failed to parse JSON for user ' . $user_id);
            continue;
        }

        // Check if watchlist exists
        if (!isset($user_data['watchlist']) || !is_array($user_data['watchlist'])) {
            continue;
        }

        $total_users_processed++;
        $user_updated_count = 0;
        $user_missing_count = 0;

        // Process each watchlist entry
        foreach ($user_data['watchlist'] as &$entry) {
            $film_id = $entry['film-id'] ?? null;
            
            if (!$film_id) {
                continue;
            }

            // Check if entry is missing film-name or film-url
            $needs_update = !isset($entry['film-name']) || !isset($entry['film-url']);

            if ($needs_update) {
                // Look up the film data
                if (isset($films_lookup[$film_id])) {
                    $entry['film-name'] = $films_lookup[$film_id]['film-name'];
                    $entry['film-url'] = $films_lookup[$film_id]['film-url'];
                    $user_updated_count++;
                } else {
                    $user_missing_count++;
                    error_log('Backfill watchlist: Film ID ' . $film_id . ' not found in films data for user ' . $user_id);
                }
            }
        }
        unset($entry); // Break reference

        // Save the updated user data
        if ($user_updated_count > 0) {
            $result = file_put_contents($user_file, json_encode($user_data, JSON_PRETTY_PRINT));
            
            if ($result === false) {
                error_log('Backfill watchlist: Failed to save data for user ' . $user_id);
                continue;
            }

            $total_users_updated++;
            $total_entries_updated += $user_updated_count;
        }

        $total_missing += $user_missing_count;
    }

    $message = 'Backfill complete! ';
    $message .= 'Processed ' . $total_users_processed . ' users with watchlists. ';
    $message .= $total_users_updated . ' users updated. ';
    $message .= $total_entries_updated . ' watchlist entries updated.';
    
    if ($total_missing > 0) {
        $message .= ' (' . $total_missing . ' film IDs not found in films data)';
    }

    return $message;
}

/**
 * Shortcode to display button for backfilling watchlist data.
 * Usage: [backfill_watchlist_data_button]
 */
function oscars_backfill_watchlist_data_button_shortcode() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }

    $output = '';

    // Check if form was submitted
    if (isset($_POST['oscars_backfill_watchlist_data'])) {
        $output .= '<div class="oscars-message" style="padding: 10px; margin: 10px 0; background: #fff; border-left: 4px solid #00a0d2;">';
        $output .= oscars_backfill_watchlist_data();
        $output .= '</div>';
    }

    // Display button form
    $output .= '<form method="post" style="margin: 10px 0;">';
    $output .= '<p style="margin-bottom: 10px;"><strong>Note:</strong> This will update watchlist data for all users.</p>';
    $output .= '<button type="submit" name="oscars_backfill_watchlist_data" class="button button-primary">Backfill Watchlist Data (All Users)</button>';
    $output .= '</form>';

    return $output;
}

add_shortcode('backfill_watchlist_data_button', 'oscars_backfill_watchlist_data_button_shortcode');
