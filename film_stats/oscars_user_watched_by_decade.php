<?php

/**
 * Shortcode: oscars_user_watched_by_decade
 * Outputs a list of watched films by decade for the current user.
 */
function oscars_user_watched_by_decade_shortcode() {
    if (!is_user_logged_in()) return '<p>Please log in to view your watched films by decade.</p>';
    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    if (!file_exists($file_path)) return '<p>No user data found.</p>';
    $data = json_decode(file_get_contents($file_path), true);
    if (!$data || empty($data['watched'])) return '<p>No watched films data found.</p>';
    $by_decade = [];
    foreach ($data['watched'] as $film) {
        if (!empty($film['film-year'])) {
            $decade = floor($film['film-year'] / 10) * 10;
            $year = intval($film['film-year']);
            if (!isset($by_decade[$decade])) $by_decade[$decade] = [];
            if (!isset($by_decade[$decade][$year])) $by_decade[$decade][$year] = [];
            $by_decade[$decade][$year][] = [
                'name' => $film['film-name'],
                'url' => $film['film-url'] ?? ''
            ];
        }
    }
    krsort($by_decade); // Descending order (most recent decade first)
    $output = '<div class="oscars-watched-by-decade">';
    foreach ($by_decade as $decade => $years) {
        // Count total films in decade
        $decade_count = 0;
        foreach ($years as $films) { $decade_count += count($films); }
        $output .= '<details><summary><strong>' . $decade . 's</strong> (' . $decade_count . ')</summary><ul>';
        krsort($years); // Most recent year first
        foreach ($years as $year => $films) {
            $year_count = count($films);
            $output .= '<li><strong>' . $year . '</strong> (' . $year_count . '): <ul>';
            // Sort films alphabetically by name
            usort($films, function($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
            foreach ($films as $film) {
                $url = esc_url('https://oscarschecklist.com/films/' . ltrim($film['url'], '/'));
                $output .= '<li><a href="' . $url . '">' . esc_html($film['name']) . '</a></li>';
            }
            $output .= '</ul></li>';
        }
        $output .= '</ul></details>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('oscars_user_watched_by_decade', 'oscars_user_watched_by_decade_shortcode');
