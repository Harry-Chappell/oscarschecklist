<?php
/**
 * Shortcode: oscars_user_stats
 * Outputs user stats (total watched, favourites, predictions, last updated).
 */
function oscars_user_stats_shortcode($atts) {
    if (!is_user_logged_in()) return '<p>Please log in to view your stats.</p>';
    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    if (!file_exists($file_path)) return '<p>No user data found.</p>';
    $data = json_decode(file_get_contents($file_path), true);
    if (!$data) return '<p>User data is invalid.</p>';
    $a = shortcode_atts([
        'field' => 'total-watched',
        'label' => '',
    ], $atts);
    $value = 0;
    switch ($a['field']) {
        case 'total-watched':
            $value = intval($data['total-watched'] ?? 0);
            break;
        case 'correct-predictions':
            $value = intval($data['correct-predictions'] ?? 0) . '/' . count($data['predictions'] ?? []);
            break;
        case 'predictions':
            $value = count($data['predictions'] ?? []);
            break;
        case 'correct-prediction-rate':
            $value = isset($data['correct-prediction-rate']) ? round($data['correct-prediction-rate'] * 100, 2) . '%' : 0;
            break;
        default:
            $value = isset($data[$a['field']]) ? esc_html($data[$a['field']]) : 0;
    }
    $output = '<span class="large">' . $value . '</span>';
    $output .= '<span class="small"> <br></span>';
    $output .= '<label>' . esc_html($a['label'] ?: ucwords(str_replace('-', ' ', $a['field']))) . '</label>';
    return $output;
}
add_shortcode('oscars_user_stats', 'oscars_user_stats_shortcode');
